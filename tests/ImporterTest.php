<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;
use App\Command\ImporterCommand;

class ImporterTest extends TestCase
{
    public function testSomething(): void
    {
        $application = new Application();
        $application->add(new ImporterCommand());

        // Get the command to test
        $command = $application->find('app:import');
        $commandImporter = new CommandTester($command);

        // Execute with argument
        $commandImporter->execute([
            'fileName' => 'stock.csv',
        ]);

        // Get output
        $output = $commandImporter->getDisplay();
        //$this->assertStringContainsString($output);
        $this->assertTrue(true);
    }

    public function testOtherThing(): void
    {
        $this->assertTrue(false);
    }
}
