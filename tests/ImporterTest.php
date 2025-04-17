<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Command\ImporterCommand;

class ImporterTest extends KernelTestCase
{

    private ValidatorInterface $validator;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        // Obtenemos el EntityManager desde el contenedor
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testFileStock(): void
    {
        $application = new Application();
        $application->add(new ImporterCommand($this->validator, $this->em));

        // Get the command to test
        $command = $application->find('app:import');
        $commandImporter = new CommandTester($command);

        // Execute with argument
        $this->assertSame( Command::SUCCESS, $commandImporter->execute([
            'fileName' => 'stock.csv',
        ]));
    }

    public function testFileNotFound(): void
    {
        $application = new Application();
        $application->add(new ImporterCommand($this->validator, $this->em));

        // Get the command to test
        $command = $application->find('app:import');
        $commandImporter = new CommandTester($command);

        // Execute with argument
        $this->assertSame( Command::FAILURE, $commandImporter->execute([
            'fileName' => 'FileNotFound.csv',
        ]));
    }

    public function testFileNoHeaders(): void
    {
        $application = new Application();
        $application->add(new ImporterCommand($this->validator, $this->em));

        // Get the command to test
        $command = $application->find('app:import');
        $commandImporter = new CommandTester($command);

        // Execute with argument
        $this->assertSame( Command::SUCCESS, $commandImporter->execute([
            'fileName' => 'stock_no_header.csv',
        ]));
    }
}
