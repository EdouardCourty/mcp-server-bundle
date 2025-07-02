<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\MethodHandler;

use Ecourty\McpServerBundle\Attribute\AsMethodHandler;
use Ecourty\McpServerBundle\Contract\MethodHandlerInterface;
use Ecourty\McpServerBundle\Event\ToolCallEvent;
use Ecourty\McpServerBundle\Event\ToolCallExceptionEvent;
use Ecourty\McpServerBundle\Event\ToolResultEvent;
use Ecourty\McpServerBundle\Exception\ToolCallException;
use Ecourty\McpServerBundle\Exception\ToolNotFoundException;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Ecourty\McpServerBundle\IO\ToolResult;
use Ecourty\McpServerBundle\Service\InputSanitizer;
use Ecourty\McpServerBundle\Service\ToolRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Handles the 'tools/call' method in the MCP server.
 *
 * This method is used to invoke a tool with the specified name and arguments.
 * It validates the input against the tool's input schema and returns the tool's response.
 *
 * @see https://modelcontextprotocol.io/specification/2025-03-26/server/tools#calling-tools
 */
#[AsMethodHandler(methodName: 'tools/call')]
class ToolsCallMethodHandler implements MethodHandlerInterface
{
    public function __construct(
        private readonly ToolRegistry $toolRegistry,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly InputSanitizer $inputSanitizer,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {
    }

    public function handle(JsonRpcRequest $request): array
    {
        $toolName = $request->params['name'] ?? null;

        if ($toolName === null) {
            throw new \InvalidArgumentException('Tool name is required.');
        }

        $tool = $this->toolRegistry->getTool($toolName);
        $toolDefinition = $this->toolRegistry->getToolDefinition($toolName);

        if ($tool === null) {
            throw new ToolNotFoundException(\sprintf('Tool "%s" not found.', $request->params['name']));
        }

        if ($toolDefinition === null) {
            throw new \InvalidArgumentException(\sprintf(
                'Tool "%s" does not have a definition.',
                $toolName,
            ));
        }

        try {
            $arguments = $request->params['arguments'] ?? [];
            $sanitizedInput = $this->inputSanitizer->sanitize($arguments);

            $jsonPayload = json_encode($sanitizedInput, \JSON_THROW_ON_ERROR);
            $inputSchemaClass = $toolDefinition->inputSchemaClass;

            $inputModel = $inputSchemaClass === null
                ? null
                : $this->serializer->deserialize(
                    data: $jsonPayload,
                    type: $inputSchemaClass,
                    format: 'json',
                );

            if ($inputSchemaClass !== null && $inputModel instanceof $inputSchemaClass === false) {
                throw new \InvalidArgumentException(\sprintf(
                    'Deserialized result is not an instance of "%s".',
                    $inputSchemaClass,
                ));
            }

            $violations = $inputModel === null
                ? new ConstraintViolationList()
                : $this->validator->validate($inputModel);

            if ($violations->count() > 0) {
                return $this->buildValidationErrorResponse(iterator_to_array($violations));
            }

            if (method_exists($tool, '__invoke') === false) {
                throw new \LogicException(\sprintf('Tool "%s" does not implement __invoke method.', $toolName));
            }

            if ($this->eventDispatcher !== null) {
                $event = new ToolCallEvent(toolName: $toolName, payload: $inputModel);

                $this->eventDispatcher->dispatch($event);
            }

            $toolResult = $inputModel === null
                ? $tool->__invoke()
                : $tool->__invoke($inputModel);

            if ($toolResult instanceof ToolResult === false) {
                throw new \LogicException(\sprintf(
                    'Tool "%s" did not return an instance of %s.',
                    $toolName,
                    ToolResult::class,
                ));
            }

            if ($this->eventDispatcher !== null) {
                $event = new ToolResultEvent(toolName: $toolName, payload: $inputModel, result: $toolResult);

                $this->eventDispatcher->dispatch($event);
            }
        } catch (\Throwable $exception) {
            if ($this->eventDispatcher !== null) {
                $event = new ToolCallExceptionEvent(toolName: $toolName, payload: $inputModel ?? null, exception: $exception);

                $this->eventDispatcher->dispatch($event);
            }

            throw new ToolCallException(
                message: 'An error occurred while handling a tool call.',
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                previous: $exception,
            );
        }

        return $toolResult->toArray();
    }

    private function buildValidationErrorResponse(array $errors): array
    {
        return [
            'isError' => true,
            'content' => array_map(function (ConstraintViolationInterface $violation) {
                return [
                    'type' => 'text',
                    'text' => \sprintf(
                        'Validation error: %s - %s',
                        $violation->getPropertyPath(),
                        $violation->getMessage(),
                    ),
                ];
            }, $errors),
        ];
    }
}
