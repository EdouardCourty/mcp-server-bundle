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
     * @param string                $name        the name of the prompt
     * @param string|null           $description a description of the prompt
     * @param array<Argument|mixed> $arguments   an array of argument definitions for the prompt
     * @param string|null           $server      optional server key to specify which server this prompt belongs to (if multiple servers are configured)
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        array $arguments = [],
        public readonly ?string $server = null,
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
