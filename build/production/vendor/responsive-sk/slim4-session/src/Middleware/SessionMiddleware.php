<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Session\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ResponsiveSk\Slim4Session\SessionInterface;
use ResponsiveSk\Slim4Session\Exceptions\SessionException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * PSR-15 Session Middleware for Slim 4.
 */
final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SessionInterface $session,
        private readonly bool $autoStart = true,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Auto-start session if enabled
        if ($this->autoStart && !$this->session->isStarted()) {
            try {
                $this->session->start();
            } catch (SessionException $e) {
                // Log error but continue - session is optional
                $this->logger->warning('Session start failed in middleware', [
                    'error' => $e->getMessage(),
                    'request_uri' => $request->getUri()->getPath()
                ]);
            }
        }

        // Add session to request attributes
        $request = $request->withAttribute('session', $this->session);

        return $handler->handle($request);
    }
}
