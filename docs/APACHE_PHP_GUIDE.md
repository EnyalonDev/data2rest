# 🔧 Apache + PHP Management Guide

## 📋 Quick Reference

### Fix Apache + PHP 8.1
```bash
cd /opt/homebrew/var/www/data2rest
chmod +x scripts/fix_apache_php.sh
./scripts/fix_apache_php.sh
```

### Switch PHP Version
```bash
cd /opt/homebrew/var/www/data2rest
chmod +x scripts/switch_php.sh
./scripts/switch_php.sh 8.1    # Switch to PHP 8.1
./scripts/switch_php.sh 7.4    # Switch to PHP 7.4
```

### Check Status
```bash
brew services list
php -v
```

---

## 🚀 Initial Setup

### Step 1: Fix Apache
Run the fix script to clean install Apache with PHP 8.1:

```bash
cd /opt/homebrew/var/www/data2rest
chmod +x scripts/fix_apache_php.sh
./scripts/fix_apache_php.sh
```

**What it does:**
- Stops all services
- Fixes permissions
- Backs up configuration
- Reinstalls Apache
- Configures Apache for PHP 8.1
- Fixes macOS fork issue
- Enables mod_rewrite
- Starts services

### Step 2: Verify
After running the script, verify:

```bash
# Check services
brew services list

# Should show:
# httpd   started  nestorovallos  ~/Library/LaunchAgents/homebrew.mxcl.httpd.plist
# php@8.1 started  nestorovallos  ~/Library/LaunchAgents/homebrew.mxcl.php@8.1.plist
```

### Step 3: Test
Open in browser:
```
http://localhost/data2rest/public/pg_test.php
```

Should show:
- ✅ pdo_pgsql driver is available!
- ✅ CONNECTION SUCCESSFUL!

---

## 🔄 Switching PHP Versions

### Available Versions
Check installed versions:
```bash
brew list | grep php
```

Common versions:
- `php@7.4` - PHP 7.4
- `php@8.1` - PHP 8.1 (recommended)
- `php@8.2` - PHP 8.2
- `php@8.3` - PHP 8.3
- `php` - Latest stable

### Install a New Version
```bash
# Install PHP 8.2
brew install php@8.2

# Install PHP 7.4
brew install php@7.4
```

### Switch to a Version
```bash
cd /opt/homebrew/var/www/data2rest

# Switch to PHP 8.1
./scripts/switch_php.sh 8.1

# Switch to PHP 7.4
./scripts/switch_php.sh 7.4

# Switch to PHP 8.2
./scripts/switch_php.sh 8.2
```

**What it does:**
- Stops all services
- Unlinks all PHP versions
- Links selected version
- Updates Apache configuration
- Restarts services
- Creates test file

### Verify Switch
```bash
# Check CLI version
php -v

# Check web version
open http://localhost/phpinfo.php

# Check services
brew services list
```

**⚠️ Important:** Delete `phpinfo.php` after testing:
```bash
rm /opt/homebrew/var/www/phpinfo.php
```

---

## 🛠️ Common Tasks

### Restart Apache
```bash
brew services restart httpd
```

### Restart PHP-FPM
```bash
brew services restart php@8.1
```

### View Apache Logs
```bash
# Error log
tail -f /opt/homebrew/var/log/httpd/error_log

# Access log
tail -f /opt/homebrew/var/log/httpd/access_log
```

### View PHP-FPM Logs
```bash
tail -f /opt/homebrew/var/log/php-fpm.log
```

### Edit Apache Config
```bash
nano /opt/homebrew/etc/httpd/httpd.conf
```

### Edit PHP Config
```bash
# PHP 8.1
nano /opt/homebrew/etc/php/8.1/php.ini

# PHP 7.4
nano /opt/homebrew/etc/php/7.4/php.ini
```

### Test Apache Configuration
```bash
/opt/homebrew/bin/apachectl configtest
```

---

## 🐘 PostgreSQL Support

### Check if pdo_pgsql is Available
```bash
php -m | grep pdo_pgsql
```

Should output: `pdo_pgsql`

### If Missing
The extension should be built-in for PHP 8.1+. If missing:

```bash
# Reinstall PHP
brew reinstall php@8.1

# Restart services
brew services restart php@8.1
brew services restart httpd
```

---

## 🔍 Troubleshooting

### Apache Won't Start
```bash
# Check error log
tail -50 /opt/homebrew/var/log/httpd/error_log

# Test configuration
/opt/homebrew/bin/apachectl configtest

# Check if port 80 is in use
lsof -i :80
```

### PHP Not Working
```bash
# Check if PHP module is loaded
/opt/homebrew/bin/apachectl -M | grep php

# Should show: php_module (shared)
```

### Wrong PHP Version
```bash
# Check CLI version
php -v

# Check web version
echo "<?php phpinfo(); ?>" > /opt/homebrew/var/www/test.php
open http://localhost/test.php
rm /opt/homebrew/var/www/test.php
```

### Permission Errors
```bash
# Fix Apache permissions
sudo chown -R $(whoami):admin /opt/homebrew/Cellar/httpd
sudo chown -R $(whoami):admin /opt/homebrew/opt/httpd

# Fix www directory
sudo chown -R $(whoami):admin /opt/homebrew/var/www
chmod -R 755 /opt/homebrew/var/www
```

### macOS Fork Error
If you see `objc_initializeAfterForkError` in logs:

```bash
# Add to httpd.conf (already done by fix script)
echo "SetEnv OBJC_DISABLE_INITIALIZE_FORK_SAFETY YES" | \
  cat - /opt/homebrew/etc/httpd/httpd.conf > /tmp/httpd.conf && \
  mv /tmp/httpd.conf /opt/homebrew/etc/httpd/httpd.conf

# Restart Apache
brew services restart httpd
```

---

## 📊 Service Management

### Start All Services
```bash
brew services start php@8.1
brew services start httpd
```

### Stop All Services
```bash
brew services stop httpd
brew services stop php@8.1
```

### Restart All Services
```bash
brew services restart php@8.1
brew services restart httpd
```

### Check Status
```bash
brew services list | grep -E "(php|httpd)"
```

---

## 🎯 Best Practices

### 1. Always Backup Before Changes
```bash
cp /opt/homebrew/etc/httpd/httpd.conf \
   /opt/homebrew/etc/httpd/httpd.conf.backup.$(date +%Y%m%d)
```

### 2. Test After Changes
```bash
/opt/homebrew/bin/apachectl configtest
```

### 3. Use Version-Specific PHP
Instead of `php`, use `php@8.1` for stability:
```bash
brew services start php@8.1  # Good
brew services start php       # May change on updates
```

### 4. Keep Logs Clean
```bash
# Rotate logs monthly
cd /opt/homebrew/var/log/httpd
gzip access_log.$(date +%Y%m)
gzip error_log.$(date +%Y%m)
```

---

## 📚 Additional Resources

### Apache Documentation
- Config: `/opt/homebrew/etc/httpd/httpd.conf`
- Logs: `/opt/homebrew/var/log/httpd/`
- Binary: `/opt/homebrew/bin/apachectl`

### PHP Documentation
- Config: `/opt/homebrew/etc/php/8.1/php.ini`
- Logs: `/opt/homebrew/var/log/php-fpm.log`
- Binary: `/opt/homebrew/opt/php@8.1/bin/php`

### Homebrew
```bash
# Update Homebrew
brew update

# Upgrade packages
brew upgrade

# List services
brew services list

# Cleanup old versions
brew cleanup
```

---

## ✅ Checklist

After running `fix_apache_php.sh`:

- [ ] Apache starts without errors
- [ ] PHP 8.1 is active
- [ ] `http://localhost` works
- [ ] `http://localhost/data2rest/public/` works
- [ ] `pdo_pgsql` is available
- [ ] PostgreSQL connection works
- [ ] No errors in logs

---

## 🆘 Emergency Recovery

If everything breaks:

```bash
# 1. Stop everything
brew services stop httpd
brew services stop php@8.1
brew services stop php

# 2. Restore backup
cp /opt/homebrew/etc/httpd/httpd.conf.backup.* \
   /opt/homebrew/etc/httpd/httpd.conf

# 3. Reinstall
brew reinstall httpd
brew reinstall php@8.1

# 4. Run fix script again
cd /opt/homebrew/var/www/data2rest
./scripts/fix_apache_php.sh
```

---

**Created:** 2026-01-16  
**Version:** 1.0.0  
**Maintained by:** DATA2REST Team
