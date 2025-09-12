<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\TestApp\Prompt;

use Ecourty\McpServerBundle\Attribute\AsPrompt;
use Ecourty\McpServerBundle\Enum\PromptRole;
use Ecourty\McpServerBundle\IO\Prompt\Content\TextContent;
use Ecourty\McpServerBundle\IO\Prompt\PromptMessage;
use Ecourty\McpServerBundle\IO\Prompt\PromptResult;
use Ecourty\McpServerBundle\Prompt\Argument;
use Ecourty\McpServerBundle\Prompt\ArgumentCollection;

#[AsPrompt(
    name: 'server-a-prompt',
    description: 'Prompt only available on server A',
    server: 'server_a',
    arguments: [
        new Argument(name: 'input', description: 'The input text', allowUnsafe: true),
    ],
)]
class ServerAPrompt
{
    public function __invoke(ArgumentCollection $arguments): PromptResult
    {
        return new PromptResult(
            description: 'Server A prompt result',
            messages: [
                new PromptMessage(
                    role: PromptRole::ASSISTANT,
                    content: new TextContent('Response from Server A'),
                ),
            ],
        );
    }
}
