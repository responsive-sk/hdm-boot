# HDM Boot Documentation

## 📚 Core Documentation

### 1. Getting Started [P0]
| Dokument | Popis | Status |
|----------|--------|--------|
| [Quick Start](../README.md) | Rýchly štart s projektom | ✅ |
| [Installation](../README.md#installation) | Inštalácia projektu | ✅ |
| [Requirements](../README.md#requirements) | Systémové požiadavky | ✅ |

### 2. Core Architecture [P0]
| Dokument | Popis | Status |
|----------|--------|--------|
| [Clean Architecture](architecture/clean-architecture.md) | Architektúra projektu | ✅ |
| [Security Architecture](architecture/security-architecture.md) | Bezpečnostná architektúra | ✅ |
| [Module System](MODULES.md) | Modulárny systém | ✅ |

### 3. Core Features [P0]
| Dokument | Popis | Status |
|----------|--------|--------|
| [Security](SECURITY.md) | Bezpečnostné funkcie | ✅ |
| [Storage System](../content/docs/hybrid-storage.md) | Hybrid storage systém | ✅ |
| [Session](SESSION.md) | Správa sessions | ✅ |

### 4. API Documentation [P1]
| Dokument | Popis | Status |
|----------|--------|--------|
| [API Overview](API.md) | Prehľad API | ✅ |
| [Auth API](api/auth-api.md) | Autentifikačné API | ✅ |
| [User API](USER_API.md) | User Management API | ✅ |

### 5. Development Guides [P1]
| Kategória | Popis | Status |
|-----------|--------|--------|
| [Coding Guides](guides/README.md#1-vývojárske-príručky) | Vývojárske príručky | 3/4 ✅ |
| [Security Guides](guides/README.md#2-bezpečnostné-príručky) | Bezpečnostné príručky | 2/3 ✅ |
| [Deployment Guides](guides/README.md#3-deployment-príručky) | Deployment príručky | 1/3 ⏳ |

### 6. Advanced Features [P2]
| Dokument | Popis | Status |
|----------|--------|--------|
| [Events](architecture/event-driven-architecture.md) | Event systém | ✅ |
| [DI Container](architecture/dependency-injection.md) | Dependency Injection | ✅ |
| [Monitoring](architecture/monitoring-logging.md) | Monitoring a logging | ✅ |

### 7. Quality Assurance [P0]
| Dokument | Popis | Status |
|----------|--------|--------|
| [PHPStan Success](PHPSTAN_COMPLETE_SUCCESS.md) | 100% PHPStan Level MAX úspech | ✅ |
| [Code Analysis](PHPSTAN_CODE_ANALYSIS.md) | Detailná analýza kvality kódu | ✅ |

## 📈 Documentation Status

| Kategória | Hotové | Status |
|-----------|--------|--------|
| Getting Started | 3/3 | ✅ Completed |
| Core Architecture | 3/3 | ✅ Completed |
| Core Features | 3/3 | ✅ Completed |
| API Documentation | 3/3 | ✅ Completed |
| Development Guides | 16/16 | ✅ Completed |
| Advanced Features | 3/3 | ✅ Completed |
| Quality Assurance | 2/2 | ✅ Completed |

## 🎯 Priority Levels

- **P0** - Kritické, potrebné pre základné použitie projektu
- **P1** - Dôležité, potrebné pre plné využitie funkcií
- **P2** - Rozširujúce, pre pokročilé použitie

## 🔄 Recent Updates

### Database Module Refactoring (2025-06-28)
| Dokument | Popis | Status |
|----------|--------|--------|
| [Database Module Refactoring](refactoring/database-module-refactoring.md) | PDO-only refaktoring | ✅ |
| [Database Architecture](DATABASE_ARCHITECTURE.md) | Aktualizovaná architektúra | ✅ |
| [Architecture Changelog](ARCHITECTURE_CHANGELOG.md) | Zmeny v architektúre | ✅ |

**Kľúčové zmeny:**
- ✅ Database modul používa len PDO implementáciu
- ✅ CakePHP podpora dočasne vypnutá (backup v `_disabled_cakephp/`)
- ✅ Zjednodušená konfigurácia a lepšia výkonnosť
- ✅ Zachovaná spätná kompatibilita pre PDO operácie

## 📝 Contributing

Pre prispievanie do dokumentácie pozrite [CONTRIBUTING.md](../CONTRIBUTING.md)
