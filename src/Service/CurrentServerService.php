<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to extract the current server key from the request context.
 *
 * This service centralizes the logic for retrieving the server key that is set
 * by the routing system when handling multi-server MCP requests.
 */
class CurrentServerService
{
    public function __construct(
        private readonly ?RequestStack $requestStack = null,
    ) {
    }

    /**
     * Get the current server key from the request attributes.
     *
     * Returns the server key if available in the current request context,
     * or null if no request is available or no server key is set.
     */
    public function getCurrentServerKey(): ?string
    {
        if ($this->requestStack === null) {
            return null;
        }

        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest === null) {
            return null;
        }

        $serverKey = $currentRequest->attributes->get('serverKey');

        return \is_string($serverKey) ? $serverKey : null;
    }
}
