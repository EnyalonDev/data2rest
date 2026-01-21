#!/bin/bash

# Script para reemplazar backticks en nombres de tablas por sintaxis compatible con PostgreSQL
# Este script busca y reemplaza `databases` por el método quoteName del adaptador

echo "Buscando archivos con backticks en nombres de tablas..."

# Buscar todos los archivos PHP con backticks
grep -r -l '`databases`' /opt/homebrew/var/www/data2rest/src --include="*.php" | while read file; do
    echo "Procesando: $file"
    # Hacer backup
    cp "$file" "$file.bak"
    
    # Reemplazar `databases` por databases (sin backticks)
    # El adaptador se encargará de quotear correctamente
    sed -i '' 's/`databases`/databases/g' "$file"
done

echo "Reemplazo completado. Los archivos originales tienen extensión .bak"
