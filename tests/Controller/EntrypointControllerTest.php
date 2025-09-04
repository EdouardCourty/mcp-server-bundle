<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\Controller;

use Ecourty\McpServerBundle\Enum\McpErrorCode;
use Ecourty\McpServerBundle\Tests\Support\Trait\McpAssertTrait;
use Ecourty\McpServerBundle\Tests\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Ecourty\McpServerBundle\Controller\EntrypointController
 */
class EntrypointControllerTest extends WebTestCase
{
    use McpAssertTrait;

    /**
     * @covers ::entrypointAction
     */
    public function testIndex(): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
        );

        $responseContent = json_decode((string) $response->getContent(), true);

        $this->assertSame([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => McpErrorCode::PARSE_ERROR->value,
                'message' => McpErrorCode::PARSE_ERROR->getMessage(),
            ],
        ], $responseContent);
    }

    #[DataProvider('provideNonExistentMethodRequest')]
    public function testWithNonExistentMethod(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertNonExistentMethod((string) $response->getContent());
    }

    #[DataProvider('provideInitializeRequest')]
    public function testInitialize(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertInitializeResponse((string) $response->getContent());
    }

    /**
     * @covers ::entrypointAction
     * @covers \Ecourty\McpServerBundle\MethodHandler\ToolsListMethodHandler
     */
    #[DataProvider('provideToolsListRequest')]
    public function testToolList(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertToolsList((string) $response->getContent());
    }

    /**
     * @covers ::entrypointAction
     * @covers \Ecourty\McpServerBundle\MethodHandler\ToolsCallMethodHandler
     */
    #[DataProvider('provideNonExistingToolRequest')]
    public function testToolCallWithNonExistingTool(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertNonExistingToolResponse((string) $response->getContent());
    }

    /**
     * @covers ::__invoke
     * @covers \Ecourty\McpServerBundle\MethodHandler\ToolsCallMethodHandler
     */
    #[DataProvider('provideToolCallWithNoParametersRequest')]
    public function testToolCallWithNoParameters(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertToolCallWithNoParametersResponse((string) $response->getContent());
    }

    /**
     * @covers ::entrypointAction
     * @covers \Ecourty\McpServerBundle\MethodHandler\ToolsCallMethodHandler
     */
    #[DataProvider('provideToolCallWithInvalidParamsRequest')]
    public function testToolCallWithInvalidParams(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertToolCallWithInvalidParamsResponse((string) $response->getContent());
    }

    /**
     * @covers ::entrypointAction
     * @covers \Ecourty\McpServerBundle\MethodHandler\ToolsCallMethodHandler
     */
    #[DataProvider('provideTestToolCalls')]
    public function testToolCalls(string $method, array $params, mixed $expectedResponse): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => $method,
                'params' => $params,
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame($expectedResponse, $responseContent);
    }

    #[DataProvider('providePromptsListRequest')]
    public function testPromptList(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertPromptsList((string) $response->getContent());
    }

    #[DataProvider('providePromptGetRequest')]
    public function testPromptGet(array $request, string $changes, string $scope): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertPromptGet((string) $response->getContent(), $changes, $scope);
    }

    #[DataProvider('providePromptGetWithMissingArgument')]
    public function testPromptGetWithNonRequiredParameterLeftEmpty(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertPromptGetWithNonRequiredArgumentLeftEmpty((string) $response->getContent());
    }

    #[DataProvider('providePromptGetWithoutArgumentsRequest')]
    public function testPromptGetWithoutArguments(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertPromptGetWithoutArguments((string) $response->getContent());
    }

    #[DataProvider('providePromptWithUnsafeParametersRequest')]
    public function testPromptGetWithUnsafeParameterAllowed(array $request, string $unsafeContent): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertPromptWithUnsafeParameters((string) $response->getContent(), $unsafeContent);
    }

    #[DataProvider('provideDirectResourceListRequest')]
    public function testDirectResourceList(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertDirectResourcesList((string) $response->getContent());
    }

    #[DataProvider('provideTemplateResourceListRequest')]
    public function testTemplateResourceList(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertTemplateResourcesList((string) $response->getContent());
    }

    #[DataProvider('provideNotFoundResourceRequest')]
    public function testCallNotFoundResource(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertCallNotFoundResource((string) $response->getContent());
    }

    #[DataProvider('provideDirectResourceRequest')]
    public function testCallDirectResource(array $request): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $this->assertCallDirectResource((string) $response->getContent());
    }

    #[DataProvider('provideTestDataForTemplateResourceCall')]
    public function testCallTemplateResource(array $request, array $expectedResult): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: $request,
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame($expectedResult, $responseContent['result']);
    }
}
