<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Session\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ResponsiveSk\Slim4Session\SessionInterface;

/**
 * Auto-refresh session on user activity.
 * 
 * Addresses the concern about missing auto-refresh functionality
 * by automatically extending session lifetime on user activity.
 */
final class SessionAutoRefreshMiddleware implements MiddlewareInterface
{
    private const LAST_ACTIVITY_KEY = '_session_last_activity';

    public function __construct(
        private readonly SessionInterface $session,
        private readonly int $refreshThreshold = 300, // 5 minutes
        private readonly int $extendBy = 3600 // 1 hour
    ) {
    }

    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if ($this->session->isStarted()) {
            $this->refreshSessionIfNeeded();
        }

        return $handler->handle($request);
    }

    private function refreshSessionIfNeeded(): void
    {
        $now = time();
        $lastActivity = $this->session->get(self::LAST_ACTIVITY_KEY, 0);
        
        // Check if refresh threshold is exceeded
        if (($now - $lastActivity) > $this->refreshThreshold) {
            // Regenerate session ID for security
            $this->session->regenerateId();
            
            // Update last activity timestamp
            $this->session->set(self::LAST_ACTIVITY_KEY, $now);
            
            // Extend cookie lifetime
            $this->extendSessionCookie();
        }
    }

    private function extendSessionCookie(): void
    {
        $params = $this->session->getCookieParams();
        $params['lifetime'] = time() + $this->extendBy;
        
        $this->session->setCookieParams($params);
        
        // Set the cookie with new expiration
        if ($this->session->getName() && $this->session->getId()) {
            setcookie(
                $this->session->getName(),
                $this->session->getId(),
                [
                    'expires' => $params['lifetime'],
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => $params['secure'] ?? false,
                    'httponly' => $params['httponly'] ?? true,
                    'samesite' => $params['samesite'] ?? 'Lax'
                ]
            );
        }
    }
}
