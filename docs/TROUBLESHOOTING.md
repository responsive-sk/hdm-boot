# 🔧 HDM Boot Troubleshooting Guide

This document contains solutions to common issues encountered in HDM Boot development and production.

## 📋 Table of Contents

- [Session Issues](#session-issues)
- [Flash Message Problems](#flash-message-problems)
- [Authentication Issues](#authentication-issues)
- [Module Loading Problems](#module-loading-problems)
- [Template Rendering Issues](#template-rendering-issues)
- [Database Connection Problems](#database-connection-problems)
- [Performance Issues](#performance-issues)

## 🔐 Session Issues

### Session Not Starting

**Symptoms:**
- Session data not persisting
- User authentication fails
- CSRF tokens not working

**Solutions:**
1. Check session configuration in `.env`
2. Verify SessionStartMiddleware is registered
3. Ensure session directory is writable
4. Check PHP session settings

```bash
# Check session directory permissions
ls -la var/sessions/
chmod 755 var/sessions/
```

### Session Data Lost After Redirect

**Cause:** Session not properly saved before redirect

**Solution:**
```php
// Ensure session is saved before redirect
$this->session->save();
return $response->withHeader('Location', '/dashboard')->withStatus(302);
```

## 💬 Flash Message Problems

### Flash Messages Persisting After Logout/Login

**Problem:** Old logout message appears after successful login.

**Root Cause:** 
- Logout sets flash message AFTER `session->destroy()`
- Flash message stored in new session
- Login `clearAll()` operates on different session

**Solution:**
```php
// ✅ CORRECT - LogoutAction
$this->session->flash('success', 'You have been logged out successfully');
$this->session->destroy(); // Flash persists to next session

// ✅ CORRECT - LoginSubmitAction
$this->session->getFlash()->clearAll(); // Clear old messages
$this->session->flash('success', 'Login successful! Welcome back.');
```

**Key Points:**
- Set flash messages **BEFORE** `session->destroy()`
- Use `clearAll()` in login for clean slate
- Flash messages automatically persist across session recreation

### Flash Messages Not Displaying

**Cause:** Messages consumed before template rendering

**Solution:**
```php
// Check if messages exist before consuming
if ($flash->has('success')) {
    $messages = $flash->get('success');
}

// Or use non-consuming methods
$allMessages = $flash->all(); // This consumes!
```

### Multiple Flash Messages Overlapping

**Problem:** Multiple actions add flash messages that overlap

**Solution:**
```php
// Clear specific message type
$this->session->getFlash()->clear('success');

// Or clear all messages
$this->session->getFlash()->clearAll();

// Then add new message
$this->session->flash('success', 'New message');
```

## 🔑 Authentication Issues

### User Stays Logged In After Logout

**Cause:** Session not properly destroyed

**Solution:**
```php
// Proper logout sequence
$this->session->flash('success', 'Logged out successfully');
$this->session->destroy(); // This clears all session data
```

### CSRF Token Mismatch

**Symptoms:**
- Forms fail with CSRF error
- "Invalid CSRF token" messages

**Solutions:**
1. Check CSRF token generation in forms
2. Verify CsrfMiddleware is registered
3. Ensure session is active

```php
// In template
<input type="hidden" name="csrf_token" value="<?= $csrf->generateToken() ?>">

// In action
if (!$this->csrfService->validateToken($token, $request)) {
    throw new SecurityException('Invalid CSRF token');
}
```

## 📦 Module Loading Problems

### Module Not Found

**Symptoms:**
- "Module 'ModuleName' not found" errors
- Services not registered

**Solutions:**
1. Check module manifest file exists
2. Verify module is in correct directory
3. Check module dependencies

```bash
# Check module structure
ls -la src/Modules/Core/ModuleName/
# Should contain: module.php, config.php

# Verify module manifest
cat src/Modules/Core/ModuleName/module.php
```

### Service Not Found in Container

**Cause:** Service not registered in module config

**Solution:**
```php
// In module config.php
'services' => [
    ServiceInterface::class => function (Container $container): ServiceInterface {
        return new ServiceImplementation(
            $container->get(DependencyInterface::class)
        );
    },
],
```

## 🎨 Template Rendering Issues

### Template Not Found

**Symptoms:**
- "Template file not found" errors
- Blank pages

**Solutions:**
1. Check template path configuration
2. Verify template file exists
3. Check file permissions

```php
// Check template paths
$paths = $container->get(Paths::class);
echo $paths->path('templates/layout.php');
```

### Variables Not Available in Template

**Cause:** Variables not passed to template renderer

**Solution:**
```php
// Pass variables to template
return $this->templateRenderer->render('template-name', [
    'title' => 'Page Title',
    'user' => $userData,
    'flash' => $flashMessages,
]);
```

## 🗄️ Database Connection Problems

### Connection Refused

**Symptoms:**
- "Connection refused" errors
- Database queries fail

**Solutions:**
1. Check database credentials in `.env`
2. Verify database server is running
3. Check network connectivity

```bash
# Test database connection
mysql -h localhost -u username -p database_name

# Check if MySQL is running
systemctl status mysql
```

### Repository Not Found

**Cause:** Repository not registered in DI container

**Solution:**
```php
// Register repository in module config
RepositoryInterface::class => function (Container $container): RepositoryInterface {
    return new DatabaseRepository(
        $container->get(PDO::class)
    );
},
```

## ⚡ Performance Issues

### Slow Page Load Times

**Causes & Solutions:**

1. **Template Caching Disabled**
   ```php
   // Enable template caching in production
   'cache_enabled' => $_ENV['APP_ENV'] === 'production',
   ```

2. **Too Many Database Queries**
   ```php
   // Use eager loading
   $articles = $repository->findAllWithAuthors();
   ```

3. **Large Log Files**
   ```bash
   # Rotate logs
   composer log:rotate
   ```

## 🚨 Emergency Procedures

### Complete Session Reset

```bash
# Clear all sessions
rm -rf var/sessions/*

# Clear template cache
rm -rf var/cache/templates/*

# Restart web server
sudo systemctl restart apache2
```

### Debug Mode Activation

```bash
# Enable debug mode
echo "APP_DEBUG=true" >> .env
echo "APP_ENV=development" >> .env

# Check logs
tail -f var/logs/app.log
```

### Factory Reset (Development Only)

```bash
# ⚠️ WARNING: This will delete all data!
rm -rf var/cache/* var/logs/* var/sessions/*
composer install --no-dev
php bin/console cache:clear
```

## 📞 Getting Help

1. **Check Logs:** `var/logs/app.log`
2. **Enable Debug Mode:** Set `APP_DEBUG=true`
3. **Review Documentation:** `docs/` directory
4. **Check GitHub Issues:** [HDM Boot Issues](https://github.com/responsive-sk/hdm-boot/issues)

---

**Last Updated:** 2025-06-24  
**Version:** HDM Boot v0.9.0
