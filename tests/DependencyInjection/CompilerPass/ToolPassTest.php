<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\DependencyInjection\CompilerPass;

use Ecourty\McpServerBundle\Attribute\AsTool;
use Ecourty\McpServerBundle\DependencyInjection\CompilerPass\ToolPass;
use Ecourty\McpServerBundle\IO\ToolResult;
use Ecourty\McpServerBundle\Service\ToolRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ToolPassTest extends TestCase
{
    public function testProcessWithValidServerConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('mcp_server.configured_servers', ['server_a', 'server_b']);

        $toolRegistryDefinition = new Definition(ToolRegistry::class);
        $container->setDefinition(ToolRegistry::class, $toolRegistryDefinition);

        $toolDefinition = new Definition(ValidTool::class);
        $container->setDefinition('test.tool', $toolDefinition);

        $pass = new ToolPass();

        // Should not throw an exception
        $pass->process($container);

        $this->assertTrue($toolDefinition->hasTag('mcp_server.tool'));
        $this->assertCount(1, $toolRegistryDefinition->getMethodCalls());
    }

    public function testProcessWithInvalidServerConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('mcp_server.configured_servers', ['server_a', 'server_b']);

        $toolRegistryDefinition = new Definition(ToolRegistry::class);
        $container->setDefinition(ToolRegistry::class, $toolRegistryDefinition);

        $toolDefinition = new Definition(InvalidServerTool::class);
        $container->setDefinition('test.tool', $toolDefinition);

        $pass = new ToolPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Tool "test-tool" references server "invalid_server" which is not configured. Available servers: server_a, server_b');

        $pass->process($container);
    }

    public function testProcessWithNullServerConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('mcp_server.configured_servers', ['server_a', 'server_b']);

        $toolRegistryDefinition = new Definition(ToolRegistry::class);
        $container->setDefinition(ToolRegistry::class, $toolRegistryDefinition);

        $toolDefinition = new Definition(NullServerTool::class);
        $container->setDefinition('test.tool', $toolDefinition);

        $pass = new ToolPass();

        // Should not throw an exception for null server
        $pass->process($container);

        $this->assertTrue($toolDefinition->hasTag('mcp_server.tool'));
        $this->assertCount(1, $toolRegistryDefinition->getMethodCalls());
    }
}

#[AsTool(
    name: 'test-tool',
    description: 'A test tool',
    server: 'server_a',
)]
class ValidTool
{
    public function __invoke(): ToolResult
    {
        return new ToolResult([]);
    }
}

#[AsTool(
    name: 'test-tool',
    description: 'A test tool',
    server: 'invalid_server',
)]
class InvalidServerTool
{
    public function __invoke(): ToolResult
    {
        return new ToolResult([]);
    }
}

#[AsTool(
    name: 'test-tool',
    description: 'A test tool',
    server: null,
)]
class NullServerTool
{
    public function __invoke(): ToolResult
    {
        return new ToolResult([]);
    }
}
