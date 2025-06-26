# 🚀 HDM Boot Deployment Guide

**Version:** 1.0  
**Target:** Shared Hosting (FTP/FTPS)  
**Updated:** 2025-06-24

## 📋 Overview

This guide covers deploying HDM Boot applications to shared hosting environments without SSH access. The deployment uses FTP/FTPS upload with pre-built production packages.

## 🎯 Deployment Strategy

### **Default: Shared Hosting (FTP/FTPS)**
- ✅ **No SSH required** - Works on basic shared hosting
- ✅ **Pre-built packages** - Ready for upload
- ✅ **Relaxed permissions** - Compatible with shared hosting
- ✅ **Security hardened** - .htaccess protection

### **Alternative: VPS/Dedicated (SSH)**
- 🔧 **SSH access** - For advanced deployments
- 🔧 **Strict permissions** - Production-grade security
- 🔧 **Automated deployment** - CI/CD pipelines

## 📦 Production Build Process

### **1. Create Production Package**
```bash
# Build production package
php bin/build-production.php

# Output: hdm-boot-production-YYYY-MM-DD-HH-MM-SS.zip
```

### **2. Package Contents**
```
hdm-boot-production.zip
├── src/                    # Application code
├── public/                 # Web root
├── config/                 # Configuration
├── vendor/                 # Dependencies (production only)
├── storage/                # Database storage
├── var/                    # Logs, cache, sessions
├── .env.example            # Environment template
├── .htaccess               # Security rules
├── DEPLOYMENT.md           # Instructions
└── composer.json           # Dependencies
```

### **3. Excluded Development Files**
- ❌ `tests/` - Unit tests
- ❌ `docs/` - Documentation
- ❌ `bin/` - Development scripts
- ❌ `.git/` - Version control
- ❌ `phpstan.neon` - Static analysis
- ❌ Development dependencies

## 🔧 Shared Hosting Deployment

### **Step 1: Upload Files**
1. **Download** production ZIP package
2. **Extract** ZIP to local directory
3. **Upload** all files via FTP/FTPS to web root
4. **Verify** file structure on server

### **Step 2: Configure Environment**
1. **Copy** `.env.example` to `.env`
2. **Edit** `.env` with production values:
```env
APP_ENV=production
APP_DEBUG=false
PERMISSIONS_STRICT=false
SECRET_KEY=your-secure-random-key-here
CSRF_SECRET=your-csrf-secret-here
```

### **Step 3: Generate Secure Keys**
```php
# Add to .env (replace with actual generated values)
SECRET_KEY=<?php echo bin2hex(random_bytes(32)); ?>
CSRF_SECRET=<?php echo bin2hex(random_bytes(32)); ?>
```

### **Step 4: Set Permissions (if possible)**
```bash
# If you have shell access
chmod 777 storage/ var/ var/logs/ var/sessions/ var/cache/
chmod 666 var/logs/*.log

# If no shell access, permissions are pre-set to 777/666
```

### **Step 5: Initialize Application**
1. **Visit** `https://yourdomain.com/`
2. **Database initialization** happens automatically
3. **Default mark user** is created:
   - Username: `mark`
   - Email: `mark@responsive.sk`
   - Password: `mark123`

### **Step 6: Security Verification**
- [ ] ✅ `.env` file is not accessible via web
- [ ] ✅ `storage/` directory is protected
- [ ] ✅ Database files are not downloadable
- [ ] ✅ Error logs are not public
- [ ] ✅ Default passwords changed

## 🛡️ Security Configuration

### **Production .htaccess**
```apache
# HDM Boot Production Security
RewriteEngine On

# Redirect to public directory
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ /public/$1 [L,QSA]

# Deny access to sensitive files
<FilesMatch "\.(env|log|db|json|lock|md)$">
    Require all denied
</FilesMatch>

# Deny access to directories
<DirectoryMatch "(storage|var|config|src|vendor)">
    Require all denied
</DirectoryMatch>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>
```

### **File Permissions**
```
Directories: 777 (rwxrwxrwx) - Shared hosting compatible
Files:       666 (rw-rw-rw-) - Shared hosting compatible
Logs:        666 (rw-rw-rw-) - Writable by web server
```

## 🗄️ Database Configuration

### **Three-Database Architecture**
```
storage/mark.db    → Mark system (super user)
storage/user.db    → User system (application users)
storage/system.db  → Core system modules (cache, logs)
```

### **Database Initialization**
- ✅ **Automatic creation** on first visit
- ✅ **Schema migration** handled internally
- ✅ **Default data** seeded automatically
- ✅ **Permissions** set correctly

### **Database Security**
- ✅ **SQLite files** protected by .htaccess
- ✅ **Outside web root** (storage/ directory)
- ✅ **WAL mode** enabled for concurrency
- ✅ **Prepared statements** prevent SQL injection

## 🔍 Testing Deployment

### **1. Basic Functionality**
- [ ] ✅ Homepage loads without errors
- [ ] ✅ Mark login works (mark/mark123)
- [ ] ✅ User registration works
- [ ] ✅ User login works
- [ ] ✅ Database operations function

### **2. Security Testing**
- [ ] ✅ Cannot access `.env` file
- [ ] ✅ Cannot download database files
- [ ] ✅ Cannot access `storage/` directory
- [ ] ✅ Cannot access `var/logs/` directory
- [ ] ✅ Error pages don't reveal sensitive info

### **3. Performance Testing**
- [ ] ✅ Page load times acceptable
- [ ] ✅ Database queries optimized
- [ ] ✅ Template caching working
- [ ] ✅ No memory limit errors

## 🚨 Troubleshooting

### **Common Issues**

#### **500 Internal Server Error**
1. **Check error logs:** `var/logs/error.log`
2. **Verify permissions:** 777 for directories, 666 for files
3. **Check .htaccess:** Ensure mod_rewrite is enabled
4. **PHP version:** Ensure PHP 8.1+ is available

#### **Database Connection Failed**
1. **Check storage permissions:** Must be writable
2. **Verify database path:** Check `.env` configuration
3. **SQLite support:** Ensure PDO SQLite is enabled

#### **Permission Denied Errors**
1. **Use relaxed permissions:** Set `PERMISSIONS_STRICT=false`
2. **Check file ownership:** Web server must own files
3. **Verify directory structure:** All required directories exist

#### **Session Issues**
1. **Check session directory:** `var/sessions/` must be writable
2. **Verify session configuration:** Check PHP session settings
3. **Clear session data:** Delete files in `var/sessions/`

### **Debug Mode**
```env
# Enable for troubleshooting (disable in production)
APP_DEBUG=true
LOG_LEVEL=debug
```

## 📞 Support

### **Documentation**
- **Architecture:** `docs/CORE_ARCHITECTURE_PRINCIPLES.md`
- **Protocol:** `docs/HDM_BOOT_PROTOCOL.md`
- **Troubleshooting:** `docs/TROUBLESHOOTING.md`

### **Tools**
- **Permission management:** `bin/fix-permissions.php`
- **Production build:** `bin/build-production.php`
- **Health checking:** Built into application

---

**HDM Boot Deployment Guide v1.0**  
**Optimized for Shared Hosting Environments**
