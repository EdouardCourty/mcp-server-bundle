<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Service;

use Ecourty\McpServerBundle\DependencyInjection\CompilerPass\PromptPass;
use Ecourty\McpServerBundle\Prompt\Argument;
use Ecourty\McpServerBundle\Prompt\PromptDefinition;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Registry for prompts.
 *
 * This class provides a way to retrieve prompts by their name, and the definitions of all prompts declared within the MCP server.
 *
 * @see PromptPass
 */
class PromptRegistry
{
    /** @var array<string, PromptDefinition> */
    private array $promptDefinitions = [];

    /** @var array<string, string|null> Mapping of prompt name to server key */
    private array $promptToServerMapping = [];

    public function __construct(// @phpstan-ignore missingType.generics
        #[AutowireLocator(services: 'mcp_server.prompt', indexAttribute: 'name')]
        private readonly ServiceLocator $promptLocator,
    ) {
    }

    public function getPrompt(string $name): ?object
    {
        if ($this->promptLocator->has($name) === false) {
            return null;
        }

        return $this->promptLocator->get($name);
    }

    public function getPromptDefinition(string $name): ?PromptDefinition
    {
        return $this->promptDefinitions[$name] ?? null;
    }

    /**
     * @return PromptDefinition[]
     */
    public function getPromptsDefinitions(?string $serverKey = null): array
    {
        if ($serverKey === null) {
            return array_values($this->promptDefinitions);
        }

        $filteredDefinitions = [];
        foreach ($this->promptDefinitions as $name => $definition) {
            $promptServerKey = $this->promptToServerMapping[$name] ?? null;

            // Include prompts that belong to the specified server or have no server specified (global prompts)
            if ($promptServerKey === null || $promptServerKey === $serverKey) {
                $filteredDefinitions[] = $definition;
            }
        }

        return $filteredDefinitions;
    }

    /**
     * Get the server key for a specific prompt.
     */
    public function getPromptServerKey(string $promptName): ?string
    {
        return $this->promptToServerMapping[$promptName] ?? null;
    }

    /**
     * @internal
     *
     * @param array<array{name: string, description: string, required: bool, allowUnsafe: bool}> $argumentDefinitions
     */
    public function addPromptDefinition(
        string $name,
        ?string $description = null,
        array $argumentDefinitions = [],
        ?string $serverKey = null,
    ): void {
        if (isset($this->promptDefinitions[$name]) === true) {
            throw new \LogicException(\sprintf('Prompt with name "%s" is already registered.', $name));
        }

        $rebuiltArgumentDefinitions = [];
        foreach ($argumentDefinitions as $argumentDefinition) {
            $rebuiltArgumentDefinitions[] = new Argument(
                name: $argumentDefinition['name'],
                description: $argumentDefinition['description'],
                required: $argumentDefinition['required'],
                allowUnsafe: $argumentDefinition['allowUnsafe'],
            );
        }

        $this->promptDefinitions[$name] = new PromptDefinition(
            name: $name,
            description: $description,
            arguments: $rebuiltArgumentDefinitions,
        );

        $this->promptToServerMapping[$name] = $serverKey;
    }
}
