<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Session\Storage;

/**
 * Session storage interface for custom storage engines.
 */
interface StorageInterface
{
    /**
     * Start the session storage.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function start(): bool;

    /**
     * Check if storage is started.
     */
    public function isStarted(): bool;

    /**
     * Get session ID.
     */
    public function getId(): ?string;

    /**
     * Set session ID.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function setId(string $id): void;

    /**
     * Regenerate session ID.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function regenerateId(bool $deleteOldSession = true): bool;

    /**
     * Get session name.
     */
    public function getName(): string;

    /**
     * Set session name.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function setName(string $name): void;

    /**
     * Get value from storage.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set value in storage.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if key exists in storage.
     */
    public function has(string $key): bool;

    /**
     * Remove key from storage.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function remove(string $key): void;

    /**
     * Get all data from storage.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Clear all data from storage.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function clear(): void;

    /**
     * Destroy the session.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function destroy(): bool;

    /**
     * Get cookie parameters.
     *
     * @return array<string, mixed>
     */
    public function getCookieParams(): array;

    /**
     * Set cookie parameters.
     *
     * @param array<string, mixed> $params
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function setCookieParams(array $params): void;
}
