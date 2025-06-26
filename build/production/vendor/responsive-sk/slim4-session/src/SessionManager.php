<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Session;

use ResponsiveSk\Slim4Session\Exceptions\SessionException;

/**
 * Session Manager
 * 
 * Complete session management implementation without external dependencies.
 */
final class SessionManager implements SessionInterface
{
    private FlashManager $flashManager;

    public function __construct()
    {
        $this->flashManager = new FlashManager();
    }

    // === Core Session Methods ===

    public function set(string $key, mixed $value): void
    {
        if (!$this->isStarted()) {
            $this->start();
        }
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isStarted()) {
            return $default;
        }
        return $_SESSION[$key] ?? $default;
    }

    public function remove(string $key): void
    {
        if ($this->isStarted()) {
            unset($_SESSION[$key]);
        }
    }

    public function has(string $key): bool
    {
        return $this->isStarted() && isset($_SESSION[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (!$this->isStarted()) {
            return [];
        }

        /** @var array<string, mixed> */
        return $_SESSION;
    }

    public function clear(): void
    {
        if ($this->isStarted()) {
            $_SESSION = [];
        }
    }

    // === Session Lifecycle Methods ===

    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function start(): bool
    {
        if ($this->isStarted()) {
            return true;
        }

        if (headers_sent($file, $line)) {
            throw SessionException::cannotStart("Headers already sent in {$file} on line {$line}");
        }

        $result = session_start();
        if (!$result) {
            throw SessionException::cannotStart('Failed to start session');
        }

        return $result;
    }

    public function destroy(): bool
    {
        if (!$this->isStarted()) {
            return true;
        }

        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            $sessionName = session_name();
            if ($sessionName !== false) {
                setcookie(
                    $sessionName,
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
        }

        $result = session_destroy();
        if (!$result) {
            throw SessionException::cannotDestroy('Session destroy failed');
        }

        return $result;
    }

    public function getId(): ?string
    {
        $id = session_id();
        return ($id !== false && $id !== '') ? $id : null;
    }

    public function regenerateId(): bool
    {
        return session_regenerate_id(true);
    }

    public function getName(): string
    {
        $name = session_name();
        return $name !== false ? $name : '';
    }

    public function setName(string $name): void
    {
        if ($this->isStarted()) {
            throw SessionException::alreadyStarted();
        }
        session_name($name);
    }

    public function getSavePath(): string
    {
        return session_save_path() ?: '';
    }

    public function setSavePath(string $path): void
    {
        session_save_path($path);
    }

    /**
     * @return array<string, mixed>
     */
    public function getCookieParams(): array
    {
        return session_get_cookie_params();
    }

    /**
     * @param array<string, mixed> $params
     */
    public function setCookieParams(array $params): void
    {
        $validParams = [];

        if (isset($params['lifetime']) && is_int($params['lifetime'])) {
            $validParams['lifetime'] = $params['lifetime'];
        }
        if (isset($params['path']) && is_string($params['path'])) {
            $validParams['path'] = $params['path'];
        }
        if (array_key_exists('domain', $params)) {
            $domainValue = $params['domain'];
            if (is_string($domainValue) || $domainValue === null) {
                $validParams['domain'] = $domainValue;
            }
        }
        if (isset($params['secure']) && is_bool($params['secure'])) {
            $validParams['secure'] = $params['secure'];
        }
        if (isset($params['httponly']) && is_bool($params['httponly'])) {
            $validParams['httponly'] = $params['httponly'];
        }
        if (isset($params['samesite']) && is_string($params['samesite'])) {
            $allowedSamesite = ['Lax', 'lax', 'None', 'none', 'Strict', 'strict'];
            if (in_array($params['samesite'], $allowedSamesite, true)) {
                $validParams['samesite'] = $params['samesite'];
            }
        }

        session_set_cookie_params($validParams);
    }

    public function getStatus(): int
    {
        return session_status();
    }

    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    // === Flash Message Methods ===

    public function flash(string $key, mixed $value): void
    {
        if (!$this->isStarted()) {
            $this->start();
        }
        
        if (is_string($value)) {
            $this->flashManager->add($key, $value);
        }
    }

    public function getFlash(): FlashInterface
    {
        return $this->flashManager;
    }

    public function getFlashMessage(string $key, mixed $default = null): mixed
    {
        $messages = $this->flashManager->get($key);
        return !empty($messages) ? $messages[0] : $default;
    }

    public function hasFlash(string $key): bool
    {
        return $this->flashManager->has($key);
    }

    /**
     * @return array<string, mixed>
     */
    public function getFlashBag(): array
    {
        return $this->flashManager->all();
    }

    // === Countable & IteratorAggregate ===

    public function count(): int
    {
        return count($this->all());
    }

    /**
     * @return \ArrayIterator<string, mixed>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->all());
    }
}
