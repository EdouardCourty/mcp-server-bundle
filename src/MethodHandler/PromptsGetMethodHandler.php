<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\MethodHandler;

use Ecourty\McpServerBundle\Attribute\AsMethodHandler;
use Ecourty\McpServerBundle\Contract\MethodHandlerInterface;
use Ecourty\McpServerBundle\Event\Prompt\PromptExceptionEvent;
use Ecourty\McpServerBundle\Event\Prompt\PromptGetEvent;
use Ecourty\McpServerBundle\Event\Prompt\PromptResultEvent;
use Ecourty\McpServerBundle\Exception\PromptGetException;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\IO\Prompt\PromptResult;
use Ecourty\McpServerBundle\Prompt\ArgumentCollection;
use Ecourty\McpServerBundle\Prompt\PromptDefinition;
use Ecourty\McpServerBundle\Service\CurrentServerService;
use Ecourty\McpServerBundle\Service\InputSanitizer;
use Ecourty\McpServerBundle\Service\PromptRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the 'prompts/get' method in the MCP server.
 *
 * This method is used to retrieve a specific prompt by its name and generate it with the provided arguments.
 *
 * @see https://modelcontextprotocol.io/specification/2025-03-26/server/prompts#getting-a-prompt
 */
#[AsMethodHandler(methodName: 'prompts/get')]
class PromptsGetMethodHandler implements MethodHandlerInterface
{
    public function __construct(
        private readonly PromptRegistry $promptRegistry,
        private readonly InputSanitizer $inputSanitizer,
        private readonly CurrentServerService $currentServerService,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {
    }

    public function handle(JsonRpcRequest $request): array
    {
        $promptName = $request->params['name'] ?? null;

        if ($promptName === null) {
            throw new \InvalidArgumentException('Prompt name is required.');
        }

        $serverKey = $this->currentServerService->getCurrentServerKey();
        $prompt = $this->promptRegistry->getPrompt($promptName, $serverKey);
        $promptDefinition = $this->promptRegistry->getPromptDefinition($promptName);

        if ($prompt === null) {
            throw new \InvalidArgumentException(\sprintf('Prompt "%s" not found or not available in current server context.', $promptName));
        }

        if ($promptDefinition === null) {
            throw new \InvalidArgumentException(\sprintf(
                'Prompt "%s" does not have a definition.',
                $promptName,
            ));
        }

        try {
            $arguments = $request->params['arguments'] ?? [];

            $this->validatePromptArguments($arguments, $promptDefinition);
            $sanitizedArguments = $this->getSanitizedArguments($arguments, $promptDefinition);

            if (method_exists($prompt, '__invoke') === false) {
                throw new \LogicException(\sprintf('Prompt "%s" does not implement the __invoke method.', $promptName));
            }

            $this->eventDispatcher?->dispatch(new PromptGetEvent(
                promptName: $promptName,
                arguments: $sanitizedArguments,
            ));

            $promptResult = $prompt->__invoke($sanitizedArguments);

            if ($promptResult instanceof PromptResult === false) {
                throw new \LogicException(\sprintf(
                    'Prompt "%s" did not return an instance of %s.',
                    $promptName,
                    PromptResult::class,
                ));
            }

            $this->eventDispatcher?->dispatch(new PromptResultEvent($promptName, $sanitizedArguments, $promptResult));
        } catch (\Throwable $exception) {
            $this->eventDispatcher?->dispatch(new PromptExceptionEvent(
                promptName: $promptName,
                arguments: $sanitizedArguments ?? null,
                exception: $exception,
            ));

            throw new PromptGetException(
                message: 'An error occurred while retrieve the prompt.',
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                previous: $exception,
            );
        }

        return $promptResult->toArray();
    }

    /**
     * @param array<string, mixed> $inputArgument
     */
    private function getSanitizedArguments(array $inputArgument, PromptDefinition $promptDefinition): ArgumentCollection
    {
        $sanitizedArguments = [];
        $promptArguments = $promptDefinition->arguments ?? [];

        foreach ($promptArguments as $argument) {
            $argumentName = $argument->name;
            $unsafeArgumentValue = $inputArgument[$argumentName] ?? null;

            if ($argument->allowUnsafe === true) {
                $sanitizedArguments[$argumentName] = $unsafeArgumentValue;

                continue;
            }

            $sanitizedArguments[$argumentName] = $this->inputSanitizer->sanitize($unsafeArgumentValue);
        }

        return ArgumentCollection::fromArray($sanitizedArguments);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function validatePromptArguments(array $arguments, PromptDefinition $promptDefinition): void
    {
        $promptArguments = $promptDefinition->arguments ?? [];

        foreach ($promptArguments as $expectedArgument) {
            if ($expectedArgument->required === true && \array_key_exists($expectedArgument->name, $arguments) === false) {
                throw new \InvalidArgumentException(\sprintf(
                    'Missing required argument "%s" for prompt "%s"',
                    $expectedArgument->name,
                    $promptDefinition->name,
                ));
            }
        }
    }
}
