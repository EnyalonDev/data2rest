# Scripts Activos - Data2Rest

Este directorio contiene **únicamente** los scripts que son utilizados activamente por el sistema.

---

## 📋 Scripts Disponibles

### 1. `maintenance.php`

**Descripción:** Limpieza automática del sistema (versiones antiguas, logs)

**Uso:**
```bash
php scripts/maintenance.php
```

**Cron recomendado:**
```cron
# Ejecutar mantenimiento diariamente a las 3 AM
0 3 * * * /usr/bin/php /ruta/a/data2rest/scripts/maintenance.php
```

**Qué hace:**
- Elimina versiones antiguas de registros
- Limpia logs antiguos
- Libera espacio en disco

---

### 2. `backup.php`

**Descripción:** Backup automático de **TODAS** las bases de datos del sistema (SQLite, MySQL, PostgreSQL)

**Uso:**
```bash
# Backup con retención por defecto (50 backups)
php scripts/backup.php

# Backup con retención personalizada
php scripts/backup.php --keep=30
```

**Cron recomendado:**
```cron
# Ejecutar backup diariamente a las 2 AM
0 2 * * * /usr/bin/php /ruta/a/data2rest/scripts/backup.php >> /ruta/a/data2rest/data/logs/backup.log 2>&1
```

**Qué hace:**
- Respalda **TODAS** las bases de datos registradas en el sistema:
  - **SQLite:** Copia directa de archivos `.sqlite`
  - **MySQL:** Usa `mysqldump` para generar archivos `.sql`
  - **PostgreSQL:** Usa `pg_dump` para generar archivos `.sql`
- Crea archivo ZIP con timestamp
- Sincroniza con Google Drive (si está configurado)
- Aplica política de retención (elimina backups antiguos)
- Genera manifest v2.0 con estadísticas

**Configuración Cloud:**
- Configurar `backup_cloud_url` en `system_settings` para sincronización automática
- Límite de tamaño: 20MB para cloud sync

**Ejemplo de salida:**
```
[2026-01-31 14:38:37] Starting automated backup...
Backup created successfully: backup_2026-01-31_14-38-37.zip
Databases: 10/10 | Size: 0.53 MB
Retention Policy (Keep 50): No cleanup needed
Cloud URL found. Uploading to Google Drive...
Cloud Upload Successful.
[2026-01-31 14:38:45] Backup process completed.
```

---

### 3. `billing_mark_overdue.php`

**Descripción:** Marcar cuotas vencidas en el módulo de billing

**Uso:**
```bash
php scripts/billing_mark_overdue.php
```

**Cron recomendado:**
```cron
# Ejecutar diariamente a las 00:30 AM
30 0 * * * /usr/bin/php /ruta/a/data2rest/scripts/billing_mark_overdue.php
```

**Qué hace:**
- Marca cuotas como vencidas según fecha de vencimiento
- Genera estadísticas de morosidad
- Registra en logs del sistema

---

### 4. `billing_send_reminders.php`

**Descripción:** Enviar recordatorios de pago a clientes

**Uso:**
```bash
php scripts/billing_send_reminders.php
```

**Cron recomendado:**
```cron
# Ejecutar diariamente a las 9 AM
0 9 * * * /usr/bin/php /ruta/a/data2rest/scripts/billing_send_reminders.php
```

**Qué hace:**
- Envía emails de recordatorio a clientes con cuotas próximas a vencer
- Envía notificaciones de cuotas vencidas
- Registra envíos en logs

---

## 🔒 Seguridad

El archivo `.htaccess` en este directorio **bloquea el acceso web** a todos los scripts:

```apache
# Deny all access to scripts directory
Order Allow,Deny
Deny from all
```

**Importante:** Los scripts SOLO pueden ejecutarse desde línea de comandos (CLI).

---

## 📊 Configuración de Cron Jobs

### Ejemplo completo de crontab:

```cron
# Data2Rest - Cron Jobs
# Editar con: crontab -e

# Backup diario a las 2 AM
0 2 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/backup.php >> /opt/homebrew/var/www/data2rest/data/logs/backup.log 2>&1

# Mantenimiento diario a las 3 AM
0 3 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/maintenance.php >> /opt/homebrew/var/www/data2rest/data/logs/maintenance.log 2>&1

# Billing: Marcar cuotas vencidas a las 00:30 AM
30 0 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/billing_mark_overdue.php >> /opt/homebrew/var/www/data2rest/data/logs/billing.log 2>&1

# Billing: Enviar recordatorios a las 9 AM
0 9 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/billing_send_reminders.php >> /opt/homebrew/var/www/data2rest/data/logs/billing.log 2>&1
```

### Verificar cron jobs activos:

```bash
crontab -l
```

---

## 📝 Logs

Los scripts generan logs en:
- `data/logs/backup.log`
- `data/logs/maintenance.log`
- `data/logs/billing.log`

También registran eventos en la tabla `logs` del sistema.

---

## 🗂️ Scripts Archivados

Los scripts deprecated (migraciones, debugging, etc.) fueron movidos a:

```
/private_docs/archived_scripts/
```

Ver `INDEX.md` en ese directorio para más información.

---

**Desarrollado por Data2Rest**  
**Última actualización:** 2026-01-31
