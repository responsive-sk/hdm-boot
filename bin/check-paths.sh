#!/bin/bash

# Path Concatenation Security Checker
# Detects dangerous path concatenation patterns in PHP code

set -e

echo "🔍 Scanning for dangerous path concatenation patterns..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
TOTAL_ISSUES=0
CRITICAL_ISSUES=0

# Function to report issue
report_issue() {
    local severity=$1
    local pattern=$2
    local file=$3
    local line=$4
    local content=$5
    
    echo -e "${RED}[$severity]${NC} $pattern detected in $file:$line"
    echo -e "  ${YELLOW}→${NC} $content"
    echo ""
    
    TOTAL_ISSUES=$((TOTAL_ISSUES + 1))
    if [ "$severity" = "CRITICAL" ]; then
        CRITICAL_ISSUES=$((CRITICAL_ISSUES + 1))
    fi
}

echo "Checking src/ directory..."

# Pattern 1: Variable concatenation with slash
echo "🔍 Checking for: \$var . '/' . \$input patterns..."
while IFS= read -r line; do
    if [[ -n "$line" ]]; then
        file=$(echo "$line" | cut -d: -f1)
        line_num=$(echo "$line" | cut -d: -f2)
        content=$(echo "$line" | cut -d: -f3-)
        report_issue "CRITICAL" "Variable + '/' + Input" "$file" "$line_num" "$content"
    fi
done < <(grep -rn "\$[a-zA-Z_][a-zA-Z0-9_]*\s*\.\s*'/'" src/ 2>/dev/null || true)

# Pattern 2: __DIR__ concatenation
echo "🔍 Checking for: __DIR__ . '/' . \$input patterns..."
while IFS= read -r line; do
    if [[ -n "$line" ]]; then
        file=$(echo "$line" | cut -d: -f1)
        line_num=$(echo "$line" | cut -d: -f2)
        content=$(echo "$line" | cut -d: -f3-)
        report_issue "CRITICAL" "__DIR__ + '/' + Input" "$file" "$line_num" "$content"
    fi
done < <(grep -rn "__DIR__\s*\.\s*'/'" src/ 2>/dev/null || true)

# Pattern 3: DIRECTORY_SEPARATOR usage
echo "🔍 Checking for: DIRECTORY_SEPARATOR patterns..."
while IFS= read -r line; do
    if [[ -n "$line" ]]; then
        file=$(echo "$line" | cut -d: -f1)
        line_num=$(echo "$line" | cut -d: -f2)
        content=$(echo "$line" | cut -d: -f3-)
        report_issue "HIGH" "DIRECTORY_SEPARATOR usage" "$file" "$line_num" "$content"
    fi
done < <(grep -rn "DIRECTORY_SEPARATOR" src/ 2>/dev/null || true)

# Pattern 4: realpath() usage (potentially dangerous)
echo "🔍 Checking for: realpath() usage..."
while IFS= read -r line; do
    if [[ -n "$line" ]]; then
        file=$(echo "$line" | cut -d: -f1)
        line_num=$(echo "$line" | cut -d: -f2)
        content=$(echo "$line" | cut -d: -f3-)
        report_issue "MEDIUM" "realpath() usage" "$file" "$line_num" "$content"
    fi
done < <(grep -rn "realpath(" src/ 2>/dev/null || true)

# Pattern 5: dirname() with concatenation
echo "🔍 Checking for: dirname() + concatenation patterns..."
while IFS= read -r line; do
    if [[ -n "$line" ]]; then
        file=$(echo "$line" | cut -d: -f1)
        line_num=$(echo "$line" | cut -d: -f2)
        content=$(echo "$line" | cut -d: -f3-)
        report_issue "MEDIUM" "dirname() + concatenation" "$file" "$line_num" "$content"
    fi
done < <(grep -rn "dirname([^)]*)\s*\.\s*'/'" src/ 2>/dev/null || true)

# Check for proper Paths usage
echo "🔍 Checking for proper Paths service usage..."
PATHS_USAGE=$(grep -r "Paths::" src/ | wc -l)
PATHS_IMPORTS=$(grep -r "use.*Paths" src/ | wc -l)

echo ""
echo "📊 SCAN RESULTS:"
echo "=================="
echo -e "Total issues found: ${RED}$TOTAL_ISSUES${NC}"
echo -e "Critical issues: ${RED}$CRITICAL_ISSUES${NC}"
echo -e "Paths service imports: ${GREEN}$PATHS_IMPORTS${NC}"
echo -e "Paths service usage: ${GREEN}$PATHS_USAGE${NC}"

# Recommendations
echo ""
echo "💡 RECOMMENDATIONS:"
echo "==================="

if [ $CRITICAL_ISSUES -gt 0 ]; then
    echo -e "${RED}❌ CRITICAL: $CRITICAL_ISSUES path concatenation vulnerabilities found!${NC}"
    echo "   → Replace with Paths service immediately"
    echo "   → Example: \$this->paths->getPath(\$baseDir, \$relativePath)"
fi

if [ $TOTAL_ISSUES -gt 0 ]; then
    echo -e "${YELLOW}⚠️  WARNING: $TOTAL_ISSUES total path-related issues found${NC}"
    echo "   → Review each instance for security implications"
    echo "   → Consider using Paths service for consistency"
fi

if [ $PATHS_USAGE -lt 5 ]; then
    echo -e "${YELLOW}⚠️  LOW PATHS USAGE: Only $PATHS_USAGE instances found${NC}"
    echo "   → Consider increasing Paths service adoption"
    echo "   → Add Paths to more classes via dependency injection"
fi

# Security recommendations
echo ""
echo "🔒 SECURITY CHECKLIST:"
echo "======================"
echo "□ All user input paths validated"
echo "□ No string concatenation for file paths"  
echo "□ Paths service used consistently"
echo "□ Path traversal tests implemented"
echo "□ File serving endpoints secured"

# Exit with error if critical issues found
if [ $CRITICAL_ISSUES -gt 0 ]; then
    echo ""
    echo -e "${RED}🚨 SECURITY ALERT: Critical path vulnerabilities detected!${NC}"
    echo -e "${RED}   Build should FAIL until these are fixed.${NC}"
    exit 1
fi

if [ $TOTAL_ISSUES -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ SUCCESS: No dangerous path patterns detected!${NC}"
    exit 0
else
    echo ""
    echo -e "${YELLOW}⚠️  WARNING: $TOTAL_ISSUES issues found. Review recommended.${NC}"
    exit 0
fi
