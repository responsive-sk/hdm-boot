<?php

declare(strict_types=1);

namespace Odan\Session;

/**
 * PHPStan stub for Odan\Session\SessionInterface.
 * 
 * This file helps PHPStan understand the methods available in Odan\Session
 * without having to modify the actual library code.
 */
interface SessionInterface
{
    /**
     * Start the session.
     */
    public function start(): void;

    /**
     * Check if session is started.
     */
    public function isStarted(): bool;

    /**
     * Regenerate session ID.
     */
    public function regenerateId(): void;

    /**
     * Destroy the session.
     */
    public function destroy(): void;

    /**
     * Get session value.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set session value.
     * 
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if session key exists.
     */
    public function has(string $key): bool;

    /**
     * Remove session key.
     */
    public function remove(string $key): void;

    /**
     * Clear all session data.
     */
    public function clear(): void;

    /**
     * Get flash messages.
     */
    public function getFlash(): FlashInterface;

    /**
     * Get session ID.
     */
    public function getId(): string;

    /**
     * Set session ID.
     */
    public function setId(string $id): void;

    /**
     * Get session name.
     */
    public function getName(): string;

    /**
     * Set session name.
     */
    public function setName(string $name): void;

    /**
     * Get all session data.
     * 
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Replace session data.
     * 
     * @param array<string, mixed> $data
     */
    public function replace(array $data): void;

    /**
     * Count session items.
     */
    public function count(): int;
}

/**
 * Flash messages interface stub.
 */
interface FlashInterface
{
    /**
     * Add flash message.
     */
    public function add(string $type, string $message): void;

    /**
     * Get flash messages.
     * 
     * @return array<string, array<string>>
     */
    public function all(): array;

    /**
     * Get flash messages by type.
     * 
     * @return array<string>
     */
    public function get(string $type): array;

    /**
     * Check if flash messages exist.
     */
    public function has(string $type): bool;

    /**
     * Clear flash messages.
     */
    public function clear(): void;
}
