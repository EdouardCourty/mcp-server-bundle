<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\DependencyInjection\CompilerPass;

use Ecourty\McpServerBundle\Attribute\AsResource;
use Ecourty\McpServerBundle\DependencyInjection\CompilerPass\ResourcePass;
use Ecourty\McpServerBundle\IO\Resource\ResourceResult;
use Ecourty\McpServerBundle\Service\ResourceRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ResourcePassTest extends TestCase
{
    public function testProcessWithValidServerConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('mcp_server.configured_servers', ['server_a', 'server_b']);

        $resourceRegistryDefinition = new Definition(ResourceRegistry::class);
        $container->setDefinition(ResourceRegistry::class, $resourceRegistryDefinition);

        $resourceDefinition = new Definition(ValidResource::class);
        $container->setDefinition('test.resource', $resourceDefinition);

        $pass = new ResourcePass();

        // Should not throw an exception
        $pass->process($container);

        $this->assertTrue($resourceDefinition->hasTag('mcp_server.resource'));
        $this->assertCount(1, $resourceRegistryDefinition->getMethodCalls());
    }

    public function testProcessWithInvalidServerConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('mcp_server.configured_servers', ['server_a', 'server_b']);

        $resourceRegistryDefinition = new Definition(ResourceRegistry::class);
        $container->setDefinition(ResourceRegistry::class, $resourceRegistryDefinition);

        $resourceDefinition = new Definition(InvalidServerResource::class);
        $container->setDefinition('test.resource', $resourceDefinition);

        $pass = new ResourcePass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Resource "test-resource" references server "invalid_server" which is not configured. Available servers: server_a, server_b');

        $pass->process($container);
    }

    public function testProcessWithNullServerConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('mcp_server.configured_servers', ['server_a', 'server_b']);

        $resourceRegistryDefinition = new Definition(ResourceRegistry::class);
        $container->setDefinition(ResourceRegistry::class, $resourceRegistryDefinition);

        $resourceDefinition = new Definition(NullServerResource::class);
        $container->setDefinition('test.resource', $resourceDefinition);

        $pass = new ResourcePass();

        // Should not throw an exception for null server
        $pass->process($container);

        $this->assertTrue($resourceDefinition->hasTag('mcp_server.resource'));
        $this->assertCount(1, $resourceRegistryDefinition->getMethodCalls());
    }
}

#[AsResource(
    name: 'test-resource',
    uri: 'test://resource',
    title: 'Test Resource',
    description: 'A test resource',
    server: 'server_a',
)]
class ValidResource
{
    public function __invoke(): ResourceResult
    {
        return new ResourceResult([]);
    }
}

#[AsResource(
    name: 'test-resource',
    uri: 'test://resource',
    title: 'Test Resource',
    description: 'A test resource',
    server: 'invalid_server',
)]
class InvalidServerResource
{
    public function __invoke(): ResourceResult
    {
        return new ResourceResult([]);
    }
}

#[AsResource(
    name: 'test-resource',
    uri: 'test://resource',
    title: 'Test Resource',
    description: 'A test resource',
    server: null,
)]
class NullServerResource
{
    public function __invoke(): ResourceResult
    {
        return new ResourceResult([]);
    }
}
