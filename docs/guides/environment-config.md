# Environment Configuration Guide

Detailný sprievodca konfiguráciou prostredí v HDM Boot aplikácii.

## 🌍 Prehľad konfigurácie

HDM Boot používa **environment-based configuration** s týmito vrstvami:

1. **Default Configuration** - Základné nastavenia v `config/`
2. **Environment Variables** - Špecifické pre prostredie v `.env`
3. **Runtime Configuration** - Dynamické nastavenia
4. **Module Configuration** - Konfigurácia modulov

## 📁 Konfiguračná štruktúra

```
config/
├── container.php           # DI Container konfigurácia
├── paths.php              # Paths service konfigurácia
├── language.php           # Jazykové nastavenia
├── routes.php             # Routing konfigurácia
├── services/              # Service konfigurácie
│   ├── database.php
│   ├── cache.php
│   ├── logging.php
│   └── security.php
└── routes/                # Route definície
    ├── api.php
    ├── web.php
    └── admin.php

.env.example               # Template pre environment variables
.env.dev                   # Development environment
.env.staging              # Staging environment
.env.production           # Production environment
```

## 🔧 Environment Variables

### Core Application Settings

```bash
# Application Identity
APP_NAME="HDM Boot"                    # Názov aplikácie
APP_ENV=production                     # Environment: development|staging|production
APP_DEBUG=false                        # Debug mode: true|false
APP_TIMEZONE=Europe/Bratislava         # Časová zóna
APP_URL=https://your-domain.com        # Base URL aplikácie
APP_VERSION=1.0.0                      # Verzia aplikácie

# Locale & Language
DEFAULT_LOCALE=sk_SK                   # Predvolený jazyk
SUPPORTED_LOCALES=sk_SK,cs_CZ,en_US    # Podporované jazyky
FALLBACK_LOCALE=en_US                  # Fallback jazyk
```

### Database Configuration

```bash
# Primary Database
DATABASE_URL="sqlite:var/storage/app.db"
DATABASE_CHARSET=utf8mb4
DATABASE_COLLATION=utf8mb4_unicode_ci

# Mark Admin Database
MARK_DATABASE_URL="sqlite:var/storage/mark.db"

# System Database
SYSTEM_DATABASE_URL="sqlite:var/storage/system.db"

# Database Pool Settings
DB_POOL_MIN=1                          # Minimálny počet spojení
DB_POOL_MAX=10                         # Maximálny počet spojení
DB_TIMEOUT=30                          # Timeout v sekundách
```

### Security Configuration

```bash
# JWT Settings
JWT_SECRET="your-256-bit-secret-key"   # JWT signing key (min 32 chars)
JWT_EXPIRY=1800                        # Token lifetime v sekundách
JWT_ISSUER="HDM Boot"                  # JWT issuer
JWT_AUDIENCE="hdm-boot-users"          # JWT audience

# Encryption
SECURITY_KEY="your-encryption-key"     # Encryption key (min 32 chars)
HASH_ALGORITHM=argon2id                # Password hashing: argon2id|bcrypt

# CSRF Protection
CSRF_TOKEN_NAME=csrf_token             # CSRF token field name
CSRF_TOKEN_LIFETIME=3600               # CSRF token lifetime
```

### Session Configuration

```bash
# Session Settings
SESSION_NAME="hdm_boot_session"        # Session cookie name
SESSION_LIFETIME=1800                  # Session lifetime v sekundách
SESSION_SECURE=true                    # HTTPS only: true|false
SESSION_HTTPONLY=true                  # HTTP only: true|false
SESSION_SAMESITE=strict                # SameSite: strict|lax|none

# Session Storage
SESSION_HANDLER=file                   # Handler: file|database|redis
SESSION_PATH=var/sessions              # Path pre file handler
```

### Caching Configuration

```bash
# Cache Settings
CACHE_ENABLED=true                     # Enable caching: true|false
CACHE_DRIVER=file                      # Driver: file|redis|memory
CACHE_TTL=3600                         # Default TTL v sekundách
CACHE_PREFIX=hdm_boot                  # Cache key prefix

# File Cache
CACHE_PATH=var/cache                   # Path pre file cache

# Redis Cache (ak používate)
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=your-redis-password
REDIS_DATABASE=0
```

### Logging Configuration

```bash
# Logging Settings
LOG_LEVEL=info                         # Level: debug|info|warning|error|critical
LOG_CHANNEL=file                       # Channel: file|syslog|stderr
LOG_PATH=var/logs                      # Log directory
LOG_ROTATION=daily                     # Rotation: daily|weekly|monthly

# Log Formats
LOG_FORMAT=json                        # Format: json|line
LOG_INCLUDE_CONTEXT=true               # Include context: true|false

# Error Logging
ERROR_LOG_ENABLED=true                 # Enable error logging
ERROR_LOG_LEVEL=error                  # Error log level
```

### Module Configuration

```bash
# Enabled Modules
ENABLED_MODULES="Blog,CMS,Analytics"   # Comma-separated list

# Module Paths
MODULES_PATH=src/Modules               # Modules directory
CUSTOM_MODULES_PATH=src/Modules/Custom # Custom modules directory

# Module Auto-discovery
MODULE_AUTO_DISCOVERY=true             # Auto-discover modules: true|false
MODULE_CACHE_ENABLED=true              # Cache module configs: true|false
```

### Performance & Optimization

```bash
# Performance Settings
OPCACHE_ENABLED=true                   # PHP OPcache: true|false
PRELOAD_ENABLED=false                  # PHP preloading: true|false
PRELOAD_FILE=config/preload.php        # Preload script

# Rate Limiting
RATE_LIMITING_ENABLED=true             # Enable rate limiting: true|false
RATE_LIMIT_REQUESTS=60                 # Requests per window
RATE_LIMIT_WINDOW=60                   # Window v sekundách

# Asset Optimization
ASSET_MINIFICATION=true                # Minify assets: true|false
ASSET_COMPRESSION=true                 # Compress assets: true|false
ASSET_VERSION=1.0.0                    # Asset version for cache busting
```

## 🏗️ Configuration Loading

### Configuration Service

```php
<?php
// src/SharedKernel/Configuration/ConfigurationService.php

namespace HdmBoot\SharedKernel\Configuration;

final class ConfigurationService
{
    private array $config = [];
    private string $environment;

    public function __construct(string $environment = 'production')
    {
        $this->environment = $environment;
        $this->loadConfiguration();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getNestedValue($this->config, $key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->setNestedValue($this->config, $key, $value);
    }

    public function has(string $key): bool
    {
        return $this->getNestedValue($this->config, $key) !== null;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    public function isDevelopment(): bool
    {
        return $this->environment === 'development';
    }

    public function isStaging(): bool
    {
        return $this->environment === 'staging';
    }

    private function loadConfiguration(): void
    {
        // 1. Load default configuration
        $this->loadDefaultConfig();

        // 2. Load environment-specific configuration
        $this->loadEnvironmentConfig();

        // 3. Load module configurations
        $this->loadModuleConfigs();

        // 4. Apply environment variable overrides
        $this->applyEnvironmentOverrides();
    }

    private function loadDefaultConfig(): void
    {
        $configFiles = [
            'app' => require __DIR__ . '/../../config/app.php',
            'database' => require __DIR__ . '/../../config/database.php',
            'cache' => require __DIR__ . '/../../config/cache.php',
            'logging' => require __DIR__ . '/../../config/logging.php',
            'security' => require __DIR__ . '/../../config/security.php',
        ];

        $this->config = array_merge($this->config, $configFiles);
    }

    private function loadEnvironmentConfig(): void
    {
        $envConfigFile = __DIR__ . "/../../config/environments/{$this->environment}.php";
        
        if (file_exists($envConfigFile)) {
            $envConfig = require $envConfigFile;
            $this->config = array_merge_recursive($this->config, $envConfig);
        }
    }

    private function loadModuleConfigs(): void
    {
        $modulesPath = __DIR__ . '/../../src/Modules';
        $moduleConfigs = [];

        foreach (glob($modulesPath . '/*/config.php') as $configFile) {
            $moduleName = basename(dirname($configFile));
            $moduleConfig = require $configFile;
            
            if (isset($moduleConfig['config'])) {
                $moduleConfigs['modules'][$moduleName] = $moduleConfig['config'];
            }
        }

        $this->config = array_merge_recursive($this->config, $moduleConfigs);
    }

    private function applyEnvironmentOverrides(): void
    {
        $overrides = [
            'app.debug' => $_ENV['APP_DEBUG'] ?? null,
            'app.timezone' => $_ENV['APP_TIMEZONE'] ?? null,
            'database.url' => $_ENV['DATABASE_URL'] ?? null,
            'cache.enabled' => $_ENV['CACHE_ENABLED'] ?? null,
            'logging.level' => $_ENV['LOG_LEVEL'] ?? null,
        ];

        foreach ($overrides as $key => $value) {
            if ($value !== null) {
                $this->set($key, $this->castValue($value));
            }
        }
    }

    private function getNestedValue(array $array, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    private function setNestedValue(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    private function castValue(string $value): mixed
    {
        // Cast string values to appropriate types
        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => is_numeric($value) ? (int) $value : $value
        };
    }
}
```

### Environment-specific Configs

```php
<?php
// config/environments/development.php

return [
    'app' => [
        'debug' => true,
        'log_level' => 'debug',
    ],
    
    'cache' => [
        'enabled' => false,
        'ttl' => 60, // Shorter TTL for development
    ],
    
    'security' => [
        'rate_limiting' => [
            'enabled' => false, // Disable for easier development
        ],
        'csrf' => [
            'enabled' => true,
            'strict' => false, // Less strict for development
        ],
    ],
    
    'modules' => [
        'auto_discovery' => true,
        'cache_enabled' => false,
    ],
];
```

```php
<?php
// config/environments/production.php

return [
    'app' => [
        'debug' => false,
        'log_level' => 'warning',
    ],
    
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],
    
    'security' => [
        'rate_limiting' => [
            'enabled' => true,
            'requests' => 60,
            'window' => 60,
        ],
        'csrf' => [
            'enabled' => true,
            'strict' => true,
        ],
        'headers' => [
            'hsts' => true,
            'csp' => true,
            'frame_options' => 'DENY',
        ],
    ],
    
    'performance' => [
        'opcache' => true,
        'preload' => true,
        'asset_minification' => true,
    ],
];
```

## 🔍 Configuration Validation

### Environment Validator

```php
<?php
// src/SharedKernel/Configuration/EnvironmentValidator.php

namespace HdmBoot\SharedKernel\Configuration;

final class EnvironmentValidator
{
    private array $requiredVars = [
        'APP_NAME',
        'APP_ENV',
        'DATABASE_URL',
        'JWT_SECRET',
        'SECURITY_KEY',
    ];

    private array $productionRequiredVars = [
        'APP_URL',
        'SESSION_SECURE',
        'LOG_LEVEL',
    ];

    public function validate(string $environment): array
    {
        $errors = [];

        // Check required variables
        foreach ($this->requiredVars as $var) {
            if (empty($_ENV[$var])) {
                $errors[] = "Missing required environment variable: {$var}";
            }
        }

        // Production-specific checks
        if ($environment === 'production') {
            foreach ($this->productionRequiredVars as $var) {
                if (empty($_ENV[$var])) {
                    $errors[] = "Missing production environment variable: {$var}";
                }
            }

            $errors = array_merge($errors, $this->validateProductionSecurity());
        }

        return $errors;
    }

    private function validateProductionSecurity(): array
    {
        $errors = [];

        // Check JWT secret strength
        if (strlen($_ENV['JWT_SECRET'] ?? '') < 32) {
            $errors[] = 'JWT_SECRET must be at least 32 characters long';
        }

        // Check security key strength
        if (strlen($_ENV['SECURITY_KEY'] ?? '') < 32) {
            $errors[] = 'SECURITY_KEY must be at least 32 characters long';
        }

        // Check HTTPS requirement
        if (($_ENV['SESSION_SECURE'] ?? 'false') !== 'true') {
            $errors[] = 'SESSION_SECURE must be true in production';
        }

        // Check debug mode
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            $errors[] = 'APP_DEBUG must be false in production';
        }

        return $errors;
    }
}
```

## 📋 Configuration Checklist

### Development:
- [ ] .env.dev súbor vytvorený
- [ ] Debug mode zapnutý
- [ ] Cache vypnutý
- [ ] Rate limiting vypnutý
- [ ] Všetky moduly povolené

### Staging:
- [ ] .env.staging súbor vytvorený
- [ ] Debug mode vypnutý
- [ ] Cache zapnutý
- [ ] HTTPS nastavené
- [ ] Produkčné moduly povolené

### Production:
- [ ] .env.production súbor vytvorený
- [ ] Silné security keys vygenerované
- [ ] Debug mode vypnutý
- [ ] Všetky optimalizácie zapnuté
- [ ] Security headers nastavené
- [ ] Environment validation prešla

## 🔗 Ďalšie zdroje

- [Environment Setup Guide](environment-setup.md)
- [Security Configuration](../SECURITY.md)
- [Module Configuration](module-configuration-guide.md)
- [Deployment Guide](../DEPLOYMENT.md)
