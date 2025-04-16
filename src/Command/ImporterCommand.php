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
use App\Validator\YesNo;
use App\Entity\TblProductData;
use App\Entity\RowErrors;
use Symfony\Component\Validator\Constraints\Count;
use Doctrine\ORM\EntityManagerInterface;

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
        if ( !$finder->hasResults() )  {
            $output->writeln("<error>File not found on var/$fileName </error>");
            return Command::FAILURE;
        }
        $filePath = null;
        foreach ($finder as $file ) {
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
        // The file could be opened
        if (($file = fopen($filePath, 'r')) !== false)
        {
            //Obtaining the header
            $headers = fgetcsv($file);
            $headerCount = count($headers);
            while (($row = fgetcsv($file)) !== false)
            {
                //Object that would be used to display errors on the rows
                $error = new RowErrors();
                $error->setStrRow(implode(',', $row));
                //validation on the same qty of rows for combine the headers and the values
                if ( count($row) == $headerCount )
                {
                    $rowAssoc = array_combine($headers, $row);
                    $producData = new TblProductData();
                    /*Assign the values to the object for the save on to de database*/
                    $producData->setStrProductName($rowAssoc['Product Name']);
                    $producData->setStrProductCode($rowAssoc['Product Code']);
                    $producData->setStrProductDesc($rowAssoc['Product Description']);
                    $producData->setIntStockLevel((int)$rowAssoc['Stock']);
                    $producData->setDblCostInGBP((float)$rowAssoc['Cost in GBP']);
                    $producData->setDiscontinued($rowAssoc['Discontinued']);
                    $producData->setDtmAdded(new \DateTime());
                    $producData->setStmTimestamp(new \DateTime());
                    /*Validate that the discontinued value*/
                    $discontinuedErrors = $this->validator->validate( $rowAssoc['Discontinued'], new YesNo() );
                    /*Validate the regular errors on the fields*/
                    $errors = $this->validator->validate($producData);

                    $error->setArrErrors($errors);
                    $error->setArrErrors($discontinuedErrors);
                }
                /*assign the error on a row without the same qty of header and field values*/
                $countViolation = $this->validator->validate($row, new Count([
                    'min' => $headerCount,
                    'max' => $headerCount,
                    'exactMessage' => 'The row must contain exactly {{ limit }} values.',
                ]));
                $error->setArrErrors($countViolation);
                $rowErrors[] = $error;
                //option for save or test also if there is any error
                if (!$input->getOption('test') && !$error->hasErrors())
                {
                    $this->em->persist($producData);
                }
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
                $output->writeln('<error> the row ('.$error->getStrRow().') '.( $input->getOption('test') ? 'is not' : 'could not be' ).' imported because has the following errors ' . $error->getErrorMessages() . '</error>');
            }
            else
            {
                $output->writeln('Row ('.$error->getStrRow().') '. ( $input->getOption('test') ? 'could be' : 'successfully').' Imported!');
            }
        }
        return Command::SUCCESS;
    }
}