# Cache Integration Guide

Komplexný sprievodca implementáciou cachingu v HDM Boot aplikácii.

## 🚀 Cache Overview

HDM Boot podporuje **multi-layer caching** s týmito vrstvami:

- **Application Cache** - Výsledky expensive operácií
- **Database Query Cache** - Cache pre databázové dotazy
- **Template Cache** - Kompilované templaty
- **HTTP Cache** - Response caching
- **Session Cache** - Session storage optimization

## 🏗️ Cache Architecture

```
Cache Layers:
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ HTTP Cache  │───▶│  App Cache  │───▶│ DB Cache    │
│ (Response)  │    │ (Business)  │    │ (Queries)   │
└─────────────┘    └─────────────┘    └─────────────┘
        │                  │                  │
        ▼                  ▼                  ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Browser   │    │    Redis    │    │   SQLite    │
│   Cache     │    │   Memory    │    │   File      │
└─────────────┘    └─────────────┘    └─────────────┘
```

## 🔧 Cache Configuration

### Cache Service Configuration

```php
<?php
// config/services/cache.php

return [
    'default' => $_ENV['CACHE_DRIVER'] ?? 'file',
    'prefix' => $_ENV['CACHE_PREFIX'] ?? 'hdm_boot',
    'ttl' => (int) ($_ENV['CACHE_TTL'] ?? 3600),
    
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => 'var/cache',
            'permissions' => 0755,
        ],
        
        'redis' => [
            'driver' => 'redis',
            'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
            'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'database' => (int) ($_ENV['REDIS_DATABASE'] ?? 0),
            'timeout' => 5.0,
            'persistent' => true,
        ],
        
        'memory' => [
            'driver' => 'memory',
            'max_size' => 100, // MB
        ],
        
        'database' => [
            'driver' => 'database',
            'table' => 'cache_entries',
            'connection' => 'system',
        ],
        
        'multi' => [
            'driver' => 'multi',
            'stores' => ['memory', 'file'],
            'strategy' => 'fallback', // fallback | replicate
        ],
    ],
    
    'tags' => [
        'enabled' => true,
        'separator' => ':',
    ],
    
    'serialization' => [
        'method' => 'serialize', // serialize | json | igbinary
        'compression' => false,
    ],
];
```

### Cache Manager

```php
<?php
// src/SharedKernel/Infrastructure/Cache/CacheManager.php

namespace HdmBoot\SharedKernel\Infrastructure\Cache;

use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;

final class CacheManager implements CacheInterface
{
    private array $stores = [];
    private array $config;
    private LoggerInterface $logger;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $prefixedKey = $this->prefixKey($key);
        
        try {
            $store = $this->getStore();
            $value = $store->get($prefixedKey, $default);
            
            if ($value !== $default) {
                $this->logger->debug('Cache hit', ['key' => $key]);
            } else {
                $this->logger->debug('Cache miss', ['key' => $key]);
            }
            
            return $value;
        } catch (\Throwable $e) {
            $this->logger->error('Cache get failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            return $default;
        }
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $prefixedKey = $this->prefixKey($key);
        $ttl = $ttl ?? $this->config['ttl'];
        
        try {
            $store = $this->getStore();
            $result = $store->set($prefixedKey, $value, $ttl);
            
            $this->logger->debug('Cache set', [
                'key' => $key,
                'ttl' => $ttl,
                'success' => $result,
            ]);
            
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Cache set failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    public function delete(string $key): bool
    {
        $prefixedKey = $this->prefixKey($key);
        
        try {
            $store = $this->getStore();
            $result = $store->delete($prefixedKey);
            
            $this->logger->debug('Cache delete', [
                'key' => $key,
                'success' => $result,
            ]);
            
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Cache delete failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    public function clear(): bool
    {
        try {
            $store = $this->getStore();
            $result = $store->clear();
            
            $this->logger->info('Cache cleared', ['success' => $result]);
            
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Cache clear failed', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        
        return $result;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $success = true;
        
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        
        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        
        return $success;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    // Extended functionality
    public function remember(string $key, callable $callback, null|int|\DateInterval $ttl = null): mixed
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    public function tags(array $tags): TaggedCache
    {
        return new TaggedCache($this, $tags);
    }

    public function increment(string $key, int $value = 1): int|false
    {
        $store = $this->getStore();
        
        if (method_exists($store, 'increment')) {
            return $store->increment($this->prefixKey($key), $value);
        }
        
        // Fallback implementation
        $current = (int) $this->get($key, 0);
        $new = $current + $value;
        
        return $this->set($key, $new) ? $new : false;
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->increment($key, -$value);
    }

    private function getStore(string $name = null): CacheInterface
    {
        $name = $name ?? $this->config['default'];
        
        if (!isset($this->stores[$name])) {
            $this->stores[$name] = $this->createStore($name);
        }
        
        return $this->stores[$name];
    }

    private function createStore(string $name): CacheInterface
    {
        if (!isset($this->config['stores'][$name])) {
            throw new \InvalidArgumentException("Cache store '{$name}' not configured");
        }
        
        $config = $this->config['stores'][$name];
        
        return match ($config['driver']) {
            'file' => new FileCache($config),
            'redis' => new RedisCache($config),
            'memory' => new MemoryCache($config),
            'database' => new DatabaseCache($config),
            'multi' => new MultiCache($config, $this),
            default => throw new \InvalidArgumentException("Unknown cache driver: {$config['driver']}")
        };
    }

    private function prefixKey(string $key): string
    {
        return $this->config['prefix'] . ':' . $key;
    }
}
```

## 💾 Cache Implementations

### File Cache

```php
<?php
// src/SharedKernel/Infrastructure/Cache/FileCache.php

namespace HdmBoot\SharedKernel\Infrastructure\Cache;

use Psr\SimpleCache\CacheInterface;

final class FileCache implements CacheInterface
{
    private string $path;
    private int $permissions;

    public function __construct(array $config)
    {
        $this->path = $config['path'];
        $this->permissions = $config['permissions'] ?? 0755;
        
        if (!is_dir($this->path)) {
            mkdir($this->path, $this->permissions, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $content = file_get_contents($file);
        $data = unserialize($content);
        
        // Check expiration
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            $this->delete($key);
            return $default;
        }
        
        return $data['value'];
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $file = $this->getFilePath($key);
        $dir = dirname($file);
        
        if (!is_dir($dir)) {
            mkdir($dir, $this->permissions, true);
        }
        
        $expires = $this->calculateExpiration($ttl);
        $data = [
            'value' => $value,
            'expires' => $expires,
            'created' => time(),
        ];
        
        $content = serialize($data);
        
        return file_put_contents($file, $content, LOCK_EX) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }

    public function clear(): bool
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                unlink($file->getPathname());
            } elseif ($file->isDir()) {
                rmdir($file->getPathname());
            }
        }
        
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        return $success;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    private function getFilePath(string $key): string
    {
        $hash = hash('sha256', $key);
        $dir = substr($hash, 0, 2);
        
        return $this->path . '/' . $dir . '/' . $hash . '.cache';
    }

    private function calculateExpiration(null|int|\DateInterval $ttl): int
    {
        if ($ttl === null) {
            return 0; // Never expires
        }
        
        if ($ttl instanceof \DateInterval) {
            return time() + $ttl->s + ($ttl->i * 60) + ($ttl->h * 3600) + ($ttl->d * 86400);
        }
        
        return time() + $ttl;
    }
}
```

### Redis Cache

```php
<?php
// src/SharedKernel/Infrastructure/Cache/RedisCache.php

namespace HdmBoot\SharedKernel\Infrastructure\Cache;

use Psr\SimpleCache\CacheInterface;
use Redis;

final class RedisCache implements CacheInterface
{
    private Redis $redis;

    public function __construct(array $config)
    {
        $this->redis = new Redis();
        
        if ($config['persistent'] ?? false) {
            $this->redis->pconnect(
                $config['host'],
                $config['port'],
                $config['timeout'] ?? 5.0
            );
        } else {
            $this->redis->connect(
                $config['host'],
                $config['port'],
                $config['timeout'] ?? 5.0
            );
        }
        
        if (!empty($config['password'])) {
            $this->redis->auth($config['password']);
        }
        
        $this->redis->select($config['database'] ?? 0);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);
        
        if ($value === false) {
            return $default;
        }
        
        return unserialize($value);
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $serialized = serialize($value);
        
        if ($ttl === null) {
            return $this->redis->set($key, $serialized);
        }
        
        if ($ttl instanceof \DateInterval) {
            $seconds = $ttl->s + ($ttl->i * 60) + ($ttl->h * 3600) + ($ttl->d * 86400);
        } else {
            $seconds = $ttl;
        }
        
        return $this->redis->setex($key, $seconds, $serialized);
    }

    public function delete(string $key): bool
    {
        return $this->redis->del($key) > 0;
    }

    public function clear(): bool
    {
        return $this->redis->flushDB();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keys = is_array($keys) ? $keys : iterator_to_array($keys);
        $values = $this->redis->mget($keys);
        
        $result = [];
        foreach ($keys as $i => $key) {
            $result[$key] = $values[$i] !== false ? unserialize($values[$i]) : $default;
        }
        
        return $result;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $keys = is_array($keys) ? $keys : iterator_to_array($keys);
        return $this->redis->del(...$keys) > 0;
    }

    public function has(string $key): bool
    {
        return $this->redis->exists($key) > 0;
    }

    public function increment(string $key, int $value = 1): int
    {
        return $this->redis->incrBy($key, $value);
    }

    public function decrement(string $key, int $value = 1): int
    {
        return $this->redis->decrBy($key, $value);
    }
}
```

## 🏷️ Tagged Cache

### Tagged Cache Implementation

```php
<?php
// src/SharedKernel/Infrastructure/Cache/TaggedCache.php

namespace HdmBoot\SharedKernel\Infrastructure\Cache;

use Psr\SimpleCache\CacheInterface;

final class TaggedCache
{
    private CacheInterface $cache;
    private array $tags;

    public function __construct(CacheInterface $cache, array $tags)
    {
        $this->cache = $cache;
        $this->tags = $tags;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $taggedKey = $this->getTaggedKey($key);
        return $this->cache->get($taggedKey, $default);
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $taggedKey = $this->getTaggedKey($key);
        
        // Store the key in tag sets
        foreach ($this->tags as $tag) {
            $this->addKeyToTag($tag, $key);
        }
        
        return $this->cache->set($taggedKey, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        $taggedKey = $this->getTaggedKey($key);
        
        // Remove key from tag sets
        foreach ($this->tags as $tag) {
            $this->removeKeyFromTag($tag, $key);
        }
        
        return $this->cache->delete($taggedKey);
    }

    public function flush(): bool
    {
        foreach ($this->tags as $tag) {
            $this->flushTag($tag);
        }
        
        return true;
    }

    private function getTaggedKey(string $key): string
    {
        $tagVersions = [];
        
        foreach ($this->tags as $tag) {
            $tagVersions[] = $this->getTagVersion($tag);
        }
        
        return implode(':', $this->tags) . ':' . implode(':', $tagVersions) . ':' . $key;
    }

    private function getTagVersion(string $tag): string
    {
        $versionKey = "tag_version:{$tag}";
        $version = $this->cache->get($versionKey);
        
        if ($version === null) {
            $version = uniqid();
            $this->cache->set($versionKey, $version);
        }
        
        return $version;
    }

    private function addKeyToTag(string $tag, string $key): void
    {
        $setKey = "tag_set:{$tag}";
        $keys = $this->cache->get($setKey, []);
        
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            $this->cache->set($setKey, $keys);
        }
    }

    private function removeKeyFromTag(string $tag, string $key): void
    {
        $setKey = "tag_set:{$tag}";
        $keys = $this->cache->get($setKey, []);
        
        $index = array_search($key, $keys);
        if ($index !== false) {
            unset($keys[$index]);
            $this->cache->set($setKey, array_values($keys));
        }
    }

    private function flushTag(string $tag): void
    {
        // Increment tag version to invalidate all tagged keys
        $versionKey = "tag_version:{$tag}";
        $this->cache->set($versionKey, uniqid());
        
        // Clear tag set
        $setKey = "tag_set:{$tag}";
        $this->cache->delete($setKey);
    }
}
```

## 🎯 Application Integration

### Cacheable Repository

```php
<?php
// src/Modules/Core/User/Infrastructure/Repository/CacheableUserRepository.php

namespace HdmBoot\Modules\Core\User\Infrastructure\Repository;

use HdmBoot\Modules\Core\User\Domain\Repository\UserRepositoryInterface;
use HdmBoot\Modules\Core\User\Domain\Entity\User;
use HdmBoot\SharedKernel\Infrastructure\Cache\CacheManager;
use Ramsey\Uuid\UuidInterface;

final class CacheableUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly CacheManager $cache
    ) {}

    public function findById(UuidInterface $id): ?User
    {
        $key = "user:id:{$id->toString()}";
        
        return $this->cache->remember($key, function () use ($id) {
            return $this->repository->findById($id);
        }, 3600);
    }

    public function findByEmail(string $email): ?User
    {
        $key = "user:email:" . hash('sha256', $email);
        
        return $this->cache->remember($key, function () use ($email) {
            return $this->repository->findByEmail($email);
        }, 3600);
    }

    public function save(User $user): void
    {
        $this->repository->save($user);
        
        // Invalidate cache
        $this->cache->tags(['users'])->flush();
        
        // Update specific caches
        $this->cache->set("user:id:{$user->getId()->toString()}", $user, 3600);
        $this->cache->set("user:email:" . hash('sha256', $user->getEmail()), $user, 3600);
    }

    public function delete(UuidInterface $id): void
    {
        $user = $this->findById($id);
        
        $this->repository->delete($id);
        
        if ($user) {
            // Clear specific caches
            $this->cache->delete("user:id:{$id->toString()}");
            $this->cache->delete("user:email:" . hash('sha256', $user->getEmail()));
        }
        
        // Invalidate tag
        $this->cache->tags(['users'])->flush();
    }

    public function findActive(): array
    {
        return $this->cache->remember('users:active', function () {
            return $this->repository->findActive();
        }, 1800);
    }
}
```

### Cache Middleware

```php
<?php
// src/SharedKernel/Presentation/Middleware/CacheMiddleware.php

namespace HdmBoot\SharedKernel\Presentation\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use HdmBoot\SharedKernel\Infrastructure\Cache\CacheManager;

final class CacheMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CacheManager $cache,
        private readonly int $ttl = 3600
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Only cache GET requests
        if ($request->getMethod() !== 'GET') {
            return $handler->handle($request);
        }

        $cacheKey = $this->generateCacheKey($request);
        
        // Try to get cached response
        $cachedResponse = $this->cache->get($cacheKey);
        
        if ($cachedResponse !== null) {
            return $this->createResponseFromCache($cachedResponse);
        }

        // Handle request and cache response
        $response = $handler->handle($request);
        
        if ($response->getStatusCode() === 200) {
            $this->cacheResponse($cacheKey, $response);
        }

        return $response;
    }

    private function generateCacheKey(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $query = $uri->getQuery();
        
        return 'http_cache:' . hash('sha256', $path . '?' . $query);
    }

    private function createResponseFromCache(array $cachedData): ResponseInterface
    {
        $response = new Response($cachedData['status']);
        
        foreach ($cachedData['headers'] as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        $response->getBody()->write($cachedData['body']);
        $response = $response->withHeader('X-Cache', 'HIT');
        
        return $response;
    }

    private function cacheResponse(string $key, ResponseInterface $response): void
    {
        $cachedData = [
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => (string) $response->getBody(),
            'cached_at' => time(),
        ];
        
        $this->cache->set($key, $cachedData, $this->ttl);
    }
}
```

## 📊 Cache Monitoring

### Cache Statistics

```php
<?php
// src/SharedKernel/Infrastructure/Cache/CacheStatistics.php

namespace HdmBoot\SharedKernel\Infrastructure\Cache;

final class CacheStatistics
{
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
    ];

    public function recordHit(): void
    {
        $this->stats['hits']++;
    }

    public function recordMiss(): void
    {
        $this->stats['misses']++;
    }

    public function recordSet(): void
    {
        $this->stats['sets']++;
    }

    public function recordDelete(): void
    {
        $this->stats['deletes']++;
    }

    public function getStats(): array
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? ($this->stats['hits'] / $total) * 100 : 0;

        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'sets' => $this->stats['sets'],
            'deletes' => $this->stats['deletes'],
            'hit_rate' => round($hitRate, 2),
            'total_requests' => $total,
        ];
    }

    public function reset(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
        ];
    }
}
```

## 📋 Cache Integration Checklist

### Setup:
- [ ] Cache drivers nakonfigurované
- [ ] Cache directories vytvorené s permissions
- [ ] Redis/Memory cache nastavené
- [ ] Cache middleware registrované

### Implementation:
- [ ] Repository caching implementované
- [ ] HTTP response caching nastavené
- [ ] Tagged cache pre invalidation
- [ ] Cache warming strategies

### Performance:
- [ ] Cache hit rates monitorované
- [ ] Cache size limits nastavené
- [ ] Expiration policies definované
- [ ] Cache cleanup jobs naplánované

### Monitoring:
- [ ] Cache statistics zbierané
- [ ] Performance metrics sledované
- [ ] Cache health checks implementované
- [ ] Alerting pre cache issues

## 🔗 Ďalšie zdroje

- [Performance Optimization](../PERFORMANCE.md)
- [Database Integration](database-integration.md)
- [Environment Configuration](environment-config.md)
- [Monitoring & Logging](../architecture/monitoring-logging.md)
