<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Command;

use Ecourty\McpServerBundle\Service\ServerConfigurationRegistry;
use Ecourty\McpServerBundle\Service\ToolRegistry;
use Ecourty\McpServerBundle\Tool\ToolDefinition;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to display information about MCP tools.
 *
 * This command allows users to view details about specific MCP tools or all available tools.
 * It provides a table format for easy readability of tool attributes such as name, description,
 * input schema, and various annotations.
 */
#[AsCommand(
    name: 'debug:mcp-tools',
    description: 'Display current MCP tools',
)]
class DebugMcpToolsCommand extends Command
{
    public function __construct(
        private readonly ToolRegistry $toolRegistry,
        private readonly ServerConfigurationRegistry $serverConfigurationRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('tool', InputArgument::OPTIONAL, 'Tool name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $toolName = $input->getArgument('tool');

        if ($toolName !== null) {
            return $this->displaySingleToolInformation($io, $toolName);
        }

        return $this->displayAllToolsInformation($io);
    }

    private function displaySingleToolInformation(SymfonyStyle $io, string $toolName): int
    {
        $tool = $this->toolRegistry->getToolDefinition($toolName);

        if ($tool === null) {
            $io->error(\sprintf('Tool "%s" not found.', $toolName));

            return self::FAILURE;
        }

        $serverKey = $this->toolRegistry->getToolServerKey($toolName);
        $serverInfo = $serverKey ? $this->getServerDisplayName($serverKey) : 'Global (All servers)';

        $io->table(
            ['Name', 'Description', 'Server', 'Input Schema', 'Title', 'ReadOnly', 'Destructive', 'Idempotent', 'OpenWorld'],
            [
                [
                    $tool->name,
                    $tool->description,
                    $serverInfo,
                    $tool->inputSchemaClass,
                    $tool->annotations['title'],
                    $tool->annotations['readOnlyHint'] ? 'Yes' : 'No',
                    $tool->annotations['destructiveHint'] ? 'Yes' : 'No',
                    $tool->annotations['idempotentHint'] ? 'Yes' : 'No',
                    $tool->annotations['openWorldHint'] ? 'Yes' : 'No',
                ],
            ],
        );

        return self::SUCCESS;
    }

    private function displayAllToolsInformation(SymfonyStyle $io): int
    {
        $io->title('MCP Tools Debug Information');

        $tools = $this->toolRegistry->getToolsDefinitions();

        if (empty($tools) === true) {
            $io->warning('No tools found.');

            return self::SUCCESS;
        }

        $io->table(
            ['Name', 'Description', 'Server', 'Input Schema'],
            array_map(function (ToolDefinition $tool) {
                $serverKey = $this->toolRegistry->getToolServerKey($tool->name);
                $serverInfo = $serverKey ? $this->getServerDisplayName($serverKey) : 'Global (All servers)';

                return [
                    $tool->name,
                    $tool->description,
                    $serverInfo,
                    $tool->inputSchemaClass,
                ];
            }, $tools),
        );

        return self::SUCCESS;
    }

    private function getServerDisplayName(string $serverKey): string
    {
        $serverConfig = $this->serverConfigurationRegistry->getServerConfiguration($serverKey);
        if ($serverConfig === null) {
            return $serverKey . ' (Not found)';
        }

        return \sprintf('%s (%s)', $serverConfig['name'], $serverKey);
    }
}
