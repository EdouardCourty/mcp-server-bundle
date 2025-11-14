<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\MethodHandler;

use Ecourty\McpServerBundle\Event\Prompt\AbstractPromptEvent;
use Ecourty\McpServerBundle\Event\Prompt\PromptExceptionEvent;
use Ecourty\McpServerBundle\Event\Prompt\PromptGetEvent;
use Ecourty\McpServerBundle\Event\Prompt\PromptResultEvent;
use Ecourty\McpServerBundle\Exception\PromptGetException;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\MethodHandler\PromptsGetMethodHandler;
use Ecourty\McpServerBundle\Service\CurrentServerService;
use Ecourty\McpServerBundle\Service\InputSanitizer;
use Ecourty\McpServerBundle\Service\PromptRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @coversDefaultClass \Ecourty\McpServerBundle\MethodHandler\PromptsGetMethodHandler
 */
class PromptsGetMethodHandlerTest extends KernelTestCase
{
    private MockObject&EventDispatcherInterface $eventDispatcher;

    private PromptsGetMethodHandler $promptsGetMethodHandler;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        /** @var PromptRegistry $promptRegistry */
        $promptRegistry = self::getContainer()->get(PromptRegistry::class);
        /** @var InputSanitizer $inputSanitizer */
        $inputSanitizer = self::getContainer()->get(InputSanitizer::class);
        /** @var CurrentServerService $currentServerService */
        $currentServerService = self::getContainer()->get(CurrentServerService::class);

        $this->promptsGetMethodHandler = new PromptsGetMethodHandler(
            promptRegistry: $promptRegistry,
            inputSanitizer: $inputSanitizer,
            currentServerService: $currentServerService,
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
            ->willReturnCallback(function (AbstractPromptEvent $event) use ($matcher, $events) {
                $this->assertInstanceOf($events[$matcher->numberOfInvocations() - 1], $event);
            });

        if ($willThrow !== null) {
            $this->expectException($willThrow);
        }

        $this->promptsGetMethodHandler->handle($jsonRpcRequest);
    }

    public static function provideEventFireTestData(): \Generator
    {
        yield 'Regular result flow' => [
            'jsonRpcRequest' => new JsonRpcRequest(
                id: 1,
                method: 'prompts/get',
                params: [
                    'name' => 'generate-git-commit-message',
                    'arguments' => [
                        'changes' => 'changes',
                        'scope' => 'feature',
                    ],
                ],
            ),
            'events' => [
                PromptGetEvent::class,
                PromptResultEvent::class,
            ],
            'willThrow' => null,
        ];

        yield 'Error result flow' => [
            'jsonRpcRequest' => new JsonRpcRequest(
                id: 1,
                method: 'prompts/get',
                params: [
                    'name' => 'generate-git-commit-message',
                    'arguments' => [
                        'changes' => 'changes',
                        'scope' => 'error', // This will trigger an error in the prompt
                    ],
                ],
            ),
            'events' => [
                PromptGetEvent::class,
                PromptExceptionEvent::class,
            ],
            'willThrow' => PromptGetException::class,
        ];

        yield 'Invalid prompt name' => [
            'jsonRpcRequest' => new JsonRpcRequest(
                id: 1,
                method: 'prompts/get',
                params: [
                    'name' => 'invalid-prompt-name',
                    'arguments' => [],
                ],
            ),
            'events' => [],
            'willThrow' => \InvalidArgumentException::class,
        ];
    }
}
