# HDM Boot Templates

## 📑 Prehľad Šablón

### 1. Module Templates [P0]
| Template | Popis | Použitie |
|----------|--------|----------|
| [Module Config](module-config-template.php) | Základná konfigurácia modulu | Pri vytváraní nového modulu |
| [Module Bootstrap](module-bootstrap-template.php) | Bootstrap trieda modulu | Inicializácia modulu |
| [Module Routes](module-routes-template.php) | Definícia routes modulu | Routing nového modulu |

### 2. Infrastructure Templates [P0]
| Template | Popis | Použitie |
|----------|--------|----------|
| [Repository](infrastructure/repository-template.php) | Database repository | Prístup k dátam |
| [Service](infrastructure/service-template.php) | Service implementácia | Business logika |
| [Factory](infrastructure/factory-template.php) | Object factory | Vytváranie objektov |

### 3. Domain Templates [P1]
| Template | Popis | Použitie |
|----------|--------|----------|
| [Entity](domain/entity-template.php) | Domain entity | Biznis objekty |
| [Value Object](domain/value-object-template.php) | Value objects | Immutable objekty |
| [Event](domain/event-template.php) | Domain events | Event handling |

## 📝 Používanie Templátov

### 1. Vytvorenie Nového Modulu
```bash
# 1. Skopírovať module config template
cp docs/templates/module-config-template.php config/modules/new-module.php

# 2. Skopírovať module bootstrap template
cp docs/templates/module-bootstrap-template.php src/Modules/NewModule/Bootstrap.php

# 3. Upraviť konfiguráciu
vim config/modules/new-module.php
```

### 2. Vytvorenie Novej Entity
```php
// Použitie entity template
namespace HdmBoot\Modules\NewModule\Domain;

class NewEntity
{
    use EntityTemplate;
    
    private string $id;
    private string $name;
    private \DateTimeImmutable $createdAt;
    
    // Implementácia...
}
```

## 🔄 Template Updates

### Current Version
- Module Templates: v1.2
- Infrastructure Templates: v1.1
- Domain Templates: v1.0

### Plánované Aktualizácie
- [ ] API Controller templates
- [ ] Test templates
- [ ] Documentation templates

## 📋 Template Guidelines

### 1. Naming Conventions
- PascalCase pre triedy
- camelCase pre metódy
- snake_case pre configy

### 2. Code Style
- PSR-12 štandard
- Typed properties
- Constructor promotion
- Readonly kde je možné

### 3. Documentation
- PHPDoc bloky
- Parameter types
- Return types
- Throws documentation

## 🎯 Template Checklist

### Pri vytváraní nových templátov:
- [ ] Dodržať coding standards
- [ ] Pridať PHPDoc dokumentáciu
- [ ] Vytvoriť príklad použitia
- [ ] Aktualizovať tento index

### Pri použití templátu:
- [ ] Správne namespace
- [ ] Upraviť konfiguráciu
- [ ] Doplniť dokumentáciu
- [ ] Pridať testy
