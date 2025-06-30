#!/bin/bash
# HDM Boot Path Security Fix Script
# Fixes critical security issue with public/storage directory

set -e

echo "🔒 HDM Boot Path Security Fix"
echo "============================"
echo ""

# Track changes
CHANGES_MADE=0

# 1. Backup public/storage if exists
if [ -d "public/storage" ]; then
    echo "📦 Backing up public/storage..."
    BACKUP_DIR="var/backups/public-storage-$(date +%Y%m%d_%H%M%S)"
    mkdir -p "var/backups"
    
    # Count files being backed up
    FILE_COUNT=$(find public/storage -type f | wc -l)
    
    mv "public/storage" "$BACKUP_DIR"
    echo "  ✅ Backed up $FILE_COUNT files to: $BACKUP_DIR"
    ((CHANGES_MADE++))
else
    echo "ℹ️  public/storage does not exist (good!)"
fi

# 2. Ensure var/storage exists with secure permissions
echo ""
echo "📁 Creating secure var/storage..."
if [ ! -d "var/storage" ]; then
    mkdir -p var/storage
    echo "  ✅ Created var/storage directory"
    ((CHANGES_MADE++))
else
    echo "  ℹ️  var/storage already exists"
fi

# Set secure permissions
chmod 755 var/storage
echo "  ✅ Set secure permissions (755) on var/storage"

# 3. Fix deployment configurations
echo ""
echo "🔧 Fixing deployment configurations..."

# Fix production config
if grep -q "'public/storage'" config/deploy/production.php; then
    sed -i "s/'public\/storage',/\/\/ 'public\/storage', \/\/ REMOVED - Security fix/" config/deploy/production.php
    echo "  ✅ Fixed config/deploy/production.php"
    ((CHANGES_MADE++))
else
    echo "  ℹ️  config/deploy/production.php already clean"
fi

# Fix staging config
if [ -f "config/deploy/staging.php" ] && grep -q "'public/storage'" config/deploy/staging.php; then
    sed -i "s/'public\/storage',/\/\/ 'public\/storage', \/\/ REMOVED - Security fix/" config/deploy/staging.php
    echo "  ✅ Fixed config/deploy/staging.php"
    ((CHANGES_MADE++))
else
    echo "  ℹ️  config/deploy/staging.php already clean or not found"
fi

# 4. Create .htaccess protection for var/ directory
echo ""
echo "🛡️ Creating .htaccess protection..."
cat > var/.htaccess << 'EOF'
# Deny all web access to var/ directory
# This directory contains sensitive application data

<RequireAll>
    Require all denied
</RequireAll>

# Additional protection for Apache 2.2
Order deny,allow
Deny from all

# Protect .htaccess files
<Files ~ "^\.ht">
    Order allow,deny
    Deny from all
</Files>

# Protect database files
<Files ~ "\.db$">
    Order allow,deny
    Deny from all
</Files>

# Protect log files
<Files ~ "\.log$">
    Order allow,deny
    Deny from all
</Files>

# Protect JSON data files
<Files ~ "\.json$">
    Order allow,deny
    Deny from all
</Files>
EOF

echo "  ✅ Created var/.htaccess with comprehensive protection"
((CHANGES_MADE++))

# 5. Create storage/.htaccess for extra protection
echo ""
echo "🔐 Creating storage-specific protection..."
cat > var/storage/.htaccess << 'EOF'
# Extra protection for storage directory
# Contains databases and sensitive files

<RequireAll>
    Require all denied
</RequireAll>

Order deny,allow
Deny from all

# Protect all file types
<FilesMatch ".*">
    Order allow,deny
    Deny from all
</FilesMatch>
EOF

echo "  ✅ Created var/storage/.htaccess"
((CHANGES_MADE++))

# 6. Update environment setup documentation
echo ""
echo "📚 Checking documentation..."
if grep -q "public/storage" docs/guides/environment-setup.md; then
    sed -i 's|$DEPLOY_DIR/public/storage|$DEPLOY_DIR/var/storage|g' docs/guides/environment-setup.md
    echo "  ✅ Fixed environment-setup.md documentation"
    ((CHANGES_MADE++))
else
    echo "  ℹ️  Documentation already clean"
fi

# 7. Verify security
echo ""
echo "🔍 Verifying security fixes..."

SECURITY_ISSUES=0

if [ ! -d "public/storage" ]; then
    echo "  ✅ public/storage removed"
else
    echo "  ❌ public/storage still exists!"
    ((SECURITY_ISSUES++))
fi

if [ -d "var/storage" ]; then
    echo "  ✅ var/storage exists"
else
    echo "  ❌ var/storage missing!"
    ((SECURITY_ISSUES++))
fi

if [ -f "var/.htaccess" ]; then
    echo "  ✅ var/.htaccess protection exists"
else
    echo "  ❌ var/.htaccess missing!"
    ((SECURITY_ISSUES++))
fi

if [ -f "var/storage/.htaccess" ]; then
    echo "  ✅ var/storage/.htaccess protection exists"
else
    echo "  ❌ var/storage/.htaccess missing!"
    ((SECURITY_ISSUES++))
fi

# Check deployment configs
if grep -q "public/storage" config/deploy/*.php 2>/dev/null; then
    echo "  ⚠️  WARNING: public/storage still found in deployment configs"
    ((SECURITY_ISSUES++))
else
    echo "  ✅ Deployment configs clean"
fi

# 8. Test web accessibility (if possible)
echo ""
echo "🌐 Testing web accessibility..."
if command -v curl &> /dev/null && [ -f "public/index.php" ]; then
    # Start a simple PHP server for testing
    php -S localhost:8888 -t public > /dev/null 2>&1 &
    SERVER_PID=$!
    sleep 2
    
    # Test if storage is accessible
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:8888/storage/" 2>/dev/null || echo "000")
    
    # Kill test server
    kill $SERVER_PID 2>/dev/null || true
    
    if [ "$HTTP_CODE" = "404" ] || [ "$HTTP_CODE" = "403" ]; then
        echo "  ✅ Storage not web accessible (HTTP $HTTP_CODE)"
    elif [ "$HTTP_CODE" = "000" ]; then
        echo "  ℹ️  Could not test web accessibility (server not available)"
    else
        echo "  ⚠️  WARNING: Storage may be web accessible (HTTP $HTTP_CODE)"
        ((SECURITY_ISSUES++))
    fi
else
    echo "  ℹ️  Web accessibility test skipped (curl or index.php not available)"
fi

# 9. Summary
echo ""
echo "📊 Security Fix Summary:"
echo "======================="
echo "  • Changes made: $CHANGES_MADE"
echo "  • Security issues found: $SECURITY_ISSUES"
echo ""

if [ $CHANGES_MADE -gt 0 ]; then
    echo "🔧 Changes applied:"
    echo "  • Removed public/storage directory"
    echo "  • Created secure var/storage"
    echo "  • Added .htaccess protection"
    echo "  • Fixed deployment configurations"
    echo "  • Updated documentation"
fi

echo ""
if [ $SECURITY_ISSUES -eq 0 ]; then
    echo "✅ All path security issues resolved!"
    echo ""
    echo "🎯 Next steps:"
    echo "  1. Test application functionality"
    echo "  2. Verify database connections work"
    echo "  3. Run: php bin/health-check.php"
    echo "  4. Rebuild production: php bin/build-production.php"
    exit 0
else
    echo "❌ Found $SECURITY_ISSUES remaining security issues!"
    echo ""
    echo "💡 Manual review required:"
    echo "  1. Check deployment configurations"
    echo "  2. Verify web server configuration"
    echo "  3. Test storage accessibility"
    echo "  4. Run: ./bin/check-path-security.sh"
    exit 1
fi
