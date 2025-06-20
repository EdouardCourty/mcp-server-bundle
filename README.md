# MCP Server Bundle

[![PHP CI](https://github.com/EdouardCourty/mcp-server-bundle/actions/workflows/php_ci.yml/badge.svg)](https://github.com/EdouardCourty/mcp-server-bundle/actions/workflows/php_ci.yml)

A powerful Symfony bundle for handling MCP (Message Control Protocol) server implementations, providing tools for JSON-RPC request handling and tool management.    
_Read the [official MCP specification](https://modelcontextprotocol.io/docs/concepts/tools#overview)._

> [!WARNING]  
> The specification of the Model Context Protocol (MCP) changes frequently.
> This bundle will evolve along with the specification, so please ensure you are using the latest version of the bundle.  
> The CHANGELOG can be found [here](CHANGELOG.md).

## Table of Contents

- [Getting Started](#getting-started)
  - [Installation](#installation)
  - [Configuration](#configuration)
- [Tools](#tools)
  - [Creating Tools](#creating-tools)
  - [Tool Results](#tool-results)
  - [Tool Events](#tool-events)
  - [Input Schema Management](#input-schema-management)
  - [JSON-RPC Integration](#json-rpc-integration)
- [Prompts](#prompts)
  - [Creating Prompts](#creating-prompts)
  - [Prompt Results](#prompt-results)
  - [Prompt Events](#prompt-events)
  - [JSON-RPC Integration](#json-rpc-integration-1)
- [JSON-RPC Methods](#json-rpc-methods)
  - [Built-in Methods](#built-in-methods)
  - [Custom Methods](#custom-methods)
- [Developer Experience](#developer-experience)
- [Contributing](#contributing)
- [License](#license)

## Getting Started

The MCP Server Bundle provides a structured way to create and manage tools that can be used by clients via JSON-RPC requests.  
It includes features for MCP tool management, and JSON-RPC method handling.

This bundle is designed to be flexible and extensible, allowing developers to create custom tool handlers and method handlers as needed.  
MethodHandlers and ToolHandlers are registered and autowired using attributes, making it easy to define and manage your own tools.

### Installation

1. Install the MCP Server Bundle via Composer:
```bash
composer require ecourty/mcp-server-bundle
```

2. Add the bundle to your `config/bundles.php` (if not using Symfony Flex):
```php
return [
    // ...
    Ecourty\McpServerBundle\McpServerBundle::class => ['all' => true],
];
```

3. Configure the routes in `config/routes/mcp.yaml`:
```yaml
mcp_controller:
  path: /mcp
  controller: mcp_server.entrypoint_controller
```

### Configuration

You can customize the MCP Server Bundle configuration in `config/packages/mcp_server.yaml`:

```yaml
mcp_server:
  server:
    name: 'My MCP Server' # The name of your MCP server, used in the initialization response
    version: '1.0.0' # The version of your MCP server, used in the initialization response
```

## Tools

Tools are the core components of the MCP Server Bundle. They allow you to define and manage custom logic that can be triggered by clients.

### Creating Tools

1. Create a new class that will handle your tool logic
2. Use the `#[AsTool]` attribute to register your tool
3. Define the input schema for your tool using a class with validation constraints and OpenAPI attributes
4. Implement the `__invoke` method to handle the tool logic and return a `ToolResult`

_As Tool classes are services within the Symfony application, any dependency can be injected in it, using the constructor, like any other service._

Example:

- Tool input schema class:
```php
#[OA\Schema(required: ['emailAddress', 'username'])]
class CreateUserSchema
{
    #[Assert\Email]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 255)]
    #[OA\Property(description: 'The email address of the user', type: 'string', maxLength: 255, minLength: 5, nullable: false)]
    public string $emailAddress;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    #[OA\Property(description: 'The username of the user', type: 'string', maxLength: 50, minLength: 3, nullable: false)]
    public string $username;
}
```

- Tool class:
```php
use App\Schema\CreateUserSchema;
use Ecourty\McpServerBundle\Attribute\AsTool;
use Ecourty\McpServerBundle\Attribute\ToolAnnotations;
use Ecourty\McpServerBundle\IO\TextToolResult;
use Ecourty\McpServerBundle\IO\ToolResult;

#[AsTool(
    name: 'create_user', # Unique identifier for the tool, used by clients to call it
    description: 'Creates a new user in the system', # This description is used by LLMs to understand the tool's purpose
    annotations: new ToolAnnotations(
        title: 'Create a user', // A human-readable title for the tool, useful for documentation
        readOnlyHint: false, // Defines the request is not read-only (creates a user)
        destructiveHint: false, // Defines the request is not destructive (does not delete data)
        idempotentHint: false, // Defines the request cannot be repeated without changing the state
        openWorldHint: false, // The tool does not interact with external systems
    )
)]
class CreateUserTool
{
    public function __invoke(CreateUserSchema $createUserSchema): ToolResult
    {
        // Your logic here...
        return new ToolResult([new TextToolResult('User created successfully!')]);
    }
}
```

### Tool Results

The MCP specification states that tool results should consist of an array of objects.  
The bundle provides several result types that can be combined in a single `ToolResult` object:

- `TextToolResult`: For text-based results
- `ImageToolResult`: For image results
- `AudioToolResult`: For audio results
- `ResourceToolResult`: For file or resource results

All tool results must be wrapped in a `ToolResult` object, which can contain multiple results and handle error state.

Example:
```php
<?php

use App\Schema\ReadFileSchema;
use Ecourty\McpServerBundle\Attribute\AsTool;
use Ecourty\McpServerBundle\IO\TextToolResult;
use Ecourty\McpServerBundle\IO\ToolResult;

#[AsTool(name: 'read_file', description: 'Reads a file and returns its content')]
class MyTool
{
    public function __invoke(ReadFileSchema $payload): ToolResult
    {
        $fileContent = file_get_contents($payload->filePath);
        $anotherFileContent = file_get_contents($payload->anotherFilePath);

        // Create individual results
        $textResult = new TextToolResult($fileContent);
        $anotherTextResult = new TextToolResult($anotherFileContent);

        // Combine them in a ToolResult
        return new ToolResult([
            $textResult,
            $anotherTextResult,
        ]);
    }
}
```

Error handling example:
```php
use Ecourty\McpServerBundle\IO\TextToolResult;
use Ecourty\McpServerBundle\IO\ToolResult;

class MyTool
{
    public function __invoke(): ToolResult
    {
        try {
            // Your logic here...
            return new ToolResult([
                new TextToolResult('Success!')
            ]);
        } catch (\Exception $e) {
            return new ToolResult(
                [new TextToolResult($e->getMessage())],
                isError: true
            );
        }
    }
}
```

The `ToolResult` class provides the following features:
- Combine multiple results of different types
- Handle error state
- Automatic serialization to the correct format
- Type safety for all results

### Tool Events

The bundle provides several events that you can listen to:

- `ToolCallEvent`: Dispatched before a tool is called, contains the tool name and input data
- `ToolResultEvent`: Dispatched after a tool has been called, contains the result of the tool call
- `ToolCallExceptionEvent`: Dispatched when a tool throws an exception, contains the tool name, input data and throwable

Example of event listener:
```php
use Ecourty\McpServerBundle\Event\ToolCallEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ToolCallEvent::class)]
class ToolCallListener
{
    public function __invoke(ToolCallEvent $event): void
    {
        // Your logic here...
    }
}
```

### Input Schema Management

The bundle provides robust input validation and sanitization through schema-based deserialization.  
Input schemas are extracted from the `__invoke` method of classes with the `#[AsTool]` attribute, allowing you to define the expected input structure and validation rules.

1. Define your input schema class:

```php
<?php

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

class CreateUser
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    #[OA\Property(type: 'string', description: 'The name of the user', nullable: false)]
    private string $name;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[OA\Property(type: 'string', description: 'The email address of the user', nullable: false)]
    private string $email;

    // Getters and setters...
}
```

2. The bundle will automatically:
   - Understand the OA attributes for OpenAPI-based documentation in `tools/list`
   - Deserialize incoming JSON data into your schema class
   - Validate all constraints defined in your schema
   - Sanitize input data

This ensures that your tool handlers always receive properly validated and sanitized data.

### JSON-RPC Integration

- **`tools/list`**: Lists all available tools and their definitions.
- **`tools/call`**: Executes a tool by name, with the provided input data.

## Prompts

Prompts are reusable templates that can be dynamically generated and returned by the MCP server.  
They are useful for providing context, instructions, or any structured message to clients, and can accept arguments for dynamic content.

### Creating Prompts

1. **Define a prompt class**  
   - Use the `#[AsPrompt]` attribute to register your prompt.
   - The class should implement the `__invoke` method, which receives an `ArgumentCollection` and returns a `PromptResult`.
   - Arguments are defined using the `Argument` class (name, description, required, allowUnsafe), within the `#[AsPrompt]` declaration.

**Example:**
```php
<?php

namespace App\Prompt;

use Ecourty\McpServerBundle\Attribute\AsPrompt;
use Ecourty\McpServerBundle\Enum\PromptRole;
use Ecourty\McpServerBundle\IO\Prompt\Content\TextContent;
use Ecourty\McpServerBundle\Prompt\Argument;
use Ecourty\McpServerBundle\IO\Prompt\PromptResult;
use Ecourty\McpServerBundle\IO\Prompt\PromptMessage;
use Ecourty\McpServerBundle\Prompt\ArgumentCollection;

#[AsPrompt(
    name: 'code_review', // Unique identifier for the prompt,
    description: 'Ask for a code review on a provided piece of code', // Description of the prompt
    arguments: [
        new Argument(name: 'code', description: 'The code snippets to review', required: true, allowUnsafe: true), // Required argument
        new Argument(name: 'language', description: 'The name of the person to greet', required: false), // Optional argument
        new Argument(name: 'reviewer_level', description: 'The level of review (senior / intermediate / junior...)', required: false), // Optional argument
    ],
)]
class CodeReviewPrompt
{
    public function __invoke(ArgumentCollection $arguments): PromptResult
    {
        $code = $arguments->get('code');
        $language = $arguments->get('language');
        $reviewerLevel = $arguments->get('reviewer_level') ?: 'senior';

        $systemMessage = <<<PROMPT
You are a $reviewerLevel code reviewer.
You will be provided with a piece of code in $language.
Your task is to review the code and provide feedback on its quality, readability, and any potential issues.
PROMPT;

        $userMessage = <<<PROMPT
Review the following code snippet: $code
PROMPT;

        return new PromptResult(
            description: 'Code Review Prompt',
            messages: [
                new PromptMessage(
                    role: PromptRole::SYSTEM,
                    content: new TextContent($systemMessage),
                ),
                new PromptMessage(
                    role: PromptRole::USER,
                    content: new TextContent($userMessage),
                ),
            ]
        );
    }
}
```

### Prompt Results

A prompt must return an instance of `PromptResult`, which contains:
- A `description` (string)
- An array of `PromptMessage` objects (each with a role and content)

**Example:**
```php
return new PromptResult(
    description: 'Greeting',
    messages: [
        new PromptMessage(role: PromptRole::SYSTEM, content: new TextContent('You are a friendly assistant.')),
        new PromptMessage(role: PromptRole::USER, content: new TextContent('Hello, how are you?')),
    ]
);
```

### Prompt Events

The bundle provides several events for prompts:
- `PromptGetEvent`: Dispatched before a prompt is generated
- `PromptResultEvent`: Dispatched after a prompt is generated
- `PromptExceptionEvent`: Dispatched if an error occurs during prompt generation

**Example of event listener:**
```php
use Ecourty\McpServerBundle\Event\Prompt\PromptExceptionEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: PromptExceptionEvent::class)]
class PromptGetListener
{
    public function __invoke(PromptExceptionEvent $event): void
    {
        // Your logic here...
    }
}
```

### JSON-RPC Integration

- **`prompts/list`**: Lists all available prompts and their definitions.
- **`prompts/get`**: Retrieves and generates a prompt by name, with arguments.

## JSON-RPC Methods

The bundle provides a robust system for handling JSON-RPC requests.

### Built-in Methods

1. **`initialize`**
   - Called when a client first connects to the server
   - Returns server information and capabilities
   - Essential for client-server handshake

2. **`tools/list`**
   - Lists all available tools on the server
   - Returns tool metadata including names, descriptions, and input schemas
   - Used by clients to discover available tools

3. **`tools/call`**
   - Executes a specific tool
   - Handles input validation and tool execution
   - Returns the tool's result or error information

4. **`prompts/list`**
   - Lists all available prompts and their definitions
   - Returns prompt names, descriptions, and argument schemas
   - Useful for clients to discover available prompts

5. **`prompts/get`**
   - Retrieves a specific prompt by its name and generates it with the provided arguments
   - Validates and sanitizes arguments, then returns the generated prompt content
   - Returns an error if the prompt is not found or arguments are invalid

These methods are automatically registered and handled by the bundle. You don't need to implement them yourself.

### Custom Methods

You can create your own JSON-RPC method handlers for additional functionality:

1. Create a new class that implements the `MethodHandlerInterface`
2. Use the `#[AsMethodHandler]` attribute to register your handler

Example:

```php
<?php

use Ecourty\McpServerBundle\Attribute\AsMethodHandler;
use Ecourty\McpServerBundle\MethodHandler\MethodHandlerInterface;

#[AsMethodHandler(
    method: 'my_method',
)]
class MyMethodHandler implements MethodHandlerInterface
{
    public function handle(JsonRpcRequest $params): array
    {
        // Your request handling logic here
        // ...

        return ['result' => 'success'];
    }
}
```

#### Method Handler Attributes

The `#[AsMethodHandler]` attribute supports:

- `method` (string, required): The JSON-RPC method name

## Developer Experience

The bundle provides several tools to help you during development:

### Debug Command

1. The `debug:mcp-tools` command helps you inspect and debug your MCP tools:

```bash
# List all registered tools
php bin/console debug:mcp-tools

# Get detailed information about a specific tool
php bin/console debug:mcp-tools my_tool_name
```

This command is particularly useful for:
- Verifying tool registration
- Checking input schemas
- Validating tool annotations

2. The `debug:mcp-prompts` command helps you inspect and debug your MCP prompts:

```bash
# List all registered prompts
php bin/console debug:mcp-prompts

# Get detailed information about a specific prompt
php bin/console debug:mcp-prompts my_prompt_name
```

This command is particularly useful for:
- Verifying prompt registration
- Checking argument

## Contributing

Contributions to the MCP Server Bundle are welcome! Here's how you can help:

1. Fork the repository
2. Create a new branch for your feature
3. Make your changes
4. Submit a pull request

Please ensure your code follows our coding standards and includes appropriate tests.

### Development Setup

1. Fork and clone the repository
2. Install dependencies
```bash
composer install
```

3. Make your chages

4. Fix the code style and run PHPStan
```bash
composer fix-cs
composer phpstan
```

5. Run the tests
```bash
composer test
```

## License

This bundle is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
