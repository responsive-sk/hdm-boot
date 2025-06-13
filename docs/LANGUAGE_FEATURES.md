# Language Features & Internationalization

## Overview

This document demonstrates the improved table styling in the web documentation viewer and showcases the language features available in the MVA Bootstrap project.

## 🌐 Supported Languages

| Language | Locale | Flag | Status | Native Name |
|----------|--------|------|--------|-------------|
| English (US) | en_US | 🇺🇸 | ✅ Default | English |
| Slovak | sk_SK | 🇸🇰 | ✅ Enabled | Slovenčina |
| Czech | cs_CZ | 🇨🇿 | ✅ Enabled | Čeština |
| German | de_DE | 🇩🇪 | ⚙️ Configurable | Deutsch |
| French | fr_FR | 🇫🇷 | ⚙️ Configurable | Français |
| Spanish | es_ES | 🇪🇸 | ⚙️ Configurable | Español |
| Italian | it_IT | 🇮🇹 | ⚙️ Configurable | Italiano |
| Polish | pl_PL | 🇵🇱 | ⚙️ Configurable | Polski |

## 🎯 Features

| Feature | Status | Description | API Endpoint |
|---------|--------|-------------|--------------|
| Translation | ✅ Enabled | Named parameter translation | `/api/translate` |
| Language Detection | ✅ Enabled | Browser language detection | `/api/language` |
| Locale Switching | ✅ Enabled | Runtime locale switching | `/api/language` |
| Pluralization | ⚙️ Configurable | Plural form handling | `/api/translate` |
| Date Formatting | ⚙️ Configurable | Locale-specific dates | N/A |
| Number Formatting | ⚙️ Configurable | Locale-specific numbers | N/A |

## 🔧 Technical Implementation

| Component | Type | Location | Purpose |
|-----------|------|----------|---------|
| LocaleService | Service | `modules/Core/Language/Services/` | Core translation logic |
| TranslateAction | API | `modules/Core/Language/Actions/Api/` | Translation endpoint |
| LanguageSettingsAction | API | `modules/Core/Language/Actions/Api/` | Language settings |
| Translation Files | Data | `modules/Core/Language/Resources/` | Translation data |

## 📊 Performance Metrics

| Metric | Value | Threshold | Status |
|--------|-------|-----------|--------|
| Translation Speed | < 1ms | 5ms | ✅ Excellent |
| Memory Usage | 2MB | 10MB | ✅ Excellent |
| Cache Hit Rate | 95% | 80% | ✅ Excellent |
| API Response Time | 50ms | 200ms | ✅ Excellent |

## 🚀 Usage Examples

### Basic Translation

```php
$translated = $localeService->translate('Hello {name}!', ['name' => 'World']);
// Result: "Hello World!"
```

### Language Switching

```php
$localeService->setLocale('sk_SK');
$translated = $localeService->translate('Hello {name}!', ['name' => 'Svet']);
// Result: "Ahoj Svet!"
```

### API Usage

```bash
# Get current language settings
curl http://localhost/api/language

# Translate text
curl -X POST http://localhost/api/translate \
  -H "Content-Type: application/json" \
  -d '{"text": "Hello {name}!", "params": {"name": "World"}}'
```

## 🎨 Table Styling Features

This documentation demonstrates the enhanced table styling features:

- **🎨 Gradient Headers**: Beautiful gradient backgrounds for table headers
- **✨ Hover Effects**: Interactive row highlighting on hover
- **🏷️ Status Badges**: Colored badges for different status types
- **🌍 Emoji Support**: Proper rendering of flag emojis and other symbols
- **📱 Responsive Design**: Tables work well on all device sizes
- **🎯 Modern Styling**: Box shadows, rounded corners, and smooth transitions

The web documentation viewer now properly converts markdown tables to beautifully styled HTML tables with all the modern CSS features you'd expect from a professional documentation system.
