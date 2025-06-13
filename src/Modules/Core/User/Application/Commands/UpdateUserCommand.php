<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Application\Commands;

use MvaBootstrap\Modules\Core\CQRS\Infrastructure\Commands\CommandInterface;

/**
 * Update User Command.
 *
 * Command to update existing user information.
 */
final readonly class UpdateUserCommand implements CommandInterface
{
    public function __construct(
        public string $commandId,
        public string $userId,
        public array $updateData,
        public string $updatedBy,
        public string $clientIp = '127.0.0.1'
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
            commandId: $data['command_id'] ?? uniqid('update_user_', true),
            userId: (string) ($data['user_id'] ?? ''),
            updateData: (array) ($data['update_data'] ?? []),
            updatedBy: (string) ($data['updated_by'] ?? ''),
            clientIp: (string) ($data['client_ip'] ?? '127.0.0.1')
        );
    }

    public function getCommandId(): string
    {
        return $this->commandId;
    }

    public function getCommandName(): string
    {
        return 'update_user';
    }

    /**
     * Get allowed fields for update.
     *
     * @return array<string>
     */
    public function getAllowedFields(): array
    {
        return ['name', 'email', 'role', 'status'];
    }

    /**
     * Get filtered update data with only allowed fields.
     *
     * @return array<string, mixed>
     */
    public function getFilteredUpdateData(): array
    {
        return array_intersect_key($this->updateData, array_flip($this->getAllowedFields()));
    }

    /**
     * Validate command data.
     *
     * @return array<string>
     */
    public function validate(): array
    {
        $errors = [];

        if (empty(trim($this->userId))) {
            $errors[] = 'User ID is required';
        }

        if (empty($this->updateData)) {
            $errors[] = 'Update data is required';
        }

        $filteredData = $this->getFilteredUpdateData();
        if (empty($filteredData)) {
            $errors[] = 'No valid fields to update';
        }

        // Validate email if provided
        if (isset($filteredData['email']) && !filter_var($filteredData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        // Validate role if provided
        if (isset($filteredData['role']) && !in_array($filteredData['role'], ['admin', 'editor', 'user'], true)) {
            $errors[] = 'Invalid user role';
        }

        // Validate status if provided
        if (isset($filteredData['status']) && !in_array($filteredData['status'], ['active', 'inactive', 'suspended'], true)) {
            $errors[] = 'Invalid user status';
        }

        return $errors;
    }

    public function toLogArray(): array
    {
        return [
            'command_id'    => $this->commandId,
            'command_name'  => $this->getCommandName(),
            'user_id'       => $this->userId,
            'update_fields' => array_keys($this->getFilteredUpdateData()),
            'updated_by'    => $this->updatedBy,
            'client_ip'     => $this->clientIp,
        ];
    }
}
