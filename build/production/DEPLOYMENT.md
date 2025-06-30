# HDM Boot Production Deployment Instructions

## 1. Upload Files (FTP/FTPS)
Upload all files from this build directory to your web server root via FTP/FileZilla.

IMPORTANT: Make sure your web server points to the 'public/' directory as document root,
or upload the contents of 'public/' to your web root and other files outside web root.

## 2. Database Files (Pre-created)
The following databases are already created and ready to use:
- var/storage/mark.db (Mark system users)
- var/storage/user.db (Application users)
- var/storage/system.db (Core system data)

NO database initialization needed - just upload and use!

## 3. Configure Environment
1. Copy `.env.example` to `.env`
2. Edit `.env` with your production values:
   - Change SECRET_KEY and CSRF_SECRET
   - Set your domain/URL settings
   - Set PERMISSIONS_STRICT=false for shared hosting

## 4. Set Permissions (if possible)
If your hosting provider allows:
```bash
chmod 777 var/ var/storage/ var/logs/ var/sessions/ var/cache/
chmod 666 var/logs/*.log var/storage/*.db
```

## 5. Default Users (Pre-created)
Mark Users (mark.db):
- mark@responsive.sk / mark123
- admin@example.com / admin123

Application Users (user.db):
- test@example.com / password123
- user@example.com / user123

## 6. Test Installation
1. Visit your website
2. Try logging in as mark user
3. Try registering as regular user
4. Check error logs if issues occur

## 7. Security Checklist
- [ ] Changed default passwords
- [ ] Updated .env secrets
- [ ] Verified .htaccess is working
- [ ] Tested file permissions
- [ ] Checked error logs

## Support
- Documentation: docs/
- Protocol: docs/HDM_BOOT_PROTOCOL.md
- Troubleshooting: docs/TROUBLESHOOTING.md
