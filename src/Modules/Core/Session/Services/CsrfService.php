<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Session\Services;

use MvaBootstrap\Modules\Core\Session\Enum\SecurityType;
use MvaBootstrap\Modules\Core\Session\Exceptions\SecurityException;
use ResponsiveSk\Slim4Session\SessionInterface;

/**
 * CSRF Protection Service.
 *
 * Provides Cross-Site Request Forgery protection using secure tokens.
 */
final class CsrfService
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = 'csrf_tokens';
    private const MAX_TOKENS = 10; // Limit stored tokens

    public function __construct(
        private readonly SessionInterface $session
    ) {
    }

    /**
     * Generate new CSRF token.
     */
    public function generateToken(string $action = 'default'): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));

        // Start session if not started
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        // Store token in session
        $tokensFromSession = $this->session->get(self::SESSION_KEY, []);
        /** @var array<string, string> $tokens */
        $tokens = is_array($tokensFromSession) ? $tokensFromSession : [];
        $tokens[$action] = $token;

        // Limit number of stored tokens
        if (count($tokens) > self::MAX_TOKENS) {
            $tokens = array_slice($tokens, -self::MAX_TOKENS, null, true);
        }

        $this->session->set(self::SESSION_KEY, $tokens);

        return $token;
    }

    /**
     * Validate CSRF token.
     */
    public function validateToken(string $token, string $action = 'default'): bool
    {
        if (!$this->session->isStarted()) {
            return false;
        }

        $tokensFromSession = $this->session->get(self::SESSION_KEY, []);
        /** @var array<string, string> $tokens */
        $tokens = is_array($tokensFromSession) ? $tokensFromSession : [];

        if (!isset($tokens[$action])) {
            return false;
        }

        $storedToken = $tokens[$action];

        // Use hash_equals to prevent timing attacks
        $isValid = hash_equals($storedToken, $token);

        // Remove token after validation (one-time use)
        unset($tokens[$action]);
        $this->session->set(self::SESSION_KEY, $tokens);

        return $isValid;
    }

    /**
     * Get CSRF token for action.
     */
    public function getToken(string $action = 'default'): ?string
    {
        if (!$this->session->isStarted()) {
            return null;
        }

        $tokensFromSession = $this->session->get(self::SESSION_KEY, []);
        if (!is_array($tokensFromSession)) {
            return null;
        }

        /** @var array<string, string> $tokens */
        $tokens = $tokensFromSession;
        $token = $tokens[$action] ?? null;

        return is_string($token) ? $token : null;
    }

    /**
     * Generate HTML hidden input for CSRF token.
     */
    public function getHiddenInput(string $action = 'default'): string
    {
        $token = $this->generateToken($action);

        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate CSRF token from request data.
     *
     * @param array<string, mixed> $data
     */
    public function validateFromRequest(array $data, string $action = 'default'): void
    {
        $token = $data['csrf_token'] ?? '';

        if (!is_string($token) || !$this->validateToken($token, $action)) {
            throw new SecurityException(
                'CSRF token validation failed',
                SecurityType::CSRF_TOKEN_INVALID
            );
        }
    }

    /**
     * Clear all CSRF tokens.
     */
    public function clearTokens(): void
    {
        if ($this->session->isStarted()) {
            $this->session->remove(self::SESSION_KEY);
        }
    }
}
