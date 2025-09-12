<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\DependencyInjection\CompilerPass;

use Ecourty\McpServerBundle\Attribute\AsTool;
use Ecourty\McpServerBundle\IO\ToolResult;
use Ecourty\McpServerBundle\Service\SchemaExtractor;
use Ecourty\McpServerBundle\Service\ToolRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * ToolPass is a compiler pass that processes MCP server tools.
 *
 * It validates the class, method signatures, and extracts tool metadata.
 */
class ToolPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $schemaExtractor = new SchemaExtractor();
        $toolRegistry = $container->getDefinition(ToolRegistry::class);

        // Get configured server keys for validation
        $configuredServers = $container->getParameter('mcp_server.configured_servers');
        if (!\is_array($configuredServers)) {
            $configuredServers = [];
        }

        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();

            try {
                if ($class === null || class_exists($class) === false) {
                    continue;
                }
            } catch (\Throwable) {
                continue;
            }

            $refClass = new \ReflectionClass($class);

            $attributes = $refClass->getAttributes(AsTool::class);
            if (\count($attributes) === 0) {
                continue;
            }

            if (\count($attributes) > 1) {
                throw new \LogicException(\sprintf(
                    'Multiple AsTool attributes found on class "%s". Only one is allowed.',
                    $class,
                ));
            }

            /** @var AsTool|null $asTool */
            $asTool = $attributes[0]->newInstance();
            if ($asTool === null) {
                throw new \LogicException(\sprintf(
                    'Failed to instantiate AsTool attribute for class "%s".',
                    $class,
                ));
            }

            if (method_exists($class, '__invoke') === false) {
                throw new \LogicException(\sprintf(
                    'Class "%s" must implement the __invoke method to be used as a tool.',
                    $class,
                ));
            }

            $invokeMethodParameters = $refClass->getMethod('__invoke')->getParameters();
            if (\count($invokeMethodParameters) > 1) {
                throw new \LogicException(\sprintf(
                    'Class "%s" must have a maximum of 1 parameter in the __invoke method to be used as a tool.',
                    $class,
                ));
            }

            $invokeMethodParameter = $invokeMethodParameters[0] ?? null;
            if (
                $invokeMethodParameter !== null
                && (
                    $invokeMethodParameter->getType() === null
                    || $invokeMethodParameter->getType()->isBuiltin() // @phpstan-ignore method.notFound
                )
            ) {
                throw new \LogicException(\sprintf(
                    'The parameter of the __invoke method in class "%s" must be a non-builtin type.',
                    $class,
                ));
            }

            $returnType = $refClass->getMethod('__invoke')->getReturnType();
            if ($returnType?->getName() !== ToolResult::class) { // @phpstan-ignore method.notFound
                throw new \LogicException(\sprintf(
                    'The __invoke method in class "%s" must return an instance of %s.',
                    $class,
                    ToolResult::class,
                ));
            }

            $inputSchemaClassName = $invokeMethodParameter?->getType()?->getName(); // @phpstan-ignore method.notFound
            $inputSchema = $invokeMethodParameter === null
                ? []
                : $schemaExtractor->extract($inputSchemaClassName);

            // Validate server key at compile time
            if ($asTool->server !== null && !\in_array($asTool->server, $configuredServers, true)) {
                throw new \LogicException(\sprintf(
                    'Tool "%s" references server "%s" which is not configured. Available servers: %s',
                    $asTool->name,
                    $asTool->server,
                    implode(', ', $configuredServers),
                ));
            }

            $definition->addTag(name: 'mcp_server.tool', attributes: [
                'name' => $asTool->name,
            ]);

            $toolRegistry->addMethodCall(method: 'addToolDefinition', arguments: [
                $asTool->name,
                $asTool->description,
                $inputSchema,
                $inputSchemaClassName,
                $asTool->getToolAnnotations()->asArray(),
                $asTool->server,
            ]);
        }
    }
}
