# 🌍 Language & Localization System

**Enterprise-grade multilingual support for MVA Bootstrap Application**

Based on [samuelgfeller's localization pattern](https://samuel-gfeller.ch/docs/Translations) with enterprise enhancements.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Architecture](#architecture)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Endpoints](#api-endpoints)
- [Middleware](#middleware)
- [Translation Files](#translation-files)
- [Development](#development)
- [Troubleshooting](#troubleshooting)

## 🎯 Overview

The Language & Localization system provides comprehensive multilingual support with:

- **Automatic language detection** from browser, session, cookies, and user preferences
- **Enterprise configuration** via environment variables and config files
- **REST API** for dynamic language management
- **Session persistence** for user language preferences
- **Fallback system** for unsupported languages
- **Translation management** with gettext support

## ✨ Features

### 🌐 Supported Languages

| Language | Locale | Flag | Status | Native Name |
|----------|--------|------|--------|-------------|
| English (US) | `en_US` | 🇺🇸 | ✅ Default | English |
| Slovak | `sk_SK` | 🇸🇰 | ✅ Enabled | Slovenčina |
| Czech | `cs_CZ` | 🇨🇿 | ✅ Enabled | Čeština |
| German | `de_DE` | 🇩🇪 | ⚙️ Configurable | Deutsch |
| French | `fr_FR` | 🇫🇷 | ⚙️ Configurable | Français |
| Spanish | `es_ES` | 🇪🇸 | ⚙️ Configurable | Español |
| Italian | `it_IT` | 🇮🇹 | ⚙️ Configurable | Italiano |
| Polish | `pl_PL` | 🇵🇱 | ⚙️ Configurable | Polski |

### 🔧 Core Features

- ✅ **Automatic Detection** - Browser, session, cookie, user preference
- ✅ **Session Persistence** - Language choice persists across requests
- ✅ **Cookie Support** - Long-term language preference storage
- ✅ **REST API** - Dynamic language management
- ✅ **Middleware Integration** - Automatic locale setting
- ✅ **Fallback System** - Graceful handling of unsupported languages
- ✅ **Enterprise Logging** - Comprehensive language operation tracking
- ✅ **Environment Configuration** - Flexible deployment options

## 🏗️ Architecture

### Core Components

```
Language System
├── LocaleService          # Core language management
├── LocaleMiddleware       # Automatic language detection
├── TranslateAction        # Translation API endpoint
├── LanguageSettingsAction # Language management API
└── Configuration          # Environment-driven config
```

### Detection Priority

1. **User Preference** (database) - *Future implementation*
2. **Session Language** (`app_language` session key)
3. **Cookie Language** (`app_language` cookie)
4. **Browser Language** (`Accept-Language` header)
5. **Default Locale** (`en_US`)

## ⚙️ Configuration

### Environment Variables

Add to your `.env` file:

```bash
# Language/Localization Configuration
DEFAULT_LOCALE=en_US
DEFAULT_TIMEZONE=Europe/Bratislava

# Enable/Disable Languages
ENABLE_SLOVAK=true
ENABLE_CZECH=true
ENABLE_GERMAN=false
ENABLE_FRENCH=false
ENABLE_SPANISH=false
ENABLE_ITALIAN=false
ENABLE_POLISH=false

# Translation Settings
TRANSLATIONS_PATH=resources/translations
TRANSLATION_DOMAIN=messages
TRANSLATION_FALLBACK=true
GETTEXT_ENABLED=true

# Language Detection
AUTO_DETECT_LANGUAGE=true
USE_BROWSER_LANGUAGE=true
USE_USER_PREFERENCE=true
LANGUAGE_COOKIE_NAME=app_language

# Language API
LANGUAGE_API_ENABLED=true
LANGUAGE_API_REQUIRE_AUTH=false

# Development
LANGUAGE_DEBUG=false
LOG_MISSING_TRANSLATIONS=true
```

### Configuration File

The system uses `config/language.php` for comprehensive configuration:

```php
return [
    'default_locale' => $_ENV['DEFAULT_LOCALE'] ?? 'en_US',
    'available_locales' => [
        'en_US' => [
            'name' => 'English (United States)',
            'native_name' => 'English',
            'flag' => '🇺🇸',
            'enabled' => true,
        ],
        // ... more locales
    ],
    'detection' => [
        'auto_detect' => true,
        'use_browser_language' => true,
        'use_session' => true,
        'use_cookie' => true,
        'cookie_name' => 'app_language',
    ],
    // ... more configuration
];
```

## 🚀 Quick Start

### 1. Enable Languages

Edit your `.env` file:
```bash
ENABLE_SLOVAK=true
ENABLE_CZECH=true
```

### 2. Test Language Detection

Visit your application - language is automatically detected from your browser!

### 3. Change Language via API

```bash
# Change to Slovak
curl -X POST http://localhost:8000/api/language \
  -H "Content-Type: application/json" \
  -d '{"locale": "sk_SK"}'

# Check current language
curl http://localhost:8000/api/language
```

### 4. Test Browser Detection

```bash
# Test with Slovak browser
curl -H "Accept-Language: sk-SK,sk;q=0.9" http://localhost:8000/api/language
```

**That's it!** 🎉 Language system works automatically!

## 🚀 Usage

### Basic Usage

The language system works automatically via middleware. No manual setup required!

```php
// Language is automatically detected and set on every request
// via LocaleMiddleware based on:
// 1. Session preference
// 2. Cookie preference
// 3. Browser Accept-Language header
// 4. Default locale
```

### Manual Language Setting

```php
use MvaBootstrap\Modules\Core\Language\Services\LocaleService;

// Get current language
$currentLocale = $localeService->getCurrentLocale(); // 'sk_SK'
$languageCode = $localeService->getCurrentLanguageCode(); // 'sk'

// Set language manually
$result = $localeService->setLanguage('sk_SK');

// Check if language is supported
$isSupported = $localeService->isLocaleSupported('cs_CZ'); // true

// Get available languages
$availableLocales = $localeService->getAvailableLocales();
// ['en_US', 'sk_SK', 'cs_CZ']
```

### Translation

```php
// Basic translation (fallback to original if not found)
$translated = $localeService->translate('Login'); // 'Prihlásenie' (in Slovak)

// Translation with parameters
$message = $localeService->translate('Welcome %s!', $userName);

// Plural translations
$count = 5;
$message = $localeService->translatePlural(
    '1 user', 
    '%d users', 
    $count, 
    $count
); // '5 users'
```

## 🔌 API Endpoints

### Get Language Settings

```http
GET /api/language
```

**Response:**
```json
{
    "success": true,
    "data": {
        "current_locale": "sk_SK",
        "current_language_code": "sk",
        "available_locales": [
            {
                "code": "en_US",
                "name": "English (United States)",
                "native_name": "English",
                "flag": "🇺🇸",
                "language_code": "en"
            },
            {
                "code": "sk_SK",
                "name": "Slovak (Slovakia)",
                "native_name": "Slovenčina",
                "flag": "🇸🇰",
                "language_code": "sk"
            }
        ],
        "language_path": "sk/"
    }
}
```

### Change Language

```http
POST /api/language
Content-Type: application/json

{
    "locale": "sk_SK"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "locale": "sk_SK",
        "language_code": "sk",
        "message": "Language set successfully"
    }
}
```

### Translate Strings

```http
GET /api/translate?strings[]=Login&strings[]=Email&strings[]=Password
```

**Response:**
```json
{
    "success": true,
    "data": {
        "translations": {
            "Login": "Prihlásenie",
            "Email": "E-mail",
            "Password": "Heslo"
        },
        "locale": "sk_SK",
        "language_code": "sk",
        "count": 3
    }
}
```

**POST Method:**
```http
POST /api/translate
Content-Type: application/json

{
    "strings": ["Login", "Email", "Password"]
}
```

## 🔄 Middleware

### LocaleMiddleware

Automatically detects and sets language on every request:

```php
// Registered in bootstrap/App.php
$this->slimApp->add($this->container->get(LocaleMiddleware::class));
```

**Detection Flow:**
1. Check user preference (database) - *Future*
2. Check session (`app_language`)
3. Check cookie (`app_language`)
4. Parse browser `Accept-Language` header
5. Use default locale

**Session & Cookie Persistence:**
- Language choice stored in session for current session
- Language choice stored in cookie for 30 days (configurable)
- Automatic language restoration on return visits

## 📁 Translation Files

### Directory Structure

```
resources/translations/
├── messages.pot              # Translation template
├── sk_SK/
│   └── LC_MESSAGES/
│       ├── messages_sk_SK.po # Slovak translations (source)
│       └── messages_sk_SK.mo # Slovak translations (compiled)
└── cs_CZ/
    └── LC_MESSAGES/
        ├── messages_cs_CZ.po # Czech translations (source)
        └── messages_cs_CZ.mo # Czech translations (compiled)
```

### Translation Template (POT)

The system includes a comprehensive translation template at `resources/translations/messages.pot` with:

- Common UI strings (Login, Email, Password, etc.)
- Authentication messages
- User management strings
- Validation messages
- Error messages
- Enterprise features strings

### Creating Translations

1. **Copy template:**
   ```bash
   cp resources/translations/messages.pot resources/translations/sk_SK/LC_MESSAGES/messages_sk_SK.po
   ```

2. **Edit translations:**
   ```po
   msgid "Login"
   msgstr "Prihlásenie"
   
   msgid "Email"
   msgstr "E-mail"
   ```

3. **Compile translations:**
   ```bash
   msgfmt resources/translations/sk_SK/LC_MESSAGES/messages_sk_SK.po \
          -o resources/translations/sk_SK/LC_MESSAGES/messages_sk_SK.mo
   ```

## 🛠️ Development

### Testing Language Detection

```bash
# Test Slovak browser language
curl -H "Accept-Language: sk-SK,sk;q=0.9,en;q=0.8" http://localhost:8000/api/language

# Test Czech browser language  
curl -H "Accept-Language: cs-CZ,cs;q=0.9,en;q=0.8" http://localhost:8000/api/language

# Test unsupported language (fallback to default)
curl -H "Accept-Language: de-DE,de;q=0.9,en;q=0.8" http://localhost:8000/api/language
```

### Session Persistence Testing

```bash
# Test with session cookies
COOKIE_JAR="/tmp/session_test.txt"

# Get initial language
curl -s -c "$COOKIE_JAR" http://localhost:8000/api/language

# Change language
curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X POST http://localhost:8000/api/language \
  -H "Content-Type: application/json" -d '{"locale": "sk_SK"}'

# Verify persistence
curl -s -b "$COOKIE_JAR" http://localhost:8000/api/language
```

### Debugging

Enable debug logging:

```bash
# In .env
LANGUAGE_DEBUG=true
LOG_MISSING_TRANSLATIONS=true
```

Check logs:
```bash
tail -f logs/debug-app.log | grep -E "(Language|Locale|Translation)"
```

## 🔧 Troubleshooting

### Common Issues

#### 1. Language Change API Returns 500 Error

**Cause:** System locales not installed or `setlocale()` fails

**Solution:**
```bash
# Install system locales (Ubuntu/Debian)
sudo apt-get install locales-all

# Or install specific locales
sudo locale-gen sk_SK.UTF-8 cs_CZ.UTF-8

# Verify available locales
locale -a | grep -E "(sk_SK|cs_CZ)"
```

#### 2. Translations Not Working

**Cause:** Missing `.mo` files or gettext not enabled

**Solution:**
```bash
# Check if gettext extension is installed
php -m | grep gettext

# Install gettext tools
sudo apt-get install gettext

# Compile translation files
msgfmt file.po -o file.mo
```

#### 3. Browser Language Not Detected

**Cause:** Middleware not registered or wrong priority

**Solution:**
```php
// Ensure LocaleMiddleware is registered early in bootstrap/App.php
$this->slimApp->add($this->container->get(LocaleMiddleware::class));
```

#### 4. Session Not Persisting

**Cause:** Session configuration or cookie issues

**Solution:**
```php
// Check session configuration in config/container.php
// Verify PHPSESSID cookie is set
// Check session.cookie_* PHP settings
```

### Debug Information

```php
// Get comprehensive language info
$config = $localeService->getConfig();
$currentLocale = $localeService->getCurrentLocale();
$availableLocales = $localeService->getAvailableLocales();

// Check system locale support
$systemLocales = shell_exec('locale -a');
$setlocaleResult = setlocale(LC_ALL, 'sk_SK.UTF-8');
```

## 📚 References

- [samuelgfeller Translations Documentation](https://samuel-gfeller.ch/docs/Translations)
- [PHP Gettext Documentation](https://www.php.net/manual/en/book.gettext.php)
- [GNU Gettext Manual](https://www.gnu.org/software/gettext/manual/gettext.html)
- [HTTP Accept-Language Header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept-Language)

---

**🌍 Enterprise multilingual support for modern PHP applications** ✨
