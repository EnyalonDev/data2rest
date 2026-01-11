# Permisos de Importación/Exportación de Datos

Este documento describe los nuevos permisos granulares implementados para las operaciones de importación y exportación de datos en Data2Rest.

## 📋 Nuevos Permisos

### `module:databases.export_data`
**Descripción**: Permite exportar datos de tablas en múltiples formatos.

**Operaciones permitidas**:
- ✅ Exportar tabla a SQL
- ✅ Exportar tabla a Excel (.xls)
- ✅ Exportar tabla a CSV
- ✅ Generar plantillas de Excel para importación
- ✅ Generar plantillas de CSV para importación

**Métodos del controlador**:
- `DatabaseController@exportTableSql()`
- `DatabaseController@exportTableExcel()`
- `DatabaseController@exportTableCsv()`
- `DatabaseController@generateExcelTemplate()`
- `DatabaseController@generateCsvTemplate()`

**Rutas afectadas**:
- `GET /admin/databases/table/export-sql`
- `GET /admin/databases/table/export-excel`
- `GET /admin/databases/table/export-csv`
- `GET /admin/databases/table/template-excel`
- `GET /admin/databases/table/template-csv`

---

### `module:databases.import_data`
**Descripción**: Permite importar datos a tablas desde múltiples formatos.

**Operaciones permitidas**:
- ✅ Importar datos desde archivo SQL
- ✅ Importar datos pegando código SQL directamente
- ✅ Importar datos desde archivo Excel (.xls, .xlsx)
- ✅ Importar datos desde archivo CSV

**Métodos del controlador**:
- `DatabaseController@importTableSql()`
- `DatabaseController@importTableSqlText()` ⭐ NUEVO
- `DatabaseController@importTableExcel()`
- `DatabaseController@importTableCsv()`

**Rutas afectadas**:
- `POST /admin/databases/table/import-sql`
- `POST /admin/databases/table/import-sql-text` ⭐ NUEVO
- `POST /admin/databases/table/import-excel`
- `POST /admin/databases/table/import-csv`

---

## 🔐 Configuración de Permisos

### Para Roles

Los permisos se configuran en el objeto JSON de permisos del rol:

```json
{
  "all": false,
  "modules": {
    "databases": {
      "view_tables": true,
      "export_data": true,
      "import_data": true,
      "edit_table": false,
      "create_table": false,
      "drop_table": false
    }
  }
}
```

### Para Grupos

Similar a los roles, los grupos pueden tener estos permisos configurados:

```json
{
  "all": false,
  "modules": {
    "databases": {
      "export_data": true,
      "import_data": false
    }
  }
}
```

### Para API Keys

Las API keys también pueden tener permisos específicos:

```json
{
  "databases": {
    "export_data": true,
    "import_data": false
  }
}
```

---

## 📊 Casos de Uso Comunes

### Caso 1: Usuario Solo Lectura con Exportación
**Escenario**: Analista de datos que necesita exportar pero no modificar datos.

**Permisos**:
```json
{
  "modules": {
    "databases": {
      "view_tables": true,
      "export_data": true,
      "import_data": false,
      "edit_table": false
    }
  }
}
```

---

### Caso 2: Usuario de Importación de Datos
**Escenario**: Operador que carga datos masivos pero no debe exportar información sensible.

**Permisos**:
```json
{
  "modules": {
    "databases": {
      "view_tables": true,
      "export_data": false,
      "import_data": true,
      "edit_table": false
    }
  }
}
```

---

### Caso 3: Administrador de Datos Completo
**Escenario**: Administrador con control total sobre datos.

**Permisos**:
```json
{
  "modules": {
    "databases": {
      "view_tables": true,
      "export_data": true,
      "import_data": true,
      "edit_table": true,
      "create_table": true,
      "drop_table": true
    }
  }
}
```

---

### Caso 4: Usuario Sin Acceso a Importación/Exportación
**Escenario**: Usuario que solo puede ver y editar registros individuales.

**Permisos**:
```json
{
  "modules": {
    "databases": {
      "view_tables": true,
      "export_data": false,
      "import_data": false,
      "crud_create": true,
      "crud_update": true,
      "crud_delete": false
    }
  }
}
```

---

## 🎯 Interfaz de Usuario

### Comportamiento Visual

**Con permiso `export_data`**:
- ✅ Se muestra el botón "Exportar" con menú desplegable
- ✅ Opciones: SQL, Excel, CSV

**Sin permiso `export_data`**:
- ❌ El botón "Exportar" está oculto

**Con permiso `import_data`**:
- ✅ Se muestra el botón "Importar"
- ✅ Modal con pestañas: SQL (Archivo/Texto), Excel, CSV

**Sin permiso `import_data`**:
- ❌ El botón "Importar" está oculto

**Adaptación del Layout**:
- Si solo uno de los botones está visible, ocupa todo el ancho disponible
- Si ambos están visibles, se distribuyen equitativamente (50/50)
- Si ninguno está visible, la sección completa está oculta

---

## 🔄 Migración desde Permisos Anteriores

### Permisos Antiguos → Nuevos Permisos

| Permiso Antiguo | Nuevo Permiso | Notas |
|----------------|---------------|-------|
| `module:databases.view_tables` | `module:databases.export_data` | Para exportación |
| `module:databases.edit_table` | `module:databases.import_data` | Para importación |

**Acción Recomendada**:
Los roles existentes que tenían `edit_table` ahora necesitarán `import_data` explícitamente si desean importar datos.

---

## 🛡️ Seguridad

### Consideraciones de Seguridad

1. **Exportación de Datos Sensibles**:
   - El permiso `export_data` permite exportar TODOS los datos de una tabla
   - Considere cuidadosamente a quién otorga este permiso
   - Los datos exportados incluyen todos los campos, incluso los ocultos en la UI

2. **Importación de Datos**:
   - El permiso `import_data` permite ejecutar SQL arbitrario (en modo texto)
   - Esto puede ser peligroso si se otorga a usuarios no confiables
   - Considere usar solo importación desde archivos para usuarios limitados

3. **Separación de Responsabilidades**:
   - Es posible tener `import_data` sin `export_data` y viceversa
   - Esto permite implementar políticas de "solo entrada" o "solo salida"

---

## 📝 Registro de Actividades

Todas las operaciones de importación/exportación se registran en el sistema de logs:

### Eventos Registrados

- `EXPORT_TABLE_SQL` - Exportación a SQL
- `EXPORT_TABLE_EXCEL` - Exportación a Excel
- `EXPORT_TABLE_CSV` - Exportación a CSV
- `IMPORT_TABLE_SQL` - Importación desde archivo SQL
- `IMPORT_TABLE_SQL_TEXT` - Importación desde texto SQL ⭐ NUEVO
- `IMPORT_TABLE_EXCEL` - Importación desde Excel
- `IMPORT_TABLE_CSV` - Importación desde CSV

### Información Registrada

Cada log incluye:
- `database_id`: ID de la base de datos
- `table`: Nombre de la tabla
- `count`: Número de registros (para importaciones)
- `affected_rows`: Filas afectadas (para SQL directo)
- Usuario que realizó la acción
- Timestamp de la operación

---

## 🧪 Testing de Permisos

### Verificar Permisos de Exportación

```php
// En el código
if (\App\Core\Auth::hasPermission('module:databases.export_data')) {
    // Mostrar opciones de exportación
}
```

### Verificar Permisos de Importación

```php
// En el código
if (\App\Core\Auth::hasPermission('module:databases.import_data')) {
    // Mostrar opciones de importación
}
```

---

## 📚 Recursos Adicionales

- [Documentación de Autenticación](../AUTH.md)
- [Documentación de Base de Datos](../DATABASE.md)
- [Sistema de Permisos](../docs/permissions.md)

---

## 🔄 Historial de Cambios

### v1.1.0 - 2026-01-10
- ✨ Agregado permiso `module:databases.export_data`
- ✨ Agregado permiso `module:databases.import_data`
- ✨ Nueva funcionalidad: Importar SQL desde campo de texto
- 🔧 Separación de permisos de importación/exportación
- 📝 Documentación completa de permisos

---

**Última actualización**: 2026-01-10
**Versión**: 1.1.0
