<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Attribute;

use Ecourty\McpServerBundle\Prompt\Argument;

/**
 * This attribute is used to mark a class as a prompt for the MCP Server.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsPrompt
{
    private readonly array $arguments;

    /**
     * @param string $name The name of the prompt.
     * @param string|null $description A description of the prompt.
     * @param array<Argument|mixed> $arguments An array of argument definitions for the prompt.
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        array $arguments = [],
    ) {
        foreach ($arguments as $argument) {
            if ($argument instanceof Argument === false) {
                throw new \InvalidArgumentException('All arguments must be instances of ArgumentDefinition.');
            }
        }

        $this->arguments = $arguments;
    }

    /**
     * @return Argument[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
