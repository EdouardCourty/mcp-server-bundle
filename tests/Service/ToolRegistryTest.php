<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\Service;

use Ecourty\McpServerBundle\Service\ToolRegistry;
use Ecourty\McpServerBundle\TestApp\Tool\CreateUserTool;
use Ecourty\McpServerBundle\TestApp\Tool\MultiplyNumbersTool;
use Ecourty\McpServerBundle\TestApp\Tool\ServerATool;
use Ecourty\McpServerBundle\TestApp\Tool\ServerBTool;
use Ecourty\McpServerBundle\TestApp\Tool\SumNumbersTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @coversDefaultClass \Ecourty\McpServerBundle\Service\ToolRegistry
 */
class ToolRegistryTest extends KernelTestCase
{
    private ToolRegistry $registry;

    protected function setUp(): void
    {
        /** @var ToolRegistry $toolRegistry */
        $toolRegistry = self::getContainer()->get(ToolRegistry::class);

        $this->registry = $toolRegistry;
    }

    /**
     * @covers ::getTool
     *
     * @param class-string $expectedToolHandlerClass
     */
    #[DataProvider('provideToolAndHandlerClass')]
    public function testGetTool(string $toolName, string $expectedToolHandlerClass): void
    {
        $tool = $this->registry->getTool($toolName);

        $this->assertInstanceOf($expectedToolHandlerClass, $tool, "Tool '$toolName' should be an instance of '$expectedToolHandlerClass'");
    }

    public static function provideToolAndHandlerClass(): array
    {
        return [
            ['sum_numbers', SumNumbersTool::class],
            ['multiply_numbers', MultiplyNumbersTool::class],
            ['create_user', CreateUserTool::class],
            ['server_a_tool', ServerATool::class],
            ['server_b_tool', ServerBTool::class],
        ];
    }

    #[DataProvider('provideToolAndDefinition')]
    public function testGetToolDefinition(string $toolName, string $expectedDescription, array $expectedSchema): void
    {
        $toolDefinition = $this->registry->getToolDefinition($toolName);

        $this->assertNotNull($toolDefinition);
        $this->assertSame($expectedDescription, $toolDefinition->description);
        $this->assertSame($expectedSchema, $toolDefinition->inputSchema);
    }

    public static function provideToolAndDefinition(): array
    {
        return [
            [
                'toolName' => 'sum_numbers',
                'expectedDescription' => 'Calculates the sum of two numbers',
                'expectedSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'number1' => ['description' => 'The first number to sum', 'type' => 'number', 'nullable' => false],
                        'number2' => ['description' => 'The second number to sum', 'type' => 'number', 'nullable' => false],
                    ],
                ],
            ],
            [
                'toolName' => 'multiply_numbers',
                'expectedDescription' => 'Calculates the product of two numbers',
                'expectedSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'number1' => ['description' => 'The first number to multiply', 'type' => 'number', 'nullable' => false],
                        'number2' => ['description' => 'The second number to multiply', 'type' => 'number', 'nullable' => false],
                    ],
                ],
            ],
            [
                'toolName' => 'create_user',
                'expectedDescription' => 'Creates a user based on the provided data',
                'expectedSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'emailAddress' => ['description' => 'The email address of the user', 'type' => 'string', 'maxLength' => 255, 'minLength' => 5, 'nullable' => false],
                        'username' => ['description' => 'The username of the user', 'type' => 'string', 'maxLength' => 50, 'minLength' => 3, 'nullable' => false],
                    ],
                    'required' => ['emailAddress', 'username'],
                ],
            ],
        ];
    }

    /**
     * @covers ::getToolsDefinitions
     */
    public function testGetToolsDefinitionsWithoutServerFilter(): void
    {
        $definitions = $this->registry->getToolsDefinitions();

        $this->assertCount(6, $definitions);

        $toolNames = array_map(fn ($def) => $def->name, $definitions);
        $this->assertContains('sum_numbers', $toolNames);
        $this->assertContains('multiply_numbers', $toolNames);
        $this->assertContains('create_user', $toolNames);
        $this->assertContains('date_time', $toolNames);
        $this->assertContains('server_a_tool', $toolNames);
        $this->assertContains('server_b_tool', $toolNames);
    }

    /**
     * @covers ::getToolsDefinitions
     */
    #[DataProvider('provideServerFilterData')]
    public function testGetToolsDefinitionsWithServerFilter(string $serverKey, array $expectedToolNames): void
    {
        $definitions = $this->registry->getToolsDefinitions($serverKey);

        $this->assertCount(\count($expectedToolNames), $definitions);

        $actualToolNames = array_map(fn ($def) => $def->name, $definitions);
        sort($actualToolNames);
        sort($expectedToolNames);

        $this->assertSame($expectedToolNames, $actualToolNames);
    }

    public static function provideServerFilterData(): array
    {
        return [
            'default server includes global tools and default server tools' => [
                'serverKey' => 'default',
                'expectedToolNames' => ['sum_numbers', 'multiply_numbers', 'create_user', 'date_time'], // Global tools (no serverKey specified)
            ],
            'server_a includes global tools and server_a tools' => [
                'serverKey' => 'server_a',
                'expectedToolNames' => ['sum_numbers', 'multiply_numbers', 'create_user', 'date_time', 'server_a_tool'],
            ],
            'server_b includes global tools and server_b tools' => [
                'serverKey' => 'server_b',
                'expectedToolNames' => ['sum_numbers', 'multiply_numbers', 'create_user', 'date_time', 'server_b_tool'],
            ],
            'non-existent server includes only global tools' => [
                'serverKey' => 'non_existent',
                'expectedToolNames' => ['sum_numbers', 'multiply_numbers', 'create_user', 'date_time'],
            ],
        ];
    }

    /**
     * @covers ::getToolServerKey
     */
    #[DataProvider('provideToolServerKeyData')]
    public function testGetToolServerKey(string $toolName, ?string $expectedServerKey): void
    {
        $serverKey = $this->registry->getToolServerKey($toolName);

        $this->assertSame($expectedServerKey, $serverKey);
    }

    public static function provideToolServerKeyData(): array
    {
        return [
            'global tool has no server key' => ['sum_numbers', null],
            'global tool has no server key (multiply)' => ['multiply_numbers', null],
            'global tool has no server key (create_user)' => ['create_user', null],
            'global tool has no server key (date_time)' => ['date_time', null],
            'server_a tool has server_a key' => ['server_a_tool', 'server_a'],
            'server_b tool has server_b key' => ['server_b_tool', 'server_b'],
            'non-existent tool returns null' => ['non_existent_tool', null],
        ];
    }
}
