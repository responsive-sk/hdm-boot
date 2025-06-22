# 🚀 HDM Boot - GitHub Export Guide

Complete guide for exporting HDM Boot framework to GitHub and setting up the ecosystem.

## 📋 Prerequisites

### GitHub Account Setup
- GitHub account with organization access
- SSH key configured for GitHub
- Git configured with your credentials

### Local Repository Status
```bash
# Verify clean working directory
git status
# Should show: "nothing to commit, working tree clean"

# Verify latest commit
git log --oneline -1
# Should show: HDM Boot v0.9.0 commit
```

## 🎯 Step 1: Create GitHub Repository

### Option A: GitHub Web Interface
1. Go to _https://github.com/responsive-sk_
2. Click "New repository"
3. Repository name: `hdm-boot`
4. Description: `HDM Boot - Hexagonal + DDD + Modular Monolith Architecture Framework`
5. Public repository ✅
6. **DO NOT** initialize with README (we have our own)
7. **DO NOT** add .gitignore (we have our own)
8. Click "Create repository"

### Option B: GitHub CLI (if installed)
```bash
# Create repository via CLI
gh repo create responsive-sk/hdm-boot \
  --public \
  --description "HDM Boot - Hexagonal + DDD + Modular Monolith Architecture Framework" \
  --homepage "https://responsive.sk"
```

## 🔗 Step 2: Add GitHub Remote

```bash
# Add GitHub as origin remote
git remote add origin git@github.com:responsive-sk/hdm-boot.git

# Or using HTTPS
git remote add origin https://github.com/responsive-sk/hdm-boot.git

# Verify remote
git remote -v
# Should show:
# origin  git@github.com:responsive-sk/hdm-boot.git (fetch)
# origin  git@github.com:responsive-sk/hdm-boot.git (push)
```

## 🚀 Step 3: Push to GitHub

```bash
# Push main branch to GitHub
git push -u origin main

# Verify push success
git log --oneline -1
# Should show the same commit hash as local
```

## 🏷️ Step 4: Create Release Tag

```bash
# Create v0.9.0 tag
git tag -a v0.9.0 -m "HDM Boot v0.9.0 - Release Candidate

🎯 Triple Architecture Framework (Hexagonal + DDD + MMA)
🚀 Production-ready with comprehensive testing
📚 Complete documentation and deployment guides
🔒 Enterprise-grade security features

Ready for production testing → v1.0.0"

# Push tag to GitHub
git push origin v0.9.0

# Verify tag
git tag -l
# Should show: v0.9.0
```

## 📦 Step 5: GitHub Repository Configuration

### Repository Settings
1. Go to repository Settings
2. **General**:
   - Features: Enable Issues, Wiki, Projects
   - Pull Requests: Enable "Allow squash merging"
3. **Pages** (optional):
   - Source: Deploy from branch `main`
   - Folder: `/docs`
4. **Security**:
   - Enable "Dependency graph"
   - Enable "Dependabot alerts"

### Repository Topics
Add these topics to help discovery:
```
php, framework, hexagonal-architecture, ddd, modular-monolith, 
enterprise, production-ready, slim4, composer, responsive-sk
```

### Repository Description
```
🚀 HDM Boot - Hexagonal + DDD + Modular Monolith Architecture Framework for PHP 8.3+
```

## 🔧 Step 6: GitHub Actions (CI/CD)

Create `.github/workflows/ci.yml`:
```yaml
name: HDM Boot CI

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: [8.3, 8.4]
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        extensions: mbstring, xml, ctype, json, tokenizer, openssl
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run PHPStan
      run: composer stan
    
    - name: Run Code Style Check
      run: PHP_CS_FIXER_IGNORE_ENV=1 composer cs-check
    
    - name: Run Tests
      run: composer test
    
    - name: Run Blog Module Tests
      run: composer test:blog
```

## 📄 Step 7: Update README for GitHub

Add GitHub-specific badges to README.md:
```markdown
# 🚀 HDM Boot Framework

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Tests](https://github.com/responsive-sk/hdm-boot/workflows/HDM%20Boot%20CI/badge.svg)](https://github.com/responsive-sk/hdm-boot/actions)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](https://phpstan.org/)

**H**exagonal + **D**DD + **M**MA (Modular Monolith Architecture)
```

## 🌟 Step 8: Create GitHub Release

### Via GitHub Web Interface
1. Go to repository → Releases
2. Click "Create a new release"
3. Tag: `v0.9.0`
4. Title: `HDM Boot v0.9.0 - Release Candidate`
5. Description:
```markdown
## 🎯 HDM Boot v0.9.0 - Release Candidate

### Triple Architecture Framework
**H**exagonal + **D**DD + **M**MA (Modular Monolith Architecture)

### ✨ Features
- 🏗️ **Triple Architecture** - Hexagonal + DDD + Modular Monolith
- 🧪 **39 Tests** - Blog module with comprehensive testing
- 🔒 **Type Safety** - PHPStan level max with 0 errors
- 📦 **Production Ready** - Complete deployment guide
- 🛡️ **Security** - Path safety and key generation
- ⚡ **Performance** - Optimized for production

### 📚 Documentation
- [Production Deployment Guide](docs/DEPLOYMENT.md)
- [Testing Framework](src/Modules/Optional/Blog/TESTING.md)
- [Architecture Overview](README.md#hdm-architecture)

### 🚀 Quick Start
```bash
composer create-project responsive-sk/hdm-boot my-app
cd my-app
php bin/generate-keys.php
composer deploy:prod
```

### 🎯 Next Steps
Ready for production testing and community feedback → v1.0.0
```

6. Check "This is a pre-release" ✅
7. Click "Publish release"

### Via GitHub CLI
```bash
gh release create v0.9.0 \
  --title "HDM Boot v0.9.0 - Release Candidate" \
  --notes-file release-notes.md \
  --prerelease
```

## 📦 Step 9: Packagist Registration

### Register on Packagist
1. Go to https://packagist.org
2. Login with GitHub account
3. Click "Submit Package"
4. Repository URL: `https://github.com/responsive-sk/hdm-boot`
5. Click "Check"
6. If validation passes, click "Submit"

### Auto-update Hook
1. Go to package page on Packagist
2. Click "Settings"
3. Copy the webhook URL
4. Go to GitHub repository → Settings → Webhooks
5. Add webhook with Packagist URL
6. Content type: `application/json`
7. Events: "Just the push event"

## 🔄 Step 10: Ecosystem Setup

### Create Additional Repositories
```bash
# Blog Module (separate package)
gh repo create responsive-sk/hdm-boot-blog \
  --public \
  --description "HDM Boot Blog Module - Optional blog functionality"

# Session Package (already exists)
# responsive-sk/slim4-session

# Paths Package (already exists)  
# responsive-sk/slim4-paths
```

### Update composer.json for Packagist
```json
{
    "name": "responsive-sk/hdm-boot",
    "description": "HDM Boot - Hexagonal + DDD + Modular Monolith Architecture Framework",
    "keywords": ["php", "framework", "hexagonal", "ddd", "modular-monolith"],
    "homepage": "https://github.com/responsive-sk/hdm-boot",
    "license": "MIT",
    "authors": [
        {
            "name": "HDM Boot Team",
            "homepage": "https://responsive.sk"
        }
    ],
    "support": {
        "issues": "https://github.com/responsive-sk/hdm-boot/issues",
        "source": "https://github.com/responsive-sk/hdm-boot"
    }
}
```

## ✅ Verification Checklist

- [ ] Repository created on GitHub
- [ ] Code pushed to main branch
- [ ] v0.9.0 tag created and pushed
- [ ] GitHub release published
- [ ] Repository configured (topics, description)
- [ ] CI/CD workflow added
- [ ] README updated with badges
- [ ] Packagist package registered
- [ ] Auto-update webhook configured
- [ ] Installation tested: `composer create-project responsive-sk/hdm-boot`

## 🎉 Success!

HDM Boot is now available on GitHub and Packagist:
- **GitHub**: https://github.com/responsive-sk/hdm-boot
- **Packagist**: https://packagist.org/packages/responsive-sk/hdm-boot

### Installation
```bash
composer create-project responsive-sk/hdm-boot my-project
```

**HDM Boot is ready for the world!** 🌍
