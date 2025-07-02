<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Command;

use Ecourty\McpServerBundle\Controller\EntrypointController;
use Ecourty\McpServerBundle\EventListener\ExceptionListener;
use Ecourty\McpServerBundle\HttpFoundation\JsonRpcRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'mcp:stdio',
    description: 'MCP server STDIO entrypoint',
)]
class EntrypointCommand extends Command
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EntrypointController $entrypointController,
        private readonly ExceptionListener $exceptionListener,
        private readonly KernelInterface $kernel,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('jsonrpc_request', InputArgument::REQUIRED, 'JSON-RPC request');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jsonRpcRequestString = (string) $input->getArgument('jsonrpc_request');
        if (empty($jsonRpcRequestString) === true) {
            throw new \UnexpectedValueException('Missing request argument');
        }

        $jsonRpcRequest = $this->serializer->deserialize($jsonRpcRequestString, JsonRpcRequest::class, 'json');
        $request = new Request(content: $jsonRpcRequestString);

        try {
            $response = $this->entrypointController->__invoke($jsonRpcRequest, $request);
        } catch (\Throwable $exception) {
            $this->requestStack->push($request);
            $exceptionEvent = new ExceptionEvent(
                kernel: $this->kernel,
                request: $request,
                requestType: HttpKernelInterface::MAIN_REQUEST,
                e: $exception,
            );
            $this->exceptionListener->onKernelException($exceptionEvent);

            $response = $exceptionEvent->getResponse();
        }

        if ($response === null) {
            throw new \RuntimeException('No response received');
        }

        $output->write((string) $response->getContent());

        return self::SUCCESS;
    }
}
