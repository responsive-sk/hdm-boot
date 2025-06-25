#!/bin/bash

# HDM Boot Production Permissions Fix Script

echo "🔧 Fixing HDM Boot permissions for production..."

# Create directories if they don't exist
mkdir -p var/logs
mkdir -p var/sessions  
mkdir -p var/cache
mkdir -p storage

# Fix directory permissions
echo "📁 Setting directory permissions..."
chmod 755 var/
chmod 755 var/logs/
chmod 755 var/sessions/
chmod 755 var/cache/
chmod 755 storage/

# Fix file permissions
echo "📄 Setting file permissions..."
chmod 644 var/logs/*.log 2>/dev/null || true
chmod 644 storage/*.sqlite 2>/dev/null || true

# Set ownership to web server user
echo "👤 Setting ownership..."
if [ "$EUID" -eq 0 ]; then
    # Running as root, can change ownership
    chown -R www-data:www-data var/
    chown -R www-data:www-data storage/
    echo "✅ Ownership set to www-data"
else
    echo "⚠️  Not running as root, skipping ownership change"
    echo "   Run with sudo to change ownership to www-data"
fi

# Create log files if they don't exist
echo "📝 Creating log files..."
touch var/logs/app.log
touch var/logs/security.log
touch var/logs/error.log

# Make log files writable
chmod 666 var/logs/*.log

# Check database file
if [ -f "storage/database.sqlite" ]; then
    echo "🗄️  Database file exists"
    chmod 644 storage/database.sqlite
    
    # Test database integrity
    if command -v sqlite3 &> /dev/null; then
        echo "🔍 Testing database integrity..."
        if sqlite3 storage/database.sqlite "PRAGMA integrity_check;" | grep -q "ok"; then
            echo "✅ Database integrity OK"
        else
            echo "❌ Database integrity check failed!"
        fi
    fi
else
    echo "⚠️  Database file not found: storage/database.sqlite"
fi

# Check PHP extensions
echo "🔍 Checking PHP extensions..."
php -m | grep -E "(sqlite3|pdo_sqlite|session)" || echo "⚠️  Some PHP extensions may be missing"

# Final permissions check
echo "📋 Final permissions check:"
ls -la var/
ls -la storage/ 2>/dev/null || echo "storage/ directory not found"

echo "✅ Permissions fix completed!"
echo ""
echo "🚀 If issues persist, check:"
echo "   1. Web server error logs: /var/log/apache2/error.log"
echo "   2. PHP-FPM logs: /var/log/php*-fpm.log"
echo "   3. Application logs: var/logs/"
