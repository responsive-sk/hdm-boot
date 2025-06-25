#!/bin/bash

# HDM Boot Production Permissions Fix Script
# Uses strict permissions (755/644) by default, with option for shared hosting

echo "🔧 Fixing HDM Boot permissions..."

# Check for shared hosting mode
SHARED_HOSTING=${1:-"false"}
if [ "$SHARED_HOSTING" = "shared" ] || [ "$SHARED_HOSTING" = "relaxed" ]; then
    echo "🏠 Shared hosting mode - using relaxed permissions (777/666)"
    DIR_PERM=777
    FILE_PERM=666
    LOG_PERM=666
else
    echo "🏢 Production mode - using strict permissions (755/644)"
    DIR_PERM=755
    FILE_PERM=644
    LOG_PERM=666  # Logs need write access
fi

# Create directories if they don't exist
echo "📁 Creating system directories..."
mkdir -p var/logs
mkdir -p var/sessions
mkdir -p var/cache
mkdir -p storage

# Fix directory permissions
echo "📁 Setting directory permissions to ${DIR_PERM}..."
chmod $DIR_PERM var/
chmod $DIR_PERM var/logs/
chmod $DIR_PERM var/sessions/
chmod $DIR_PERM var/cache/
chmod $DIR_PERM storage/

# Fix file permissions
echo "📄 Setting file permissions to ${FILE_PERM}..."
chmod $FILE_PERM var/logs/*.log 2>/dev/null || true
chmod $FILE_PERM storage/*.sqlite 2>/dev/null || true

# Special permissions for log files (need write access)
echo "📝 Setting log file permissions to ${LOG_PERM}..."
chmod $LOG_PERM var/logs/*.log 2>/dev/null || true

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
touch var/logs/debug.log

# Make log files writable
chmod $LOG_PERM var/logs/*.log

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
echo "📋 Permission Summary:"
echo "   • Directories: ${DIR_PERM} ($([ $DIR_PERM -eq 755 ] && echo "strict" || echo "relaxed"))"
echo "   • Files: ${FILE_PERM} ($([ $FILE_PERM -eq 644 ] && echo "strict" || echo "relaxed"))"
echo "   • Logs: ${LOG_PERM} (writable)"
echo ""
echo "🔧 Usage:"
echo "   • Production: ./bin/fix-permissions.sh"
echo "   • Shared hosting: ./bin/fix-permissions.sh shared"
echo ""
echo "🚀 If issues persist, check:"
echo "   1. Web server error logs: /var/log/apache2/error.log"
echo "   2. PHP-FPM logs: /var/log/php*-fpm.log"
echo "   3. Application logs: var/logs/"
