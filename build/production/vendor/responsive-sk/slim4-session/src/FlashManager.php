<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Session;

/**
 * Flash Message Manager.
 *
 * Manages temporary messages stored in session.
 */
final class FlashManager implements FlashInterface
{
    private const FLASH_KEY = '__flash_messages';

    public function add(string $key, string $message): void
    {
        if (!isset($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = [];
        }

        if (!is_array($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = [];
        }

        if (!isset($_SESSION[self::FLASH_KEY][$key])) {
            $_SESSION[self::FLASH_KEY][$key] = [];
        }

        if (!is_array($_SESSION[self::FLASH_KEY][$key])) {
            $_SESSION[self::FLASH_KEY][$key] = [];
        }

        $_SESSION[self::FLASH_KEY][$key][] = $message;
    }

    /**
     * @return array<string>
     */
    public function get(string $key): array
    {
        if (!isset($_SESSION[self::FLASH_KEY]) || !is_array($_SESSION[self::FLASH_KEY])) {
            return [];
        }

        $messages = $_SESSION[self::FLASH_KEY][$key] ?? [];
        if (!is_array($messages)) {
            return [];
        }

        // Ensure all elements are strings
        $result = [];
        foreach ($messages as $message) {
            if (is_string($message)) {
                $result[] = $message;
            }
        }
        return $result;
    }

    /**
     * @return array<string, array<string>>
     */
    public function all(): array
    {
        if (!isset($_SESSION[self::FLASH_KEY]) || !is_array($_SESSION[self::FLASH_KEY])) {
            return [];
        }

        $flashData = $_SESSION[self::FLASH_KEY];
        $result = [];

        foreach ($flashData as $key => $messages) {
            if (is_string($key) && is_array($messages)) {
                $stringMessages = [];
                foreach ($messages as $message) {
                    if (is_string($message)) {
                        $stringMessages[] = $message;
                    }
                }
                $result[$key] = $stringMessages;
            }
        }

        return $result;
    }

    public function has(string $key): bool
    {
        if (!isset($_SESSION[self::FLASH_KEY]) || !is_array($_SESSION[self::FLASH_KEY])) {
            return false;
        }

        return isset($_SESSION[self::FLASH_KEY][$key]) && !empty($_SESSION[self::FLASH_KEY][$key]);
    }

    public function clear(string $key): void
    {
        if (isset($_SESSION[self::FLASH_KEY]) && is_array($_SESSION[self::FLASH_KEY])) {
            unset($_SESSION[self::FLASH_KEY][$key]);
        }
    }

    public function clearAll(): void
    {
        $_SESSION[self::FLASH_KEY] = [];
    }

    /**
     * @return array<string>
     */
    public function consume(string $key): array
    {
        $messages = $this->get($key);
        $this->clear($key);
        return $messages;
    }

    /**
     * @return array<string, array<string>>
     */
    public function consumeAll(): array
    {
        $messages = $this->all();
        $this->clearAll();
        return $messages;
    }
}
