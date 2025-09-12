<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class AsResource
{
    public function __construct(
        public readonly string $uri, // Unique identifier for the resource
        public readonly string $name, // The name of the resource
        public readonly ?string $title = null, // Optional human-readable title
        public readonly ?string $description = null, // Optional description
        public readonly ?string $mimeType = null, // Optional MIME type of the resource
        public readonly ?string $size = null, // Optional size of the resource, in bytes
        public readonly ?string $server = null, // Optional server key to specify which server this resource belongs to (if multiple servers are configured)
    ) {
    }

    public function isTemplatedResource(): bool
    {
        return str_contains($this->uri, '{') && str_contains($this->uri, '}');
    }
}
