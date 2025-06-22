# Paths Refactor Plan

**CRITICAL**: Systematic refactor to eliminate ALL path concatenation vulnerabilities

## 🚨 INCIDENT SUMMARY

**Date**: 2025-06-18  
**Issue**: Path concatenation used throughout module system instead of Paths service  
**Risk Level**: HIGH - Path traversal vulnerabilities  
**Impact**: Module loading failures, security risks  

### Root Cause
- **Missing Paths enforcement** - No systematic check for path concatenation
- **Inconsistent usage** - Some files use Paths, others use string concatenation
- **No centralized policy** - Developers don't know when to use Paths vs concatenation

## 📋 REFACTOR PHASES

### **Phase 1: IMMEDIATE FIXES** (Priority: CRITICAL)

#### 1.1 Module System Core
- [ ] **ModuleManager.php** - Add Paths dependency injection
- [ ] **ModuleManifest.php** - Replace all path concatenation with Paths
- [ ] **GenericModule.php** - Paths integration for module paths
- [ ] **ModuleServiceLoader.php** - Secure service loading paths

#### 1.2 Module Manifests
- [ ] **Core/Template/module.php** - Fix config path
- [ ] **Core/Session/module.php** - Fix config path  
- [ ] **Core/User/module.php** - Fix config path
- [ ] **Core/Security/module.php** - Fix config path
- [ ] **Core/Language/module.php** - Fix config path
- [ ] **Core/Database/module.php** - Fix config path
- [ ] **Optional/Blog/module.php** - Fix config path

#### 1.3 Bootstrap System
- [ ] **App.php** - Paths integration for module discovery
- [ ] **Container setup** - Ensure Paths is available early

### **Phase 2: SYSTEMATIC AUDIT** (Priority: HIGH)

#### 2.1 Codebase Scan
```bash
# Find all path concatenation patterns
grep -r "\$.*\s*\.\s*'/'" src/
grep -r "__DIR__\s*\.\s*'/'" src/
grep -r "DIRECTORY_SEPARATOR" src/
grep -r "realpath(" src/
grep -r "dirname(" src/
```

#### 2.2 File Categories
- [ ] **Config files** - All config loading
- [ ] **Template files** - Template path resolution
- [ ] **Storage files** - File storage paths
- [ ] **Cache files** - Cache directory paths
- [ ] **Log files** - Log file paths
- [ ] **Upload files** - Upload directory paths

#### 2.3 Security Review
- [ ] **User input paths** - Any user-controlled path input
- [ ] **API endpoints** - File serving endpoints
- [ ] **Template includes** - Dynamic template loading
- [ ] **File uploads** - Upload path validation

### **Phase 3: PATHS ENFORCEMENT** (Priority: MEDIUM)

#### 3.1 Static Analysis Rules
- [ ] **PHPStan rule** - Detect path concatenation patterns
- [ ] **Custom rule** - Enforce Paths service usage
- [ ] **CI/CD integration** - Fail builds on path concatenation

#### 3.2 Code Standards
- [ ] **Coding standards** - Document Paths usage rules
- [ ] **Code review checklist** - Paths validation checklist
- [ ] **Developer guidelines** - When and how to use Paths

#### 3.3 Testing
- [ ] **Unit tests** - Test all Paths integrations
- [ ] **Security tests** - Path traversal attack tests
- [ ] **Integration tests** - Module loading with Paths

### **Phase 4: PREVENTION** (Priority: MEDIUM)

#### 4.1 Architecture Changes
- [ ] **PathsAware interface** - For classes that need Paths
- [ ] **PathsProvider trait** - Common Paths functionality
- [ ] **PathsValidator** - Validate all path operations

#### 4.2 Development Tools
- [ ] **IDE snippets** - Auto-complete for Paths usage
- [ ] **Linting rules** - ESLint-style rules for PHP
- [ ] **Pre-commit hooks** - Check for path concatenation

#### 4.3 Documentation
- [ ] **Update PATH_SECURITY.md** - Add enforcement rules
- [ ] **Developer onboarding** - Paths training materials
- [ ] **Architecture docs** - Paths integration patterns

## 🔧 IMPLEMENTATION STRATEGY

### **Step 1: Create Paths-Aware Module System**

```php
// New PathsAware interface
interface PathsAware
{
    public function setPaths(Paths $paths): void;
    public function getPaths(): Paths;
}

// Updated ModuleManager
class ModuleManager implements PathsAware
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Paths $paths,  // ← ADD THIS
        private readonly string $modulesPath = 'modules'
    ) {}
}
```

### **Step 2: Secure Path Resolution**

```php
// Replace ALL instances of:
// ❌ BAD
$configFile = $moduleDir . '/' . $data['config'];

// ✅ GOOD  
$configFile = $this->paths->getPath($moduleDir, $data['config']);
```

### **Step 3: Validation Layer**

```php
// New PathValidator class
class PathValidator
{
    public static function validatePath(string $path): bool
    {
        // Check for path traversal attempts
        if (str_contains($path, '..')) return false;
        if (str_contains($path, '~')) return false;
        // Add more validation rules
        return true;
    }
}
```

## 🚨 CRITICAL SECURITY FIXES

### **Immediate Actions Required**

1. **Stop using string concatenation** for ANY file paths
2. **Inject Paths service** into ALL classes that handle files
3. **Validate ALL user input** that becomes file paths
4. **Test path traversal attacks** on all file endpoints

### **Security Checklist**

- [ ] No `$var . '/' . $input` patterns anywhere
- [ ] No `__DIR__ . '/' . $input` patterns anywhere  
- [ ] All file operations use Paths service
- [ ] All user input is validated before path operations
- [ ] All file serving endpoints are secured
- [ ] All template includes are validated

## 📊 PROGRESS TRACKING

### **Metrics to Track**

- **Path concatenation instances**: Target 0
- **Paths service usage**: Target 100%
- **Security test coverage**: Target 100%
- **PHPStan compliance**: Target Level MAX

### **Success Criteria**

- ✅ **Zero path concatenation** in entire codebase
- ✅ **All modules load successfully** with Paths
- ✅ **Security tests pass** - no path traversal vulnerabilities
- ✅ **PHPStan Level MAX** with no path-related errors
- ✅ **Documentation complete** - clear Paths usage guidelines

## 🔄 ROLLBACK PLAN

### **If Issues Arise**

1. **Revert to working state** - Git rollback to last known good
2. **Isolate changes** - Apply fixes incrementally
3. **Test each change** - Verify no regressions
4. **Document issues** - Update plan with lessons learned

### **Risk Mitigation**

- **Backup before changes** - Full system backup
- **Test in staging** - Never apply directly to production
- **Monitor logs** - Watch for path-related errors
- **Have rollback ready** - Quick revert procedure

## 📚 RELATED DOCUMENTATION

- [Path Security Guide](PATH_SECURITY.md)
- [Paths Package Documentation](https://github.com/responsive-sk/slim4-paths)
- [Security Best Practices](SECURITY.md)
- [Module Development Guide](MODULES.md)

## 🎯 NEXT STEPS

1. **Review this plan** with team
2. **Assign responsibilities** for each phase
3. **Set timeline** for completion
4. **Begin Phase 1** immediately
5. **Track progress** daily

---

**This refactor is CRITICAL for security and stability. No new features until Paths integration is complete!**
