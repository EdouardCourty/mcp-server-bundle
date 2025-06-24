<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Service;

use Ecourty\McpServerBundle\Enum\McpErrorCode;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcError;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Factory for creating JSON-RPC responses.
 */
class ResponseFactory
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function success(int|string|null $id, mixed $result): JsonResponse
    {
        return $this->createResponse(new JsonRpcResponse(
            id: $id,
            result: $result,
            error: null,
        ));
    }

    public function error(int|string|null $id, McpErrorCode $errorCode): JsonResponse
    {
        return $this->createResponse(new JsonRpcResponse(
            id: $id,
            result: null,
            error: new JsonRpcError(
                code: $errorCode,
                message: $errorCode->getMessage(),
            ),
        ));
    }

    private function createResponse(JsonRpcResponse $response): JsonResponse
    {
        $json = $this->serializer->serialize($response, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true,
        ]);

        return JsonResponse::fromJsonString($json, JsonResponse::HTTP_OK);
    }
}
