<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\Support;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class CommandTestCase extends KernelTestCase
{
    protected function getCommandTester(string $commandName): CommandTester
    {
        if (self::$booted === false) {
            self::bootKernel();
        }

        /** @var KernelInterface $kernel */
        $kernel = self::$kernel;

        $application = new Application($kernel);
        $command = $application->find($commandName);

        return new CommandTester($command);
    }
}
