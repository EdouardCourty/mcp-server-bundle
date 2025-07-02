<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\EventListener;

use Ecourty\McpServerBundle\Enum\McpErrorCode;
use Ecourty\McpServerBundle\Exception\MethodHandlerNotFoundException;
use Ecourty\McpServerBundle\Exception\RequestHandlingException;
use Ecourty\McpServerBundle\Exception\ToolNotFoundException;
use Ecourty\McpServerBundle\Service\ResponseFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles exceptions thrown during the request handling process.
 *
 * It converts exceptions into JSON-RPC error responses.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, method: 'onKernelException')]
class ExceptionListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $jsonRpcRequestId = $request?->attributes->get('json_rpc_request_id');
        $exception = $event->getThrowable();

        $event->allowCustomResponseCode();

        $response = match (true) {
            $exception instanceof UnprocessableEntityHttpException => $this->responseFactory->error($jsonRpcRequestId, McpErrorCode::PARSE_ERROR),
            $exception instanceof MethodHandlerNotFoundException => $this->responseFactory->error($jsonRpcRequestId, McpErrorCode::METHOD_NOT_FOUND),
            $exception instanceof RequestHandlingException => $this->handleRequestHandlingException($exception, $jsonRpcRequestId),
            default => null,
        };

        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    private function handleRequestHandlingException(
        RequestHandlingException $exception,
        string|int|null $jsonRpcRequestId = null,
    ): JsonResponse {
        $previous = $exception->getPrevious();
        if ($previous instanceof ToolNotFoundException === true) {
            return $this->responseFactory->error(
                id: $jsonRpcRequestId,
                errorCode: McpErrorCode::TOOL_NOT_FOUND,
            );
        }

        return $this->responseFactory->error($jsonRpcRequestId, McpErrorCode::INTERNAL_ERROR);
    }
}
