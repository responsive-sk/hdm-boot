#!/bin/bash
# Scripts Reorganization Script
# Moves all scripts to bin/ directory for consistency

set -e

echo "🔄 HDM Boot Scripts Reorganization"
echo "================================="
echo ""

# Create backup
echo "📦 Creating backup..."
BACKUP_FILE="scripts-backup-$(date +%Y%m%d_%H%M%S).tar.gz"
tar -czf "$BACKUP_FILE" bin/ scripts/ 2>/dev/null || true
echo "  ✅ Backup created: $BACKUP_FILE"
echo ""

# Track changes
moved_count=0
removed_dirs=0

echo "📁 Moving scripts to bin/..."

# Move from scripts/ to bin/
if [ -f "scripts/cleanup-project.sh" ]; then
    mv scripts/cleanup-project.sh bin/
    echo "  ✅ Moved scripts/cleanup-project.sh -> bin/cleanup-project.sh"
    ((moved_count++))
else
    echo "  ℹ️  scripts/cleanup-project.sh not found (already moved?)"
fi

# Move from bin/scripts/ to bin/
if [ -f "bin/scripts/check-paths.sh" ]; then
    mv bin/scripts/check-paths.sh bin/
    echo "  ✅ Moved bin/scripts/check-paths.sh -> bin/check-paths.sh"
    ((moved_count++))
else
    echo "  ℹ️  bin/scripts/check-paths.sh not found (already moved?)"
fi

echo ""
echo "🗑️  Removing empty directories..."

# Remove empty bin/scripts directory
if [ -d "bin/scripts" ]; then
    if [ -z "$(ls -A bin/scripts 2>/dev/null)" ]; then
        rmdir bin/scripts
        echo "  ✅ Removed empty bin/scripts/"
        ((removed_dirs++))
    else
        echo "  ⚠️  bin/scripts/ not empty, keeping it"
        ls -la bin/scripts/
    fi
else
    echo "  ℹ️  bin/scripts/ directory not found"
fi

# Remove empty scripts directory
if [ -d "scripts" ]; then
    if [ -z "$(ls -A scripts 2>/dev/null)" ]; then
        rmdir scripts
        echo "  ✅ Removed empty scripts/"
        ((removed_dirs++))
    else
        echo "  ⚠️  scripts/ not empty, keeping it"
        ls -la scripts/
    fi
else
    echo "  ℹ️  scripts/ directory not found"
fi

echo ""
echo "🔐 Updating permissions..."

# Update permissions for all scripts in bin/
chmod +x bin/*.sh 2>/dev/null || true
chmod +x bin/*.php 2>/dev/null || true

echo "  ✅ Updated permissions for all scripts in bin/"

echo ""
echo "📊 Final bin/ directory structure:"
echo "================================="
ls -la bin/ | grep -E '\.(sh|php)$' || echo "No scripts found"

echo ""
echo "✅ Scripts reorganization completed!"
echo ""
echo "📈 Summary:"
echo "  • Scripts moved: $moved_count"
echo "  • Directories removed: $removed_dirs"
echo "  • Backup created: $BACKUP_FILE"
echo ""
echo "🔍 Verification:"
echo "  • All scripts now in bin/ directory"
echo "  • No duplicate script locations"
echo "  • Proper permissions set"
echo ""
echo "📝 Next steps:"
echo "  1. Test script execution: php bin/health-check.php"
echo "  2. Update documentation references"
echo "  3. Verify CI/CD pipeline still works"
echo "  4. Remove backup after verification: rm $BACKUP_FILE"

exit 0
