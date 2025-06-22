<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Application\Handlers;

use InvalidArgumentException;
use HdmBoot\SharedKernel\CQRS\Commands\CommandInterface;
use HdmBoot\SharedKernel\CQRS\Handlers\CommandHandlerInterface;
use HdmBoot\Modules\Core\User\Application\Commands\RegisterUserCommand;
use HdmBoot\Modules\Core\User\Domain\Events\UserWasRegistered;
use HdmBoot\Modules\Core\User\Repository\UserRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Register User Command Handler.
 *
 * Handles user registration commands with business logic validation,
 * data persistence, and event dispatching.
 */
final class RegisterUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(CommandInterface $command): void
    {
        if (!$command instanceof RegisterUserCommand) {
            throw new InvalidArgumentException(
                'Expected RegisterUserCommand, got ' . get_class($command)
            );
        }

        $this->logger->info('Handling register user command', $command->toLogArray());

        try {
            // Validate command
            $validationErrors = $command->validate();
            if (!empty($validationErrors)) {
                throw new InvalidArgumentException(
                    'Command validation failed: ' . implode(', ', $validationErrors)
                );
            }

            // Business rule: Check if email already exists
            if ($this->userRepository->emailExists($command->email)) {
                throw new InvalidArgumentException('Email address is already in use');
            }

            // Create user data
            $userData = [
                'email'      => $command->email,
                'name'       => $command->name,
                'password'   => password_hash($command->password, PASSWORD_DEFAULT),
                'role'       => $command->role,
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s'),
            ];

            // Persist user
            $savedUser = $this->userRepository->save($userData);

            $this->logger->info('User registered successfully', [
                'command_id' => $command->getCommandId(),
                'user_id'    => $savedUser['id'],
                'email'      => $savedUser['email'],
            ]);

            // Dispatch domain event
            $event = UserWasRegistered::fromUserData($savedUser, [
                'client_ip'  => $command->clientIp,
                'user_agent' => $command->userAgent,
            ]);

            $this->eventDispatcher->dispatch($event);

            $this->logger->info('UserWasRegistered event dispatched', [
                'event_id' => $event->getEventId(),
                'user_id'  => $savedUser['id'],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to register user', [
                'command_id'   => $command->getCommandId(),
                'error'        => $e->getMessage(),
                'command_data' => $command->toLogArray(),
            ]);

            throw $e;
        }
    }

    public function getSupportedCommandClass(): string
    {
        return RegisterUserCommand::class;
    }
}
