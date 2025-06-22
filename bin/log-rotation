#!/bin/bash

# MVA Bootstrap Core - Log Rotation Script
# This script should be run via cron for automatic log management
# 
# Recommended cron schedule:
# # Daily log cleanup (keep 30 days)
# 0 2 * * * /path/to/bootstrap/scripts/log-rotation.sh cleanup
# 
# # Weekly compression (compress files older than 7 days)
# 0 3 * * 0 /path/to/bootstrap/scripts/log-rotation.sh compress

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_PATH="$PROJECT_ROOT/var/logs"
RETENTION_DAYS=30
COMPRESSION_DAYS=7
MAX_LOG_SIZE_MB=50

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Check if log directory exists
check_log_directory() {
    if [ ! -d "$LOG_PATH" ]; then
        error "Log directory does not exist: $LOG_PATH"
        exit 1
    fi
}

# Get disk usage in MB
get_disk_usage() {
    if [ -d "$LOG_PATH" ]; then
        du -sm "$LOG_PATH" 2>/dev/null | cut -f1 || echo "0"
    else
        echo "0"
    fi
}

# Clean up old log files
cleanup_logs() {
    log "Starting log cleanup (keeping $RETENTION_DAYS days)..."
    
    local files_removed=0
    local bytes_freed=0
    local cutoff_date=$(date -d "$RETENTION_DAYS days ago" +%s)
    
    for file in "$LOG_PATH"/*.log*; do
        if [ -f "$file" ]; then
            local file_date=$(stat -c %Y "$file" 2>/dev/null || echo "0")
            
            if [ "$file_date" -lt "$cutoff_date" ]; then
                local file_size=$(stat -c %s "$file" 2>/dev/null || echo "0")
                local filename=$(basename "$file")
                
                if rm "$file" 2>/dev/null; then
                    files_removed=$((files_removed + 1))
                    bytes_freed=$((bytes_freed + file_size))
                    log "Removed: $filename ($(echo "scale=2; $file_size/1024/1024" | bc -l) MB)"
                else
                    error "Failed to remove: $filename"
                fi
            fi
        fi
    done
    
    if [ $files_removed -gt 0 ]; then
        local mb_freed=$(echo "scale=2; $bytes_freed/1024/1024" | bc -l)
        success "Cleanup completed: $files_removed files removed, ${mb_freed} MB freed"
    else
        log "No old files to remove"
    fi
}

# Compress old log files
compress_logs() {
    log "Starting log compression (files older than $COMPRESSION_DAYS days)..."
    
    local files_compressed=0
    local bytes_saved=0
    local cutoff_date=$(date -d "$COMPRESSION_DAYS days ago" +%s)
    
    for file in "$LOG_PATH"/*.log; do
        if [ -f "$file" ] && [[ ! "$file" =~ \.gz$ ]]; then
            local file_date=$(stat -c %Y "$file" 2>/dev/null || echo "0")
            
            if [ "$file_date" -lt "$cutoff_date" ]; then
                local original_size=$(stat -c %s "$file" 2>/dev/null || echo "0")
                local filename=$(basename "$file")
                
                if gzip "$file" 2>/dev/null; then
                    local compressed_size=$(stat -c %s "${file}.gz" 2>/dev/null || echo "0")
                    local saved=$((original_size - compressed_size))
                    
                    files_compressed=$((files_compressed + 1))
                    bytes_saved=$((bytes_saved + saved))
                    
                    local compression_ratio=$(echo "scale=1; $saved*100/$original_size" | bc -l)
                    log "Compressed: $filename (${compression_ratio}% reduction)"
                else
                    error "Failed to compress: $filename"
                fi
            fi
        fi
    done
    
    if [ $files_compressed -gt 0 ]; then
        local mb_saved=$(echo "scale=2; $bytes_saved/1024/1024" | bc -l)
        success "Compression completed: $files_compressed files compressed, ${mb_saved} MB saved"
    else
        log "No files to compress"
    fi
}

# Check for large log files
check_large_files() {
    log "Checking for large log files (>${MAX_LOG_SIZE_MB}MB)..."
    
    local large_files=0
    
    for file in "$LOG_PATH"/*.log; do
        if [ -f "$file" ]; then
            local file_size_mb=$(echo "scale=2; $(stat -c %s "$file" 2>/dev/null || echo "0")/1024/1024" | bc -l)
            local size_check=$(echo "$file_size_mb > $MAX_LOG_SIZE_MB" | bc -l)
            
            if [ "$size_check" -eq 1 ]; then
                warning "Large file detected: $(basename "$file") (${file_size_mb} MB)"
                large_files=$((large_files + 1))
            fi
        fi
    done
    
    if [ $large_files -eq 0 ]; then
        success "No large files detected"
    else
        warning "Found $large_files large files - consider more frequent rotation"
    fi
}

# Show statistics
show_stats() {
    log "Log directory statistics:"
    
    local total_files=$(find "$LOG_PATH" -name "*.log*" -type f 2>/dev/null | wc -l)
    local total_size_mb=$(get_disk_usage)
    
    echo "  Total files: $total_files"
    echo "  Total size: ${total_size_mb} MB"
    
    # Count by type
    local security_files=$(find "$LOG_PATH" -name "*security*" -type f 2>/dev/null | wc -l)
    local performance_files=$(find "$LOG_PATH" -name "*performance*" -type f 2>/dev/null | wc -l)
    local debug_files=$(find "$LOG_PATH" -name "*debug*" -type f 2>/dev/null | wc -l)
    local error_files=$(find "$LOG_PATH" -name "*error*" -type f 2>/dev/null | wc -l)
    
    echo "  Security logs: $security_files files"
    echo "  Performance logs: $performance_files files"
    echo "  Debug logs: $debug_files files"
    echo "  Error logs: $error_files files"
    
    # Disk usage warning
    if [ "$total_size_mb" -gt 100 ]; then
        warning "High disk usage: ${total_size_mb} MB"
    fi
}

# Health check
health_check() {
    log "Performing log rotation health check..."
    
    check_log_directory
    show_stats
    check_large_files
    
    success "Health check completed"
}

# Main execution
main() {
    local command="${1:-help}"
    
    case "$command" in
        "cleanup")
            check_log_directory
            cleanup_logs
            ;;
        "compress")
            check_log_directory
            compress_logs
            ;;
        "stats")
            check_log_directory
            show_stats
            ;;
        "health")
            health_check
            ;;
        "full")
            check_log_directory
            cleanup_logs
            compress_logs
            check_large_files
            show_stats
            ;;
        "help"|*)
            echo "MVA Bootstrap Core - Log Rotation Script"
            echo ""
            echo "Usage: $0 [command]"
            echo ""
            echo "Commands:"
            echo "  cleanup    Clean up old log files (older than $RETENTION_DAYS days)"
            echo "  compress   Compress old log files (older than $COMPRESSION_DAYS days)"
            echo "  stats      Show log directory statistics"
            echo "  health     Perform health check"
            echo "  full       Run cleanup, compression, and health check"
            echo "  help       Show this help message"
            echo ""
            echo "Recommended cron schedule:"
            echo "  # Daily cleanup at 2 AM"
            echo "  0 2 * * * /path/to/bootstrap/bin/log-rotation cleanup"
            echo ""
            echo "  # Weekly compression on Sunday at 3 AM"
            echo "  0 3 * * 0 /path/to/bootstrap/bin/log-rotation compress"
            echo ""
            ;;
    esac
}

# Check dependencies
if ! command -v bc &> /dev/null; then
    error "bc calculator is required but not installed"
    exit 1
fi

# Run main function
main "$@"
