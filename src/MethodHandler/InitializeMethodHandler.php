<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\MethodHandler;

use Ecourty\McpServerBundle\Attribute\AsMethodHandler;
use Ecourty\McpServerBundle\Contract\MethodHandlerInterface;
use Ecourty\McpServerBundle\DependencyInjection\Configuration;
use Ecourty\McpServerBundle\Event\InitializeEvent;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\Service\CurrentServerService;
use Ecourty\McpServerBundle\Service\ServerConfigurationRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Handles the 'initialize' method for the MCP server.
 *
 * This method is called when a client initializes a connection to the MCP server.
 * It returns the protocol version and capabilities of the server.
 */
#[AsMethodHandler(methodName: 'initialize')]
class InitializeMethodHandler implements MethodHandlerInterface
{
    public const string PROTOCOL_VERSION = '2025-06-18';

    public function __construct(
        private readonly ?string $serverName = null,
        private readonly ?string $serverTitle = null,
        private readonly ?string $serverVersion = null,
        private readonly ?ServerConfigurationRegistry $serverConfigurationRegistry = null,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        private readonly ?CurrentServerService $currentServerService = null,
    ) {
    }

    public function handle(JsonRpcRequest $request): array
    {
        $this->eventDispatcher?->dispatch(new InitializeEvent($request));

        // Determine server info based on configuration type
        $serverInfo = $this->getServerInfo();

        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => [
                'prompts' => [
                    'listChanged' => false,
                ],
                'tools' => [
                    'listChanged' => false,
                ],
                'resources' => [
                    'subscribe' => false,
                    'listChanged' => false,
                ],
            ],
            'serverInfo' => $serverInfo,
        ];
    }

    /**
     * @return array{name: string, title: string, version: string}
     */
    private function getServerInfo(): array
    {
        // If we have explicit server parameters (single server configuration)
        if ($this->serverName !== null && $this->serverTitle !== null && $this->serverVersion !== null) {
            return [
                'name' => $this->serverName,
                'title' => $this->serverTitle,
                'version' => $this->serverVersion,
            ];
        }

        // If we have a server configuration registry (multiple servers configuration)
        if ($this->serverConfigurationRegistry !== null) {
            // Try to determine which server to use based on the serverKey in request attributes
            $serverConfig = $this->determineServerFromRequest();
            if ($serverConfig !== null) {
                return [
                    'name' => $serverConfig['name'],
                    'title' => $serverConfig['title'] ?? $serverConfig['name'],
                    'version' => $serverConfig['version'],
                ];
            }

            // Fallback to "default" server if it exists
            $defaultConfig = $this->serverConfigurationRegistry->getServerConfiguration('default');
            if ($defaultConfig !== null) {
                return [
                    'name' => $defaultConfig['name'],
                    'title' => $defaultConfig['title'] ?? $defaultConfig['name'],
                    'version' => $defaultConfig['version'],
                ];
            }

            // Fallback to first available server
            $allConfigs = $this->serverConfigurationRegistry->getAllServerConfigurations();
            if (!empty($allConfigs)) {
                $firstConfig = reset($allConfigs);

                return [
                    'name' => $firstConfig['name'],
                    'title' => $firstConfig['title'] ?? $firstConfig['name'],
                    'version' => $firstConfig['version'],
                ];
            }
        }

        // Fallback values
        return [
            'name' => Configuration::DEFAULT_NAME,
            'title' => Configuration::DEFAULT_TITLE,
            'version' => Configuration::DEFAULT_VERSION,
        ];
    }

    /**
     * @return array{name: string, title?: string, version: string}|null
     */
    private function determineServerFromRequest(): ?array
    {
        if ($this->currentServerService === null || $this->serverConfigurationRegistry === null) {
            return null;
        }

        $serverKey = $this->currentServerService->getCurrentServerKey();
        if ($serverKey === null) {
            return null;
        }

        // Get the server configuration for this serverKey
        return $this->serverConfigurationRegistry->getServerConfiguration($serverKey);
    }
}
