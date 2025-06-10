<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Actions;

use InvalidArgumentException;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Create User Action.
 *
 * Handles POST /api/users - create new user.
 */
final class CreateUserAction
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $rawData = $request->getParsedBody();

        // Ensure data is array or null
        $data = is_array($rawData) ? $rawData : null;

        // Validate required fields
        $validationErrors = $this->validateInput($data);
        if (!empty($validationErrors)) {
            if (!is_array($data)) {
                $data = [];
            }
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'details' => $validationErrors,
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/User/Actions/CreateUserAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(422);
        }

        try {
            // Ensure data is array at this point
            if (!is_array($data)) {
                throw new InvalidArgumentException('Invalid request data');
            }

            $user = $this->userService->createUser(
                email: (string) ($data['email'] ?? ''),
                name: (string) ($data['name'] ?? ''),
                password: (string) ($data['password'] ?? ''),
                role: is_string($data['role'] ?? null) ? $data['role'] : 'user'
            );

            $responseData = [
                'success' => true,
                'data'    => $user->toArray(),
                'message' => 'User created successfully',
            ];

            $response->getBody()->write(json_encode($responseData, JSON_PRETTY_PRINT) ?: "modules/Core/User/Actions/CreateUserAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (InvalidArgumentException $e) {
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/User/Actions/CreateUserAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(422);
        } catch (\Exception $e) {
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'INTERNAL_ERROR',
                    'message' => 'Failed to create user',
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/User/Actions/CreateUserAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * @param array<string, mixed>|null $data
     * @return array<string, array<string>>
     */
    private function validateInput(?array $data): array
    {
        $errors = [];

        if (empty($data)) {
            return ['general' => ['Request body is required']];
        }

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = ['Email is required'];
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['Invalid email format'];
        }

        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = ['Name is required'];
        } elseif (!is_string($data['name']) || strlen(trim($data['name'])) < 2) {
            $errors['name'] = ['Name must be at least 2 characters long'];
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = ['Password is required'];
        } elseif (!is_string($data['password'])) {
            $errors['password'] = ['Password must be a string'];
        } else {
            $passwordErrors = [];

            if (strlen($data['password']) < 8) {
                $passwordErrors[] = 'Password must be at least 8 characters long';
            }

            if (!preg_match('/[a-z]/', $data['password'])) {
                $passwordErrors[] = 'Password must contain at least one lowercase letter';
            }

            if (!preg_match('/[A-Z]/', $data['password'])) {
                $passwordErrors[] = 'Password must contain at least one uppercase letter';
            }

            if (!preg_match('/\d/', $data['password'])) {
                $passwordErrors[] = 'Password must contain at least one number';
            }

            if (!empty($passwordErrors)) {
                $errors['password'] = $passwordErrors;
            }
        }

        // Role validation
        if (!empty($data['role'])) {
            $allowedRoles = ['user', 'editor', 'admin'];
            if (!in_array($data['role'], $allowedRoles, true)) {
                $errors['role'] = ['Invalid role. Allowed roles: ' . implode(', ', $allowedRoles)];
            }
        }

        return $errors;
    }
}
