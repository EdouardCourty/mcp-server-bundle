<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Tests\Controller;

use Ecourty\McpServerBundle\Enum\McpErrorCode;
use Ecourty\McpServerBundle\Enum\PromptRole;
use Ecourty\McpServerBundle\MethodHandler\InitializeMethodHandler;
use Ecourty\McpServerBundle\Tests\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Ecourty\McpServerBundle\Controller\EntrypointController
 */
class EntrypointControllerTest extends WebTestCase
{
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

    public function testInitialize(): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => [],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertArrayHasKey('result', $responseContent);
        $this->assertArrayNotHasKey('error', $responseContent);

        $resultContent = $responseContent['result'];

        $this->assertArrayHasKey('protocolVersion', $resultContent);
        $this->assertSame(InitializeMethodHandler::PROTOCOL_VERSION, $resultContent['protocolVersion']);

        $this->assertArrayHasKey('serverInfo', $resultContent);
        $serverInfo = $resultContent['serverInfo'];

        $this->assertArrayHasKey('name', $serverInfo);
        $this->assertSame('My Test MCP Server', $serverInfo['name']);

        $this->assertArrayHasKey('title', $serverInfo);
        $this->assertSame('My Test MCP Server Title', $serverInfo['title']);

        $this->assertArrayHasKey('version', $serverInfo);
        $this->assertSame('1.0.1', $serverInfo['version']);
    }

    /**
     * @covers ::entrypointAction
     * @covers \Ecourty\McpServerBundle\MethodHandler\ToolsListMethodHandler
     */
    public function testToolList(): void
    {
        $requestId = uniqid('request_id_');

        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => $requestId,
                'method' => 'tools/list',
                'params' => [],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame('2.0', $responseContent['jsonrpc']);
        $this->assertSame($requestId, $responseContent['id']);
        $this->assertArrayHasKey('result', $responseContent);

        $resultContent = $responseContent['result'];

        $this->assertArrayNotHasKey('isError', $resultContent);

        $this->assertArrayHasKey('tools', $resultContent);
        $this->assertCount(6, $resultContent['tools']);

        $tools = $resultContent['tools'];

        $this->assertSame('create_user', $tools[0]['name']);
        $this->assertSame('Creates a user based on the provided data', $tools[0]['description']);

        $this->assertSame('date_time', $tools[1]['name']);
        $this->assertSame('Retrieve the date and time of the server', $tools[1]['description']);

        $this->assertSame('multiply_numbers', $tools[2]['name']);
        $this->assertSame('Calculates the product of two numbers', $tools[2]['description']);

        $this->assertSame('server_a_tool', $tools[3]['name']);
        $this->assertSame('Tool only available on server A', $tools[3]['description']);

        $this->assertSame('server_b_tool', $tools[4]['name']);
        $this->assertSame('Tool only available on server B', $tools[4]['description']);

        $this->assertSame('sum_numbers', $tools[5]['name']);
        $this->assertSame('Calculates the sum of two numbers', $tools[5]['description']);

        $this->assertArrayHasKey('inputSchema', $tools[0]);
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'emailAddress' => [
                    'description' => 'The email address of the user',
                    'type' => 'string',
                    'maxLength' => 255,
                    'minLength' => 5,
                    'nullable' => false,
                ],
                'username' => [
                    'description' => 'The username of the user',
                    'type' => 'string',
                    'maxLength' => 50,
                    'minLength' => 3,
                    'nullable' => false,
                ],
            ],
            'required' => ['emailAddress', 'username'],
        ], $tools[0]['inputSchema']);
        $this->assertArrayHasKey('annotations', $tools[0]);
        $this->assertSame([
            'title' => 'Create User',
            'readOnlyHint' => false,
            'destructiveHint' => false,
            'idempotentHint' => false,
            'openWorldHint' => false,
        ], $tools[0]['annotations']);

        $this->assertArrayHasKey('inputSchema', $tools[1]);
        $this->assertSame([], $tools[1]['inputSchema']);

        $this->assertArrayHasKey('inputSchema', $tools[2]);
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'number1' => [
                    'description' => 'The first number to multiply',
                    'type' => 'number',
                    'nullable' => false,
                ],
                'number2' => [
                    'description' => 'The second number to multiply',
                    'type' => 'number',
                    'nullable' => false,
                ],
            ],
        ], $tools[2]['inputSchema']);

        $this->assertArrayHasKey('annotations', $tools[2]);
        $this->assertSame([
            'title' => 'Multiply Numbers',
            'readOnlyHint' => true,
            'destructiveHint' => false,
            'idempotentHint' => false,
            'openWorldHint' => false,
        ], $tools[2]['annotations']);

        $this->assertArrayHasKey('inputSchema', $tools[3]);
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'number1' => [
                    'description' => 'The first number to sum',
                    'type' => 'number',
                    'nullable' => false,
                ],
                'number2' => [
                    'description' => 'The second number to sum',
                    'type' => 'number',
                    'nullable' => false,
                ],
            ],
        ], $tools[3]['inputSchema']);

        $this->assertArrayHasKey('annotations', $tools[3]);
        $this->assertSame([
            'title' => '',
            'readOnlyHint' => false,
            'destructiveHint' => true,
            'idempotentHint' => false,
            'openWorldHint' => true,
        ], $tools[3]['annotations']);
    }

    /**
     * @covers ::entrypointAction
     * @covers \Ecourty\McpServerBundle\MethodHandler\ToolsCallMethodHandler
     */
    public function testToolCallWithNonExistingTool(): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/non_existing_tool',
                'params' => [],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame([
            'jsonrpc' => '2.0',
            'id' => 1,
            'error' => [
                'code' => McpErrorCode::TOOL_NOT_FOUND->value,
                'message' => McpErrorCode::TOOL_NOT_FOUND->getMessage(),
            ],
        ], $responseContent);
    }

    public function testToolCallWithNoParameters(): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'date_time',
                ],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertArrayHasKey('result', $responseContent);

        $result = $responseContent['result'];

        $this->assertArrayHasKey('content', $result);
        $content = $result['content'];

        $this->assertCount(1, $content);

        $content0 = $content[0];
        $this->assertSame('text', $content0['type']);
        $dateTime = $content0['text'];

        $this->assertEqualsWithDelta(new \DateTime(), new \DateTime($dateTime), 1);
    }

    /**
     * @covers ::entrypointAction
     * @covers \Ecourty\McpServerBundle\MethodHandler\ToolsCallMethodHandler
     */
    public function testToolCallWithInvalidParams(): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'sum_numbers',
                    'arguments' => ['a', 1],
                ],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame([
            'jsonrpc' => '2.0',
            'id' => 1,
            'error' => [
                'code' => McpErrorCode::INTERNAL_ERROR->value,
                'message' => McpErrorCode::INTERNAL_ERROR->getMessage(),
            ],
        ], $responseContent);
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

    public static function provideTestToolCalls(): \Generator
    {
        yield 'Sum two numbers' => [
            'method' => 'tools/call',
            'params' => [
                'name' => 'sum_numbers',
                'arguments' => [
                    'number1' => 5,
                    'number2' => 10,
                ],
            ],
            'expectedResponse' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => '15',
                        ],
                    ],
                ],
            ],
        ];

        yield 'Multiply two numbers' => [
            'method' => 'tools/call',
            'params' => [
                'name' => 'multiply_numbers',
                'arguments' => [
                    'number1' => 3,
                    'number2' => 4,
                ],
            ],
            'expectedResponse' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => '12',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testPromptList(): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'prompts/list',
                'params' => [],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame('2.0', $responseContent['jsonrpc']);
        $this->assertSame(1, $responseContent['id']);
        $this->assertArrayHasKey('result', $responseContent);

        $resultContent = $responseContent['result'];

        $this->assertArrayHasKey('prompts', $resultContent);
        $this->assertCount(5, $resultContent['prompts']);

        $prompts = $resultContent['prompts'];

        $this->assertSame('generate-git-commit-message', $prompts[0]['name']);
        $this->assertSame('Generate a git commit message based on the provided changes.', $prompts[0]['description']);
        $this->assertArrayHasKey('arguments', $prompts[0]);
        $this->assertCount(2, $prompts[0]['arguments']);

        $this->assertSame('changes', $prompts[0]['arguments'][0]['name']);
        $this->assertSame('The changed made in the codebase', $prompts[0]['arguments'][0]['description']);
        $this->assertSame('scope', $prompts[0]['arguments'][1]['name']);
        $this->assertSame('The scope of the changes, e.g., feature, bugfix, etc.', $prompts[0]['arguments'][1]['description']);

        $this->assertSame('greeting', $prompts[1]['name']);
        $this->assertArrayNotHasKey('description', $prompts[1]);
        $this->assertArrayHasKey('arguments', $prompts[1]);

        $this->assertCount(1, $prompts[1]['arguments']);
        $this->assertSame('name', $prompts[1]['arguments'][0]['name']);
        $this->assertSame('The name of the person to greet.', $prompts[1]['arguments'][0]['description']);
        $this->assertFalse($prompts[1]['arguments'][0]['required']);

        $this->assertSame('say_hello', $prompts[2]['name']);
        $this->assertSame('Says hello', $prompts[2]['description']);
        $this->assertArrayNotHasKey('arguments', $prompts[2]);

        $this->assertSame('server-a-prompt', $prompts[3]['name']);
        $this->assertSame('Prompt only available on server A', $prompts[3]['description']);
        $this->assertArrayHasKey('arguments', $prompts[3]);
        $this->assertCount(1, $prompts[3]['arguments']);

        $this->assertSame('server-b-prompt', $prompts[4]['name']);
        $this->assertSame('Prompt only available on server B', $prompts[4]['description']);
        $this->assertArrayHasKey('arguments', $prompts[4]);
        $this->assertCount(1, $prompts[4]['arguments']);
    }

    public function testPromptGet(): void
    {
        $changes = 'Fixed a bug in the user authentication flow';
        $scope = 'bugfix';

        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'prompts/get',
                'params' => [
                    'name' => 'generate-git-commit-message',
                    'arguments' => [
                        'changes' => $changes,
                        'scope' => $scope,
                    ],
                ],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame('2.0', $responseContent['jsonrpc']);
        $this->assertSame(1, $responseContent['id']);
        $this->assertArrayHasKey('result', $responseContent);

        $resultContent = $responseContent['result'];

        // Check if the result contains the expected prompt structure
        $this->assertSame('A concise git commit message prompt', $resultContent['description']);
        $this->assertArrayHasKey('messages', $resultContent);
        $this->assertCount(1, $resultContent['messages']);

        // Check the first message in the result
        $message = $resultContent['messages'][0];
        $this->assertSame(PromptRole::USER->value, $message['role']);
        $this->assertArrayHasKey('content', $message);
        $content = $message['content'];

        // Check the content type and text
        $this->assertSame('text', $content['type']);
        $this->assertArrayHasKey('text', $content);

        // Verify that the content text contains the changes and scope
        $this->assertStringContainsString($changes, $content['text']);
        $this->assertStringContainsString($scope, $content['text']);
    }

    public function testPromptGetWithoutArguments(): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'prompts/get',
                'params' => [
                    'name' => 'say_hello',
                ],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);

        $this->assertSame([
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'description' => 'Says hello',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => [
                            'type' => 'text',
                            'text' => 'Hello!',
                        ],
                    ],
                ],
            ],
        ], $responseContent);
    }

    public function testPromptGetWithNonRequiredParameterLeftEmpty(): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'prompts/get',
                'params' => [
                    'name' => 'greeting',
                ],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame('2.0', $responseContent['jsonrpc']);
        $this->assertSame(1, $responseContent['id']);
        $this->assertArrayHasKey('result', $responseContent);
        $this->assertArrayNotHasKey('error', $responseContent);
    }

    public function testPromptGetWithUnsafeParameterAllowed(): void
    {
        $unsafeContent = '<script>alert("This is unsafe!");</script>';

        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'prompts/get',
                'params' => [
                    'name' => 'generate-git-commit-message',
                    'arguments' => [
                        'changes' => $unsafeContent,
                        'scope' => 'feature',
                    ],
                ],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame('2.0', $responseContent['jsonrpc']);
        $this->assertSame(1, $responseContent['id']);
        $this->assertArrayHasKey('result', $responseContent);

        // Check if the unsafe content is present in the response
        $resultContent = $responseContent['result'];
        $this->assertStringContainsString($unsafeContent, $resultContent['messages'][0]['content']['text']);
    }

    public function testDirectResourceList(): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'resources/list',
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame([
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'resources' => [
                    [
                        'uri' => 'file://random',
                        'name' => 'random_file',
                        'title' => 'Get a random file',
                        'description' => 'This resource returns the content of a random file.',
                        'mimeType' => 'text/plain',
                    ],
                    [
                        'uri' => 'file://robots.txt',
                        'name' => 'robots_txt',
                        'title' => 'Get the Robots.txt file',
                        'description' => 'This resource returns the content of the robots.txt file.',
                        'mimeType' => 'text/plain',
                    ],
                ],
            ],
        ], $responseContent);
    }

    public function testTemplateResourceList(): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'resources/templates/list',
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame([
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'resourceTemplates' => [
                    [
                        'name' => 'order_data',
                        'title' => 'Get Order Data',
                        'description' => 'Gathers the data of an order by their ID.',
                        'mimeType' => 'application/json',
                        'uriTemplate' => 'database://order/{id}',
                    ],
                    [
                        'name' => 'user_data',
                        'title' => 'Get User Data',
                        'description' => 'Gathers the data of a user by their ID.',
                        'mimeType' => 'application/json',
                        'uriTemplate' => 'database://user/{id}',
                    ],
                ],
            ],
        ], $responseContent);
    }

    public function testCallNotFoundResource(): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'resources/read',
                'params' => [
                    'uri' => 'database://non_existing_resource/123',
                ],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame([
            'jsonrpc' => '2.0',
            'id' => 1,
            'error' => [
                'code' => McpErrorCode::INTERNAL_ERROR->value,
                'message' => McpErrorCode::INTERNAL_ERROR->getMessage(),
            ],
        ], $responseContent);
    }

    public function testCallDirectResource(): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'resources/read',
                'params' => [
                    'uri' => 'file://robots.txt',
                ],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame([
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'contents' => [
                    [
                        'uri' => 'file://robots.txt',
                        'name' => 'robots.txt',
                        'title' => 'The robots.txt file',
                        'mimeType' => 'text/plain',
                        'text' => 'Disallow: /',
                    ],
                ],
            ],
        ], $responseContent);
    }

    #[DataProvider('provideTestDataForTemplateResourceCall')]
    public function testCallTemplateResource(string $uri, array $expectedResult): void
    {
        $response = $this->request(
            method: Request::METHOD_POST,
            url: '/mcp',
            body: [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'resources/read',
                'params' => [
                    'uri' => $uri,
                ],
            ],
        );

        $responseContent = json_decode((string) $response->getContent(), true);
        $this->assertNotFalse($responseContent);

        $this->assertSame($expectedResult, $responseContent['result']);
    }

    public static function provideTestDataForTemplateResourceCall(): \Generator
    {
        yield [
            'uri' => 'database://order/1',
            'expectedResult' => [
                'contents' => [
                    [
                        'uri' => 'database://order/1',
                        'name' => 'order_1',
                        'title' => 'Order data',
                        'mimeType' => 'application/json',
                        'text' => '{"id":1,"reference":"C4CA4238A0B923820DCC509A6F75849B","status":"pending"}',
                    ],
                ],
            ],
        ];

        yield [
            'uri' => 'database://user/2',
            'expectedResult' => [
                'contents' => [
                    [
                        'uri' => 'database://user/2',
                        'name' => 'user_2',
                        'title' => 'User data',
                        'mimeType' => 'application/json',
                        'text' => '{"id":2,"name":"User 2","email":"user2@example.com"}',
                    ],
                ],
            ],
        ];

        yield [
            'uri' => 'database://user/999',
            'expectedResult' => [
                'contents' => [
                    [
                        'uri' => 'database://user/999',
                        'name' => 'user_999',
                        'title' => 'User data',
                        'mimeType' => 'application/json',
                        'text' => '{"id":999,"name":"User 999","email":"user999@example.com"}',
                    ],
                ],
            ],
        ];
    }
}
