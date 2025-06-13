<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Session\Infrastructure\Middleware;

use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Session Start Middleware.
 *
 * Automatically starts sessions for all requests that need them.
 */
final class SessionStartMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SessionInterface $session,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Process the request and start session if needed.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Start session if not already started
        if (!$this->session->isStarted()) {
            $this->session->start();
            
            $this->logger->debug('Session started by SessionStartMiddleware', [
                'session_id' => $this->session->getId(),
                'request_uri' => $request->getUri()->getPath(),
                'request_method' => $request->getMethod(),
            ]);
        }

        // Process the request
        $response = $handler->handle($request);

        // Log session info after request processing
        $this->logger->debug('Session info after request processing', [
            'session_id' => $this->session->getId(),
            'session_started' => $this->session->isStarted(),
        ]);

        return $response;
    }
}
