<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\DependencyInjection\CompilerPass;

use Ecourty\McpServerBundle\Attribute\AsPrompt;
use Ecourty\McpServerBundle\IO\Prompt\PromptResult;
use Ecourty\McpServerBundle\Prompt\Argument;
use Ecourty\McpServerBundle\Service\PromptRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * PromptPass is a compiler pass that processes MCP server prompts.
 *
 * It validates the class and method signatures.
 */
class PromptPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $promptRegistry = $container->getDefinition(PromptRegistry::class);

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

            $attributes = $refClass->getAttributes(AsPrompt::class);
            if (\count($attributes) === 0) {
                continue;
            }

            if (\count($attributes) > 1) {
                throw new \LogicException(\sprintf(
                    'Multiple AsPrompt attributes found on class "%s". Only one is allowed.',
                    $class,
                ));
            }

            if ($refClass->hasMethod('__invoke') === false) {
                throw new \LogicException(\sprintf(
                    'Class "%s" must implement the __invoke method to be used as a prompt.',
                    $class,
                ));
            }

            $invokeMethodParameters = $refClass->getMethod('__invoke')->getParameters();
            if (\count($invokeMethodParameters) > 1) {
                throw new \LogicException(\sprintf(
                    'Class "%s" must have a maximum of one parameter in the __invoke method to be used as a prompt.',
                    $class,
                ));
            }

            $returnType = $refClass->getMethod('__invoke')->getReturnType();
            if ($returnType?->getName() !== PromptResult::class) { // @phpstan-ignore method.notFound
                throw new \LogicException(\sprintf(
                    'The __invoke method in class "%s" must return an instance of %s.',
                    $class,
                    PromptResult::class,
                ));
            }

            /** @var AsPrompt|null $attr */
            $attr = $attributes[0]->newInstance();
            if ($attr === null) {
                throw new \LogicException(\sprintf(
                    'Failed to instantiate AsPrompt attribute for class "%s".',
                    $class,
                ));
            }

            // Validate server key at compile time
            if ($attr->server !== null && !\in_array($attr->server, $configuredServers, true)) {
                throw new \LogicException(\sprintf(
                    'Prompt "%s" references server "%s" which is not configured. Available servers: %s',
                    $attr->name,
                    $attr->server,
                    implode(', ', $configuredServers),
                ));
            }

            $definition
                ->addTag(name: 'mcp_server.prompt', attributes: [
                    'name' => $attr->name,
                ]);

            $promptRegistry->addMethodCall(method: 'addPromptDefinition', arguments: [
                $attr->name,
                $attr->description,
                array_map(fn (Argument $argument) => $argument->toArray(), $attr->getArguments()),
                $attr->server,
            ]);
        }
    }
}
