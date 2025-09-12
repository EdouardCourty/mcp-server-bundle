<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\MethodHandler;

use Ecourty\McpServerBundle\Event\Resource\AbstractResourceEvent;
use Ecourty\McpServerBundle\Event\Resource\ResourceReadEvent;
use Ecourty\McpServerBundle\Event\Resource\ResourceReadResultEvent;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\IO\Resource\BinaryResource;
use Ecourty\McpServerBundle\IO\Resource\ResourceResult;
use Ecourty\McpServerBundle\MethodHandler\ResourcesReadMethodHandler;
use Ecourty\McpServerBundle\Service\CurrentServerService;
use Ecourty\McpServerBundle\Service\ResourceExecutor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Ecourty\McpServerBundle\MethodHandler\ResourcesReadMethodHandler
 */
class ResourcesReadMethodHandlerTest extends TestCase
{
    private MockObject&ResourceExecutor $resourceExecutor;
    private MockObject&CurrentServerService $currentServerService;
    private MockObject&EventDispatcherInterface $eventDispatcher;

    private ResourcesReadMethodHandler $handler;

    protected function setUp(): void
    {
        $this->resourceExecutor = $this->createMock(ResourceExecutor::class);
        $this->currentServerService = $this->createMock(CurrentServerService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->handler = new ResourcesReadMethodHandler(
            resourceExecutor: $this->resourceExecutor,
            currentServerService: $this->currentServerService,
            eventDispatcher: $this->eventDispatcher,
        );
    }

    public function testFireEvents(): void
    {
        $events = [
            ResourceReadEvent::class,
            ResourceReadResultEvent::class,
        ];

        $matcher = $this->exactly(\count($events));
        $this->eventDispatcher
            ->expects($matcher)
            ->method('dispatch')
            ->willReturnCallback(function (AbstractResourceEvent $event) use ($matcher, $events) {
                $this->assertInstanceOf($events[$matcher->numberOfInvocations() - 1], $event);
            });

        $resourceResult = new ResourceResult([
            new BinaryResource(
                uri: 'file://random',
                name: 'file.txt',
                title: 'A random file from the filesystem',
                mimeType: 'text/plain',
                blob: 'blob_data',
            ),
        ]);

        $serverKey = 'test_server';
        $this->currentServerService
            ->expects($this->once())
            ->method('getCurrentServerKey')
            ->willReturn($serverKey);

        $this->resourceExecutor
            ->expects($this->once())
            ->method('execute')
            ->with('file://random', $serverKey)
            ->willReturn($resourceResult);

        $uri = 'file://random';

        $request = new JsonRpcRequest(
            id: 1,
            method: 'resources/read',
            params: [
                'uri' => $uri,
            ],
        );

        $result = $this->handler->handle($request);
        $this->assertSame([
            'contents' => [
                [
                    'uri' => 'file://random',
                    'name' => 'file.txt',
                    'title' => 'A random file from the filesystem',
                    'mimeType' => 'text/plain',
                    'blob' => 'blob_data',
                ],
            ],
        ], $result);
    }
}
