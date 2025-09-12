<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Service;

/**
 * Registry for managing server configurations.
 */
class ServerConfigurationRegistry
{
    /**
     * @param array<string, array{name: string, title?: string, version: string}> $serverConfigurations
     */
    public function __construct(
        private readonly array $serverConfigurations,
    ) {
    }

    /**
     * Get all server configurations.
     *
     * @return array<string, array{name: string, title?: string, version: string}>
     */
    public function getAllServerConfigurations(): array
    {
        return $this->serverConfigurations;
    }

    /**
     * Get a specific server configuration by key.
     *
     * @return array{name: string, title?: string, version: string}|null
     */
    public function getServerConfiguration(string $serverKey): ?array
    {
        return $this->serverConfigurations[$serverKey] ?? null;
    }

    /**
     * Check if a server exists.
     */
    public function hasServer(string $serverKey): bool
    {
        return isset($this->serverConfigurations[$serverKey]);
    }

    /**
     * Get all server keys.
     *
     * @return string[]
     */
    public function getServerKeys(): array
    {
        return array_keys($this->serverConfigurations);
    }
}
