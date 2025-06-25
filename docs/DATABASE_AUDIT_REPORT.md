# 🔍 HDM Boot Database & Naming Audit Report

**Date:** 2025-06-24  
**Version:** HDM Boot v0.9.0  
**Status:** 🚨 CRITICAL ISSUES FOUND

## 📋 Executive Summary

Multiple **critical inconsistencies** found in database schemas and column naming that could cause production authentication failures.

## 🚨 Critical Issues

### 1. **Password Column Inconsistency**

**Problem:** UserRepository has fallback logic that conflicts with UserService expectations.

**Location:** `src/Modules/Core/User/Repository/SqliteUserRepository.php:311`
```php
// ❌ PROBLEMATIC FALLBACK
$userData['password_hash'] ?? $userData['password'] ?? ''
```

**Impact:** 
- UserService expects `password_hash` column
- Repository falls back to `password` column if `password_hash` missing
- Could cause authentication failures in production

**Root Cause:** Legacy code from migration between naming conventions.

### 2. **Multiple Database Schema Definitions**

**Problem:** Different database managers define different user table schemas.

**Conflicting Schemas:**

**A) Storage/DatabaseManager.php (Lines 290-306):**
```sql
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,  -- ❌ INTEGER vs TEXT
    username TEXT UNIQUE NOT NULL,         -- ❌ Has username
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,                       -- ❌ Has first_name/last_name
    last_name TEXT,
    role TEXT DEFAULT "user",
    status TEXT DEFAULT "active",
    email_verified INTEGER DEFAULT 0,
    email_verified_at TEXT,
    last_login_at TEXT,
    login_count INTEGER DEFAULT 0,
    preferences TEXT,                      -- ❌ Has preferences
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)
```

**B) User/SqliteUserRepository.php (Lines 160-172):**
```sql
CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,                   -- ✅ TEXT ID
    email TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,                    -- ✅ Single name field
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    status TEXT NOT NULL DEFAULT 'active',
    email_verified INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
)
```

**Impact:** 
- Different table structures in different parts of application
- ID type mismatch (INTEGER vs TEXT)
- Column name conflicts (username vs name, first_name/last_name vs name)

### 3. **Database Connection Inconsistency**

**Problem:** Multiple database connection managers with different configurations.

**Files:**
- `src/Modules/Core/Storage/Services/DatabaseManager.php`
- `src/Modules/Core/Database/Infrastructure/Services/DatabaseConnectionManager.php`
- `src/Modules/Core/Database/Infrastructure/Services/CakePHPDatabaseManager.php`

**Impact:** Unclear which database manager is actually used in production.

## 🔧 Recommended Fixes

### Priority 1: Fix Password Column Fallback

**File:** `src/Modules/Core/User/Repository/SqliteUserRepository.php`

**Current (Lines 311, 331):**
```php
$userData['password_hash'] ?? $userData['password'] ?? ''
```

**Fix:**
```php
$userData['password_hash'] ?? ''  // Remove password fallback
```

### Priority 2: Standardize User Table Schema

**Recommended Schema:**
```sql
CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,                    -- UUID string
    email TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,                     -- Single name field
    password_hash TEXT NOT NULL,            -- Consistent naming
    role TEXT NOT NULL DEFAULT 'user',
    status TEXT NOT NULL DEFAULT 'active',
    email_verified INTEGER NOT NULL DEFAULT 0,
    last_login_at TEXT,
    login_count INTEGER DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)
```

### Priority 3: Remove Duplicate Database Managers

**Keep:** `src/Modules/Core/User/Repository/SqliteUserRepository.php` (most consistent)
**Remove/Deprecate:** Other database managers with conflicting schemas

## 🎯 Production Impact Analysis

### **Admin vs User Login Issue**

**Hypothesis:** The production authentication failure for users (but not admin) could be caused by:

1. **Schema Mismatch:** Production database has `username` column but code expects `name`
2. **Password Column:** Production has `password` but code expects `password_hash`
3. **ID Type:** Production has INTEGER ID but code expects TEXT

**Verification Steps:**
1. Check production database schema: `PRAGMA table_info(users);`
2. Check actual column names in production
3. Verify which database manager is used in production

## 📊 Files Requiring Changes

### High Priority
- [ ] `src/Modules/Core/User/Repository/SqliteUserRepository.php` - Remove password fallback
- [ ] `src/Modules/Core/Storage/Services/DatabaseManager.php` - Align schema
- [ ] Production database - Schema migration needed

### Medium Priority  
- [ ] `src/Modules/Core/Database/Infrastructure/Services/DatabaseConnectionManager.php`
- [ ] Documentation updates
- [ ] Test data alignment

### Low Priority
- [ ] Remove unused database managers
- [ ] Consolidate database initialization code

## 🚀 Next Steps

1. **Immediate:** Deploy debug version to see exact production error
2. **Emergency:** Check production database schema
3. **Fix:** Apply schema standardization
4. **Test:** Verify both admin and user login work
5. **Document:** Update database documentation

## 📝 Notes

- This audit was triggered by production authentication failures
- Admin login works, user login fails (500 error)
- Suggests schema/data inconsistency rather than code logic issue
- Multiple database managers indicate architectural debt

---

**Audit Completed By:** HDM Boot Development Team  
**Next Review:** After production fixes implemented
