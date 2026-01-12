---
description: Configurar copias de seguridad automáticas (Cron Job)
---

Este workflow describe cómo configurar copias de seguridad automáticas para `data2rest`.

## 1. Verificar el script de respaldo
Se ha creado un script en `scripts/backup.php` que realiza la copia de seguridad de todas las bases de datos `.sqlite` en el directorio `data/backups/` y gestiona la rotación de archivos (mantiene las últimas 50 copias por defecto).

Puedes probarlo manualmente ejecutando:
```bash
php scripts/backup.php
```
*Nota: Si recibes errores de permisos, asegúrate de que el usuario que ejecuta el comando tenga permisos de escritura en la carpeta `data/backups`.*


## 2. Sincronización en la Nube (Google Drive / Apps Script)
El script también verifica si existe una URL configurada en `system_settings` (clave `backup_cloud_url`).
- Si existe, el script intentará subir automáticamente el archivo ZIP generado a esa URL mediante POST.
- Esto permite tener una copia remota inmediata tras cada respaldo local.
- *Nota: Archivos mayores a 20MB pueden ser omitidos para evitar límites de timeouts en Apps Script.*

## 3. Configurar Cron Job
Para ejecutar esto automáticamente cada 4 horas, debes añadir una tarea al Cron de tu sistema.

1. Abre el editor de cron:
   ```bash
   crontab -e
   ```

2. Añade la siguiente línea al final del archivo:
   ```cron
   # Respaldo de Data2Rest cada 4 horas
   0 */4 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/backup.php >> /opt/homebrew/var/www/data2rest/data/backup_cron.log 2>&1
   ```
   *(Asegúrate de que la ruta a `php` sea correcta. Puedes verificarla con `which php`)*

3. Guarda y cierra el editor.

## 3. Opciones Avanzadas
El script acepta un argumento `--keep=N` para definir cuántas copias guardar. Ejemplo para guardar solo las últimas 10:
```cron
0 */4 * * * /usr/bin/php /opt/homebrew/var/www/data2rest/scripts/backup.php --keep=10 >> ...
```

## 4. Verificación
Verifica que la tarea se haya añadido correctamente listando las tareas cron:
```bash
crontab -l
```
