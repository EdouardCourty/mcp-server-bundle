<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\MethodHandler;

use Ecourty\McpServerBundle\Attribute\AsMethodHandler;
use Ecourty\McpServerBundle\Contract\MethodHandlerInterface;
use Ecourty\McpServerBundle\Event\InitializeEvent;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Handles the 'initialize' method for the MCP server.
 *
 * This method is called when a client initializes a connection to the MCP server.
 * It returns the protocol version and capabilities of the server.
 */
#[AsMethodHandler(methodName: 'initialize')]
class InitializeMethodHandler implements MethodHandlerInterface
{
    public const string PROTOCOL_VERSION = '2024-11-05';

    public function __construct(
        private readonly string $serverName,
        private readonly string $serverVersion,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {
    }

    public function handle(JsonRpcRequest $request): array
    {
        $this->eventDispatcher?->dispatch(new InitializeEvent($request));

        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => [
                'prompts' => [
                    'listChanged' => false,
                ],
                'tools' => [
                    'listChanged' => false,
                ],
            ],
            'serverInfo' => [
                'name' => $this->serverName,
                'version' => $this->serverVersion,
            ],
        ];
    }
}
