# 🗄️ Módulo de Bases de Datos

[← Volver al README principal](../README.md)

## 📋 Descripción

El **Módulo de Bases de Datos** permite crear y gestionar bases de datos SQLite de forma visual, con soporte completo para operaciones CRUD, configuración de campos y gestión de tablas.

---

## 📁 Estructura del Módulo

```
src/Modules/Database/
├── DatabaseController.php  # Gestión de bases de datos y tablas
└── CrudController.php      # Operaciones CRUD en registros
```

---

## ✨ Características

### 📦 Gestión de Bases de Datos
- Crear nuevas bases de datos SQLite
- Listar bases de datos existentes
- Eliminar bases de datos
- Ver información detallada

### 📋 Gestión de Tablas
- Crear tablas dinámicamente
- Configurar campos con tipos de datos
- Eliminar tablas
- Ver estructura de tablas

### ✏️ Operaciones CRUD
- Crear registros
- Leer/Listar registros
- Actualizar registros
- Eliminar registros
- Búsqueda y filtrado

### 🎨 Configuración de Campos
- Tipos de datos: TEXT, INTEGER, REAL, BLOB
- Campos especiales: file, textarea, checkbox
- Validaciones personalizadas
- Valores por defecto

---

## 🚀 Uso

### 1. Crear una Base de Datos

1. Ve a **Databases**
2. Completa el formulario "Initialize New Node"
3. Ingresa nombre y descripción
4. Click en "Create Database"

### 2. Crear Tablas

1. Selecciona una base de datos
2. Click en "View Tables"
3. Ingresa el nombre de la tabla
4. Click en "Create Table"

### 3. Configurar Campos

1. Click en el ícono ⚙️ de la tabla
2. Agrega campos:
   - **Field Name**: nombre del campo
   - **Type**: tipo de dato (TEXT, INTEGER, etc.)
   - **Special**: opciones especiales (file, textarea)
3. Guarda la configuración

### 4. Gestionar Datos

1. Click en "Enter Segment"
2. Usa "New Entry" para crear registros
3. Edita con el botón "Edit"
4. Elimina con el botón "Kill"

---

## 🔧 Controladores

### DatabaseController.php

**Métodos principales:**
- `index()` - Lista todas las bases de datos
- `create()` - Crea nueva base de datos
- `delete()` - Elimina base de datos
- `viewTables()` - Muestra tablas de una BD
- `createTable()` - Crea nueva tabla
- `deleteTable()` - Elimina tabla
- `fields()` - Gestiona campos de tabla

### CrudController.php

**Métodos principales:**
- `list()` - Lista registros de una tabla
- `form()` - Formulario crear/editar
- `save()` - Guarda registro
- `delete()` - Elimina registro
- `mediaList()` - Gestiona archivos subidos

---

## 📊 Tipos de Campos

### Tipos de Datos SQLite

- **TEXT**: Cadenas de texto
- **INTEGER**: Números enteros
- **REAL**: Números decimales
- **BLOB**: Datos binarios

### Tipos Especiales

- **file**: Campo de subida de archivos
- **textarea**: Área de texto grande
- **checkbox**: Campo booleano
- **date**: Selector de fecha
- **time**: Selector de hora

---

## 🔒 Seguridad

### Validación de Permisos

Cada operación valida que el usuario tenga permisos:

```php
Auth::requireDatabaseAccess($db_id);
Auth::requirePermission("db:$db_id", "write");
```

### Prepared Statements

Todas las consultas usan prepared statements:

```php
$stmt = $pdo->prepare("INSERT INTO table (field) VALUES (?)");
$stmt->execute([$value]);
```

### Sanitización

Los datos se sanitizan antes de mostrar:

```php
echo htmlspecialchars($data);
```

---

## 📁 Gestión de Archivos

Los archivos subidos se organizan por:
- Fecha de subida
- Tabla de origen
- Tipo de archivo

Estructura:
```
public/uploads/
└── YYYY-MM-DD/
    └── tabla_nombre/
        └── archivo.ext
```

---

## 🎯 Mejores Prácticas

1. **Nombra las tablas** en singular y minúsculas
2. **Usa campos descriptivos** para mejor comprensión
3. **Configura validaciones** en campos críticos
4. **Realiza backups** periódicos de las bases de datos
5. **Limita el tamaño** de archivos subidos

---

## 📚 Ejemplos

### Crear Tabla de Usuarios

1. Nombre: `usuarios`
2. Campos:
   - `nombre` (TEXT)
   - `email` (TEXT)
   - `edad` (INTEGER)
   - `activo` (INTEGER, checkbox)
   - `foto` (TEXT, file)

### Crear Tabla de Productos

1. Nombre: `productos`
2. Campos:
   - `titulo` (TEXT)
   - `descripcion` (TEXT, textarea)
   - `precio` (REAL)
   - `stock` (INTEGER)
   - `imagen` (TEXT, file)

---

[← Volver al README principal](../README.md)


---

## 🚧 TODOs y Mejoras Propuestas

### 🎯 Prioridad Alta

- [ ] **Backup y Restauración**
  - Backup automático programado
  - Backup manual con un click
  - Restauración desde backup
  - Almacenamiento en múltiples ubicaciones
  - Compresión de backups

- [ ] **Importación/Exportación de Datos**
  - Importar desde CSV/Excel
  - Exportar a CSV/Excel/JSON/SQL
  - Mapeo de columnas
  - Validación de datos en importación
  - Importación masiva optimizada

- [ ] **Relaciones entre Tablas**
  - Definir Foreign Keys
  - Visualización de relaciones
  - Joins automáticos en queries
  - Integridad referencial
  - Cascada en eliminaciones

- [ ] **Índices de Base de Datos**
  - Crear índices en campos
  - Índices compuestos
  - Análisis de rendimiento
  - Sugerencias automáticas de índices

### 🔧 Prioridad Media

- [ ] **Vistas (Views)**
  - Crear vistas SQL
  - Vistas materializadas
  - Gestión visual de vistas
  - Actualización automática

- [ ] **Triggers y Procedimientos**
  - Definir triggers
  - Procedimientos almacenados
  - Eventos programados
  - Editor de SQL

- [ ] **Validaciones de Datos**
  - Validaciones personalizadas por campo
  - Expresiones regulares
  - Rangos de valores
  - Valores únicos
  - Mensajes de error personalizados

- [ ] **Valores por Defecto**
  - Configurar valores default
  - Funciones SQL (NOW(), UUID(), etc.)
  - Valores calculados
  - Auto-incremento personalizado

- [ ] **Búsqueda Full-Text**
  - Índices full-text
  - Búsqueda en múltiples campos
  - Búsqueda fuzzy
  - Ranking de resultados

### 💡 Prioridad Baja

- [ ] **Migrador de Esquemas**
  - Versionado de esquema
  - Migraciones automáticas
  - Rollback de migraciones
  - Historial de cambios

- [ ] **Query Builder Visual**
  - Constructor de consultas drag & drop
  - Preview de resultados
  - Exportar a SQL
  - Guardar queries favoritas

- [ ] **Replicación de Datos**
  - Réplica master-slave
  - Sincronización entre BDs
  - Resolución de conflictos
  - Replicación selectiva

- [ ] **Particionamiento de Tablas**
  - Particiones por rango
  - Particiones por hash
  - Mejora de rendimiento
  - Gestión automática

### 🎨 Campos y Tipos

- [ ] **Tipos de Datos Adicionales**
  - JSON/JSONB
  - Arrays
  - Geolocalización (lat/lng)
  - UUID
  - Enum personalizado

- [ ] **Campos Especiales**
  - Editor WYSIWYG para HTML
  - Markdown con preview
  - Color picker
  - Selector de iconos
  - Tags/Etiquetas

- [ ] **Campos Calculados**
  - Campos virtuales
  - Fórmulas personalizadas
  - Agregaciones automáticas
  - Actualización en tiempo real

### 📊 Visualización

- [ ] **Gráficos y Estadísticas**
  - Gráficos de barras/líneas/pie
  - Dashboard por tabla
  - Métricas en tiempo real
  - Exportar gráficos

- [ ] **Vistas Personalizadas**
  - Vista de tabla
  - Vista de tarjetas
  - Vista de calendario
  - Vista de kanban
  - Vista de galería (para imágenes)

- [ ] **Filtros Avanzados**
  - Filtros guardados
  - Filtros compartidos
  - Combinación de filtros
  - Búsqueda global

### 🔐 Seguridad

- [ ] **Encriptación de Campos**
  - Encriptar campos sensibles
  - Desencriptación automática
  - Gestión de claves
  - Cumplimiento GDPR

- [ ] **Auditoría de Cambios**
  - Log de todos los cambios
  - Quién, cuándo, qué cambió
  - Comparación de versiones
  - Rollback de cambios

- [ ] **Permisos por Campo**
  - Campos de solo lectura
  - Campos ocultos por rol
  - Edición condicional
  - Máscaras de datos

### 🚀 Rendimiento

- [ ] **Caché de Consultas**
  - Cache en memoria (Redis)
  - Invalidación automática
  - TTL configurable
  - Estadísticas de cache

- [ ] **Optimización Automática**
  - Análisis de queries lentas
  - Sugerencias de optimización
  - Reescritura de queries
  - Monitoreo de rendimiento

- [ ] **Paginación Eficiente**
  - Cursor-based pagination
  - Infinite scroll
  - Carga lazy de datos
  - Virtualización de listas

### 📁 Gestión de Archivos

- [ ] **Almacenamiento en la Nube**
  - AWS S3
  - Google Cloud Storage
  - Azure Blob Storage
  - Configuración por tabla

- [ ] **Procesamiento de Imágenes**
  - Redimensionamiento automático
  - Thumbnails
  - Compresión
  - Filtros y efectos
  - Múltiples versiones

- [ ] **Gestión de Archivos Mejorada**
  - Galería de medios
  - Búsqueda de archivos
  - Organización en carpetas
  - Metadatos de archivos
  - Preview de documentos

---

[← Volver al README principal](../README.md)
