<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Domain\DTOs;

/**
 * Login Result Data Transfer Object.
 *
 * Represents the result of an authentication attempt.
 */
final readonly class LoginResult
{
    /**
     * @param array<string, mixed>|null $user
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public bool $success,
        public ?array $user = null,
        public ?string $token = null,
        public ?string $errorMessage = null,
        public ?string $errorCode = null,
        public array $metadata = []
    ) {
    }

    /**
     * Create successful login result.
     *
     * @param array<string, mixed> $user
     * @param array<string, mixed> $metadata
     */
    public static function success(array $user, ?string $token = null, array $metadata = []): self
    {
        return new self(
            success: true,
            user: $user,
            token: $token,
            metadata: $metadata
        );
    }

    /**
     * Create failed login result.
     *
     * @param array<string, mixed> $metadata
     */
    public static function failure(
        string $errorMessage,
        ?string $errorCode = null,
        array $metadata = []
    ): self {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            errorCode: $errorCode,
            metadata: $metadata
        );
    }

    /**
     * Check if login was successful.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if login failed.
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Get user data (only if successful).
     *
     * @return array<string, mixed>|null
     */
    public function getUser(): ?array
    {
        return $this->success ? $this->user : null;
    }

    /**
     * Get error message (only if failed).
     */
    public function getErrorMessage(): ?string
    {
        return $this->success ? null : $this->errorMessage;
    }

    /**
     * Get error code (only if failed).
     */
    public function getErrorCode(): ?string
    {
        return $this->success ? null : $this->errorCode;
    }

    /**
     * Convert to array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'success'  => $this->success,
            'metadata' => $this->metadata,
        ];

        if ($this->success) {
            $result['user'] = $this->user;
            if ($this->token) {
                $result['token'] = $this->token;
            }
        } else {
            $result['error'] = [
                'message' => $this->errorMessage,
                'code'    => $this->errorCode,
            ];
        }

        return $result;
    }

    /**
     * Convert to array for logging (without sensitive data).
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array
    {
        $result = [
            'success'  => $this->success,
            'metadata' => $this->metadata,
        ];

        if ($this->success && $this->user) {
            $result['user_id'] = $this->user['id'] ?? null;
            $result['user_email'] = $this->user['email'] ?? null;
            $result['user_role'] = $this->user['role'] ?? null;
            // Note: full user data and token excluded for security
        } else {
            $result['error_message'] = $this->errorMessage;
            $result['error_code'] = $this->errorCode;
        }

        return $result;
    }
}
