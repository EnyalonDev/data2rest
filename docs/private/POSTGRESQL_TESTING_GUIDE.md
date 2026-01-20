# PostgreSQL Testing Guide

## Preparación del Entorno de Pruebas

### Opción 1: PostgreSQL Local (macOS con Homebrew)

#### 1. Instalar PostgreSQL
```bash
brew install postgresql@15
brew services start postgresql@15
```

#### 2. Crear Base de Datos de Prueba
```bash
# Conectar como usuario postgres
psql postgres

# Dentro de psql:
CREATE DATABASE test_data2rest;
\q
```

#### 3. Configurar Contraseña (Opcional)
```bash
psql postgres
ALTER USER postgres WITH PASSWORD 'tu_contraseña';
\q
```

---

### Opción 2: PostgreSQL con Docker

#### 1. Iniciar Contenedor PostgreSQL
```bash
docker run --name postgres-test \
  -e POSTGRES_PASSWORD=test123 \
  -e POSTGRES_DB=test_data2rest \
  -p 5432:5432 \
  -d postgres:15
```

#### 2. Verificar que está corriendo
```bash
docker ps | grep postgres-test
```

#### 3. Conectar al contenedor (opcional)
```bash
docker exec -it postgres-test psql -U postgres -d test_data2rest
```

---

### Opción 3: PostgreSQL Remoto

Si tienes un servidor PostgreSQL remoto, asegúrate de:
1. Tener las credenciales correctas
2. El puerto 5432 esté accesible
3. El usuario tenga permisos de CREATE TABLE

---

## Ejecutar las Pruebas

### 1. Configurar Credenciales

Edita el archivo `scripts/test_postgresql.php` líneas 35-43:

```php
$testConfig = [
    'type' => 'pgsql',
    'host' => 'localhost',        // Cambia si es remoto
    'port' => 5432,
    'database' => 'test_data2rest', // Tu base de datos
    'username' => 'postgres',      // Tu usuario
    'password' => '',              // Tu contraseña
    'schema' => 'public',
    'charset' => 'utf8'
];
```

### 2. Ejecutar Script de Pruebas

```bash
cd /opt/homebrew/var/www/data2rest
php scripts/test_postgresql.php
```

### 3. Interpretar Resultados

El script ejecutará **18 pruebas**:

✅ **Verde** = Prueba exitosa  
❌ **Rojo** = Prueba fallida  
ℹ️ **Amarillo** = Información adicional

**Pruebas incluidas:**
1. Creación del adaptador
2. Conexión a PostgreSQL
3. Método testConnection()
4. Obtener tipo de BD
5. Crear tabla
6. Listar tablas
7. Obtener columnas
8. Añadir columna
9. Insertar datos (CREATE)
10. Leer datos (READ)
11. Actualizar datos (UPDATE)
12. Transacciones (Rollback)
13. Transacciones (Commit)
14. Eliminar columna
15. Tamaño de BD
16. Optimizar BD (VACUUM)
17. Eliminar tabla
18. Integración con Factory

---

## Solución de Problemas

### Error: "Connection refused"
```
✗ Connection failed: SQLSTATE[08006] Connection refused
```

**Solución:**
- Verifica que PostgreSQL esté corriendo: `brew services list` o `docker ps`
- Verifica el puerto: `lsof -i :5432`
- Revisa host y puerto en la configuración

### Error: "Authentication failed"
```
✗ Connection failed: SQLSTATE[28P01] password authentication failed
```

**Solución:**
- Verifica usuario y contraseña
- Si usas Docker, la contraseña es la que definiste en `-e POSTGRES_PASSWORD`
- Para PostgreSQL local sin contraseña, deja el campo vacío: `'password' => ''`

### Error: "Database does not exist"
```
✗ Connection failed: SQLSTATE[3D000] database "test_data2rest" does not exist
```

**Solución:**
```bash
psql postgres
CREATE DATABASE test_data2rest;
\q
```

### Error: "Permission denied"
```
✗ Failed to create table: permission denied for schema public
```

**Solución:**
```bash
psql test_data2rest
GRANT ALL ON SCHEMA public TO postgres;
\q
```

---

## Pruebas Manuales (Interfaz Web)

Después de que el script pase, puedes probar la interfaz web:

### 1. Acceder al Formulario
```
http://localhost/admin/databases/create-form
```

### 2. Seleccionar PostgreSQL
- Click en la tarjeta azul "PostgreSQL"
- Llenar los campos de configuración
- Click en "Test Connection"
- Debe mostrar: ✓ Connection successful

### 3. Crear Base de Datos
- Click en "Create Database"
- Debe redirigir a la vista de sincronización

### 4. Crear Tabla de Prueba
- En la vista de tablas, crear una tabla llamada "productos"
- Añadir campos: nombre (TEXT), precio (REAL)

### 5. Probar CRUD
- Insertar registros vía interfaz web
- Editar registros
- Eliminar registros
- Verificar que fecha_edicion se actualiza automáticamente

### 6. Probar API
```bash
# Obtener API key desde /admin/api

# GET - Listar registros
curl -H "X-API-KEY: tu_api_key" \
  http://localhost/api/v1/{db_id}/productos

# POST - Crear registro
curl -X POST \
  -H "X-API-KEY: tu_api_key" \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Test","precio":99.99}' \
  http://localhost/api/v1/{db_id}/productos

# PUT - Actualizar registro
curl -X PUT \
  -H "X-API-KEY: tu_api_key" \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Updated","precio":149.99}' \
  http://localhost/api/v1/{db_id}/productos/1

# DELETE - Eliminar registro
curl -X DELETE \
  -H "X-API-KEY: tu_api_key" \
  http://localhost/api/v1/{db_id}/productos/1
```

---

## Checklist de Validación

### Pruebas Automatizadas
- [ ] Script ejecuta sin errores
- [ ] 18/18 pruebas pasan (100%)
- [ ] Triggers funcionan correctamente
- [ ] Transacciones funcionan

### Pruebas de Interfaz
- [ ] Formulario muestra 3 opciones (SQLite, MySQL, PostgreSQL)
- [ ] Test Connection funciona
- [ ] Creación de BD exitosa
- [ ] Creación de tablas funciona
- [ ] CRUD web funciona

### Pruebas de API
- [ ] GET lista registros
- [ ] POST crea registros
- [ ] PUT actualiza registros
- [ ] DELETE elimina registros
- [ ] Documentación API muestra tablas PostgreSQL

### Pruebas Avanzadas
- [ ] Múltiples schemas funcionan
- [ ] Tipos de datos especiales (JSONB, ARRAY) - Opcional
- [ ] Rendimiento aceptable
- [ ] Manejo de errores correcto

---

## Comandos Útiles de PostgreSQL

```bash
# Listar bases de datos
psql -l

# Conectar a una BD
psql test_data2rest

# Dentro de psql:
\dt              # Listar tablas
\d tabla_nombre  # Describir tabla
\du              # Listar usuarios
\dn              # Listar schemas
\q               # Salir

# Backup
pg_dump test_data2rest > backup.sql

# Restore
psql test_data2rest < backup.sql

# Ver tamaño de BD
SELECT pg_size_pretty(pg_database_size('test_data2rest'));
```

---

## Siguiente Paso

Una vez que todas las pruebas pasen:

```bash
# Revisar cambios
git status
git diff

# Hacer commit
git add .
git commit -m "feat: PostgreSQL integration complete with full CRUD and API support"
git push origin main
```

---

**¿Listo para probar?**

1. Elige tu método de instalación (Local/Docker/Remoto)
2. Configura las credenciales en `scripts/test_postgresql.php`
3. Ejecuta: `php scripts/test_postgresql.php`
4. Revisa los resultados
5. Prueba la interfaz web
6. ¡Haz commit si todo funciona!
