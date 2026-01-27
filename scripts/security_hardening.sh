#!/bin/bash

# Data2Rest Security Hardening Script
# This script sets secure permissions for database and upload directories

echo "╔════════════════════════════════════════════════════════════╗"
echo "║   Data2Rest - Security Hardening                          ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "📂 Project root: $PROJECT_ROOT"
echo ""

# Function to set permissions
set_permissions() {
    local path=$1
    local perm=$2
    local desc=$3
    
    if [ -e "$path" ]; then
        chmod -R $perm "$path"
        echo "✓ $desc: $perm"
    else
        echo "⚠ $desc: Not found, skipping"
    fi
}

# 1. Protect database directory
echo "🔒 Securing database directory..."
if [ -d "$PROJECT_ROOT/data" ]; then
    # Directory: 750 (rwxr-x---)
    # Owner can read/write/execute, group can read/execute, others nothing
    chmod 750 "$PROJECT_ROOT/data"
    echo "✓ data/ directory: 750"
    
    # Database files: 640 (rw-r-----)
    # Owner can read/write, group can read, others nothing
    find "$PROJECT_ROOT/data" -type f -name "*.db" -exec chmod 640 {} \;
    find "$PROJECT_ROOT/data" -type f -name "*.sqlite" -exec chmod 640 {} \;
    find "$PROJECT_ROOT/data" -type f -name "*.sqlite3" -exec chmod 640 {} \;
    echo "✓ Database files: 640"
    
    # .htaccess: 644 (rw-r--r--)
    if [ -f "$PROJECT_ROOT/data/.htaccess" ]; then
        chmod 644 "$PROJECT_ROOT/data/.htaccess"
        echo "✓ data/.htaccess: 644"
    fi
else
    echo "⚠ data/ directory not found, creating..."
    mkdir -p "$PROJECT_ROOT/data"
    chmod 750 "$PROJECT_ROOT/data"
fi

echo ""

# 2. Protect uploads directory
echo "📁 Securing uploads directory..."
if [ -d "$PROJECT_ROOT/uploads" ]; then
    # Directory: 755 (rwxr-xr-x)
    # Needs to be readable by web server for serving files
    chmod 755 "$PROJECT_ROOT/uploads"
    echo "✓ uploads/ directory: 755"
    
    # Media files: 644 (rw-r--r--)
    # Readable by everyone, writable only by owner
    find "$PROJECT_ROOT/uploads" -type f -exec chmod 644 {} \;
    echo "✓ Media files: 644"
    
    # Subdirectories: 755
    find "$PROJECT_ROOT/uploads" -type d -exec chmod 755 {} \;
    echo "✓ Subdirectories: 755"
    
    # .htaccess: 644
    if [ -f "$PROJECT_ROOT/uploads/.htaccess" ]; then
        chmod 644 "$PROJECT_ROOT/uploads/.htaccess"
        echo "✓ uploads/.htaccess: 644"
    fi
else
    echo "⚠ uploads/ directory not found, creating..."
    mkdir -p "$PROJECT_ROOT/uploads"
    chmod 755 "$PROJECT_ROOT/uploads"
fi

echo ""

# 3. Protect configuration files
echo "⚙️  Securing configuration files..."
set_permissions "$PROJECT_ROOT/.env" 600 ".env file"
set_permissions "$PROJECT_ROOT/composer.json" 644 "composer.json"
set_permissions "$PROJECT_ROOT/composer.lock" 644 "composer.lock"

echo ""

# 4. Protect source code
echo "📝 Securing source code..."
if [ -d "$PROJECT_ROOT/src" ]; then
    # PHP files: 644 (rw-r--r--)
    find "$PROJECT_ROOT/src" -type f -name "*.php" -exec chmod 644 {} \;
    echo "✓ PHP files: 644"
    
    # Directories: 755
    find "$PROJECT_ROOT/src" -type d -exec chmod 755 {} \;
    echo "✓ Directories: 755"
fi

echo ""

# 5. Protect public directory
echo "🌐 Securing public directory..."
if [ -d "$PROJECT_ROOT/public" ]; then
    # Public files: 644
    find "$PROJECT_ROOT/public" -type f -exec chmod 644 {} \;
    echo "✓ Public files: 644"
    
    # Directories: 755
    find "$PROJECT_ROOT/public" -type d -exec chmod 755 {} \;
    echo "✓ Directories: 755"
fi

echo ""

# 6. Verify .htaccess files exist
echo "🛡️  Verifying .htaccess protection..."

if [ ! -f "$PROJECT_ROOT/data/.htaccess" ]; then
    echo "⚠ WARNING: data/.htaccess not found!"
    echo "  Creating protective .htaccess..."
    cat > "$PROJECT_ROOT/data/.htaccess" << 'EOF'
# Deny all access to database files
<Files "*.db">
    Order Allow,Deny
    Deny from all
</Files>

<Files "*.sqlite">
    Order Allow,Deny
    Deny from all
</Files>

<Files "*.sqlite3">
    Order Allow,Deny
    Deny from all
</Files>

# Deny access to this directory
Options -Indexes

# Additional protection
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule .* - [F,L]
</IfModule>
EOF
    chmod 644 "$PROJECT_ROOT/data/.htaccess"
    echo "✓ Created data/.htaccess"
else
    echo "✓ data/.htaccess exists"
fi

if [ ! -f "$PROJECT_ROOT/uploads/.htaccess" ]; then
    echo "⚠ WARNING: uploads/.htaccess not found!"
    echo "  Creating protective .htaccess..."
    cat > "$PROJECT_ROOT/uploads/.htaccess" << 'EOF'
# Allow access to media files but deny PHP execution
<FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Allow common media files
<FilesMatch "\.(jpg|jpeg|png|gif|webp|avif|svg|pdf|mp4|webm|mp3|wav|zip|doc|docx|xls|xlsx)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>
EOF
    chmod 644 "$PROJECT_ROOT/uploads/.htaccess"
    echo "✓ Created uploads/.htaccess"
else
    echo "✓ uploads/.htaccess exists"
fi

# Check src/.htaccess
if [ ! -f "$PROJECT_ROOT/src/.htaccess" ]; then
    echo "⚠ WARNING: src/.htaccess not found!"
    echo "  Creating protective .htaccess..."
    cat > "$PROJECT_ROOT/src/.htaccess" << 'EOF'
# Deny all access to source code
Order Allow,Deny
Deny from all

# Additional protection
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule .* - [F,L]
</IfModule>
EOF
    chmod 644 "$PROJECT_ROOT/src/.htaccess"
    echo "✓ Created src/.htaccess"
else
    echo "✓ src/.htaccess exists"
fi

# Check vendor/.htaccess
if [ ! -f "$PROJECT_ROOT/vendor/.htaccess" ]; then
    echo "⚠ WARNING: vendor/.htaccess not found!"
    echo "  Creating protective .htaccess..."
    cat > "$PROJECT_ROOT/vendor/.htaccess" << 'EOF'
# Deny all access to vendor directory
Order Allow,Deny
Deny from all

# Additional protection
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule .* - [F,L]
</IfModule>
EOF
    chmod 644 "$PROJECT_ROOT/vendor/.htaccess"
    echo "✓ Created vendor/.htaccess"
else
    echo "✓ vendor/.htaccess exists"
fi

# Check scripts/.htaccess
if [ ! -f "$PROJECT_ROOT/scripts/.htaccess" ]; then
    echo "⚠ WARNING: scripts/.htaccess not found!"
    echo "  Creating protective .htaccess..."
    cat > "$PROJECT_ROOT/scripts/.htaccess" << 'EOF'
# Deny all access to scripts directory
Order Allow,Deny
Deny from all

# Additional protection
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule .* - [F,L]
</IfModule>
EOF
    chmod 644 "$PROJECT_ROOT/scripts/.htaccess"
    echo "✓ Created scripts/.htaccess"
else
    echo "✓ scripts/.htaccess exists"
fi


echo ""

# 7. Summary
echo "╔════════════════════════════════════════════════════════════╗"
echo "║   ✅ SECURITY HARDENING COMPLETED                          ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  SUMMARY:"
echo "═══════════════════════════════════════════════════════════"
echo "📂 data/           → 750 (protected from web access)"
echo "📄 *.db files      → 640 (owner read/write, group read)"
echo "📁 uploads/        → 755 (web accessible for media)"
echo "🖼️  media files     → 644 (readable by web server)"
echo "📝 src/            → Protected (403 Forbidden)"
echo "📦 vendor/         → Protected (403 Forbidden)"
echo "🔧 scripts/        → Protected (403 Forbidden)"
echo "🛡️  .htaccess       → Deny rules in place"
echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  SECURITY LAYERS:"
echo "═══════════════════════════════════════════════════════════"
echo "1. ✓ Directories outside public/"
echo "2. ✓ File permissions (640 for databases)"
echo "3. ✓ .htaccess deny rules (data, src, vendor, scripts)"
echo "4. ✓ No directory listing"
echo "5. ✓ PHP execution blocked in uploads/"
echo "6. ✓ Source code protected from web access"

echo ""
echo "🔒 Your databases are now protected!"
echo ""
