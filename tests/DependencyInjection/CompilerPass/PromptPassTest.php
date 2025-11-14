<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\DependencyInjection\CompilerPass;

use Ecourty\McpServerBundle\Attribute\AsPrompt;
use Ecourty\McpServerBundle\DependencyInjection\CompilerPass\PromptPass;
use Ecourty\McpServerBundle\IO\Prompt\PromptResult;
use Ecourty\McpServerBundle\Service\PromptRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class PromptPassTest extends TestCase
{
    public function testProcessWithValidServerConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('mcp_server.configured_servers', ['server_a', 'server_b']);

        $promptRegistryDefinition = new Definition(PromptRegistry::class);
        $container->setDefinition(PromptRegistry::class, $promptRegistryDefinition);

        $promptDefinition = new Definition(ValidPrompt::class);
        $container->setDefinition('test.prompt', $promptDefinition);

        $pass = new PromptPass();

        // Should not throw an exception
        $pass->process($container);

        $this->assertTrue($promptDefinition->hasTag('mcp_server.prompt'));
        $this->assertCount(1, $promptRegistryDefinition->getMethodCalls());
    }

    public function testProcessWithInvalidServerConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('mcp_server.configured_servers', ['server_a', 'server_b']);

        $promptRegistryDefinition = new Definition(PromptRegistry::class);
        $container->setDefinition(PromptRegistry::class, $promptRegistryDefinition);

        $promptDefinition = new Definition(InvalidServerPrompt::class);
        $container->setDefinition('test.prompt', $promptDefinition);

        $pass = new PromptPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Prompt "test-prompt" references server "invalid_server" which is not configured. Available servers: server_a, server_b');

        $pass->process($container);
    }

    public function testProcessWithNullServerConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('mcp_server.configured_servers', ['server_a', 'server_b']);

        $promptRegistryDefinition = new Definition(PromptRegistry::class);
        $container->setDefinition(PromptRegistry::class, $promptRegistryDefinition);

        $promptDefinition = new Definition(NullServerPrompt::class);
        $container->setDefinition('test.prompt', $promptDefinition);

        $pass = new PromptPass();

        // Should not throw an exception for null server
        $pass->process($container);

        $this->assertTrue($promptDefinition->hasTag('mcp_server.prompt'));
        $this->assertCount(1, $promptRegistryDefinition->getMethodCalls());
    }
}

#[AsPrompt(
    name: 'test-prompt',
    description: 'A test prompt',
    server: 'server_a',
)]
class ValidPrompt
{
    public function __invoke(): PromptResult
    {
        return new PromptResult('Test prompt content');
    }
}

#[AsPrompt(
    name: 'test-prompt',
    description: 'A test prompt',
    server: 'invalid_server',
)]
class InvalidServerPrompt
{
    public function __invoke(): PromptResult
    {
        return new PromptResult('Test prompt content');
    }
}

#[AsPrompt(
    name: 'test-prompt',
    description: 'A test prompt',
    server: null,
)]
class NullServerPrompt
{
    public function __invoke(): PromptResult
    {
        return new PromptResult('Test prompt content');
    }
}
