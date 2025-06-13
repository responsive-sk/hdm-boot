<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Application\Commands;

use MvaBootstrap\Modules\Core\CQRS\Infrastructure\Commands\CommandInterface;

/**
 * Register User Command.
 *
 * Command to register a new user in the system.
 */
final readonly class RegisterUserCommand implements CommandInterface
{
    public function __construct(
        public string $commandId,
        public string $email,
        public string $name,
        public string $password,
        public string $role = 'user',
        public string $clientIp = '127.0.0.1',
        public ?string $userAgent = null
    ) {
    }

    /**
     * Create command from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            commandId: $data['command_id'] ?? uniqid('register_user_', true),
            email: (string) ($data['email'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            password: (string) ($data['password'] ?? ''),
            role: (string) ($data['role'] ?? 'user'),
            clientIp: (string) ($data['client_ip'] ?? '127.0.0.1'),
            userAgent: isset($data['user_agent']) ? (string) $data['user_agent'] : null
        );
    }

    public function getCommandId(): string
    {
        return $this->commandId;
    }

    public function getCommandName(): string
    {
        return 'register_user';
    }

    /**
     * Validate command data.
     *
     * @return array<string>
     */
    public function validate(): array
    {
        $errors = [];

        if (empty(trim($this->email))) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty(trim($this->name))) {
            $errors[] = 'Name is required';
        }

        if (strlen($this->password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (!in_array($this->role, ['admin', 'editor', 'user'], true)) {
            $errors[] = 'Invalid user role';
        }

        return $errors;
    }

    public function toLogArray(): array
    {
        return [
            'command_id'   => $this->commandId,
            'command_name' => $this->getCommandName(),
            'email'        => $this->email,
            'name'         => $this->name,
            'role'         => $this->role,
            'client_ip'    => $this->clientIp,
            'user_agent'   => $this->userAgent,
            // Note: password excluded for security
        ];
    }
}
