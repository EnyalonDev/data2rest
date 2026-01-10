---
description: Sincroniza el esquema de la base de datos de sistema con el instalador automático
---
# Workflow de Sincronización de Base de Datos

Este workflow debe ejecutarse **siempre** que se realice un cambio estructural en la base de datos `system.sqlite` (crear tablas, modificar columnas, etc.).

// turbo-all
1. Realiza las modificaciones necesarias en la base de datos operativa (`data/system.sqlite`).
2. Ejecuta el script de sincronización para actualizar el Maestro de Esquema en el código:
```bash
php scripts/sync_installer_schema.php
```
3. Verifica que `src/Core/Installer.php` refleje los cambios en su array `$SCHEMA`.
4. Si el cambio requiere lógica de migración compleja (más allá de añadir columnas), añade la lógica necesaria en el método `runHealthChecks` de `Installer.php`.

Este proceso asegura que:
- Las nuevas instalaciones tengan la estructura correcta.
- Los sitios existentes se actualicen automáticamente al detectar la falta de columnas o tablas.
