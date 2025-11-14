<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\TestApp\Tool;

use Ecourty\McpServerBundle\Attribute\AsTool;
use Ecourty\McpServerBundle\IO\TextToolResult;
use Ecourty\McpServerBundle\IO\ToolResult;
use Ecourty\McpServerBundle\TestApp\Model\SumNumbers;

#[AsTool(
    name: 'server_b_tool',
    description: 'Tool only available on server B',
    server: 'server_b',
)]
class ServerBTool
{
    public function __invoke(SumNumbers $data): ToolResult
    {
        return new ToolResult(elements: [new TextToolResult(content: 'Server B Tool Result')]);
    }
}
