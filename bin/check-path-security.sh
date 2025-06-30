#!/bin/bash
# HDM Boot Path Security Check Script
# Verifies that storage and cache directories are in correct locations

echo "🔍 HDM Boot Path Security Check"
echo "==============================="
echo ""

ISSUES=0
WARNINGS=0

# Check 1: public/storage should NOT exist
echo "🔍 Checking public/storage..."
if [ -d "public/storage" ]; then
    echo "❌ CRITICAL: public/storage exists (SECURITY RISK!)"
    echo "   💡 Storage should be in var/storage, not public/"
    ((ISSUES++))
else
    echo "✅ public/storage does not exist"
fi

# Check 2: var/storage should exist
echo ""
echo "🔍 Checking var/storage..."
if [ -d "var/storage" ]; then
    echo "✅ var/storage exists"
    
    # Check permissions
    PERMS=$(stat -c "%a" var/storage 2>/dev/null || echo "unknown")
    if [ "$PERMS" = "755" ] || [ "$PERMS" = "775" ]; then
        echo "✅ var/storage permissions OK ($PERMS)"
    else
        echo "⚠️  WARNING: var/storage permissions may be incorrect ($PERMS)"
        ((WARNINGS++))
    fi
else
    echo "❌ WARNING: var/storage missing"
    ((ISSUES++))
fi

# Check 3: src/cache should NOT exist
echo ""
echo "🔍 Checking src/cache..."
if [ -d "src/cache" ]; then
    echo "❌ CRITICAL: src/cache exists (WRONG LOCATION!)"
    echo "   💡 Cache should be in var/cache, not src/"
    ((ISSUES++))
else
    echo "✅ src/cache does not exist"
fi

# Check 4: var/cache should exist
echo ""
echo "🔍 Checking var/cache..."
if [ -d "var/cache" ]; then
    echo "✅ var/cache exists"
    
    # Check for translations subdirectory
    if [ -d "var/cache/translations" ]; then
        echo "✅ var/cache/translations exists"
    else
        echo "⚠️  WARNING: var/cache/translations missing"
        ((WARNINGS++))
    fi
else
    echo "❌ WARNING: var/cache missing"
    ((ISSUES++))
fi

# Check 5: var/.htaccess protection
echo ""
echo "🔍 Checking var/.htaccess protection..."
if [ -f "var/.htaccess" ]; then
    echo "✅ var/.htaccess protection exists"
    
    # Check if it contains proper protection
    if grep -q "Deny from all" var/.htaccess || grep -q "Require all denied" var/.htaccess; then
        echo "✅ var/.htaccess contains access denial"
    else
        echo "⚠️  WARNING: var/.htaccess may not contain proper protection"
        ((WARNINGS++))
    fi
else
    echo "⚠️  WARNING: var/.htaccess missing"
    echo "   💡 Create protection: echo 'Deny from all' > var/.htaccess"
    ((WARNINGS++))
fi

# Check 6: Deployment configs
echo ""
echo "🔍 Checking deployment configurations..."
if grep -q "public/storage" config/deploy/*.php 2>/dev/null; then
    echo "❌ WARNING: public/storage found in deployment configs"
    echo "   💡 Remove public/storage from create_directories"
    ((WARNINGS++))
else
    echo "✅ Deployment configs clean"
fi

# Check 7: Web accessibility test (if possible)
echo ""
echo "🔍 Testing web accessibility..."
if command -v curl &> /dev/null && [ -f "public/index.php" ]; then
    # Start a simple PHP server for testing
    echo "   🌐 Starting test server..."
    php -S localhost:8889 -t public > /dev/null 2>&1 &
    SERVER_PID=$!
    sleep 2
    
    # Test if storage is accessible
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:8889/storage/" 2>/dev/null || echo "000")
    
    # Kill test server
    kill $SERVER_PID 2>/dev/null || true
    
    if [ "$HTTP_CODE" = "404" ] || [ "$HTTP_CODE" = "403" ]; then
        echo "✅ Storage not web accessible (HTTP $HTTP_CODE)"
    elif [ "$HTTP_CODE" = "000" ]; then
        echo "ℹ️  Could not test web accessibility (server not available)"
    else
        echo "❌ CRITICAL: Storage may be web accessible (HTTP $HTTP_CODE)"
        ((ISSUES++))
    fi
else
    echo "ℹ️  Web accessibility test skipped (curl or index.php not available)"
fi

# Check 8: File structure validation
echo ""
echo "🔍 Validating file structure..."

EXPECTED_DIRS=(
    "var/storage"
    "var/cache" 
    "var/logs"
    "var/sessions"
)

FORBIDDEN_DIRS=(
    "public/storage"
    "public/cache"
    "src/cache"
    "src/storage"
)

echo "   📁 Expected directories:"
for dir in "${EXPECTED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "   ✅ $dir"
    else
        echo "   ❌ $dir (missing)"
        ((WARNINGS++))
    fi
done

echo ""
echo "   🚫 Forbidden directories:"
for dir in "${FORBIDDEN_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "   ❌ $dir (should not exist!)"
        ((ISSUES++))
    else
        echo "   ✅ $dir (correctly absent)"
    fi
done

# Summary
echo ""
echo "📊 Security Check Summary:"
echo "========================="
echo "  • Critical issues: $ISSUES"
echo "  • Warnings: $WARNINGS"
echo ""

if [ $ISSUES -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo "🎉 Perfect! All path security checks passed!"
    echo ""
    echo "✅ Your HDM Boot installation is secure:"
    echo "  • Storage is properly located in var/"
    echo "  • No sensitive directories in public/"
    echo "  • Proper access protection in place"
    exit 0
elif [ $ISSUES -eq 0 ]; then
    echo "✅ Good! No critical security issues found."
    echo "⚠️  However, $WARNINGS warnings need attention."
    echo ""
    echo "💡 Recommended actions:"
    echo "  • Review warnings above"
    echo "  • Consider running: ./bin/fix-path-security.sh"
    exit 0
else
    echo "❌ SECURITY ISSUES FOUND!"
    echo "🚨 $ISSUES critical issues and $WARNINGS warnings detected."
    echo ""
    echo "🔧 IMMEDIATE ACTION REQUIRED:"
    echo "  1. Run: ./bin/fix-path-security.sh"
    echo "  2. Review all issues above"
    echo "  3. Test application after fixes"
    echo "  4. Re-run this check to verify"
    echo ""
    echo "⚠️  DO NOT DEPLOY until all issues are resolved!"
    exit 1
fi
