<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Attribute;

/**
 * This attribute is used to mark a class as a tool for the MCP Server.
 *
 * Tools are used to perform specific actions or provide functionalities that can be invoked by clients.
 * The tool's name and description are used to identify and describe the tool's purpose.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsTool
{
    private readonly ToolAnnotations $toolAnnotations;

    /**
     * @param string               $name        the name of the tool, which can be called by clients
     * @param string               $description a human-readable description of the tool, useful for LLMs to understand its purpose
     * @param ToolAnnotations|null $annotations optional annotations for the tool, providing additional metadata such as title, read-only status, and hints about the tool's behavior
     * @param string|null          $server      optional server key to specify which server this tool belongs to (if multiple servers are configured)
     */
    public function __construct(
        public string $name,
        public string $description,
        ?ToolAnnotations $annotations = null,
        public ?string $server = null,
    ) {
        $this->toolAnnotations = $annotations ?? new ToolAnnotations();
    }

    public function getToolAnnotations(): ToolAnnotations
    {
        return $this->toolAnnotations;
    }
}
