# 🔄 HDM Boot Core - Log Rotation & Cleanup

## 📊 **Log Management Overview**

HDM Boot Core provides comprehensive log rotation and cleanup mechanisms to prevent disk space issues and maintain optimal performance in production environments.

## 🔧 **Automatic Rotation Configuration**

### **Retention Policies**
```php
// LoggerFactory constants
private const DEFAULT_RETENTION_DAYS = 30;     // General logs
private const PERFORMANCE_RETENTION_DAYS = 14; // Performance logs  
private const DEBUG_RETENTION_DAYS = 7;        // Debug logs
private const AUDIT_RETENTION_DAYS = 365;      // Audit logs (compliance)
private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB per file
```

### **Log Types & Retention**
| Log Type | Retention | Reason |
|----------|-----------|--------|
| **Security** | 30 days | Security incident investigation |
| **Performance** | 14 days | Performance monitoring and optimization |
| **Debug** | 7 days | Development debugging (high volume) |
| **Audit** | 365 days | Compliance and legal requirements |
| **General** | 30 days | Application monitoring |
| **Errors** | 30 days | Error tracking and resolution |

---

## 🛠️ **Manual Log Management**

### **CLI Tools Available**

#### **1. PHP Log Stats Tool**
```bash
# Show log directory statistics
php bin/log-cleanup stats

# Output example:
📊 Log Directory Statistics
==================================================
Total Files: 4
Total Size: 596.02 KB

Files by Type:
  debug: 2 files, 584.8 KB
  performance: 1 files, 10.18 KB
  security: 1 files, 1.04 KB

Oldest File: security-2025-06-20.log (2025-06-20 09:58:27)
Newest File: debug-app.log (2025-06-20 10:11:03)
```

#### **2. Bash Log Rotation Script**
```bash
# Show statistics
./scripts/log-rotation.sh stats

# Clean up old files (30+ days)
./scripts/log-rotation.sh cleanup

# Compress old files (7+ days)
./scripts/log-rotation.sh compress

# Full maintenance (cleanup + compress + health check)
./scripts/log-rotation.sh full

# Health check
./scripts/log-rotation.sh health
```

---

## ⏰ **Automated Rotation with Cron**

### **Recommended Cron Schedule**

Add to your crontab (`crontab -e`):

```bash
# HDM Boot Core Log Rotation
# Daily cleanup at 2 AM (remove files older than 30 days)
0 2 * * * /path/to/boot/bin/log-rotation cleanup

# Weekly compression on Sunday at 3 AM (compress files older than 7 days)
0 3 * * 0 /path/to/boot/bin/log-rotation compress

# Monthly health check on 1st day at 4 AM
0 4 1 * * /path/to/boot/bin/log-rotation health
```

### **Production Cron Setup**
```bash
# Install cron jobs
sudo crontab -e

# Add log rotation jobs for www-data user
sudo crontab -u www-data -e

# Verify cron jobs
crontab -l
```

---

## 📁 **Log Directory Structure**

### **File Naming Convention**
```
var/logs/
├── debug-app.log              # Current application debug log
├── debug-app-2025-06-19.log   # Rotated debug log
├── debug-profile.log          # Current profile debug log
├── security-2025-06-20.log    # Daily security log
├── performance-2025-06-20.log # Daily performance log
├── audit-2025-06-20.log       # Daily audit log
├── errors.log                 # Current error log
└── old-logs/
    ├── debug-app-2025-06-01.log.gz    # Compressed old logs
    └── security-2025-06-01.log.gz
```

### **Log Rotation Behavior**
- **Daily rotation** for date-based logs (security, performance, audit)
- **Size-based rotation** for high-volume logs (debug, errors)
- **Automatic compression** after 7 days
- **Automatic deletion** after retention period

---

## 🔍 **Monitoring & Health Checks**

### **Health Check Indicators**
```bash
./bin/log-rotation health

# Checks for:
# ✅ Large files (>50MB)
# ✅ High disk usage (>100MB total)
# ✅ Very old files (>60 days)
# ✅ Rotation functionality
```

### **Disk Usage Monitoring**
```bash
# Check log directory size
du -sh var/logs/

# Check individual log sizes
ls -lh var/logs/*.log

# Find largest log files
find var/logs/ -name "*.log*" -exec ls -lh {} \; | sort -k5 -hr | head -10
```

### **Log Growth Monitoring**
```bash
# Monitor log growth over time
watch -n 60 'du -sh var/logs/ && ls -lt var/logs/ | head -5'

# Check log file growth rate
stat var/logs/debug-app.log
```

---

## 🚨 **Troubleshooting**

### **Common Issues**

#### **1. Disk Space Full**
```bash
# Emergency cleanup - remove all logs older than 7 days
find var/logs/ -name "*.log*" -mtime +7 -delete

# Compress all current logs
gzip var/logs/*.log

# Check available space
df -h
```

#### **2. Large Log Files**
```bash
# Find files larger than 50MB
find var/logs/ -name "*.log" -size +50M

# Truncate large log file (keep last 1000 lines)
tail -1000 var/logs/debug-app.log > var/logs/debug-app.log.tmp
mv var/logs/debug-app.log.tmp var/logs/debug-app.log
```

#### **3. Permission Issues**
```bash
# Fix log directory permissions
sudo chown -R www-data:www-data var/logs/
sudo chmod -R 755 var/logs/

# Make scripts executable
chmod +x bin/log-rotation
chmod +x bin/log-cleanup
```

#### **4. Cron Not Working**
```bash
# Check cron service
sudo systemctl status cron

# Check cron logs
sudo tail -f /var/log/cron.log

# Test cron job manually
sudo -u www-data /path/to/boot/bin/log-rotation stats
```

---

## ⚙️ **Configuration Customization**

### **Modify Retention Policies**

Edit `src/Modules/Core/Logging/Infrastructure/Services/LoggerFactory.php`:

```php
// Custom retention periods
private const DEFAULT_RETENTION_DAYS = 60;     // Increase to 60 days
private const PERFORMANCE_RETENTION_DAYS = 30; // Increase to 30 days
private const DEBUG_RETENTION_DAYS = 3;        // Decrease to 3 days
```

### **Modify Script Configuration**

Edit `bin/log-rotation`:

```bash
# Configuration section
RETENTION_DAYS=60        # Change from 30 to 60 days
COMPRESSION_DAYS=14      # Change from 7 to 14 days
MAX_LOG_SIZE_MB=100      # Change from 50 to 100 MB
```

### **Environment-Specific Settings**

```bash
# Development - shorter retention
RETENTION_DAYS=7
COMPRESSION_DAYS=3

# Production - longer retention
RETENTION_DAYS=90
COMPRESSION_DAYS=30

# Compliance - very long retention
RETENTION_DAYS=2555  # 7 years
```

---

## 📊 **Performance Impact**

### **Rotation Performance**
| Operation | Time | Impact |
|-----------|------|--------|
| **Stats check** | <1s | Minimal |
| **Cleanup (30 days)** | 1-5s | Low |
| **Compression** | 5-30s | Medium |
| **Health check** | 1-3s | Minimal |

### **Storage Efficiency**
- **Compression ratio**: 70-90% space savings
- **Cleanup impact**: Prevents disk full scenarios
- **Performance benefit**: Faster log searches

---

## 🎯 **Best Practices**

### **Production Recommendations**
- ✅ **Run cleanup daily** during low-traffic hours (2-4 AM)
- ✅ **Compress weekly** to save disk space
- ✅ **Monitor disk usage** with alerts at 80% capacity
- ✅ **Test rotation scripts** before production deployment
- ✅ **Backup critical logs** before cleanup (audit, security)

### **Development Recommendations**
- ✅ **Shorter retention** (3-7 days) for faster development
- ✅ **More frequent cleanup** to prevent disk issues
- ✅ **Disable compression** for easier debugging
- ✅ **Monitor log growth** during development

### **Security Considerations**
- ✅ **Secure log files** with proper permissions (644)
- ✅ **Encrypt sensitive logs** before archival
- ✅ **Audit log access** and modifications
- ✅ **Backup security logs** to separate storage

---

## 📈 **Monitoring Integration**

### **Log Metrics to Track**
```bash
# Daily log volume
grep "$(date +%Y-%m-%d)" var/logs/*.log | wc -l

# Error rate
grep -c "ERROR\|CRITICAL" var/logs/errors.log

# Security events
grep -c "security" var/logs/security-*.log

# Performance issues
grep -c "slow\|timeout" var/logs/performance-*.log
```

### **Alerting Thresholds**
- **Disk usage > 80%**: Warning alert
- **Disk usage > 95%**: Critical alert
- **Log file > 100MB**: Size alert
- **No logs for 1 hour**: Service alert

---

## 🎯 **Summary**

**HDM Boot Core provides enterprise-grade log rotation:**

- ✅ **Automatic rotation** - Monolog RotatingFileHandler
- ✅ **Manual management** - CLI tools for stats and cleanup
- ✅ **Cron automation** - Scheduled cleanup and compression
- ✅ **Health monitoring** - Proactive issue detection
- ✅ **Flexible configuration** - Customizable retention policies
- ✅ **Production ready** - Battle-tested rotation strategies

**Your logs are properly managed and will never fill up your disk!** 🚀

---

*Documentation updated: 2025-06-20*  
*Log rotation: Automated, monitored, and optimized*  
*Tools: CLI, Bash scripts, Cron integration*
