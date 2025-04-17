<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Count;
use Doctrine\ORM\EntityManagerInterface;
use App\Validator\YesNo;
use App\Validator\StockRestriction;
use App\Validator\CSVHeaderStructure;
use App\Entity\TblProductData;
use App\Entity\RowErrors;

#[AsCommand(
    name: 'app:import',
    description: 'Add a short description for your command',
)]
class ImporterCommand extends Command
{
    private ValidatorInterface $validator;
    private EntityManagerInterface $em;

    public function __construct(ValidatorInterface $validator, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->validator = $validator;
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('fileName', InputArgument::REQUIRED, 'Name of File')
            ->addOption('test', null, InputOption::VALUE_NONE, 'Run on Test Mode')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        //Name of filename that would be imported
        $fileName = $input->getArgument('fileName');
        $finder = new Finder();
        $finder->name($fileName);
        $finder->in('var');

        //Validate the file is on var folder
        if ( !$finder->hasResults() )
        {
            $output->writeln("<error>File not found on var/$fileName </error>");
            return Command::FAILURE;
        }
        $filePath = null;
        foreach ($finder as $file )
        {
            $filePath = $file->getRealPath();
            break;
        }

        //validation of the currentfile name (only takes first);
        if ( !$filePath )
        {
            $output->writeln("<error>File not found on var/$fileName </error>");
            return Command::FAILURE;
        }
        $rowErrors = [];
        $hasHeader = true;
        // The file could be opened
        if (($file = fopen($filePath, 'r')) !== false)
        {
            //Obtaining the header
            $headers = TblProductData::CSV_HEADER;
            $firstRow = fgetcsv($file);
            $headerErrors = $this->validator->validate( [
                    "headers" => $headers,
                    "values" => $firstRow
                ], new CSVHeaderStructure()
            );
            if ( count($headerErrors) > 0 )
            {
                $output->writeln('<error> The file doesn´t have headers</error>');
                foreach( $headerErrors as $error )
                {
                    $output->writeln('<error>'.$error->getMessage().'</error>');
                }
                $rowErrors[] = $this->validateRow($firstRow, $headers, $input->getOption('test'));
            }
            while ( ($row = fgetcsv($file)) !== false )
            {
                //Object that would be used to display errors on the rows
                $rowErrors[] = $this->validateRow($row, $headers, $input->getOption('test'));
            }
            $this->em->flush();
            fclose($file);
        } else {
            $output->writeln('<error>Could not open file: ' . $file->getFilename() . '</error>');
        }
        // Iterate the errors and display saved/valid data
        foreach($rowErrors as $error)
        {
            if ( $error->hasErrors() )
            {
                $output->writeln('<error>Row ('.$error->getStrRow().') '.( $input->getOption('test') ? 'is not' : 'could not be' ).' imported because got the following errors:</error>');
                $output->writeln('<error> '.$error->getErrorMessages().'</error>');
            }
            else
            {
                $output->writeln('Row ('.$error->getStrRow().') '. ( $input->getOption('test') ? 'could be' : 'successfully').' Imported!!');
            }
        }
        return Command::SUCCESS;
    }

    private function validateRow( array $row, array $headers, bool $isTest = true) : RowErrors
    {
        $headerCount = count($headers);
        $rowErrors = new RowErrors();
        $rowErrors->setStrRow(implode(',', $row));
        //validation on the same qty of rows for combine the headers and the values
        $countErrors = $this->validator->validate($row, new Count([
            'min' => $headerCount,
            'max' => $headerCount,
            'exactMessage' => 'The row must contain exactly {{ limit }} values.',
        ]));
        $rowErrors->setArrErrors($countErrors);
        if ( $headerCount == count($row) )
        {
            $producData = new TblProductData();
            /*Assign the values to the object for save on to de database*/
            $rowAssoc = array_combine($headers, $row);
            $producData->init($rowAssoc);
            /*Validate that the discontinued value*/
            $discontinuedErrors = $this->validator->validate( 
                $rowAssoc[TblProductData::CSV_DISCONTINUED], new YesNo() 
            );
            // Validate te stock restriction when the cost is less than 5 and the stoch has less 
            // than 10 
            $stockErrors = $this->validator->validate( [
                    "stock" => $producData->getIntStockLevel(),
                    "cost" => $producData->getDblCostInGBP()
                ], new StockRestriction()
            );
            /*Validate the regular errors on the fields*/
            $mainErrors = $this->validator->validate($producData);
            $rowErrors->setArrErrors($mainErrors);
            $rowErrors->setArrErrors($discontinuedErrors);
            $rowErrors->setArrErrors($stockErrors);
        }
        //option for save or test also if there is any error
        if (!$isTest && !$rowErrors->hasErrors())
        {
            $this->em->persist($producData);
        }
        return $rowErrors;
    }
}