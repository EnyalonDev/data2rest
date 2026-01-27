# Guía de Migración a Instalación Limpia

> **Objetivo**: Migrar de la instalación actual de Data2Rest a una instalación completamente nueva y limpia, preservando solo los datos de clientes y proyectos.

---

## 📋 Pre-requisitos

- [ ] Acceso SSH al servidor de producción
- [ ] Permisos de escritura en el directorio web
- [ ] Backup completo de la instalación actual
- [ ] Subdominio o ruta alternativa configurada (ej: `data2rest-new.nestorovallos.com`)

---

## 🎯 Fase 1: Preparación y Backup

### 1.1 Crear Backup Completo de Seguridad

```bash
# Conectar al servidor
ssh usuario@servidor

# Ir al directorio de Data2Rest actual
cd /opt/homebrew/var/www/data2rest

# Crear backup completo (por si acaso)
tar -czf ~/data2rest_backup_$(date +%Y%m%d_%H%M%S).tar.gz .

# Verificar que se creó
ls -lh ~/data2rest_backup_*
```

### 1.2 Exportar Datos Selectivos

```bash
# Crear directorio temporal para la migración
mkdir -p ~/migracion_data2rest
cd ~/migracion_data2rest

# Copiar bases de datos de clientes (NO system.db)
cp /opt/homebrew/var/www/data2rest/data/*.db . 2>/dev/null || true
rm -f system.db  # Eliminar system.db si se copió

# Copiar archivos media
cp -r /opt/homebrew/var/www/data2rest/uploads ./uploads

# Verificar contenido
ls -lh
```

### 1.3 Exportar Configuraciones de Proyectos

Ejecutar el script de exportación (ver Fase 2).

---

## 🎯 Fase 2: Exportar Datos del Sistema Actual

### 2.1 Ejecutar Script de Exportación

```bash
cd /opt/homebrew/var/www/data2rest
php scripts/export_for_migration.php
```

Esto creará: `~/migracion_data2rest/migration_data.json`

### 2.2 Verificar Exportación

```bash
cat ~/migracion_data2rest/migration_data.json | jq '.'
```

Deberías ver:
- `projects`: Lista de proyectos
- `databases`: Configuraciones de bases de datos
- `clients`: Usuarios clientes
- `project_users`: Relaciones proyecto-usuario
- `google_configs`: Configuraciones OAuth

---

## 🎯 Fase 3: Instalación Limpia

### 3.1 Preparar Nuevo Directorio

```bash
# Opción A: Nuevo subdominio
cd /opt/homebrew/var/www
git clone https://github.com/tu-repo/data2rest.git data2rest-new
cd data2rest-new

# Opción B: Renombrar actual y clonar
cd /opt/homebrew/var/www
mv data2rest data2rest-old
git clone https://github.com/tu-repo/data2rest.git data2rest
cd data2rest
```

### 3.2 Actualizar Código

```bash
git pull origin main
composer install  # Si usas composer
```

### 3.3 Configurar Permisos

```bash
chmod -R 755 .
chmod -R 777 data
chmod -R 777 uploads
```

### 3.4 Acceder al Instalador Web

1. Navegar a: `https://data2rest-new.nestorovallos.com/install`
2. Completar el formulario de instalación:
   - **Base de datos**: SQLite (o la que uses)
   - **Usuario Admin**: `admin` / `[tu-password-seguro]`
   - **Idioma**: Español

3. ✅ Verificar que la instalación se completó correctamente

---

## 🎯 Fase 4: Importar Datos Migrados

### 4.1 Copiar Bases de Datos de Clientes

```bash
# Copiar archivos .db (excepto system.db)
cp ~/migracion_data2rest/*.db /opt/homebrew/var/www/data2rest-new/data/

# Copiar uploads
cp -r ~/migracion_data2rest/uploads/* /opt/homebrew/var/www/data2rest-new/uploads/
```

### 4.2 Ejecutar Script de Importación

```bash
cd /opt/homebrew/var/www/data2rest-new
php scripts/import_from_migration.php ~/migracion_data2rest/migration_data.json
```

### 4.3 Verificar Importación

```bash
# Verificar proyectos
sqlite3 data/system.db "SELECT id, name, status FROM projects;"

# Verificar usuarios clientes
sqlite3 data/system.db "SELECT id, username, email, role_id FROM users WHERE role_id >= 3;"

# Verificar bases de datos
sqlite3 data/system.db "SELECT id, name, project_id FROM databases;"
```

---

## 🎯 Fase 5: Configuración Post-Migración

### 5.1 Configurar Google OAuth (si aplica)

Para cada proyecto que use autenticación externa:

1. Ir a: `Admin > Proyectos > [Proyecto] > Usuarios Web`
2. Configurar:
   - Google Client ID
   - Google Client Secret
   - Dominio permitido
   - Orígenes permitidos (CORS)
3. ✅ Guardar

### 5.2 Verificar Permisos de Usuarios

1. Ir a: `Admin > Usuarios`
2. Verificar que los roles sean correctos:
   - Clientes: `role_id = 3` (Cliente)
   - Usuarios externos: `role_id = 4` (Usuario)

### 5.3 Probar Funcionalidades Clave

- [ ] Login como admin
- [ ] Acceso a proyectos
- [ ] Visualización de bases de datos
- [ ] Subida de archivos media
- [ ] Login externo con Google OAuth
- [ ] Creación de API Keys

---

## 🎯 Fase 6: Puesta en Producción

### 6.1 Pruebas Finales

```bash
# Verificar que todo funciona en data2rest-new
curl -I https://data2rest-new.nestorovallos.com
curl -I https://data2rest-new.nestorovallos.com/admin/dashboard
```

### 6.2 Cambiar DNS/Rutas (Opción A: Subdominio)

Si usaste un subdominio nuevo, simplemente actualiza tus enlaces.

### 6.3 Reemplazar Instalación (Opción B: Mismo dominio)

```bash
# Detener servidor web (si es necesario)
sudo systemctl stop nginx  # o apache2

# Renombrar directorios
cd /opt/homebrew/var/www
mv data2rest data2rest-backup-$(date +%Y%m%d)
mv data2rest-new data2rest

# Reiniciar servidor web
sudo systemctl start nginx
```

### 6.4 Verificar Producción

```bash
curl -I https://data2rest.nestorovallos.com
```

---

## 🎯 Fase 7: Limpieza (Después de 1 semana)

Una vez que confirmes que todo funciona correctamente:

```bash
# Eliminar backup temporal
rm -rf ~/migracion_data2rest

# Eliminar instalación antigua (CUIDADO)
# Solo después de estar 100% seguro
# rm -rf /opt/homebrew/var/www/data2rest-old
```

---

## 🆘 Rollback de Emergencia

Si algo sale mal:

```bash
# Restaurar backup completo
cd /opt/homebrew/var/www
rm -rf data2rest-new
tar -xzf ~/data2rest_backup_[timestamp].tar.gz -C data2rest-old
mv data2rest-old data2rest
```

---

## 📊 Checklist Final

### Antes de Empezar
- [ ] Backup completo creado
- [ ] Script de exportación probado
- [ ] Nuevo subdominio configurado

### Durante la Migración
- [ ] Instalación limpia completada
- [ ] Datos exportados correctamente
- [ ] Datos importados sin errores
- [ ] Configuraciones verificadas

### Después de la Migración
- [ ] Todas las funcionalidades probadas
- [ ] Google OAuth funcionando
- [ ] API Keys funcionando
- [ ] Usuarios pueden acceder
- [ ] Proyectos visibles
- [ ] Bases de datos accesibles

### Producción
- [ ] DNS actualizado (si aplica)
- [ ] SSL funcionando
- [ ] Logs sin errores
- [ ] Backup de la nueva instalación creado

---

## 📞 Soporte

Si encuentras algún problema durante la migración:

1. **No elimines** la instalación antigua hasta estar seguro
2. Revisa los logs: `tail -f /var/log/nginx/error.log`
3. Verifica permisos: `ls -la data/`
4. Consulta el script de importación para ver mensajes de error

---

**¡Buena suerte con la migración! 🚀**
