<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\MethodHandler;

use Ecourty\McpServerBundle\Attribute\AsMethodHandler;
use Ecourty\McpServerBundle\Contract\MethodHandlerInterface;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\Resource\AbstractResourceDefinition;
use Ecourty\McpServerBundle\Resource\TemplateResourceDefinition;
use Ecourty\McpServerBundle\Service\CurrentServerService;
use Ecourty\McpServerBundle\Service\ResourceRegistry;

/**
 * Handles the 'resources/templates/list' method in the MCP server.
 *
 * This method is used to list all templated resources available in the MCP server.
 *
 * @see https://modelcontextprotocol.io/specification/2025-06-18/server/resources#resource-templates
 */
#[AsMethodHandler(
    methodName: 'resources/templates/list',
)]
class ResourcesTemplatesListMethodHandler implements MethodHandlerInterface
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
        /** @var TemplateResourceDefinition[] $templateDefinitions */
        $templateDefinitions = array_filter($resourceDefinitions, function (AbstractResourceDefinition $definition) {
            return $definition instanceof TemplateResourceDefinition;
        });

        return [
            'resourceTemplates' => array_values($templateDefinitions),
        ];
    }
}
