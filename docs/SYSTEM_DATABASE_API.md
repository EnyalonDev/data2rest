# API Endpoints - System Database Administration

## Descripción

Los endpoints API del módulo de Administración de Base de Datos del Sistema permiten realizar operaciones programáticas sobre la base de datos del sistema. **Todos los endpoints requieren un API Key de un usuario Super Admin**.

## 🔒 Autenticación y Seguridad

### Requisitos de API Key

Para usar estos endpoints, el API Key debe cumplir:

1. **API Key válida y activa** en la tabla `api_keys`
2. **Usuario asociado** con rol asignado
3. **Rol con permisos de Super Admin** (`permissions.all = true`)

### Validación de Super Admin

El controlador verifica automáticamente:

```php
// 1. Obtiene el API Key
$apiKey = $headers['X-API-KEY'] ?? $_GET['api_key'];

// 2. Valida que el key existe y está activo
SELECT ak.*, u.id as user_id, u.role_id 
FROM api_keys ak 
LEFT JOIN users u ON ak.user_id = u.id 
WHERE ak.key_value = ? AND ak.status = 1

// 3. Verifica que el rol tiene permissions.all = true
SELECT permissions FROM roles WHERE id = ?
```

Si cualquiera de estas validaciones falla, se retorna un error `403 Forbidden`.

### Métodos de Autenticación

El API Key puede enviarse de dos formas:

1. **Header HTTP** (recomendado):
   ```
   X-API-KEY: tu_api_key_aqui
   ```

2. **Query Parameter**:
   ```
   ?api_key=tu_api_key_aqui
   ```

## 📋 Endpoints Disponibles

### 1. GET /api/system/info

Obtiene información general de la base de datos del sistema.

**Autenticación**: Super Admin API Key requerido

**Respuesta exitosa** (200):
```json
{
  "success": true,
  "data": {
    "database_size": "2.45 MB",
    "database_size_bytes": 2568192,
    "total_tables": 15,
    "total_records": 1234,
    "last_backup": {
      "filename": "system_auto_2026-01-13_02-00-00.sqlite",
      "size": "2.40 MB",
      "size_bytes": 2516992,
      "date": "2026-01-13 02:00:00",
      "timestamp": 1736748000,
      "type": "automatic"
    },
    "disk_space": {
      "total": "500 GB",
      "used": "250 GB",
      "free": "250 GB",
      "used_percent": 50.00
    }
  }
}
```

**Ejemplo de uso**:
```bash
curl -H "X-API-KEY: your_super_admin_key" \
     https://your-domain.com/api/system/info
```

---

### 2. POST /api/system/backup

Crea un nuevo backup de la base de datos del sistema.

**Autenticación**: Super Admin API Key requerido

**Respuesta exitosa** (201):
```json
{
  "success": true,
  "message": "Backup created successfully",
  "data": {
    "filename": "system_api_2026-01-13_21-15-30.sqlite",
    "size": "2.45 MB",
    "size_bytes": 2568192,
    "created_at": "2026-01-13 21:15:30",
    "type": "api"
  }
}
```

**Ejemplo de uso**:
```bash
curl -X POST \
     -H "X-API-KEY: your_super_admin_key" \
     https://your-domain.com/api/system/backup
```

**Logging**: Se registra en logs con tipo `SYSTEM_BACKUP_CREATED` incluyendo el nombre del API Key usado.

---

### 3. GET /api/system/backups

Lista todos los backups disponibles del sistema.

**Autenticación**: Super Admin API Key requerido

**Respuesta exitosa** (200):
```json
{
  "success": true,
  "data": [
    {
      "filename": "system_auto_2026-01-13_02-00-00.sqlite",
      "size": "2.40 MB",
      "size_bytes": 2516992,
      "date": "2026-01-13 02:00:00",
      "timestamp": 1736748000,
      "type": "automatic"
    },
    {
      "filename": "system_manual_2026-01-12_18-30-00.sqlite",
      "size": "2.38 MB",
      "size_bytes": 2494464,
      "date": "2026-01-12 18:30:00",
      "timestamp": 1736721000,
      "type": "manual"
    }
  ],
  "count": 2
}
```

**Tipos de backup**:
- `manual` - Creado desde la interfaz web
- `automatic` - Creado por cron job
- `api` - Creado vía API
- `pre-restore` - Creado antes de restaurar

**Ejemplo de uso**:
```bash
curl -H "X-API-KEY: your_super_admin_key" \
     https://your-domain.com/api/system/backups
```

---

### 4. POST /api/system/optimize

Optimiza la base de datos del sistema ejecutando VACUUM y ANALYZE.

**Autenticación**: Super Admin API Key requerido

**Respuesta exitosa** (200):
```json
{
  "success": true,
  "message": "Database optimized successfully",
  "operations": ["VACUUM", "ANALYZE"]
}
```

**Ejemplo de uso**:
```bash
curl -X POST \
     -H "X-API-KEY: your_super_admin_key" \
     https://your-domain.com/api/system/optimize
```

**Cuándo usar**:
- Después de eliminar grandes cantidades de datos
- Como parte de mantenimiento programado
- Cuando la base de datos crece significativamente

**Logging**: Se registra en logs con tipo `SYSTEM_DATABASE_OPTIMIZED`.

---

### 5. POST /api/system/query

Ejecuta una consulta SELECT en la base de datos del sistema.

**Autenticación**: Super Admin API Key requerido

**Restricciones de seguridad**:
- ✅ Solo se permiten consultas `SELECT`
- ❌ Prohibido: `DROP`, `TRUNCATE`, `DELETE`, `UPDATE`, `INSERT`, `ALTER`

**Request Body**:
```json
{
  "query": "SELECT * FROM users WHERE status = 1 LIMIT 10"
}
```

**Respuesta exitosa** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "username": "admin",
      "status": 1,
      "created_at": "2026-01-01 00:00:00"
    }
  ],
  "count": 1
}
```

**Errores comunes**:

- **400 Bad Request**: Query vacío
  ```json
  {"error": "Query is required"}
  ```

- **403 Forbidden**: Query no es SELECT
  ```json
  {"error": "Only SELECT queries are allowed via API"}
  ```

- **403 Forbidden**: Query contiene operaciones prohibidas
  ```json
  {"error": "Query contains forbidden operations"}
  ```

- **500 Internal Server Error**: Error de ejecución
  ```json
  {"error": "Query execution failed: [mensaje de error]"}
  ```

**Ejemplo de uso**:
```bash
curl -X POST \
     -H "X-API-KEY: your_super_admin_key" \
     -H "Content-Type: application/json" \
     -d '{"query":"SELECT COUNT(*) as total FROM logs"}' \
     https://your-domain.com/api/system/query
```

**Logging**: Se registra en logs con tipo `SYSTEM_QUERY_EXECUTED` incluyendo:
- Query ejecutado (primeros 500 caracteres)
- Número de filas retornadas
- Nombre del API Key usado

---

### 6. GET /api/system/tables

Lista todas las tablas del sistema con estadísticas.

**Autenticación**: Super Admin API Key requerido

**Respuesta exitosa** (200):
```json
{
  "success": true,
  "data": [
    {
      "name": "users",
      "records": 25
    },
    {
      "name": "api_keys",
      "records": 10
    },
    {
      "name": "logs",
      "records": 5432
    }
  ],
  "count": 3
}
```

**Ejemplo de uso**:
```bash
curl -H "X-API-KEY: your_super_admin_key" \
     https://your-domain.com/api/system/tables
```

---

## 🔐 Códigos de Estado HTTP

| Código | Significado | Cuándo ocurre |
|--------|-------------|---------------|
| 200 | OK | Operación exitosa (GET) |
| 201 | Created | Backup creado exitosamente |
| 400 | Bad Request | Datos inválidos o faltantes |
| 401 | Unauthorized | API Key no proporcionado |
| 403 | Forbidden | API Key inválido o sin permisos de Super Admin |
| 500 | Internal Server Error | Error del servidor |

## 📝 Logging

Todas las operaciones API se registran en la tabla `logs` con los siguientes tipos de eventos:

| Evento | Descripción |
|--------|-------------|
| `SYSTEM_API_INFO` | Consulta de información del sistema |
| `SYSTEM_BACKUP_CREATED` | Backup creado vía API |
| `SYSTEM_DATABASE_OPTIMIZED` | Base de datos optimizada |
| `SYSTEM_QUERY_EXECUTED` | Consulta SQL ejecutada |

Cada log incluye:
- Nombre del API Key usado
- Timestamp
- Detalles de la operación

## 🧪 Ejemplos de Integración

### Python

```python
import requests

API_KEY = "your_super_admin_key"
BASE_URL = "https://your-domain.com"

headers = {
    "X-API-KEY": API_KEY,
    "Content-Type": "application/json"
}

# Obtener información del sistema
response = requests.get(f"{BASE_URL}/api/system/info", headers=headers)
print(response.json())

# Crear backup
response = requests.post(f"{BASE_URL}/api/system/backup", headers=headers)
print(response.json())

# Ejecutar consulta
query_data = {"query": "SELECT COUNT(*) as total FROM users"}
response = requests.post(f"{BASE_URL}/api/system/query", 
                        headers=headers, 
                        json=query_data)
print(response.json())
```

### JavaScript (Node.js)

```javascript
const axios = require('axios');

const API_KEY = 'your_super_admin_key';
const BASE_URL = 'https://your-domain.com';

const headers = {
  'X-API-KEY': API_KEY,
  'Content-Type': 'application/json'
};

// Obtener información del sistema
async function getSystemInfo() {
  const response = await axios.get(`${BASE_URL}/api/system/info`, { headers });
  console.log(response.data);
}

// Crear backup
async function createBackup() {
  const response = await axios.post(`${BASE_URL}/api/system/backup`, {}, { headers });
  console.log(response.data);
}

// Ejecutar consulta
async function executeQuery(query) {
  const response = await axios.post(
    `${BASE_URL}/api/system/query`,
    { query },
    { headers }
  );
  console.log(response.data);
}

// Uso
getSystemInfo();
createBackup();
executeQuery('SELECT * FROM logs ORDER BY id DESC LIMIT 10');
```

### PHP

```php
<?php

$apiKey = 'your_super_admin_key';
$baseUrl = 'https://your-domain.com';

// Obtener información del sistema
$ch = curl_init("$baseUrl/api/system/info");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-KEY: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
echo $response;

// Crear backup
$ch = curl_init("$baseUrl/api/system/backup");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-KEY: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
echo $response;
```

## ⚠️ Consideraciones de Seguridad

1. **Nunca compartas tu API Key de Super Admin**: Tiene acceso total al sistema
2. **Usa HTTPS**: Siempre en producción para proteger el API Key en tránsito
3. **Rotación de keys**: Cambia periódicamente los API Keys
4. **Monitoreo**: Revisa los logs regularmente para detectar uso no autorizado
5. **Rate limiting**: Considera implementar límites de tasa en producción
6. **IP Whitelisting**: Restringe el acceso a IPs conocidas cuando sea posible

## 🔗 Enlaces Relacionados

- [Documentación del Módulo](SYSTEM_DATABASE.md)
- [Documentación de API REST](../README.md#api)
- [Gestión de API Keys](../README.md#api-keys)
