<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\DependencyInjection\CompilerPass;

use Ecourty\McpServerBundle\Attribute\AsResource;
use Ecourty\McpServerBundle\IO\Resource\ResourceResult;
use Ecourty\McpServerBundle\Service\ResourceRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ResourcePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $promptRegistry = $container->getDefinition(ResourceRegistry::class);

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

            $attributes = $refClass->getAttributes(AsResource::class);
            if (\count($attributes) === 0) {
                continue;
            }

            if (\count($attributes) > 1) {
                throw new \LogicException(\sprintf(
                    'Multiple AsResource attributes found on class "%s". Only one is allowed.',
                    $class,
                ));
            }

            if ($refClass->hasMethod('__invoke') === false) {
                throw new \LogicException(\sprintf(
                    'Class "%s" must implement the __invoke method to be used as a resource.',
                    $class,
                ));
            }

            /** @var AsResource|null $attr */
            $attr = $attributes[0]->newInstance();
            if ($attr === null) {
                throw new \LogicException(\sprintf(
                    'Failed to instantiate AsResource attribute for class "%s".',
                    $class,
                ));
            }

            $returnType = $refClass->getMethod('__invoke')->getReturnType();
            if ($returnType?->getName() !== ResourceResult::class) { // @phpstan-ignore method.notFound
                throw new \LogicException(\sprintf(
                    'The __invoke method in class "%s" must return an instance of %s.',
                    $class,
                    ResourceResult::class,
                ));
            }

            // Validate server key at compile time
            if ($attr->server !== null && !\in_array($attr->server, $configuredServers, true)) {
                throw new \LogicException(\sprintf(
                    'Resource "%s" references server "%s" which is not configured. Available servers: %s',
                    $attr->name,
                    $attr->server,
                    implode(', ', $configuredServers),
                ));
            }

            $definition
                ->addTag(name: 'mcp_server.resource', attributes: [
                    'uri' => $attr->uri,
                ]);

            if ($attr->isTemplatedResource() === true) {
                $promptRegistry->addMethodCall(method: 'addTemplateResourceDefinition', arguments: [
                    $attr->name,
                    $attr->uri,
                    $attr->title,
                    $attr->description,
                    $attr->mimeType,
                    $attr->server,
                ]);
            } else {
                $promptRegistry->addMethodCall(method: 'addDirectResourceDefinition', arguments: [
                    $attr->name,
                    $attr->uri,
                    $attr->title,
                    $attr->description,
                    $attr->mimeType,
                    $attr->size,
                    $attr->server,
                ]);
            }
        }
    }
}
