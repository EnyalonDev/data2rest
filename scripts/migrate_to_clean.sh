#!/bin/bash

# Script de Migración Completa - Data2Rest
# Automatiza el proceso de migración a instalación limpia
# Uso: bash scripts/migrate_to_clean.sh

set -e  # Detener en caso de error

echo "╔════════════════════════════════════════════════════════════╗"
echo "║   Data2Rest - Migración a Instalación Limpia              ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración
CURRENT_DIR="/opt/homebrew/var/www/data2rest"
NEW_DIR="/opt/homebrew/var/www/data2rest-new"
BACKUP_DIR="$HOME/migracion_data2rest"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Funciones auxiliares
print_step() {
    echo -e "\n${BLUE}▶ $1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

confirm() {
    read -p "$(echo -e ${YELLOW}$1 [y/N]: ${NC})" -n 1 -r
    echo
    [[ $REPLY =~ ^[Yy]$ ]]
}

# ============================================================
# FASE 1: VERIFICACIONES PREVIAS
# ============================================================

print_step "Fase 1: Verificaciones Previas"

# Verificar que estamos en el directorio correcto
if [ ! -f "$CURRENT_DIR/public/index.php" ]; then
    print_error "No se encontró la instalación actual en: $CURRENT_DIR"
    exit 1
fi
print_success "Instalación actual encontrada"

# Verificar que existe git
if ! command -v git &> /dev/null; then
    print_error "Git no está instalado"
    exit 1
fi
print_success "Git disponible"

# Verificar que existe php
if ! command -v php &> /dev/null; then
    print_error "PHP no está instalado"
    exit 1
fi
print_success "PHP disponible"

# ============================================================
# FASE 2: BACKUP COMPLETO
# ============================================================

print_step "Fase 2: Creando Backup Completo de Seguridad"

BACKUP_FILE="$HOME/data2rest_backup_$TIMESTAMP.tar.gz"

if confirm "¿Crear backup completo de la instalación actual?"; then
    cd $CURRENT_DIR
    tar -czf "$BACKUP_FILE" .
    print_success "Backup creado: $BACKUP_FILE"
    print_success "Tamaño: $(du -h $BACKUP_FILE | cut -f1)"
else
    print_warning "Backup omitido (NO RECOMENDADO)"
fi

# ============================================================
# FASE 3: EXPORTAR DATOS
# ============================================================

print_step "Fase 3: Exportando Datos Selectivos"

# Crear directorio de migración
mkdir -p "$BACKUP_DIR"
print_success "Directorio de migración creado: $BACKUP_DIR"

# Ejecutar script de exportación
print_step "Ejecutando script de exportación..."
cd $CURRENT_DIR
php scripts/export_for_migration.php

if [ ! -f "$BACKUP_DIR/migration_data.json" ]; then
    print_error "Error: No se generó el archivo migration_data.json"
    exit 1
fi
print_success "Datos exportados correctamente"

# Copiar bases de datos de clientes (excepto system.db)
print_step "Copiando bases de datos de clientes..."
cd $CURRENT_DIR/data
for db in *.db; do
    if [ "$db" != "system.db" ]; then
        cp "$db" "$BACKUP_DIR/"
        print_success "Copiado: $db"
    fi
done

# Copiar archivos media
if [ -d "$CURRENT_DIR/uploads" ]; then
    print_step "Copiando archivos media..."
    cp -r "$CURRENT_DIR/uploads" "$BACKUP_DIR/"
    print_success "Archivos media copiados"
fi

# ============================================================
# FASE 4: INSTALACIÓN LIMPIA
# ============================================================

print_step "Fase 4: Preparando Instalación Limpia"

if [ -d "$NEW_DIR" ]; then
    print_warning "El directorio $NEW_DIR ya existe"
    if confirm "¿Eliminar y recrear?"; then
        rm -rf "$NEW_DIR"
        print_success "Directorio eliminado"
    else
        print_error "Cancelado por el usuario"
        exit 1
    fi
fi

# Clonar repositorio
print_step "Clonando repositorio..."
cd /opt/homebrew/var/www
git clone https://github.com/tu-repo/data2rest.git data2rest-new
cd $NEW_DIR
git pull origin main
print_success "Código actualizado"

# Configurar permisos
print_step "Configurando permisos..."
chmod -R 755 .
mkdir -p data uploads
chmod -R 777 data uploads
print_success "Permisos configurados"

# ============================================================
# FASE 5: INSTALACIÓN WEB
# ============================================================

print_step "Fase 5: Instalación Web"
echo ""
print_warning "IMPORTANTE: Debes completar la instalación web manualmente"
echo ""
echo "1. Abre tu navegador en: https://data2rest-new.nestorovallos.com/install"
echo "2. Completa el formulario de instalación"
echo "3. Crea el usuario administrador"
echo "4. Espera a que termine la instalación"
echo ""

if ! confirm "¿Has completado la instalación web?"; then
    print_error "Instalación web no completada. Ejecuta este script nuevamente cuando esté lista."
    exit 1
fi

# ============================================================
# FASE 6: IMPORTAR DATOS
# ============================================================

print_step "Fase 6: Importando Datos Migrados"

# Copiar bases de datos
print_step "Copiando bases de datos de clientes..."
cp $BACKUP_DIR/*.db $NEW_DIR/data/ 2>/dev/null || true
rm -f $NEW_DIR/data/system.db  # Eliminar si se copió accidentalmente
print_success "Bases de datos copiadas"

# Copiar uploads
if [ -d "$BACKUP_DIR/uploads" ]; then
    print_step "Copiando archivos media..."
    cp -r $BACKUP_DIR/uploads/* $NEW_DIR/uploads/
    print_success "Archivos media copiados"
fi

# Ejecutar script de importación
print_step "Ejecutando script de importación..."
cd $NEW_DIR
php scripts/import_from_migration.php "$BACKUP_DIR/migration_data.json"

# ============================================================
# FASE 7: VERIFICACIONES POST-IMPORTACIÓN
# ============================================================

print_step "Fase 7: Verificaciones Post-Importación"

# Verificar permisos
chmod -R 777 $NEW_DIR/data
chmod -R 777 $NEW_DIR/uploads
print_success "Permisos actualizados"

# Verificar que existen proyectos
PROJECT_COUNT=$(sqlite3 $NEW_DIR/data/system.db "SELECT COUNT(*) FROM projects;" 2>/dev/null || echo "0")
print_success "Proyectos encontrados: $PROJECT_COUNT"

# Verificar que existen usuarios
USER_COUNT=$(sqlite3 $NEW_DIR/data/system.db "SELECT COUNT(*) FROM users WHERE role_id >= 3;" 2>/dev/null || echo "0")
print_success "Usuarios clientes encontrados: $USER_COUNT"

# ============================================================
# RESUMEN FINAL
# ============================================================

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║   ✅ MIGRACIÓN COMPLETADA EXITOSAMENTE                     ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
print_success "Nueva instalación en: $NEW_DIR"
print_success "Backup completo en: $BACKUP_FILE"
print_success "Datos de migración en: $BACKUP_DIR"
echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  PRÓXIMOS PASOS:"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "1. Probar la nueva instalación:"
echo "   https://data2rest-new.nestorovallos.com"
echo ""
echo "2. Verificar funcionalidades:"
echo "   - Login como admin"
echo "   - Acceso a proyectos"
echo "   - Visualización de bases de datos"
echo "   - Google OAuth (si aplica)"
echo ""
echo "3. Cuando esté todo OK, cambiar producción:"
echo "   cd /opt/homebrew/var/www"
echo "   mv data2rest data2rest-old-$TIMESTAMP"
echo "   mv data2rest-new data2rest"
echo ""
echo "4. Después de 1 semana, limpiar:"
echo "   rm -rf /opt/homebrew/var/www/data2rest-old-$TIMESTAMP"
echo "   rm -rf $BACKUP_DIR"
echo ""
echo "═══════════════════════════════════════════════════════════"
echo ""
print_warning "IMPORTANTE: No elimines la instalación antigua hasta estar 100% seguro"
echo ""
