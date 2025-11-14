<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Service;

use Ecourty\McpServerBundle\IO\Resource\ResourceResult;
use Ecourty\McpServerBundle\Resource\AbstractResourceDefinition;

/**
 * Executes a resource based on its URI.
 */
class ResourceExecutor
{
    public function __construct(
        private readonly ResourceRegistry $registry,
        private readonly ResourceUriMatcher $matcher,
    ) {
    }

    public function execute(string $uri, ?string $serverKey = null): ResourceResult
    {
        $definition = $this->findDefinition($uri, $serverKey);
        $resource = $this->getResourceInstance($definition->uri, $serverKey);
        $invokeMethod = $this->getInvokeMethod($resource, $definition->uri);

        $rawArgs = $this->matcher->match($definition->uri, $uri);

        $orderedArgs = $this->resolveArguments($invokeMethod, $rawArgs, $definition->uri);

        $result = $invokeMethod->invoke($resource, ...$orderedArgs);

        if ($result instanceof ResourceResult === false) {
            throw new \RuntimeException(\sprintf(
                'Resource "%s" did not return an instance of ResourceResult.',
                $definition->uri,
            ));
        }

        return $result;
    }

    private function findDefinition(string $uri, ?string $serverKey = null): AbstractResourceDefinition
    {
        $resourceDefinitions = $this->registry->getResourceDefinitions($serverKey);

        foreach ($resourceDefinitions as $resourceDefinition) {
            $matchResult = $this->matcher->match($resourceDefinition->uri, $uri);

            if (empty($matchResult) === true) {
                continue; // No match, skip to next definition
            }

            return $resourceDefinition;
        }

        throw new \InvalidArgumentException(\sprintf(
            'No resource matched URI "%s" or resource not available in current server context.',
            $uri,
        ));
    }

    private function getResourceInstance(string $resourceUri, ?string $serverKey = null): object
    {
        $resource = $this->registry->getResource($resourceUri, $serverKey);

        if ($resource === null) {
            throw new \RuntimeException(\sprintf(
                'Resource "%s" not found or not available in current server context.',
                $resourceUri,
            ));
        }

        return $resource;
    }

    private function getInvokeMethod(object $resource, string $resourceUri): \ReflectionMethod
    {
        $reflection = new \ReflectionClass($resource);

        if ($reflection->hasMethod('__invoke') === false) {
            throw new \RuntimeException(\sprintf(
                'Resource "%s" does not have an __invoke method.',
                $resourceUri,
            ));
        }

        return $reflection->getMethod('__invoke');
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<int, mixed>
     */
    private function resolveArguments(
        \ReflectionMethod $method,
        array $args,
        string $resourceUri,
    ): array {
        $ordered = [];

        foreach ($method->getParameters() as $param) {
            $name = $param->getName();

            if (\array_key_exists($name, $args) === true) {
                $ordered[] = $this->castParameter($param, $args[$name]);
            } elseif ($param->isDefaultValueAvailable() === true) {
                $ordered[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException(\sprintf(
                    'Missing required parameter "%s" for resource "%s".',
                    $name,
                    $resourceUri,
                ));
            }
        }

        return $ordered;
    }

    /**
     * Cast a raw URI value to the parameter's declared type
     */
    private function castParameter(\ReflectionParameter $param, mixed $value): mixed
    {
        $type = $param->getType();

        if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
            // Handle simple built-in types
            switch ($type->getName()) {
                case 'int':
                    return (int) $value;

                case 'float':
                    return (float) $value;

                case 'bool':
                    return filter_var(
                        (string) $value,
                        \FILTER_VALIDATE_BOOL,
                        \FILTER_NULL_ON_FAILURE,
                    );

                case 'string':
                    return (string) $value;

                case 'array':
                    if (\is_string($value)) {
                        return json_decode($value, true, flags: \JSON_THROW_ON_ERROR);
                    }

                    return (array) $value;

                default:
                    // Fallback for other built-ins
                    settype($value, $type->getName());

                    return $value;
            }
        }

        return $value;
    }
}
