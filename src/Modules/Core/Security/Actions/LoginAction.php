<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Actions;

use HdmBoot\Modules\Core\Security\Services\AuthenticationService;
use HdmBoot\Modules\Core\Security\Services\AuthenticationValidator;
use HdmBoot\Modules\Core\Security\Exceptions\AuthenticationException;
use HdmBoot\Modules\Core\Security\Exceptions\SecurityException;
use HdmBoot\Modules\Core\Security\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Login Action (API)
 *
 * Handles API login requests with JSON responses.
 */
final class LoginAction
{
    public function __construct(
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
            // Validate input data
            $this->validator->validateUserLogin($data);

            // Authenticate user with proper type casting
            $email = is_string($data['email'] ?? null) ? $data['email'] : '';
            $password = is_string($data['password'] ?? null) ? $data['password'] : '';
            $serverParams = $request->getServerParams();
            $clientIp = is_string($serverParams['REMOTE_ADDR'] ?? null)
                ? $serverParams['REMOTE_ADDR']
                : '127.0.0.1';

            $user = $this->authenticationService->authenticateForApi($email, $password, $clientIp);

            // Generate JWT token
            $token = $this->authenticationService->generateToken($user);

            // Log successful login
            $this->securityLogger->info('🔐 API login successful', [
                'event' => 'api_login_success',
                'user_id' => $user['id'],
                'email' => $user['email'],
                'ip' => $clientIp,
                'user_agent' => $request->getServerParams()['HTTP_USER_AGENT'] ?? 'unknown',
            ]);

            // Return success response with token
            $responseData = [
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'] ?? '',
                        'email' => $user['email'] ?? '',
                        'name' => $user['name'] ?? '',
                        'role' => $user['role'] ?? '',
                    ],
                ],
            ];

            $jsonResponse = json_encode($responseData);
            if ($jsonResponse === false) {
                throw new \RuntimeException('Failed to encode JSON response');
            }

            $response->getBody()->write($jsonResponse);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (ValidationException $e) {
            // Validation errors
            $this->securityLogger->warning('🚨 API login validation failed', [
                'event' => 'api_login_validation_failed',
                'errors' => $e->getErrors(),
                'email' => $data['email'] ?? 'unknown',
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->getErrors(),
            ];

            $jsonResponse = json_encode($errorData);
            if ($jsonResponse === false) {
                $jsonResponse = '{"success":false,"message":"Validation failed"}';
            }

            $response->getBody()->write($jsonResponse);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(422);
        } catch (SecurityException $e) {
            // Security throttling
            $errorData = [
                'success' => false,
                'message' => 'Too many login attempts. Please try again later.',
                'error_code' => 'RATE_LIMITED',
            ];

            $jsonResponse = json_encode($errorData);
            if ($jsonResponse === false) {
                $jsonResponse = '{"success":false,"message":"Rate limited"}';
            }

            $response->getBody()->write($jsonResponse);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(429);
        } catch (AuthenticationException $e) {
            // Invalid credentials
            $this->securityLogger->warning('🚨 API login failed - invalid credentials', [
                'event' => 'api_login_failed_invalid_credentials',
                'email' => $data['email'] ?? 'unknown',
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Invalid email or password',
                'error_code' => 'INVALID_CREDENTIALS',
            ];

            $jsonResponse = json_encode($errorData);
            if ($jsonResponse === false) {
                $jsonResponse = '{"success":false,"message":"Invalid credentials"}';
            }

            $response->getBody()->write($jsonResponse);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        } catch (\Exception $e) {
            // General errors - log to both loggers
            $this->logger->error('API login system error', [
                'message' => $e->getMessage(),
                'email' => $data['email'] ?? 'unknown',
            ]);

            $this->securityLogger->error('🚨 API login system error', [
                'event' => 'api_login_system_error',
                'message' => $e->getMessage(),
                'email' => $data['email'] ?? 'unknown',
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            $errorData = [
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error_code' => 'SYSTEM_ERROR',
            ];

            $jsonResponse = json_encode($errorData);
            if ($jsonResponse === false) {
                $jsonResponse = '{"success":false,"message":"System error"}';
            }

            $response->getBody()->write($jsonResponse);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
