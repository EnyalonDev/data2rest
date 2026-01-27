#!/bin/bash

# Data2Rest - Production Update Script
# This script pulls latest code and applies security hardening

echo "╔════════════════════════════════════════════════════════════╗"
echo "║   Data2Rest - Production Update                           ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT" || exit 1

# Check if git repo
if [ ! -d ".git" ]; then
    echo "❌ Error: Not a git repository"
    exit 1
fi

# 1. Show current branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
echo "📍 Current branch: $CURRENT_BRANCH"
echo ""

# 2. Fetch latest changes
echo "🔍 Checking for updates..."
git fetch origin

# Check if there are updates
LOCAL=$(git rev-parse @)
REMOTE=$(git rev-parse @{u})

if [ "$LOCAL" = "$REMOTE" ]; then
    echo "✓ Already up to date"
    echo ""
else
    echo "📥 Updates available, pulling..."
    
    # 3. Pull latest code
    if git pull origin "$CURRENT_BRANCH"; then
        echo "✓ Code updated successfully"
        echo ""
    else
        echo "❌ Error pulling updates"
        exit 1
    fi
fi

# 4. Apply security hardening
echo "🔒 Applying security hardening..."
if [ -f "$SCRIPT_DIR/security_hardening.sh" ]; then
    bash "$SCRIPT_DIR/security_hardening.sh"
else
    echo "⚠ Warning: security_hardening.sh not found"
fi

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║   ✅ UPDATE COMPLETED                                      ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "Next steps:"
echo "  • Test your application: https://your-domain.com"
echo "  • Check logs for errors"
echo "  • Verify database access is blocked"
echo ""
