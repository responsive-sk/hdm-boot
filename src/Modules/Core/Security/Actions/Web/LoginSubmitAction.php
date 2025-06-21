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
use ResponsiveSk\Slim4Session\SessionInterface;
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
        private readonly SessionInterface $session,
        private readonly AuthenticationService $authenticationService,
        private readonly AuthenticationValidator $validator,
        private readonly LoggerInterface $logger,
        private readonly LoggerInterface $securityLogger
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        /** @var array<string, mixed> $data */
        $data = is_array($parsedBody) ? $parsedBody : [];

        try {
            // Session is automatically started by SessionStartMiddleware

            // Note: CSRF validation disabled for login
            // Consider implementing reCaptcha or other anti-bot protection instead

            // Validate input data
            $this->validator->validateUserLogin($data);

            // Authenticate user with safe type casting
            $serverParams = $request->getServerParams();
            $clientIp = is_string($serverParams['REMOTE_ADDR'] ?? null) ? $serverParams['REMOTE_ADDR'] : '127.0.0.1';
            $email = is_string($data['email'] ?? null) ? $data['email'] : '';
            $password = is_string($data['password'] ?? null) ? $data['password'] : '';
            $user = $this->authenticationService->authenticateForWeb($email, $password, $clientIp);

            // Check if authentication failed
            if (!$user) {
                // Log handled in AuthenticationException catch block
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

            $oldSessionId = $this->session->getId();
            $this->session->regenerateId();
            $newSessionId = $this->session->getId();

            $this->logger->debug('LoginSubmitAction: Session regeneration', [
                'old_session_id' => $oldSessionId,
                'new_session_id' => $newSessionId,
                'session_started' => $this->session->isStarted(),
            ]);

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

            $this->logger->debug('LoginSubmitAction: Session data set', [
                'session_id' => $this->session->getId(),
                'user_id' => $this->session->get('user_id'),
                'session_started' => $this->session->isStarted(),
            ]);

            // Session data set successfully

            // Add success message to flash
            $this->session->flash('success', 'Login successful! Welcome back.');

            // Log successful login (security event only)
            $this->securityLogger->info('🔐 User login successful', [
                'event'      => 'user_login_success',
                'user_id'    => $user['id'],
                'email'      => $user['email'],
                'ip'         => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $request->getServerParams()['HTTP_USER_AGENT'] ?? 'unknown',
                'session_id' => session_id(),
            ]);

            // Redirect to profile
            return $response
                ->withHeader('Location', '/profile')
                ->withStatus(302);
        } catch (ValidationException $e) {
            // Validation errors (security event only)
            $this->securityLogger->warning('🚨 Login validation failed', [
                'event'  => 'login_validation_failed',
                'errors' => $e->getErrors(),
                'email'  => $data['email'] ?? 'unknown',
                'ip'     => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
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
            // Security throttling (logged in AuthenticationService)
            // No additional logging needed here to avoid duplicates

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
            // Invalid credentials (security event)
            $this->securityLogger->warning('🚨 Login failed - invalid credentials', [
                'event' => 'login_failed_invalid_credentials',
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
            // General errors (security event)
            $this->securityLogger->error('🚨 Login system error', [
                'event'   => 'login_system_error',
                'message' => $e->getMessage(),
                'email'   => $data['email'] ?? 'unknown',
                'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
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
