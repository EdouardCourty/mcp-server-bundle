<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\Command;

use Ecourty\McpServerBundle\Tests\Support\CommandTestCase;
use Ecourty\McpServerBundle\Tests\Support\Trait\McpAssertTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \Ecourty\McpServerBundle\Command\EntrypointCommand
 */
class EntrypointCommandTest extends CommandTestCase
{
    use McpAssertTrait;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->commandTester = $this->getCommandTester('mcp:stdio');
    }

    #[DataProvider('provideNonExistentMethodRequest')]
    public function testExecuteWithNonExistentMethod(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertNonExistentMethod($output);
    }

    #[DataProvider('provideInitializeRequest')]
    public function testExecuteInitialize(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertInitializeResponse($output);
    }

    #[DataProvider('provideToolsListRequest')]
    public function testExecuteToolsList(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertToolsList($output);
    }

    #[DataProvider('provideNonExistingToolRequest')]
    public function testNonExistentTool(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertNonExistingToolResponse($output);
    }

    #[DataProvider('provideToolCallWithNoParametersRequest')]
    public function testToolCallWithNoParameters(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertToolCallWithNoParametersResponse($output);
    }

    #[DataProvider('provideToolCallWithInvalidParamsRequest')]
    public function testToolCallWithInvalidParams(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertToolCallWithInvalidParamsResponse($output);
    }

    #[DataProvider('provideTestToolCalls')]
    public function testToolCalls(string $method, array $params, mixed $expectedResponse): void
    {
        $request = \sprintf(
            '{"jsonrpc": "2.0", "id": 1, "method": "%s", "params": %s}',
            $method,
            json_encode($params, \JSON_THROW_ON_ERROR),
        );

        $this->commandTester->execute([
            'jsonrpc_request' => $request,
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);
        $responseContent = json_decode($output, true);

        $this->assertSame($expectedResponse, $responseContent);
    }

    #[DataProvider('providePromptsListRequest')]
    public function testPromptsList(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertPromptsList($output);
    }

    #[DataProvider('providePromptGetRequest')]
    public function testPromptGet(array $request, string $scope, string $changes): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertPromptGet($output, $changes, $scope);
    }

    #[DataProvider('providePromptGetWithMissingArgument')]
    public function testPromptGetWithNonRequiredParameterLeftEmpty(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertPromptGetWithNonRequiredArgumentLeftEmpty($output);
    }

    #[DataProvider('providePromptGetWithoutArgumentsRequest')]
    public function testPromptGetWithoutArguments(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertPromptGetWithoutArguments($output);
    }

    #[DataProvider('providePromptWithUnsafeParametersRequest')]
    public function testPromptWithUnsafeContent(array $request, string $unsafeContent): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertPromptWithUnsafeParameters($output, $unsafeContent);
    }

    #[DataProvider('provideDirectResourceListRequest')]
    public function testDirectResourceList(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertDirectResourcesList($output);
    }

    #[DataProvider('provideTemplateResourceListRequest')]
    public function testTemplateResourceList(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertTemplateResourcesList($output);
    }

    #[DataProvider('provideNotFoundResourceRequest')]
    public function testCallNotFoundResource(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertCallNotFoundResource($output);
    }

    #[DataProvider('provideDirectResourceRequest')]
    public function testCallDirectResource(array $request): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $this->assertCallDirectResource($output);
    }

    #[DataProvider('provideTestDataForTemplateResourceCall')]
    public function testCallTemplateResource(array $request, array $expectedResult): void
    {
        $this->commandTester->execute([
            'jsonrpc_request' => json_encode($request),
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $output = $this->commandTester->getDisplay(true);

        $parsedOutput = json_decode($output, true);
        $result = $parsedOutput['result'] ?? null;

        $this->assertNotNull($result);

        $this->assertEquals($result, $expectedResult);
    }
}
