<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\Service;

use Ecourty\McpServerBundle\Service\ServerConfigurationRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Ecourty\McpServerBundle\Service\ServerConfigurationRegistry
 */
class ServerConfigurationRegistryTest extends TestCase
{
    private ServerConfigurationRegistry $registry;

    protected function setUp(): void
    {
        // Set up test data representing different server configurations
        $serverConfigurations = [
            'default' => [
                'name' => 'Default Server',
                'title' => 'Default MCP Server',
                'version' => '1.0.0',
            ],
            'simple_rest' => [
                'name' => 'Simple REST API Server',
                'title' => 'Pimcore Data Hub Simple REST API',
                'version' => '2.1.0',
            ],
            'analytics' => [
                'name' => 'Analytics Server',
                'version' => '1.5.0',
                // Note: no title provided to test optional field
            ],
        ];

        $this->registry = new ServerConfigurationRegistry($serverConfigurations);
    }

    /**
     * @covers ::__construct
     * @covers ::getAllServerConfigurations
     */
    public function testGetAllServerConfigurations(): void
    {
        $expected = [
            'default' => [
                'name' => 'Default Server',
                'title' => 'Default MCP Server',
                'version' => '1.0.0',
            ],
            'simple_rest' => [
                'name' => 'Simple REST API Server',
                'title' => 'Pimcore Data Hub Simple REST API',
                'version' => '2.1.0',
            ],
            'analytics' => [
                'name' => 'Analytics Server',
                'version' => '1.5.0',
            ],
        ];

        $result = $this->registry->getAllServerConfigurations();

        $this->assertSame($expected, $result);
    }

    /**
     * @covers ::getServerConfiguration
     *
     * @param array{name: string, title?: string, version: string}|null $expected
     */
    #[DataProvider('provideServerConfigurationData')]
    public function testGetServerConfiguration(string $serverKey, ?array $expected): void
    {
        $result = $this->registry->getServerConfiguration($serverKey);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: string, 1: array{name: string, title?: string, version: string}|null}>
     */
    public static function provideServerConfigurationData(): array
    {
        return [
            'existing server with title' => [
                'default',
                [
                    'name' => 'Default Server',
                    'title' => 'Default MCP Server',
                    'version' => '1.0.0',
                ],
            ],
            'existing server without title' => [
                'analytics',
                [
                    'name' => 'Analytics Server',
                    'version' => '1.5.0',
                ],
            ],
            'existing server with all fields' => [
                'simple_rest',
                [
                    'name' => 'Simple REST API Server',
                    'title' => 'Pimcore Data Hub Simple REST API',
                    'version' => '2.1.0',
                ],
            ],
            'non-existing server' => [
                'non_existent',
                null,
            ],
            'empty string server key' => [
                '',
                null,
            ],
        ];
    }

    /**
     * @covers ::hasServer
     */
    #[DataProvider('provideHasServerData')]
    public function testHasServer(string $serverKey, bool $expected): void
    {
        $result = $this->registry->hasServer($serverKey);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function provideHasServerData(): array
    {
        return [
            'existing server default' => ['default', true],
            'existing server simple_rest' => ['simple_rest', true],
            'existing server analytics' => ['analytics', true],
            'non-existing server' => ['non_existent', false],
            'empty string' => ['', false],
            'case sensitive check' => ['DEFAULT', false], // Should be case-sensitive
        ];
    }

    /**
     * @covers ::getServerKeys
     */
    public function testGetServerKeys(): void
    {
        $expected = ['default', 'simple_rest', 'analytics'];

        $result = $this->registry->getServerKeys();

        $this->assertSame($expected, $result);
    }

    /**
     * @covers ::__construct
     * @covers ::getAllServerConfigurations
     * @covers ::getServerKeys
     */
    public function testEmptyServerConfigurationRegistry(): void
    {
        $emptyRegistry = new ServerConfigurationRegistry([]);

        $this->assertSame([], $emptyRegistry->getAllServerConfigurations());
        $this->assertSame([], $emptyRegistry->getServerKeys());
        $this->assertFalse($emptyRegistry->hasServer('any_key'));
        $this->assertNull($emptyRegistry->getServerConfiguration('any_key'));
    }

    /**
     * @covers ::__construct
     * @covers ::getServerConfiguration
     * @covers ::hasServer
     * @covers ::getServerKeys
     */
    public function testSingleServerConfiguration(): void
    {
        $singleServerConfig = [
            'only_server' => [
                'name' => 'Only Server',
                'title' => 'The Only Server',
                'version' => '3.0.0',
            ],
        ];

        $registry = new ServerConfigurationRegistry($singleServerConfig);

        $this->assertTrue($registry->hasServer('only_server'));
        $this->assertFalse($registry->hasServer('other_server'));
        $this->assertSame(['only_server'], $registry->getServerKeys());
        $this->assertSame($singleServerConfig['only_server'], $registry->getServerConfiguration('only_server'));
        $this->assertNull($registry->getServerConfiguration('other_server'));
    }

    /**
     * @covers ::getServerConfiguration
     */
    public function testGetServerConfigurationWithMinimalData(): void
    {
        $minimalConfig = [
            'minimal' => [
                'name' => 'Minimal Server',
                'version' => '1.0.0',
                // No title field
            ],
        ];

        $registry = new ServerConfigurationRegistry($minimalConfig);
        $result = $registry->getServerConfiguration('minimal');

        $this->assertSame([
            'name' => 'Minimal Server',
            'version' => '1.0.0',
        ], $result);

        // Verify that title is not present in the result
        $this->assertArrayNotHasKey('title', $result);
    }
}
