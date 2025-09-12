<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Service;

use Ecourty\McpServerBundle\DependencyInjection\CompilerPass\ResourcePass;
use Ecourty\McpServerBundle\Resource\AbstractResourceDefinition;
use Ecourty\McpServerBundle\Resource\DirectResourceDefinition;
use Ecourty\McpServerBundle\Resource\TemplateResourceDefinition;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Registry for resources.
 *
 * This class provides a way to retrieve resources by their uri, and the definitions of all resources declared within the MCP server.
 *
 * @see ResourcePass
 */
class ResourceRegistry
{
    /** @var array<string, AbstractResourceDefinition> */
    private array $resourceDefinitions = [];

    /** @var array<string, string|null> Mapping of resource URI to server key */
    private array $resourceToServerMapping = [];

    public function __construct(// @phpstan-ignore missingType.generics
        #[AutowireLocator(services: 'mcp_server.resource', indexAttribute: 'uri')]
        private readonly ServiceLocator $resourceLocator,
    ) {
    }

    public function getResource(string $uri, ?string $serverKey = null): ?object
    {
        if ($this->resourceLocator->has($uri) === false) {
            return null;
        }

        // If server key is provided, validate that the resource belongs to this server
        if ($serverKey !== null) {
            $resourceServerKey = $this->resourceToServerMapping[$uri] ?? null;

            // Allow access to resources with no server key (global resources) or resources that belong to the current server
            if ($resourceServerKey !== null && $resourceServerKey !== $serverKey) {
                return null;
            }
        }

        return $this->resourceLocator->get($uri);
    }

    public function getResourceDefinition(string $name): ?AbstractResourceDefinition
    {
        return $this->resourceDefinitions[$name] ?? null;
    }

    /**
     * @return AbstractResourceDefinition[]
     */
    public function getResourceDefinitions(?string $serverKey = null): array
    {
        if ($serverKey === null) {
            return array_values($this->resourceDefinitions);
        }

        $filteredDefinitions = [];
        foreach ($this->resourceDefinitions as $uri => $definition) {
            $resourceServerKey = $this->resourceToServerMapping[$uri] ?? null;

            // Include resources that belong to the specified server or have no server specified (global resources)
            if ($resourceServerKey === null || $resourceServerKey === $serverKey) {
                $filteredDefinitions[] = $definition;
            }
        }

        return $filteredDefinitions;
    }

    /**
     * Get the server key for a specific resource.
     */
    public function getResourceServerKey(string $uri): ?string
    {
        return $this->resourceToServerMapping[$uri] ?? null;
    }

    /**
     * @internal
     */
    public function addDirectResourceDefinition(
        string $name,
        string $uri,
        ?string $title = null,
        ?string $description = null,
        ?string $mimeType = null,
        ?string $size = null,
        ?string $serverKey = null,
    ): void {
        $resourceDefinition = new DirectResourceDefinition(
            uri: $uri,
            name: $name,
            title: $title,
            description: $description,
            mimeType: $mimeType,
            size: $size,
        );

        $this->resourceDefinitions[$uri] = $resourceDefinition;
        $this->resourceToServerMapping[$uri] = $serverKey;
    }

    /**
     * @internal
     */
    public function addTemplateResourceDefinition(
        string $name,
        string $uri,
        ?string $title = null,
        ?string $description = null,
        ?string $mimeType = null,
        ?string $serverKey = null,
    ): void {
        $resourceDefinition = new TemplateResourceDefinition(
            uri: $uri,
            name: $name,
            title: $title,
            description: $description,
            mimeType: $mimeType,
        );

        $this->resourceDefinitions[$uri] = $resourceDefinition;
        $this->resourceToServerMapping[$uri] = $serverKey;
    }
}
