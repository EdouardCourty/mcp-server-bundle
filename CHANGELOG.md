# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] - 2025-07-28

### Added

- Added support for STDIO transport through a Symfony Command : `mcp:stdio`
- Added a new CommandTestCase for handling tests of the Symfony command

### Updated

- Updated the test to be more reusable, by creating a trait that contains DataProviders and assertion logic

## [1.3.0] - 2025-07-27

### Updated

- MCP Server Bundle now supports tools and prompts without arguments.
- Updated tests fo fit the nex behavior

## [1.2.0] - 2025-06-26

### Added

- Added support for Resources
  - Support for `resources/list`, `resources/templates/list` and `resources/read` JSON-RPC methods
  - Added `AsResource` attribute to register resources
  - Added events for resource management (`ResourceReadEvent`, `ResourceReadResultEvent`)
  - Added tests to cover the resource functionality

### Updated

- Updated MCP Protocol to version `2025-06-18`
- Updated the configuration to include the `title` value, used in the initialization phase (JSON-RPC `initialize` method)
- Updated the `initialize` JSON-RPC method to return the `title` value from the configuration
- Updated the README to include the new resource functionality and examples
- Updated the configuration to use `scalarNode` instead of `stringNode` for Symfony 6.4 compatibility

## [1.1.0] - 2025-06-19

### Added

- Added support for Prompts
  - Support for `prompts/list` and `prompts/call` JSON-RPC methods
  - Added `AsPrompt` attribute to register prompts
  - Added tests to cover the prompt functionality

## [1.0.2] - 2025-06-18

### Updated
- Modified `composer.json` to allow for Symfony packages versions `^6.4 || ^7.0`

## [1.0.1] - 2025-06-17

### Added
- Added `ResponseFactory` which handles serializing responsed into JSON-RPC-compliant responses [Link to the issue](https://github.com/EdouardCourty/mcp-server-bundle/issues/2)

### Updated
- Updated `EntrypointController` to use the new `ResponseFormatter` class to format the JSON-RPC response
- Updated `ExceptionListener` to use `ResponseFormatter` to format JSON-RPC error responses

### Removed

- Removed `composer.lock` from the repository [Link to the issue](https://github.com/EdouardCourty/mcp-server-bundle/issues/3)

## [1.0.0] - 2025-06-13

### Added
- Initial stable release of the MCP Server Bundle
- Support for Model Context Protocol (MCP) tools implementation
- JSON-RPC method handlers for tool management
- Built-in `tools/list` and `tools/call` method handlers
- Tool registration system using PHP 8 attributes
- Input schema validation and sanitization
- OpenAPI integration for tool documentation
- README documentation
