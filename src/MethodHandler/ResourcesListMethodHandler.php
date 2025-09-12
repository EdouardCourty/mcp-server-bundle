<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\MethodHandler;

use Ecourty\McpServerBundle\Attribute\AsMethodHandler;
use Ecourty\McpServerBundle\Contract\MethodHandlerInterface;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\Resource\AbstractResourceDefinition;
use Ecourty\McpServerBundle\Resource\DirectResourceDefinition;
use Ecourty\McpServerBundle\Service\ResourceRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

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
        private readonly ?RequestStack $requestStack = null,
    ) {
    }

    public function handle(JsonRpcRequest $request): array
    {
        // Try to get the server key from the current request
        $serverKey = null;
        if ($this->requestStack !== null) {
            $currentRequest = $this->requestStack->getCurrentRequest();
            if ($currentRequest !== null) {
                $serverKey = $currentRequest->attributes->get('serverKey');
            }
        }

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
