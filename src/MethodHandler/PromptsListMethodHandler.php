<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\MethodHandler;

use Ecourty\McpServerBundle\Attribute\AsMethodHandler;
use Ecourty\McpServerBundle\Contract\MethodHandlerInterface;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\Service\CurrentServerService;
use Ecourty\McpServerBundle\Service\PromptRegistry;

/**
 * Handles the 'prompts/list' method in the MCP server.
 *
 * This method is used to retrieve a list of all available prompts and their definitions.
 *
 * @see https://modelcontextprotocol.io/specification/2025-03-26/server/prompts#listing-prompts
 */
#[AsMethodHandler(methodName: 'prompts/list')]
class PromptsListMethodHandler implements MethodHandlerInterface
{
    public function __construct(
        private readonly PromptRegistry $toolRegistry,
        private readonly CurrentServerService $currentServerService,
    ) {
    }

    public function handle(JsonRpcRequest $request): array
    {
        $serverKey = $this->currentServerService->getCurrentServerKey();

        return [
            'prompts' => $this->toolRegistry->getPromptsDefinitions($serverKey),
        ];
    }
}
