<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Application\Actions;

use MvaBootstrap\Modules\Core\Security\Domain\DTOs\LoginRequest;
use MvaBootstrap\Modules\Core\Security\Domain\Services\AuthenticationDomainService;
use MvaBootstrap\Modules\Core\Session\Services\CsrfService;
use MvaBootstrap\Modules\Core\Template\Infrastructure\Services\TemplateRenderer;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Login Submit Action - HTTP Adapter.
 *
 * This action only handles HTTP concerns and delegates business logic to domain services.
 * No business logic should be in this class - only HTTP request/response transformation.
 */
final class LoginSubmitAction
{
    public function __construct(
        private readonly AuthenticationDomainService $authenticationDomainService,
        private readonly TemplateRenderer $templateRenderer,
        private readonly SessionInterface $session,
        private readonly CsrfService $csrfService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            // Extract and validate CSRF token
            $data = (array) $request->getParsedBody();
            $this->csrfService->validateFromRequest($data, 'login');

            // Create domain DTO from HTTP request
            $loginRequest = $this->createLoginRequestFromHttpRequest($request, $data);

            // Delegate to domain service (pure business logic)
            $loginResult = $this->authenticationDomainService->handleLogin($loginRequest);

            // Transform domain result to HTTP response
            if ($loginResult->isSuccess()) {
                return $this->handleSuccessfulLogin($loginResult, $request, $response);
            } else {
                return $this->handleFailedLogin($loginResult, $request, $response, $data);
            }
        } catch (\Exception $e) {
            $this->logger->error('Login action error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->templateRenderer->render(
                $response->withStatus(500),
                'auth/login.php',
                [
                    'title'       => 'Login',
                    'error'       => 'An unexpected error occurred. Please try again.',
                    'email'       => $data['email'] ?? '',
                    'queryParams' => $request->getQueryParams(),
                ]
            );
        }
    }

    /**
     * Create LoginRequest DTO from HTTP request data.
     *
     * @param array<string, mixed> $data
     */
    private function createLoginRequestFromHttpRequest(
        ServerRequestInterface $request,
        array $data
    ): LoginRequest {
        $serverParams = $request->getServerParams();

        return LoginRequest::fromArray([
            'email'       => $data['email'] ?? '',
            'password'    => $data['password'] ?? '',
            'client_ip'   => $serverParams['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent'  => $serverParams['HTTP_USER_AGENT'] ?? null,
            'csrf_token'  => $data['csrf_token'] ?? null,
            'remember_me' => isset($data['remember_me']),
        ]);
    }

    /**
     * Handle successful login - HTTP response transformation.
     */
    private function handleSuccessfulLogin(
        \MvaBootstrap\Modules\Core\Security\Domain\DTOs\LoginResult $loginResult,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $user = $loginResult->getUser();
        $metadata = $loginResult->metadata;

        // Start session if not started, then regenerate ID for security
        if (!$this->session->isStarted()) {
            $this->session->start();
        }
        $this->session->regenerateId();

        // Add user to session
        $this->session->set('user_id', $user['id']);
        $this->session->set('login_time', $metadata['login_time'] ?? time());
        $this->session->set('last_activity', time());
        $this->session->set('user_data', [
            'email'  => $user['email'],
            'name'   => $user['name'],
            'role'   => $user['role'],
            'status' => $user['status'],
        ]);

        // Determine redirect URL
        $queryParams = $request->getQueryParams();
        $redirectUrl = $queryParams['redirect'] ?? '/';

        // Create redirect response
        return $response
            ->withStatus(302)
            ->withHeader('Location', $redirectUrl);
    }

    /**
     * Handle failed login - HTTP response transformation.
     *
     * @param array<string, mixed> $requestData
     */
    private function handleFailedLogin(
        \MvaBootstrap\Modules\Core\Security\Domain\DTOs\LoginResult $loginResult,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $requestData
    ): ResponseInterface {
        $errorMessage = $loginResult->getErrorMessage() ?? 'Login failed';
        $errorCode = $loginResult->getErrorCode();

        // Determine HTTP status based on error type
        $httpStatus = match ($errorCode) {
            'VALIDATION_ERROR'    => 400,
            'INVALID_CREDENTIALS' => 401,
            'RATE_LIMITED'        => 429,
            default               => 401,
        };

        // Render login form with error
        return $this->templateRenderer->render(
            $response->withStatus($httpStatus),
            'auth/login.php',
            [
                'title'             => 'Login',
                'error'             => $errorMessage,
                'email'             => $requestData['email'] ?? '',
                'queryParams'       => $request->getQueryParams(),
                'validation_errors' => $loginResult->metadata['validation_errors'] ?? [],
            ]
        );
    }
}
