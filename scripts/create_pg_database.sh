#!/bin/bash

# Quick PostgreSQL Database Creator
# Usage: ./scripts/create_pg_database.sh nombre_de_base_de_datos

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
PG_BIN="/Library/PostgreSQL/17/bin"
PG_HOST="localhost"
PG_USER="postgres"
PG_PASSWORD="Mede2020"

# Check if database name provided
if [ -z "$1" ]; then
    echo -e "${RED}Error: Database name required${NC}"
    echo ""
    echo "Usage: $0 database_name"
    echo ""
    echo "Examples:"
    echo "  $0 mi_tienda"
    echo "  $0 mi_blog"
    echo "  $0 clientes_app"
    exit 1
fi

DB_NAME=$1

echo "🐘 PostgreSQL Database Creator"
echo "=============================="
echo ""
echo "Database: $DB_NAME"
echo "Host: $PG_HOST"
echo "User: $PG_USER"
echo ""

# Check if PostgreSQL is running
if ! pgrep -x "postgres" > /dev/null; then
    echo -e "${RED}✗ PostgreSQL is not running!${NC}"
    echo ""
    echo "Please start Postgres.app first"
    exit 1
fi

# Check if database already exists
PGPASSWORD="$PG_PASSWORD" $PG_BIN/psql -h $PG_HOST -U $PG_USER -lqt | cut -d \| -f 1 | grep -qw $DB_NAME
if [ $? -eq 0 ]; then
    echo -e "${YELLOW}⚠ Database '$DB_NAME' already exists${NC}"
    echo ""
    read -p "Do you want to drop and recreate it? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Dropping database..."
        PGPASSWORD="$PG_PASSWORD" $PG_BIN/psql -h $PG_HOST -U $PG_USER -c "DROP DATABASE $DB_NAME;" postgres
        echo -e "${GREEN}✓ Database dropped${NC}"
    else
        echo "Keeping existing database"
        exit 0
    fi
fi

# Create database
echo "Creating database..."
PGPASSWORD="$PG_PASSWORD" $PG_BIN/psql -h $PG_HOST -U $PG_USER -c "CREATE DATABASE $DB_NAME;" postgres

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database '$DB_NAME' created successfully!${NC}"
    echo ""
    echo "📋 Connection Details:"
    echo "======================"
    echo "Host:     $PG_HOST"
    echo "Port:     5432"
    echo "Database: $DB_NAME"
    echo "Username: $PG_USER"
    echo "Password: $PG_PASSWORD"
    echo "Schema:   public"
    echo ""
    echo "✅ You can now use this database in DATA2REST:"
    echo "   1. Go to http://localhost/admin/databases/create-form"
    echo "   2. Select PostgreSQL"
    echo "   3. Fill in the details above"
    echo "   4. Click 'Test Connection' - should work now!"
    echo "   5. Click 'Create Database'"
    echo ""
else
    echo -e "${RED}✗ Failed to create database${NC}"
    exit 1
fi
