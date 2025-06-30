<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Session;

/**
 * Flash Message Interface.
 *
 * Provides temporary message storage across HTTP requests.
 */
interface FlashInterface
{
    /**
     * Add a flash message.
     */
    public function add(string $key, string $message): void;

    /**
     * Get flash messages for a key.
     *
     * @return array<string>
     */
    public function get(string $key): array;

    /**
     * Get all flash messages.
     *
     * @return array<string, array<string>>
     */
    public function all(): array;

    /**
     * Check if flash messages exist for a key.
     */
    public function has(string $key): bool;

    /**
     * Clear flash messages for a key.
     */
    public function clear(string $key): void;

    /**
     * Clear all flash messages.
     */
    public function clearAll(): void;

    /**
     * Get and clear flash messages for a key (consume).
     *
     * @return array<string>
     */
    public function consume(string $key): array;

    /**
     * Get and clear all flash messages (consume all).
     *
     * @return array<string, array<string>>
     */
    public function consumeAll(): array;
}
