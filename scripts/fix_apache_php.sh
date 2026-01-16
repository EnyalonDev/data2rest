#!/bin/bash

# Apache + PHP 8.1 Clean Installation Script
# This script will fix Apache to work properly with PHP 8.1

echo "🔧 Apache + PHP 8.1 Fix Script"
echo "=============================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Step 1: Stop all services
echo -e "${YELLOW}Step 1: Stopping services...${NC}"
sudo brew services stop httpd
brew services stop httpd
brew services stop php
brew services stop php@7.4
echo -e "${GREEN}✓ Services stopped${NC}"
echo ""

# Step 2: Remove problematic ownership
echo -e "${YELLOW}Step 2: Fixing permissions...${NC}"
sudo chown -R $(whoami):admin /opt/homebrew/Cellar/httpd
sudo chown -R $(whoami):admin /opt/homebrew/opt/httpd
sudo chown -R $(whoami):admin /opt/homebrew/var/homebrew/linked/httpd
echo -e "${GREEN}✓ Permissions fixed${NC}"
echo ""

# Step 3: Backup current config
echo -e "${YELLOW}Step 3: Backing up configuration...${NC}"
cp /opt/homebrew/etc/httpd/httpd.conf /opt/homebrew/etc/httpd/httpd.conf.backup.$(date +%Y%m%d_%H%M%S)
echo -e "${GREEN}✓ Backup created${NC}"
echo ""

# Step 4: Reinstall httpd
echo -e "${YELLOW}Step 4: Reinstalling Apache...${NC}"
brew reinstall httpd
echo -e "${GREEN}✓ Apache reinstalled${NC}"
echo ""

# Step 5: Configure Apache for PHP 8.1
echo -e "${YELLOW}Step 5: Configuring Apache for PHP 8.1...${NC}"

# Add PHP module
if ! grep -q "LoadModule php_module.*php@8.1" /opt/homebrew/etc/httpd/httpd.conf; then
    # Comment out any existing PHP modules
    sed -i.bak 's/^LoadModule php/#LoadModule php/g' /opt/homebrew/etc/httpd/httpd.conf
    
    # Add PHP 8.1 module after the last LoadModule line
    sed -i.bak2 '/^LoadModule.*$/a\
LoadModule php_module /opt/homebrew/opt/php@8.1/lib/httpd/modules/libphp.so
' /opt/homebrew/etc/httpd/httpd.conf
fi

# Add PHP handler
if ! grep -q "FilesMatch.*php" /opt/homebrew/etc/httpd/httpd.conf; then
    echo "" >> /opt/homebrew/etc/httpd/httpd.conf
    echo "<FilesMatch \.php$>" >> /opt/homebrew/etc/httpd/httpd.conf
    echo "    SetHandler application/x-httpd-php" >> /opt/homebrew/etc/httpd/httpd.conf
    echo "</FilesMatch>" >> /opt/homebrew/etc/httpd/httpd.conf
fi

# Add DirectoryIndex for PHP
sed -i.bak3 's/DirectoryIndex index.html/DirectoryIndex index.php index.html/g' /opt/homebrew/etc/httpd/httpd.conf

# Fix macOS fork issue
if ! grep -q "OBJC_DISABLE_INITIALIZE_FORK_SAFETY" /opt/homebrew/etc/httpd/httpd.conf; then
    sed -i.bak4 '1i\
SetEnv OBJC_DISABLE_INITIALIZE_FORK_SAFETY YES
' /opt/homebrew/etc/httpd/httpd.conf
fi

# Enable mod_rewrite (for clean URLs)
sed -i.bak5 's/#LoadModule rewrite_module/LoadModule rewrite_module/g' /opt/homebrew/etc/httpd/httpd.conf

# Set DocumentRoot
sed -i.bak6 's|DocumentRoot "/opt/homebrew/var/www"|DocumentRoot "/opt/homebrew/var/www"|g' /opt/homebrew/etc/httpd/httpd.conf
sed -i.bak7 's|<Directory "/opt/homebrew/var/www">|<Directory "/opt/homebrew/var/www">|g' /opt/homebrew/etc/httpd/httpd.conf

# Allow .htaccess
sed -i.bak8 's/AllowOverride None/AllowOverride All/g' /opt/homebrew/etc/httpd/httpd.conf

echo -e "${GREEN}✓ Apache configured${NC}"
echo ""

# Step 6: Start services
echo -e "${YELLOW}Step 6: Starting services...${NC}"
brew services start php@8.1
sleep 2
brew services start httpd
echo -e "${GREEN}✓ Services started${NC}"
echo ""

# Step 7: Verify
echo -e "${YELLOW}Step 7: Verifying installation...${NC}"
sleep 3
brew services list | grep -E "(php|httpd)"
echo ""

# Test Apache
if curl -s http://localhost:80 > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Apache is responding on port 80${NC}"
else
    echo -e "${RED}✗ Apache is not responding${NC}"
    echo "Check logs: tail -50 /opt/homebrew/var/log/httpd/error_log"
fi

echo ""
echo -e "${GREEN}✅ Installation complete!${NC}"
echo ""
echo "📋 Next steps:"
echo "1. Test: http://localhost/data2rest/public/pg_test.php"
echo "2. If it works, you can stop the PHP dev server (port 8000)"
echo "3. Use the switch_php.sh script to change PHP versions"
echo ""
