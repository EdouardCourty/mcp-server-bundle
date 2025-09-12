<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\MethodHandler;

use Ecourty\McpServerBundle\Attribute\AsMethodHandler;
use Ecourty\McpServerBundle\Contract\MethodHandlerInterface;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\Resource\AbstractResourceDefinition;
use Ecourty\McpServerBundle\Resource\DirectResourceDefinition;
use Ecourty\McpServerBundle\Service\CurrentServerService;
use Ecourty\McpServerBundle\Service\ResourceRegistry;

/**
 * Handles the 'resources/list' method in the MCP server.
 *
 * This method is used to list all direct resources (non templated) available in the MCP server.
 *
 * @see https://modelcontextprotocol.io/specification/2025-06-18/server/resources#listing-resources
 */
#[AsMethodHandler(
    methodName: 'resources/list',
)]
class ResourcesListMethodHandler implements MethodHandlerInterface
{
    public function __construct(
        private readonly ResourceRegistry $resourceRegistry,
        private readonly CurrentServerService $currentServerService,
    ) {
    }

    public function handle(JsonRpcRequest $request): array
    {
        $serverKey = $this->currentServerService->getCurrentServerKey();

        $resourceDefinitions = $this->resourceRegistry->getResourceDefinitions($serverKey);
        /** @var DirectResourceDefinition[] $directResourcesDefinitions */
        $directResourcesDefinitions = array_filter($resourceDefinitions, function (AbstractResourceDefinition $definition) {
            return $definition instanceof DirectResourceDefinition;
        });

        return [
            'resources' => array_values($directResourcesDefinitions),
        ];
    }
}
