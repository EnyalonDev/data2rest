# Guía de Migración: SQLite → MySQL

> **Escenario**: Migrar de instalación actual (SQLite) a nueva instalación con MySQL como base de datos del sistema

---

## 📋 Configuración

**Sistema Actual:**
- Base de datos del sistema: `data/system.db` (SQLite)
- Bases de datos de clientes: `data/*.db` (SQLite)

**Sistema Nuevo:**
- Base de datos del sistema: **MySQL** (servidor remoto o local)
- Bases de datos de clientes: SQLite (se mantienen)

---

## 🎯 Paso 1: Preparar Servidor MySQL

### 1.1 Crear Base de Datos MySQL

```sql
-- Conectar a MySQL
mysql -u root -p

-- Crear base de datos
CREATE DATABASE data2rest_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario (opcional, recomendado)
CREATE USER 'data2rest'@'localhost' IDENTIFIED BY 'tu_password_seguro';
GRANT ALL PRIVILEGES ON data2rest_system.* TO 'data2rest'@'localhost';
FLUSH PRIVILEGES;

-- Verificar
SHOW DATABASES;
EXIT;
```

### 1.2 Probar Conexión

```bash
mysql -u data2rest -p data2rest_system
```

---

## 🎯 Paso 2: Clonar Repositorio y Configurar

### 2.1 Clonar en Producción

```bash
# SSH a producción
ssh usuario@servidor

# Ir al directorio web
cd /opt/homebrew/var/www

# Clonar repositorio
git clone https://github.com/tu-repo/data2rest.git data2rest-new
cd data2rest-new

# Actualizar a última versión
git pull origin main
```

### 2.2 Configurar Permisos

```bash
chmod -R 755 .
mkdir -p data uploads
chmod -R 777 data uploads
```

---

## 🎯 Paso 3: Instalación Web con MySQL

### 3.1 Acceder al Instalador

Navegar a: `https://data2rest-new.nestorovallos.com/install`

### 3.2 Completar Formulario

**Tipo de Base de Datos:** MySQL

**Configuración MySQL:**
- Host: `localhost` (o IP del servidor MySQL)
- Puerto: `3306`
- Base de datos: `data2rest_system`
- Usuario: `data2rest`
- Contraseña: `tu_password_seguro`

**Usuario Administrador:**
- Usuario: `admin`
- Contraseña: `[tu-password-seguro]`

### 3.3 Completar Instalación

✅ Esperar a que termine la instalación
✅ Verificar que puedes hacer login

---

## 🎯 Paso 4: Exportar Datos de SQLite

### 4.1 En el Servidor Actual

```bash
# Conectar al servidor de producción actual
ssh usuario@servidor

# Ir al directorio actual
cd /opt/homebrew/var/www/data2rest

# Crear directorio de migración
mkdir -p ~/migracion_mysql

# Copiar system.db
cp data/system.db ~/migracion_mysql/

# Copiar bases de datos de clientes (IMPORTANTE: NO copiar system.db)
cd data
for db in *.db; do
    if [ "$db" != "system.db" ]; then
        cp "$db" ~/migracion_mysql/
        echo "Copiado: $db"
    fi
done

# Copiar uploads
cp -r ../uploads ~/migracion_mysql/

# Verificar
ls -lh ~/migracion_mysql/
```

---

## 🎯 Paso 5: Migrar Datos a MySQL

### 5.1 Copiar system.db a Nueva Instalación

```bash
# Copiar system.db al servidor nuevo (si es el mismo servidor)
cp ~/migracion_mysql/system.db /tmp/old_system.db

# Si es servidor diferente, usar scp
# scp ~/migracion_mysql/system.db usuario@nuevo-servidor:/tmp/old_system.db
```

### 5.2 Ejecutar Script de Migración

```bash
# Ir a la nueva instalación
cd /opt/homebrew/var/www/data2rest-new

# Ejecutar migración SQLite → MySQL
php scripts/migrate_sqlite_to_mysql.php /tmp/old_system.db
```

**Salida Esperada:**
```
╔════════════════════════════════════════════════════════════╗
║   Data2Rest - Migración SQLite → MySQL                    ║
╚════════════════════════════════════════════════════════════╝

✓ Conectado a MySQL (nueva instalación)
✓ Conectado a SQLite (instalación antigua)

📦 Migrando proyectos...
   ✓ 'Mi Proyecto' (ID: 1 → 2)
   ✓ 'Otro Proyecto' (ID: 2 → 3)

📦 Migrando usuarios clientes...
   ✓ 'cliente1' (ID: 5 → 6)
   ✓ 'cliente2' (ID: 6 → 7)

📦 Migrando configuraciones de bases de datos...
   ✓ 'db_cliente1' (ID: 1 → 1)
   ✓ 'db_cliente2' (ID: 2 → 2)

📦 Migrando relaciones proyecto-usuario...
   ✓ 4 relaciones migradas

╔════════════════════════════════════════════════════════════╗
║   ✅ MIGRACIÓN COMPLETADA EXITOSAMENTE                     ║
╚════════════════════════════════════════════════════════════╝
```

---

## 🎯 Paso 6: Copiar Archivos de Clientes

### 6.1 Copiar Bases de Datos de Clientes

```bash
# Copiar archivos .db (excepto system.db que ya no se usa)
cp ~/migracion_mysql/*.db /opt/homebrew/var/www/data2rest-new/data/

# Verificar que NO se copió system.db
ls -la /opt/homebrew/var/www/data2rest-new/data/
# Debe mostrar solo las bases de datos de clientes
```

### 6.2 Copiar Archivos Media

```bash
cp -r ~/migracion_mysql/uploads/* /opt/homebrew/var/www/data2rest-new/uploads/
```

### 6.3 Configurar Permisos

```bash
cd /opt/homebrew/var/www/data2rest-new
chmod -R 777 data uploads
```

---

## 🎯 Paso 7: Verificación

### 7.1 Verificar MySQL

```bash
# Conectar a MySQL
mysql -u data2rest -p data2rest_system

# Verificar proyectos
SELECT id, name, status FROM projects;

# Verificar usuarios
SELECT id, username, email, role_id FROM users WHERE role_id >= 3;

# Verificar bases de datos
SELECT id, name, project_id FROM `databases`;

EXIT;
```

### 7.2 Verificar Archivos

```bash
# Verificar que existen las bases de datos de clientes
ls -lh /opt/homebrew/var/www/data2rest-new/data/*.db

# Verificar uploads
ls -lh /opt/homebrew/var/www/data2rest-new/uploads/
```

### 7.3 Probar en el Navegador

1. Acceder a: `https://data2rest-new.nestorovallos.com`
2. Login como admin
3. Verificar que aparecen los proyectos
4. Abrir un proyecto y verificar bases de datos
5. Probar Google OAuth (si aplica)

---

## 🎯 Paso 8: Puesta en Producción

### 8.1 Crear Backup de Instalación Actual

```bash
cd /opt/homebrew/var/www
tar -czf ~/data2rest_old_backup_$(date +%Y%m%d).tar.gz data2rest
```

### 8.2 Cambiar Instalación

```bash
# Renombrar actual
mv data2rest data2rest-old-$(date +%Y%m%d)

# Renombrar nueva
mv data2rest-new data2rest

# Reiniciar servidor web (si es necesario)
sudo systemctl restart nginx
# o
sudo systemctl restart apache2
```

### 8.3 Verificar Producción

```bash
curl -I https://data2rest.nestorovallos.com
```

---

## 🆘 Solución de Problemas

### Error: "Access denied for user"

```bash
# Verificar credenciales MySQL
mysql -u data2rest -p

# Si falla, recrear usuario
mysql -u root -p
DROP USER 'data2rest'@'localhost';
CREATE USER 'data2rest'@'localhost' IDENTIFIED BY 'nueva_password';
GRANT ALL PRIVILEGES ON data2rest_system.* TO 'data2rest'@'localhost';
FLUSH PRIVILEGES;
```

### Error: "Table doesn't exist"

La instalación web no se completó correctamente. Reiniciar:

```bash
# Borrar base de datos
mysql -u root -p
DROP DATABASE data2rest_system;
CREATE DATABASE data2rest_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Volver a hacer la instalación web
```

### Error: "Database file not found"

Las bases de datos de clientes no se copiaron correctamente:

```bash
# Verificar que existen en el backup
ls -lh ~/migracion_mysql/*.db

# Copiar nuevamente
cp ~/migracion_mysql/*.db /opt/homebrew/var/www/data2rest/data/
chmod -R 777 /opt/homebrew/var/www/data2rest/data
```

---

## ✅ Checklist Final

### Antes de Migrar
- [ ] Servidor MySQL configurado
- [ ] Base de datos `data2rest_system` creada
- [ ] Usuario MySQL creado con permisos
- [ ] Backup completo de instalación actual

### Durante la Migración
- [ ] Repositorio clonado
- [ ] Instalación web completada (MySQL)
- [ ] Script de migración ejecutado sin errores
- [ ] Bases de datos de clientes copiadas
- [ ] Archivos media copiados
- [ ] Permisos configurados

### Después de la Migración
- [ ] Login funciona
- [ ] Proyectos visibles
- [ ] Bases de datos accesibles
- [ ] Google OAuth funcionando (si aplica)
- [ ] API Keys funcionando
- [ ] Usuarios pueden acceder

---

## 📊 Ventajas de MySQL

✅ **Mejor rendimiento** en consultas complejas
✅ **Concurrencia** mejorada (múltiples usuarios simultáneos)
✅ **Escalabilidad** para crecimiento futuro
✅ **Backups** más robustos y automatizables
✅ **Replicación** y alta disponibilidad (si se necesita)

---

**¡Listo para migrar! 🚀**
