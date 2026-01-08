# 🔌 Módulo de API REST

[← Volver al README principal](../README.md)

## 📋 Descripción

El **Módulo de API REST** proporciona generación automática de endpoints RESTful para todas las tablas de las bases de datos gestionadas por el sistema. Incluye autenticación por API Keys, documentación interactiva y soporte completo para operaciones CRUD.

---

## 📁 Estructura del Módulo

```
src/Modules/Api/
├── RestController.php      # Controlador principal de API REST
└── ApiDocsController.php   # Generador de documentación
```

---

## ✨ Características

### 🔄 Endpoints Automáticos
- **GET** `/api/v1/{database}/{table}` - Listar todos los registros
- **GET** `/api/v1/{database}/{table}/{id}` - Obtener un registro específico
- **POST** `/api/v1/{database}/{table}` - Crear nuevo registro
- **PUT** `/api/v1/{database}/{table}/{id}` - Actualizar registro completo
- **PATCH** `/api/v1/{database}/{table}/{id}` - Actualizar registro parcial
- **DELETE** `/api/v1/{database}/{table}/{id}` - Eliminar registro

### 🔐 Autenticación
- API Keys almacenadas en la base de datos del sistema
- Validación en cada petición
- Gestión de keys desde el panel de administración

### 📖 Documentación Automática
- Generación dinámica de documentación tipo Swagger
- Ejemplos de uso con cURL
- Listado de todos los endpoints disponibles

---

## 🚀 Uso

### 1. Generar API Key

1. Accede al panel de administración
2. Ve a **API Management**
3. Click en "Generate New Key"
4. Copia y guarda la API Key generada

### 2. Realizar Peticiones

Todas las peticiones deben incluir el header `X-API-Key`:

```bash
curl -H "X-API-Key: tu-api-key-aqui" \
     http://localhost/data2rest/api/v1/midb/usuarios
```

### 3. Ejemplos de Uso

#### Listar Todos los Registros

```bash
GET /api/v1/midb/usuarios

curl -H "X-API-Key: abc123..." \
     http://localhost/data2rest/api/v1/midb/usuarios
```

**Respuesta:**
```json
[
  {
    "id": 1,
    "nombre": "Juan Pérez",
    "email": "juan@example.com"
  }
]
```

#### JavaScript (Fetch API)
```javascript
const response = await fetch('http://localhost/data2rest/api/v1/midb/usuarios', {
    method: 'POST',
    headers: {
        'X-API-Key': 'tu-api-key-aqui',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        nombre: 'Pedro López',
        email: 'pedro@example.com'
    })
});
const data = await response.json();
console.log(data);
```

#### Python (Requests)
```python
import requests

url = "http://localhost/data2rest/api/v1/midb/usuarios"
headers = {
    "X-API-Key": "tu-api-key-aqui",
    "Content-Type": "application/json"
}
data = {
    "nombre": "Pedro López",
    "email": "pedro@example.com"
}

response = requests.post(url, json=data, headers=headers)
print(response.json())
```

---

## 🔒 Seguridad

### API Keys

Las API Keys se almacenan en la tabla `api_keys` de la base de datos del sistema.

### Validación

Cada petición pasa por:
1. **Validación de API Key** - Verifica que existe y está activa
2. **Validación de Base de Datos** - Verifica que la BD existe
3. **Validación de Tabla** - Verifica que la tabla existe
4. **Validación de Datos** - Sanitiza inputs antes de ejecutar queries

### Prepared Statements

Todas las consultas SQL utilizan prepared statements para prevenir inyección SQL.

---

## 📊 Respuestas de Error

### 401 Unauthorized
```json
{
  "error": "Invalid or missing API key"
}
```

### 404 Not Found
```json
{
  "error": "Record not found"
}
```

---

[← Volver al README principal](../README.md)


---

## 🚧 TODOs y Mejoras Propuestas

### 🎯 Prioridad Alta

- [ ] **Soporte Multi-Database**
  - Drivers para **MySQL, PostgreSQL y SQL Server**
  - Configuración de cadena de conexión por "Nodo"
  - Endpoints unificados independientemente del motor
  - Sincronización de estructuras Multi-DBS

- [ ] **Paginación de Resultados**
  - Implementar `?page=1&limit=50`
  - Headers con información de paginación
  - Links a siguiente/anterior página
  - Total de registros en respuesta

- [ ] **Filtrado Avanzado**
  - Filtros por campo: `?nombre=Juan&edad>18`
  - Operadores: `=`, `!=`, `>`, `<`, `>=`, `<=`, `LIKE`
  - Filtros combinados con AND/OR
  - Búsqueda full-text

- [ ] **Ordenamiento**
  - Ordenar por campo: `?sort=nombre&order=asc`
  - Ordenamiento múltiple: `?sort=edad,nombre`
  - Orden ascendente/descendente

- [ ] **Rate Limiting por API Key**
  - Límite de peticiones por minuto/hora
  - Headers con información de límites
  - Respuesta 429 cuando se excede
  - Configuración personalizada por key

### 🔧 Prioridad Media

- [ ] **Versionado de API**
  - `/api/v2/` para nuevas versiones
  - Deprecación gradual de versiones antiguas
  - Changelog de cambios entre versiones

- [ ] **Webhooks**
  - Notificaciones POST a URLs configuradas
  - Eventos: create, update, delete
  - Reintentos automáticos en fallos
  - Firma de seguridad en payloads

- [ ] **Búsqueda Avanzada**
  - Endpoint `/api/v1/{db}/search`
  - Búsqueda en múltiples tablas
  - Búsqueda fuzzy
  - Resultados ponderados

- [ ] **Batch Operations**
  - Crear múltiples registros: `POST /api/v1/{db}/{table}/batch`
  - Actualizar múltiples: `PATCH /api/v1/{db}/{table}/batch`
  - Eliminar múltiples: `DELETE /api/v1/{db}/{table}/batch`

- [ ] **Campos Específicos**
  - Seleccionar campos: `?fields=id,nombre,email`
  - Reducir tamaño de respuesta
  - Optimización de queries

### 💡 Prioridad Baja

- [ ] **GraphQL API**
  - Endpoint `/graphql`
  - Queries y mutations
  - Subscripciones en tiempo real
  - Playground interactivo

- [ ] **API Keys con Scopes**
  - Permisos granulares por key
  - Read-only, write-only, full-access
  - Restricción por tabla/base de datos

- [ ] **CORS Configurable**
  - Configuración de dominios permitidos
  - Headers personalizados
  - Métodos permitidos

- [ ] **Compresión de Respuestas**
  - Gzip/Brotli automático
  - Reducción de ancho de banda
  - Header `Accept-Encoding`

### 🔐 Seguridad

- [ ] **OAuth 2.0**
  - Autenticación con tokens
  - Refresh tokens
  - Integración con proveedores (Google, GitHub)

- [ ] **IP Whitelisting**
  - Restricción por IP
  - Configuración por API Key
  - Logs de intentos bloqueados

- [ ] **Firma de Peticiones**
  - HMAC para validar integridad
  - Timestamp para prevenir replay attacks
  - Nonce para peticiones únicas

### 📊 Monitoreo

- [ ] **Métricas de API**
  - Peticiones por segundo
  - Tiempo de respuesta promedio
  - Errores por endpoint
  - Dashboard de métricas

- [ ] **Logs Detallados**
  - Registro de todas las peticiones
  - Información de usuario/IP
  - Payloads y respuestas
  - Búsqueda y filtrado de logs

### 📚 Documentación

- [ ] **OpenAPI/Swagger Completo**
  - Especificación OpenAPI 3.0
  - Documentación interactiva
  - Generación automática de clientes
  - Ejemplos en múltiples lenguajes

- [ ] **SDKs en Múltiples Lenguajes**
  - JavaScript/TypeScript
  - Python
  - PHP
  - Ruby
  - Go

---

[← Volver al README principal](../README.md)
