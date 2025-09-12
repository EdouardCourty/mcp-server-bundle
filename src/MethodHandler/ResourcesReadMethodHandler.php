<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\MethodHandler;

use Ecourty\McpServerBundle\Attribute\AsMethodHandler;
use Ecourty\McpServerBundle\Contract\MethodHandlerInterface;
use Ecourty\McpServerBundle\Event\Resource\ResourceReadEvent;
use Ecourty\McpServerBundle\Event\Resource\ResourceReadResultEvent;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\Service\CurrentServerService;
use Ecourty\McpServerBundle\Service\ResourceExecutor;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Handles the 'resources/read' method in the MCP server.
 *
 * This method is used to read the contents of a resource identified by its URI.
 *
 * @see https://modelcontextprotocol.io/specification/2025-06-18/server/resources#reading-resources
 */
#[AsMethodHandler(
    methodName: 'resources/read',
)]
class ResourcesReadMethodHandler implements MethodHandlerInterface
{
    public function __construct(
        private readonly ResourceExecutor $resourceExecutor,
        private readonly CurrentServerService $currentServerService,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {
    }

    public function handle(JsonRpcRequest $request): array
    {
        $uri = $request->params['uri'] ?? null;

        if ($uri === null) {
            throw new \InvalidArgumentException('Resource URI is required.');
        }

        $this->eventDispatcher?->dispatch(new ResourceReadEvent($uri));

        $serverKey = $this->currentServerService->getCurrentServerKey();
        $result = $this->resourceExecutor->execute($uri, $serverKey);

        $this->eventDispatcher?->dispatch(new ResourceReadResultEvent($uri, $result));

        return [
            'contents' => $result->toArray(),
        ];
    }
}
