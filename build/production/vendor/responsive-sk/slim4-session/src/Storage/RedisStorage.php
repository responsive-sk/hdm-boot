<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Session\Storage;

use ResponsiveSk\Slim4Session\Exceptions\SessionException;

/**
 * Redis session storage engine.
 */
final class RedisStorage implements StorageInterface
{
    private bool $started = false;
    private string $sessionId = '';
    private string $sessionName = 'PHPSESSID';
    private array $data = [];
    private array $cookieParams = [];

    public function __construct(
        private readonly \Redis $redis,
        private readonly int $ttl = 3600,
        private readonly string $prefix = 'session:'
    ) {
        $this->cookieParams = session_get_cookie_params();
    }

    public function start(): bool
    {
        if ($this->started) {
            throw SessionException::alreadyStarted();
        }

        // Generate or get session ID
        if (empty($this->sessionId)) {
            $this->sessionId = $this->generateSessionId();
        }

        // Load data from Redis
        $key = $this->prefix . $this->sessionId;
        $serializedData = $this->redis->get($key);
        
        if ($serializedData !== false) {
            $this->data = unserialize($serializedData) ?: [];
        } else {
            $this->data = [];
        }

        $this->started = true;
        return true;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function getId(): ?string
    {
        return $this->sessionId ?: null;
    }

    public function setId(string $id): void
    {
        if ($this->started) {
            throw SessionException::alreadyStarted();
        }
        $this->sessionId = $id;
    }

    public function regenerateId(bool $deleteOldSession = true): bool
    {
        if (!$this->started) {
            throw SessionException::notStarted();
        }

        $oldId = $this->sessionId;
        $this->sessionId = $this->generateSessionId();

        // Save current data to new ID
        $this->save();

        // Delete old session if requested
        if ($deleteOldSession && $oldId) {
            $this->redis->del($this->prefix . $oldId);
        }

        return true;
    }

    public function getName(): string
    {
        return $this->sessionName;
    }

    public function setName(string $name): void
    {
        if ($this->started) {
            throw SessionException::alreadyStarted();
        }
        $this->sessionName = $name;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        if (!$this->started) {
            throw SessionException::notStarted();
        }
        
        $this->data[$key] = $value;
        $this->save();
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function remove(string $key): void
    {
        if (!$this->started) {
            throw SessionException::notStarted();
        }
        
        unset($this->data[$key]);
        $this->save();
    }

    public function all(): array
    {
        return $this->data;
    }

    public function clear(): void
    {
        if (!$this->started) {
            throw SessionException::notStarted();
        }
        
        $this->data = [];
        $this->save();
    }

    public function destroy(): bool
    {
        if (!$this->started) {
            return true;
        }

        $key = $this->prefix . $this->sessionId;
        $this->redis->del($key);
        
        $this->data = [];
        $this->started = false;
        $this->sessionId = '';
        
        return true;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function setCookieParams(array $params): void
    {
        $this->cookieParams = array_merge($this->cookieParams, $params);
    }

    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function save(): void
    {
        if (!$this->started) {
            return;
        }

        $key = $this->prefix . $this->sessionId;
        $serializedData = serialize($this->data);
        
        $this->redis->setex($key, $this->ttl, $serializedData);
    }

    public function __destruct()
    {
        if ($this->started) {
            $this->save();
        }
    }
}
