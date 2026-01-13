<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\MethodHandler;

use Ecourty\McpServerBundle\Event\AbstractToolEvent;
use Ecourty\McpServerBundle\Event\ToolCallEvent;
use Ecourty\McpServerBundle\Event\ToolResultEvent;
use Ecourty\McpServerBundle\Exception\ToolNotFoundException;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\MethodHandler\ToolsCallMethodHandler;
use Ecourty\McpServerBundle\Service\InputSanitizer;
use Ecourty\McpServerBundle\Service\ToolRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @coversDefaultClass \Ecourty\McpServerBundle\MethodHandler\ToolsCallMethodHandler
 */
class ToolsCallMethodHandlerTest extends KernelTestCase
{
    private MockObject&EventDispatcherInterface $eventDispatcher;

    private ToolsCallMethodHandler $toolsCallMethodHandler;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        /** @var ToolRegistry $toolRegistry */
        $toolRegistry = self::getContainer()->get(ToolRegistry::class);
        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get(ValidatorInterface::class);
        /** @var InputSanitizer $inputSanitizer */
        $inputSanitizer = self::getContainer()->get(InputSanitizer::class);

        $this->toolsCallMethodHandler = new ToolsCallMethodHandler(
            toolRegistry: $toolRegistry,
            serializer: $serializer,
            validator: $validator,
            inputSanitizer: $inputSanitizer,
            eventDispatcher: $this->eventDispatcher,
        );
    }

    /**
     * @covers ::handle
     *
     * @param class-string<\Throwable>|null $willThrow
     */
    #[DataProvider('provideEventFireTestData')]
    public function testFiresEvents(JsonRpcRequest $jsonRpcRequest, array $events, ?string $willThrow): void
    {
        $matcher = $this->exactly(\count($events));
        $this->eventDispatcher
            ->expects($matcher)
            ->method('dispatch')
            ->willReturnCallback(function (AbstractToolEvent $event) use ($matcher, $events) {
                $this->assertInstanceOf($events[$matcher->numberOfInvocations() - 1], $event);
            });

        if ($willThrow !== null) {
            $this->expectException($willThrow);
        }

        $this->toolsCallMethodHandler->handle($jsonRpcRequest);
    }

    public static function provideEventFireTestData(): \Generator
    {
        yield 'Regular result flow' => [
            'jsonRpcRequest' => new JsonRpcRequest(
                id: 1,
                method: 'tools/call',
                params: [
                    'name' => 'sum_numbers',
                    'arguments' => [
                        'number1' => 3,
                        'number2' => 5,
                    ],
                ],
            ),
            'events' => [
                ToolCallEvent::class,
                ToolResultEvent::class,
            ],
            'willThrow' => null,
        ];

        yield 'Error result flow' => [
            'jsonRpcRequest' => new JsonRpcRequest(
                id: 1,
                method: 'tools/call',
                params: [
                    'name' => 'create_user',
                    'arguments' => [
                        'username' => 'testIsError', // This username is expected to trigger an error
                        'emailAddress' => 'test@mcp.com',
                    ],
                ],
            ),
            'events' => [
                ToolCallEvent::class,
                ToolResultEvent::class,
            ],
            'willThrow' => null,
        ];

        yield 'Invalid tool name' => [
            'jsonRpcRequest' => new JsonRpcRequest(
                id: 1,
                method: 'tools/call',
                params: [
                    'name' => 'non_existent_tool',
                    'arguments' => [],
                ],
            ),
            'events' => [],
            'willThrow' => ToolNotFoundException::class,
        ];
    }
}
