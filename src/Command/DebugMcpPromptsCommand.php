<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Command;

use Ecourty\McpServerBundle\Prompt\Argument;
use Ecourty\McpServerBundle\Prompt\PromptDefinition;
use Ecourty\McpServerBundle\Service\PromptRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to display information about MCP prompts.
 *
 * This command allows users to view details about specific MCP prompts or all available prompts.
 * It provides a table format for easy readability of prompts attributes such as name, description, and arguments.
 */
#[AsCommand(
    name: 'debug:mcp-prompts',
    description: 'Display current MCP prompts',
)]
class DebugMcpPromptsCommand extends Command
{
    public function __construct(
        private readonly PromptRegistry $promptRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('prompt', InputArgument::OPTIONAL, 'Prompt name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $promptName = $input->getArgument('prompt');

        if ($promptName !== null) {
            return $this->displaySinglePromptInformation($io, $promptName);
        }

        return $this->displayAllPromptsInformation($io);
    }

    private function displaySinglePromptInformation(SymfonyStyle $io, string $promptName): int
    {
        $prompt = $this->promptRegistry->getPromptDefinition($promptName);

        if ($prompt === null) {
            $io->error(\sprintf('Prompt "%s" not found.', $promptName));

            return self::FAILURE;
        }

        $serverKey = $this->promptRegistry->getPromptServerKey($promptName);
        $serverInfo = $serverKey ?? 'Global';

        $io->table(
            ['Name', 'Description', 'Server', 'Arguments'],
            [
                [
                    $prompt->name,
                    $prompt->description,
                    $serverInfo,
                    implode(', ', array_map(fn (Argument $argument) => $argument->name, $prompt->arguments ?? [])),
                ],
            ],
        );

        return self::SUCCESS;
    }

    private function displayAllPromptsInformation(SymfonyStyle $io): int
    {
        $io->title('MCP Prompts Debug Information');

        $promptsDefinitions = $this->promptRegistry->getPromptsDefinitions();

        if (empty($promptsDefinitions) === true) {
            $io->warning('No prompts found.');

            return self::SUCCESS;
        }

        $io->table(
            ['Name', 'Description', 'Server', 'Arguments'],
            array_map(function (PromptDefinition $prompt) {
                $serverKey = $this->promptRegistry->getPromptServerKey($prompt->name);
                $serverInfo = $serverKey ?? 'Global';

                return [
                    $prompt->name,
                    $prompt->description,
                    $serverInfo,
                    implode(', ', array_map(fn (Argument $argument) => $argument->name, $prompt->arguments ?? [])),
                ];
            }, $promptsDefinitions),
        );

        return self::SUCCESS;
    }
}
