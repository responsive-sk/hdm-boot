<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Domain\DTOs;

/**
 * Login Request Data Transfer Object.
 *
 * Represents the data required for user authentication.
 */
final readonly class LoginRequest
{
    public function __construct(
        public string $email,
        public string $password,
        public string $clientIp,
        public ?string $userAgent = null,
        public ?string $csrfToken = null,
        public bool $rememberMe = false
    ) {
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: is_string($data['email'] ?? null) ? $data['email'] : '',
            password: is_string($data['password'] ?? null) ? $data['password'] : '',
            clientIp: is_string($data['client_ip'] ?? null) ? $data['client_ip'] : '127.0.0.1',
            userAgent: is_string($data['user_agent'] ?? null) ? $data['user_agent'] : null,
            csrfToken: is_string($data['csrf_token'] ?? null) ? $data['csrf_token'] : null,
            rememberMe: is_bool($data['remember_me'] ?? null) ? $data['remember_me'] : false
        );
    }

    /**
     * Validate the login request data.
     *
     * @return array<string, string>
     */
    public function validate(): array
    {
        $errors = [];

        if (empty(trim($this->email))) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($this->password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($this->password) < 3) {
            $errors['password'] = 'Password is too short';
        }

        if (empty(trim($this->clientIp))) {
            $errors['client_ip'] = 'Client IP is required';
        }

        return $errors;
    }

    /**
     * Check if the request is valid.
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Convert to array for logging (without sensitive data).
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array
    {
        return [
            'email'       => $this->email,
            'client_ip'   => $this->clientIp,
            'user_agent'  => $this->userAgent,
            'remember_me' => $this->rememberMe,
            // Note: password and csrf_token are excluded for security
        ];
    }
}
