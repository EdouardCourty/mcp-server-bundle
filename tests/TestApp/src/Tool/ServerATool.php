<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\TestApp\Tool;

use Ecourty\McpServerBundle\Attribute\AsTool;
use Ecourty\McpServerBundle\IO\TextToolResult;
use Ecourty\McpServerBundle\IO\ToolResult;
use Ecourty\McpServerBundle\TestApp\Model\SumNumbers;

#[AsTool(
    name: 'server_a_tool',
    description: 'Tool only available on server A',
    server: 'server_a',
)]
class ServerATool
{
    public function __invoke(SumNumbers $data): ToolResult
    {
        return new ToolResult(elements: [new TextToolResult(content: 'Server A Tool Result')]);
    }
}
