<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Mark\Actions\Web;

use HdmBoot\Modules\Core\Mark\Services\MarkAuthenticationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Mark Login Submit Action.
 *
 * Handles mark login form submission.
 * Uses mark.db for authentication.
 */
final class MarkLoginSubmitAction
{
    public function __construct(
        private readonly MarkAuthenticationService $markAuthService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            $data = [];
        }

        $email = isset($data['email']) && is_string($data['email']) ? $data['email'] : '';
        $password = isset($data['password']) && is_string($data['password']) ? $data['password'] : '';
        $redirect = isset($data['redirect']) && is_string($data['redirect']) ? $data['redirect'] : '/mark/dashboard';

        // Validate input
        if (empty($email) || empty($password)) {
            return $this->redirectWithError($response, 'Email and password are required.');
        }

        // Log mark login attempt
        $this->logger->info('🔴 MARK LOGIN ATTEMPT', [
            'email'      => $email,
            'ip'         => $this->getClientIp($request),
            'user_agent' => $request->getHeaderLine('User-Agent'),
        ]);

        try {
            // Authenticate mark user
            $markUser = $this->markAuthService->authenticate($email, $password);

            if ($markUser === null) {
                $this->logger->warning('🔴 MARK LOGIN FAILED', [
                    'email'  => $email,
                    'reason' => 'Invalid credentials',
                ]);

                return $this->redirectWithError($response, 'Invalid email or password. Please try again.');
            }

            // Start mark session
            $session = $request->getAttribute('session');
            if (is_array($session)) {
                $session['mark_user_id'] = $markUser['id'] ?? '';
                $session['mark_user_email'] = $markUser['email'] ?? '';
                $session['mark_user_role'] = $markUser['role'] ?? '';
                $session['mark_login_time'] = time();
            }

            $this->logger->info('🔴 MARK LOGIN SUCCESS', [
                'email'   => $email,
                'user_id' => $markUser['id'] ?? 'unknown',
                'role'    => $markUser['role'] ?? 'unknown',
            ]);

            // Redirect to mark dashboard
            return $response
                ->withHeader('Location', $redirect)
                ->withStatus(302);
        } catch (\Exception $e) {
            $this->logger->error('🔴 MARK LOGIN ERROR', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return $this->redirectWithError($response, 'Login failed. Please try again.');
        }
    }

    private function redirectWithError(ResponseInterface $response, string $error): ResponseInterface
    {
        return $response
            ->withHeader('Location', '/mark?error=' . urlencode($error))
            ->withStatus(302);
    }

    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        // Check for IP from various headers
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($serverParams[$header]) && is_string($serverParams[$header])) {
                $ip = trim(explode(',', $serverParams[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? 'unknown';

        return is_string($remoteAddr) ? $remoteAddr : 'unknown';
    }
}
