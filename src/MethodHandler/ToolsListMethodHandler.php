<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\MethodHandler;

use Ecourty\McpServerBundle\Attribute\AsMethodHandler;
use Ecourty\McpServerBundle\Contract\MethodHandlerInterface;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\Service\CurrentServerService;
use Ecourty\McpServerBundle\Service\ToolRegistry;

/**
 * Handles the 'tools/list' method in the MCP server.
 *
 * This method is used to retrieve a list of all available tools and their definitions.
 * It does not require any parameters and returns an array of tool definitions.
 *
 * @see https://modelcontextprotocol.io/specification/2025-03-26/server/tools#listing-tools
 */
#[AsMethodHandler(methodName: 'tools/list')]
class ToolsListMethodHandler implements MethodHandlerInterface
{
    public function __construct(
        private readonly ToolRegistry $toolRegistry,
        private readonly CurrentServerService $currentServerService,
    ) {
    }

    public function handle(JsonRpcRequest $request): array
    {
        $serverKey = $this->currentServerService->getCurrentServerKey();

        return [
            'tools' => $this->toolRegistry->getToolsDefinitions($serverKey),
        ];
    }
}
