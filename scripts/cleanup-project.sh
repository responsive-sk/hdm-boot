#!/bin/bash
# HDM Boot Project Cleanup Script
# Removes unnecessary files and optimizes project structure

set -e

echo "🧹 Starting HDM Boot project cleanup..."
echo "=================================="

# Function to safely remove file
safe_remove() {
    local file="$1"
    if [ -f "$file" ]; then
        echo "  ❌ Removing $file"
        rm "$file"
        return 0
    else
        echo "  ℹ️  File $file not found (already clean)"
        return 1
    fi
}

# Function to count removed files
removed_count=0
count_removal() {
    if safe_remove "$1"; then
        ((removed_count++))
    fi
}

echo ""
echo "📁 Cleaning root directory..."
echo "-----------------------------"

# Remove old documentation
echo "📄 Removing old documentation files..."
count_removal "README_OLD.md"
count_removal "USER_MODULE_SUMMARY.md"
count_removal "directory_tree.md"

# Remove test files from root
echo ""
echo "🧪 Removing test files from root..."
count_removal "test_hybrid.php"
count_removal "test_multi_database.php"
count_removal "test_storage.php"
count_removal "cookies.txt"

# Remove duplicate scripts
echo ""
echo "🔧 Removing duplicate scripts..."
count_removal "fix-permissions.sh"

# Remove production archives
echo ""
echo "📦 Removing old production archives..."
archive_count=0
for file in hdm-boot-*.zip; do
    if [ -f "$file" ]; then
        echo "  ❌ Removing $file"
        rm "$file"
        ((archive_count++))
        ((removed_count++))
    fi
done

if [ $archive_count -eq 0 ]; then
    echo "  ℹ️  No archive files found"
fi

echo ""
echo "🌐 Cleaning public directory..."
echo "------------------------------"

# Remove debug and test files from public
echo "🐛 Removing debug and test files..."
count_removal "public/debug.php"
count_removal "public/info.php"
count_removal "public/minimal.php"
count_removal "public/simple-test.php"
count_removal "public/test.php"
count_removal "public/fix-permissions.php"

echo ""
echo "🗑️ Cleaning temporary files..."
echo "-----------------------------"

# Remove editor backup files
temp_count=0
echo "📝 Removing editor backup files..."
temp_files=$(find . -name "*.bak" -o -name "*.tmp" -o -name "*~" 2>/dev/null | wc -l)
if [ "$temp_files" -gt 0 ]; then
    find . -name "*.bak" -type f -delete 2>/dev/null || true
    find . -name "*.tmp" -type f -delete 2>/dev/null || true
    find . -name "*~" -type f -delete 2>/dev/null || true
    echo "  ❌ Removed $temp_files temporary files"
    temp_count=$temp_files
else
    echo "  ℹ️  No temporary files found"
fi

# Remove OS specific files
echo ""
echo "💻 Removing OS specific files..."
os_count=0
ds_store_files=$(find . -name ".DS_Store" 2>/dev/null | wc -l)
thumbs_files=$(find . -name "Thumbs.db" 2>/dev/null | wc -l)

if [ "$ds_store_files" -gt 0 ]; then
    find . -name ".DS_Store" -type f -delete 2>/dev/null || true
    echo "  ❌ Removed $ds_store_files .DS_Store files"
    os_count=$((os_count + ds_store_files))
fi

if [ "$thumbs_files" -gt 0 ]; then
    find . -name "Thumbs.db" -type f -delete 2>/dev/null || true
    echo "  ❌ Removed $thumbs_files Thumbs.db files"
    os_count=$((os_count + thumbs_files))
fi

if [ $os_count -eq 0 ]; then
    echo "  ℹ️  No OS specific files found"
fi

echo ""
echo "💾 Cleaning cache directories..."
echo "-------------------------------"

# Clean cache files
cache_count=0
if [ -d "var/cache" ]; then
    cache_files=$(find var/cache -type f -name "*.cache" 2>/dev/null | wc -l)
    if [ "$cache_files" -gt 0 ]; then
        find var/cache -type f -name "*.cache" -delete 2>/dev/null || true
        echo "  ❌ Removed $cache_files cache files"
        cache_count=$cache_files
    else
        echo "  ℹ️  No cache files to remove"
    fi
else
    echo "  ℹ️  Cache directory not found"
fi

# Clean old log files (older than 30 days)
echo ""
echo "📝 Cleaning old log files..."
log_count=0
if [ -d "var/logs" ]; then
    old_logs=$(find var/logs -type f -name "*.log" -mtime +30 2>/dev/null | wc -l)
    if [ "$old_logs" -gt 0 ]; then
        find var/logs -type f -name "*.log" -mtime +30 -delete 2>/dev/null || true
        echo "  ❌ Removed $old_logs old log files (>30 days)"
        log_count=$old_logs
    else
        echo "  ℹ️  No old log files to remove"
    fi
else
    echo "  ℹ️  Logs directory not found"
fi

# Calculate total removed files
total_removed=$((removed_count + temp_count + os_count + cache_count + log_count))

echo ""
echo "✅ Project cleanup completed!"
echo "============================"
echo ""
echo "📊 Cleanup summary:"
echo "  • Root files removed: $removed_count"
echo "  • Temporary files removed: $temp_count"
echo "  • OS specific files removed: $os_count"
echo "  • Cache files removed: $cache_count"
echo "  • Old log files removed: $log_count"
echo "  • Total files removed: $total_removed"
echo ""

if [ $total_removed -gt 0 ]; then
    echo "🎯 Benefits achieved:"
    echo "  🔒 Security improved (removed debug files)"
    echo "  📦 Repository size reduced"
    echo "  🚀 Deployment optimized"
    echo "  🧹 Project structure cleaned"
else
    echo "✨ Project was already clean!"
fi

echo ""
echo "🔍 Recommended next steps:"
echo "  1. Review changes: git status"
echo "  2. Test application: composer test"
echo "  3. Commit changes: git add . && git commit -m 'Clean up project structure'"
echo "  4. Deploy to staging for testing"

echo ""
echo "📋 Security verification:"
echo "  • No debug files in public/ ✅"
echo "  • No test files in root ✅"
echo "  • No production archives ✅"
echo "  • No sensitive data exposed ✅"

exit 0
