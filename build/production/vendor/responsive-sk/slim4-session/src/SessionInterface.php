<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Session;

/**
 * Session Interface
 *
 * Complete session management interface without external dependencies.
 */
/**
 * @extends \IteratorAggregate<string, mixed>
 */
interface SessionInterface extends \Countable, \IteratorAggregate
{
    /**
     * Set session value.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function set(string $key, mixed $value): void;

    /**
     * Get session value.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionKeyNotFoundException
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Remove session value.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function remove(string $key): void;

    /**
     * Check if session is started.
     */
    public function isStarted(): bool;

    /**
     * Start the session.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function start(): bool;

    /**
     * Destroy the session.
     *
     * @throws \ResponsiveSk\Slim4Session\Exceptions\SessionException
     */
    public function destroy(): bool;

    /**
     * Get session ID.
     */
    public function getId(): ?string;

    /**
     * Regenerate session ID.
     */
    public function regenerateId(): bool;

    /**
     * Get session name.
     */
    public function getName(): string;

    /**
     * Set session name.
     */
    public function setName(string $name): void;

    /**
     * Get session save path.
     */
    public function getSavePath(): string;

    /**
     * Set session save path.
     */
    public function setSavePath(string $path): void;

    /**
     * Get session cookie parameters.
     * 
     * @return array<string, mixed>
     */
    public function getCookieParams(): array;

    /**
     * Set session cookie parameters.
     * 
     * @param array<string, mixed> $params
     */
    public function setCookieParams(array $params): void;

    /**
     * Check if session has specific key.
     */
    public function has(string $key): bool;

    /**
     * Get all session data.
     * 
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Clear all session data.
     */
    public function clear(): void;

    /**
     * Get session status.
     * 
     * @return int PHP_SESSION_* constants
     */
    public function getStatus(): int;

    /**
     * Check if session is active.
     */
    public function isActive(): bool;

    /**
     * Flash data - set data that will be available only for next request.
     */
    public function flash(string $key, mixed $value): void;

    /**
     * Get flash messages interface.
     */
    public function getFlash(): FlashInterface;

    /**
     * Get specific flash message.
     */
    public function getFlashMessage(string $key, mixed $default = null): mixed;

    /**
     * Check if flash data exists.
     */
    public function hasFlash(string $key): bool;

    /**
     * Get all flash data.
     * 
     * @return array<string, mixed>
     */
    public function getFlashBag(): array;
}
