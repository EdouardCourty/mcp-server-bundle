<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\Service;

use Ecourty\McpServerBundle\Prompt\Argument;
use Ecourty\McpServerBundle\Service\PromptRegistry;
use Ecourty\McpServerBundle\TestApp\Prompt\GenerateGitCommitMessage;
use Ecourty\McpServerBundle\TestApp\Prompt\ServerAPrompt;
use Ecourty\McpServerBundle\TestApp\Prompt\ServerBPrompt;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PromptRegistryTest extends KernelTestCase
{
    private PromptRegistry $registry;

    protected function setUp(): void
    {
        /** @var PromptRegistry $promptRegistry */
        $promptRegistry = self::getContainer()->get(PromptRegistry::class);

        $this->registry = $promptRegistry;
    }

    /**
     * @param class-string $expectedClass
     */
    #[DataProvider('providePromptTestData')]
    public function testGetPrompt(string $name, string $expectedClass): void
    {
        $prompt = $this->registry->getPrompt($name);

        $this->assertInstanceOf($expectedClass, $prompt);
    }

    public static function providePromptTestData(): array
    {
        return [
            [
                'name' => 'generate-git-commit-message',
                'expectedClass' => GenerateGitCommitMessage::class,
            ],
            [
                'name' => 'server-a-prompt',
                'expectedClass' => ServerAPrompt::class,
            ],
            [
                'name' => 'server-b-prompt',
                'expectedClass' => ServerBPrompt::class,
            ],
        ];
    }

    #[DataProvider('providePromptDefinitionTestData')]
    public function testGetPromptDefinition(string $name, string $expectedDescription, array $expectedArguments): void
    {
        $promptDefinition = $this->registry->getPromptDefinition($name);

        $this->assertNotNull($promptDefinition);

        $this->assertSame($name, $promptDefinition->name);
        $this->assertSame($expectedDescription, $promptDefinition->description);
        $this->assertEquals($expectedArguments, $promptDefinition->arguments);
    }

    public static function providePromptDefinitionTestData(): array
    {
        return [
            [
                'name' => 'generate-git-commit-message',
                'expectedDescription' => 'Generate a git commit message based on the provided changes.',
                'expectedArguments' => [
                    new Argument('changes', 'The changed made in the codebase', true, true),
                    new Argument('scope', 'The scope of the changes, e.g., feature, bugfix, etc.', true),
                ],
            ],
            [
                'name' => 'server-a-prompt',
                'expectedDescription' => 'Prompt only available on server A',
                'expectedArguments' => [
                    new Argument('input', 'The input text', true, true),
                ],
            ],
            [
                'name' => 'server-b-prompt',
                'expectedDescription' => 'Prompt only available on server B',
                'expectedArguments' => [
                    new Argument('input', 'The input text', true, true),
                ],
            ],
        ];
    }

    /**
     * @covers ::getPromptsDefinitions
     */
    public function testGetPromptsDefinitionsWithoutServerFilter(): void
    {
        $definitions = $this->registry->getPromptsDefinitions();

        $this->assertCount(5, $definitions);

        $promptNames = array_map(fn ($def) => $def->name, $definitions);
        $this->assertContains('generate-git-commit-message', $promptNames);
        $this->assertContains('greeting', $promptNames);
        $this->assertContains('say_hello', $promptNames);
        $this->assertContains('server-a-prompt', $promptNames);
        $this->assertContains('server-b-prompt', $promptNames);
    }

    /**
     * @covers ::getPromptsDefinitions
     */
    #[DataProvider('provideServerFilterData')]
    public function testGetPromptsDefinitionsWithServerFilter(string $serverKey, array $expectedPromptNames): void
    {
        $definitions = $this->registry->getPromptsDefinitions($serverKey);

        $this->assertCount(\count($expectedPromptNames), $definitions);

        $actualPromptNames = array_map(fn ($def) => $def->name, $definitions);
        sort($actualPromptNames);
        sort($expectedPromptNames);

        $this->assertSame($expectedPromptNames, $actualPromptNames);
    }

    public static function provideServerFilterData(): array
    {
        return [
            'default server includes global prompts and default server prompts' => [
                'serverKey' => 'default',
                'expectedPromptNames' => ['generate-git-commit-message', 'greeting', 'say_hello'], // Global prompts (no serverKey specified)
            ],
            'server_a includes global prompts and server_a prompts' => [
                'serverKey' => 'server_a',
                'expectedPromptNames' => ['generate-git-commit-message', 'greeting', 'say_hello', 'server-a-prompt'],
            ],
            'server_b includes global prompts and server_b prompts' => [
                'serverKey' => 'server_b',
                'expectedPromptNames' => ['generate-git-commit-message', 'greeting', 'say_hello', 'server-b-prompt'],
            ],
            'non-existent server includes only global prompts' => [
                'serverKey' => 'non_existent',
                'expectedPromptNames' => ['generate-git-commit-message', 'greeting', 'say_hello'],
            ],
        ];
    }

    /**
     * @covers ::getPrompt
     */
    public function testGetPromptWithServerKey(): void
    {
        // Test getting a global prompt with any server key - should work
        $globalPrompt = $this->registry->getPrompt('generate-git-commit-message', 'server_a');
        $this->assertInstanceOf(GenerateGitCommitMessage::class, $globalPrompt);

        // Test getting a server-specific prompt with correct server key - should work
        $serverAPrompt = $this->registry->getPrompt('server-a-prompt', 'server_a');
        $this->assertInstanceOf(ServerAPrompt::class, $serverAPrompt);

        // Test getting a server-specific prompt with wrong server key - should return null
        $wrongServerPrompt = $this->registry->getPrompt('server-a-prompt', 'server_b');
        $this->assertNull($wrongServerPrompt);

        // Test getting a server-specific prompt with no server key - should work (backwards compatibility)
        $noServerPrompt = $this->registry->getPrompt('server-a-prompt');
        $this->assertInstanceOf(ServerAPrompt::class, $noServerPrompt);
    }

    /**
     * @covers ::getPromptServerKey
     */
    #[DataProvider('providePromptServerKeyData')]
    public function testGetPromptServerKey(string $promptName, ?string $expectedServerKey): void
    {
        $serverKey = $this->registry->getPromptServerKey($promptName);

        $this->assertSame($expectedServerKey, $serverKey);
    }

    public static function providePromptServerKeyData(): array
    {
        return [
            'global prompt has no server key (generate-git-commit-message)' => ['generate-git-commit-message', null],
            'global prompt has no server key (greeting)' => ['greeting', null],
            'global prompt has no server key (say_hello)' => ['say_hello', null],
            'server_a prompt has server_a key' => ['server-a-prompt', 'server_a'],
            'server_b prompt has server_b key' => ['server-b-prompt', 'server_b'],
            'non-existent prompt returns null' => ['non_existent_prompt', null],
        ];
    }
}
