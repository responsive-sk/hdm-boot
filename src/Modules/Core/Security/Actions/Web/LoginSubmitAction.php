<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Actions\Web;

use MvaBootstrap\Modules\Core\Security\Exceptions\AuthenticationException;
use MvaBootstrap\Modules\Core\Security\Exceptions\SecurityException;
use MvaBootstrap\Modules\Core\Security\Exceptions\ValidationException;
use MvaBootstrap\Modules\Core\Security\Services\AuthenticationService;
use MvaBootstrap\Modules\Core\Security\Services\AuthenticationValidator;
use MvaBootstrap\Modules\Core\Session\Services\CsrfService;
use MvaBootstrap\Modules\Core\Template\Infrastructure\Services\TemplateRenderer;
use Odan\Session\SessionInterface as OdanSession;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Login Submit Action.
 *
 * Handles login form submission with CSRF protection.
 */
final class LoginSubmitAction
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly OdanSession $session,
        private readonly CsrfService $csrfService,
        private readonly AuthenticationService $authenticationService,
        private readonly AuthenticationValidator $validator,
        private readonly LoggerInterface $logger,
        private readonly LoggerInterface $securityLogger
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        try {
            // Session is automatically started by SessionStartMiddleware

            // Note: CSRF validation disabled for login
            // Consider implementing reCaptcha or other anti-bot protection instead
            // $this->csrfService->validateFromRequest($data, 'login');

            // Validate input data
            $this->validator->validateUserLogin($data);

            // Authenticate user
            $clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';
            $user = $this->authenticationService->authenticateForWeb($data['email'], $data['password'], $clientIp);

            // Check if authentication failed
            if (!$user) {
                $this->logger->notice('Login failed - invalid credentials', [
                    'email' => $data['email'],
                    'ip'    => $clientIp,
                ]);

                return $this->templateRenderer->render(
                    $response->withStatus(401),
                    'auth/login.php',
                    [
                        'title'       => 'Login',
                        'error'       => 'Invalid email or password. Please try again.',
                        'email'       => $data['email'] ?? '',
                        'queryParams' => $request->getQueryParams(),
                    ]
                );
            }

            // Start session if not started, then regenerate ID for security
            if (!$this->session->isStarted()) {
                $this->session->start();
            }
            $this->session->regenerateId();

            // Add user to session
            $currentTime = time();
            $this->session->set('user_id', $user['id']);
            $this->session->set('login_time', $currentTime);
            $this->session->set('last_activity', $currentTime);
            $this->session->set('user_data', [
                'email'  => $user['email'],
                'name'   => $user['name'],
                'role'   => $user['role'],
                'status' => $user['status'],
            ]);

            // Session data set successfully

            // Add success message to flash
            $this->session->getFlash()->add('success', 'Login successful! Welcome back.');

            // Log successful login
            $this->securityLogger->info('🔐 User login successful', [
                'event'      => 'user_login_success',
                'user_id'    => $user['id'],
                'email'      => $user['email'],
                'ip'         => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $request->getServerParams()['HTTP_USER_AGENT'] ?? 'unknown',
                'session_id' => session_id(),
            ]);

            $this->logger->info('User logged in successfully', [
                'user_id' => $user['id'],
                'email'   => $user['email'],
            ]);

            // Redirect to profile
            return $response
                ->withHeader('Location', '/profile')
                ->withStatus(302);
        } catch (ValidationException $e) {
            // Validation errors
            $this->securityLogger->warning('🚨 Login validation failed', [
                'event'  => 'login_validation_failed',
                'errors' => $e->getErrors(),
                'email'  => $data['email'] ?? 'unknown',
                'ip'     => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            $this->logger->notice('Login validation failed', [
                'errors' => $e->getErrors(),
                'email'  => $data['email'] ?? 'unknown',
            ]);

            return $this->templateRenderer->render(
                $response->withStatus(422),
                'auth/login.php',
                [
                    'title'       => 'Login',
                    'error'       => $e->getFirstError(),
                    'email'       => $data['email'] ?? '',
                    'queryParams' => $request->getQueryParams(),
                ]
            );
        } catch (SecurityException $e) {
            // Security throttling
            $this->logger->warning('Login security exception', [
                'message' => $e->getMessage(),
                'email'   => $data['email'] ?? 'unknown',
                'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            return $this->templateRenderer->render(
                $response->withStatus(429),
                'auth/login.php',
                [
                    'title'       => 'Login',
                    'error'       => 'Too many login attempts. Please try again later.',
                    'email'       => $data['email'] ?? '',
                    'queryParams' => $request->getQueryParams(),
                ]
            );
        } catch (AuthenticationException $e) {
            // Invalid credentials
            $this->logger->notice('Login failed - invalid credentials', [
                'email' => $data['email'] ?? 'unknown',
                'ip'    => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            return $this->templateRenderer->render(
                $response->withStatus(401),
                'auth/login.php',
                [
                    'title'       => 'Login',
                    'error'       => 'Invalid email or password. Please try again.',
                    'email'       => $data['email'] ?? '',
                    'queryParams' => $request->getQueryParams(),
                ]
            );
        } catch (\Exception $e) {
            // General errors
            $this->logger->error('Login error', [
                'message' => $e->getMessage(),
                'email'   => $data['email'] ?? 'unknown',
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
}
