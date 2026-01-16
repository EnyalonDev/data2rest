#!/bin/bash

# PHP Version Switcher for Apache
# Usage: ./switch_php.sh [7.4|8.1|8.2|8.3]

VERSION=$1

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

if [ -z "$VERSION" ]; then
    echo -e "${RED}Error: PHP version required${NC}"
    echo ""
    echo "Usage: $0 [version]"
    echo ""
    echo "Available versions:"
    brew list | grep "^php@" | sed 's/php@/  - /'
    echo "  - php (latest)"
    echo ""
    echo "Example:"
    echo "  $0 8.1    # Switch to PHP 8.1"
    echo "  $0 7.4    # Switch to PHP 7.4"
    exit 1
fi

# Normalize version
if [ "$VERSION" = "8.1" ]; then
    PHP_PACKAGE="php@8.1"
    PHP_PATH="/opt/homebrew/opt/php@8.1"
elif [ "$VERSION" = "7.4" ]; then
    PHP_PACKAGE="php@7.4"
    PHP_PATH="/opt/homebrew/opt/php@7.4"
elif [ "$VERSION" = "8.2" ]; then
    PHP_PACKAGE="php@8.2"
    PHP_PATH="/opt/homebrew/opt/php@8.2"
elif [ "$VERSION" = "8.3" ]; then
    PHP_PACKAGE="php@8.3"
    PHP_PATH="/opt/homebrew/opt/php@8.3"
elif [ "$VERSION" = "latest" ] || [ "$VERSION" = "8" ]; then
    PHP_PACKAGE="php"
    PHP_PATH="/opt/homebrew/opt/php"
else
    echo -e "${RED}Error: Unsupported PHP version: $VERSION${NC}"
    exit 1
fi

echo "🔄 Switching to PHP $VERSION"
echo "=============================="
echo ""

# Check if version is installed
if [ ! -d "$PHP_PATH" ]; then
    echo -e "${YELLOW}PHP $VERSION is not installed. Installing...${NC}"
    brew install $PHP_PACKAGE
    echo ""
fi

# Step 1: Stop services
echo -e "${YELLOW}Step 1: Stopping services...${NC}"
brew services stop httpd
brew services stop php 2>/dev/null
brew services stop php@7.4 2>/dev/null
brew services stop php@8.1 2>/dev/null
brew services stop php@8.2 2>/dev/null
brew services stop php@8.3 2>/dev/null
echo -e "${GREEN}✓ Services stopped${NC}"
echo ""

# Step 2: Unlink all PHP versions
echo -e "${YELLOW}Step 2: Unlinking PHP versions...${NC}"
brew unlink php 2>/dev/null
brew unlink php@7.4 2>/dev/null
brew unlink php@8.1 2>/dev/null
brew unlink php@8.2 2>/dev/null
brew unlink php@8.3 2>/dev/null
echo -e "${GREEN}✓ PHP versions unlinked${NC}"
echo ""

# Step 3: Link selected version
echo -e "${YELLOW}Step 3: Linking PHP $VERSION...${NC}"
brew link --force --overwrite $PHP_PACKAGE
echo -e "${GREEN}✓ PHP $VERSION linked${NC}"
echo ""

# Step 4: Update Apache configuration
echo -e "${YELLOW}Step 4: Updating Apache configuration...${NC}"

# Backup current config
cp /opt/homebrew/etc/httpd/httpd.conf /opt/homebrew/etc/httpd/httpd.conf.backup.$(date +%Y%m%d_%H%M%S)

# Comment out all PHP modules
sed -i.bak 's/^LoadModule php/#LoadModule php/g' /opt/homebrew/etc/httpd/httpd.conf

# Add the selected PHP module
if grep -q "#LoadModule php_module" /opt/homebrew/etc/httpd/httpd.conf; then
    sed -i.bak2 "s|#LoadModule php_module.*|LoadModule php_module $PHP_PATH/lib/httpd/modules/libphp.so|" /opt/homebrew/etc/httpd/httpd.conf
else
    # Add after last LoadModule
    sed -i.bak2 "/^LoadModule.*$/a\\
LoadModule php_module $PHP_PATH/lib/httpd/modules/libphp.so
" /opt/homebrew/etc/httpd/httpd.conf
fi

echo -e "${GREEN}✓ Apache configuration updated${NC}"
echo ""

# Step 5: Start services
echo -e "${YELLOW}Step 5: Starting services...${NC}"
brew services start $PHP_PACKAGE
sleep 2
brew services start httpd
echo -e "${GREEN}✓ Services started${NC}"
echo ""

# Step 6: Verify
echo -e "${YELLOW}Step 6: Verifying...${NC}"
sleep 2

PHP_VERSION=$(php -v | head -1)
echo "PHP CLI: $PHP_VERSION"
echo ""

brew services list | grep -E "(php|httpd)"
echo ""

# Create test file
echo "<?php phpinfo(); ?>" > /opt/homebrew/var/www/phpinfo.php

echo -e "${GREEN}✅ PHP switched to version $VERSION${NC}"
echo ""
echo "📋 Verification:"
echo "1. CLI: php -v"
echo "2. Web: http://localhost/phpinfo.php"
echo "3. Check: brew services list"
echo ""
echo "⚠️  Remember to delete /opt/homebrew/var/www/phpinfo.php after testing"
echo ""
