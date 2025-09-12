<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\MethodHandler;

use Ecourty\McpServerBundle\Attribute\AsMethodHandler;
use Ecourty\McpServerBundle\Contract\MethodHandlerInterface;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\Service\PromptRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

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

        return [
            'prompts' => $this->toolRegistry->getPromptsDefinitions($serverKey),
        ];
    }
}
