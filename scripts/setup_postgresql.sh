#!/bin/bash

# PostgreSQL Setup Script for Postgres.app
# This script helps configure PostgreSQL installed via Postgres.app

echo "🐘 PostgreSQL Setup for Postgres.app"
echo "======================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Find Postgres.app installation
echo "🔍 Searching for Postgres.app installation..."

# Common Postgres.app paths
POSTGRES_PATHS=(
    "/Applications/Postgres.app/Contents/Versions/*/bin"
    "$HOME/Applications/Postgres.app/Contents/Versions/*/bin"
    "/Library/PostgreSQL/*/bin"
)

PSQL_PATH=""
for path_pattern in "${POSTGRES_PATHS[@]}"; do
    for path in $path_pattern; do
        if [ -f "$path/psql" ]; then
            PSQL_PATH="$path"
            break 2
        fi
    done
done

if [ -z "$PSQL_PATH" ]; then
    echo -e "${RED}✗ PostgreSQL not found!${NC}"
    echo ""
    echo "Please make sure Postgres.app is installed and running."
    echo "Download from: https://postgresapp.com/"
    exit 1
fi

echo -e "${GREEN}✓ Found PostgreSQL at: $PSQL_PATH${NC}"
echo ""

# Add to PATH temporarily
export PATH="$PSQL_PATH:$PATH"

# Check if PostgreSQL is running
echo "🔍 Checking if PostgreSQL is running..."
if ! pgrep -x "postgres" > /dev/null; then
    echo -e "${YELLOW}⚠ PostgreSQL is not running!${NC}"
    echo ""
    echo "Please start Postgres.app:"
    echo "1. Open Postgres.app from Applications"
    echo "2. Click 'Start' if not already started"
    echo "3. Run this script again"
    exit 1
fi

echo -e "${GREEN}✓ PostgreSQL is running${NC}"
echo ""

# Test connection
echo "🔍 Testing connection to PostgreSQL..."
if $PSQL_PATH/psql -U postgres -d postgres -c "SELECT version();" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Connection successful!${NC}"
    VERSION=$($PSQL_PATH/psql -U postgres -d postgres -t -c "SELECT version();")
    echo "   Version: $VERSION"
else
    echo -e "${YELLOW}⚠ Connection test failed${NC}"
    echo "   This might be normal if password is required"
fi
echo ""

# Create test database
echo "📦 Creating test database 'test_data2rest'..."
if $PSQL_PATH/psql -U postgres -d postgres -c "CREATE DATABASE test_data2rest;" 2>/dev/null; then
    echo -e "${GREEN}✓ Database 'test_data2rest' created${NC}"
elif $PSQL_PATH/psql -U postgres -d postgres -c "SELECT 1 FROM pg_database WHERE datname='test_data2rest';" | grep -q 1; then
    echo -e "${YELLOW}ℹ Database 'test_data2rest' already exists${NC}"
else
    echo -e "${RED}✗ Failed to create database${NC}"
    echo ""
    echo "You may need to create it manually:"
    echo "1. Open Postgres.app"
    echo "2. Double-click on a database to open psql"
    echo "3. Run: CREATE DATABASE test_data2rest;"
fi
echo ""

# Show connection info
echo "📋 Connection Information:"
echo "=========================="
echo "Host:     localhost"
echo "Port:     5432"
echo "Database: test_data2rest"
echo "Username: postgres"
echo "Password: Mede2020"
echo ""

# Export PATH for current session
echo "🔧 Setting up PATH..."
SHELL_RC=""
if [ -n "$ZSH_VERSION" ]; then
    SHELL_RC="$HOME/.zshrc"
elif [ -n "$BASH_VERSION" ]; then
    SHELL_RC="$HOME/.bashrc"
fi

if [ -n "$SHELL_RC" ]; then
    if ! grep -q "$PSQL_PATH" "$SHELL_RC" 2>/dev/null; then
        echo ""
        echo "To make PostgreSQL commands available permanently, add this to your $SHELL_RC:"
        echo ""
        echo -e "${YELLOW}export PATH=\"$PSQL_PATH:\$PATH\"${NC}"
        echo ""
        read -p "Would you like me to add it now? (y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            echo "export PATH=\"$PSQL_PATH:\$PATH\"" >> "$SHELL_RC"
            echo -e "${GREEN}✓ Added to $SHELL_RC${NC}"
            echo "Run: source $SHELL_RC"
        fi
    else
        echo -e "${GREEN}✓ PATH already configured in $SHELL_RC${NC}"
    fi
fi
echo ""

# Test PHP PDO PostgreSQL extension
echo "🔍 Checking PHP PDO PostgreSQL extension..."
if php -m | grep -q pdo_pgsql; then
    echo -e "${GREEN}✓ PHP PDO PostgreSQL extension is installed${NC}"
else
    echo -e "${RED}✗ PHP PDO PostgreSQL extension is NOT installed${NC}"
    echo ""
    echo "To install:"
    echo "brew install php"
    echo "# or if you have a specific PHP version:"
    echo "brew install php@8.2"
    echo ""
    echo "Then restart your web server"
    exit 1
fi
echo ""

# Ready to test
echo "✅ Setup Complete!"
echo "=================="
echo ""
echo "You can now run the PostgreSQL tests:"
echo ""
echo -e "${GREEN}cd /opt/homebrew/var/www/data2rest${NC}"
echo -e "${GREEN}php scripts/test_postgresql.php${NC}"
echo ""
echo "Or test manually with psql:"
echo -e "${GREEN}$PSQL_PATH/psql -U postgres -d test_data2rest${NC}"
echo ""
