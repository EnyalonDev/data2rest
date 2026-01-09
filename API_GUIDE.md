# 🚀 Data2Rest API Guide

Esta guía proporciona las instrucciones necesarias para interactuar con la API dinámica de Data2Rest. Está diseñada para ser consumida por desarrolladores o agentes de IA.

---

## 🛠️ Configuración Base

- **Base URL:** `http://localhost/data2rest/public/api/v1/{db_id}`
- **Autenticación:** Se requiere el header `X-API-KEY`.
- **Formato de Datos:** JSON para peticiones estándar, `multipart/form-data` para subida de archivos (vía POST con Method Spoofing).

---

## 📁 Estructura de Endpoints

Cualquier tabla creada en la base de datos se convierte automáticamente en un endpoint:
`GET /api/v1/1/usuarios` -> Lista usuarios de la DB 1.
`GET /api/v1/1/usuarios/5` -> Obtiene el usuario con ID 5.

---

## 🔍 Consultas Avanzadas (Parámetros GET)

Puedes filtrar y organizar los datos usando parámetros en la URL:

| Parámetro | Descripción | Ejemplo |
| :--- | :--- | :--- |
| `{columna}` | Filtra por el valor exacto de una columna. | `?email=test@mail.com` |
| `limit` | Número de registros a devolver (Default: 50). | `?limit=10` |
| `offset` | Desplazamiento para paginación. | `?offset=20` |
| `order_by` | Columna por la cual ordenar. | `?order_by=created_at` |
| `order` | Dirección del orden (`asc` o `desc`). | `?order=desc` |
| `fields` | Lista de columnas separadas por coma. | `?fields=id,nombre,email` |
| `slug` | Común para filtrar páginas o contenido único. | `?slug=home-hero` |

---

## 📤 Escritura y Gestión de Archivos

### 1. Crear Registro (POST)
Envía un JSON con los campos.
```http
POST /api/v1/1/projects
Content-Type: application/json
{
  "title": "Nuevo Proyecto",
  "budget": 5000
}
```

### 2. Actualizar con Archivos (PATCH + Method Spoofing) ⚠️ **Importante**
PHP tiene limitaciones procesando `multipart/form-data` nativamente en peticiones `PATCH`. Para subir archivos y actualizar un registro, utiliza:
- **Método HTTP:** `POST`
- **Body:** `FormData` (multipart)
- **Campo especial:** `_method: "PATCH"`

```javascript
const formData = new FormData();
formData.append('title', 'Nuevo Título');
formData.append('featured_image', fileInput.files[0]);
formData.append('_method', 'PATCH'); // Spoofing

axios.post('/api/v1/1/web_pages/1', formData);
```

---

## 🔗 Relaciones Automáticas (Foreign Keys)
Si una tabla tiene una relación (FK), la API devolverá automáticamente un campo extra con el label legible.
- **Ejemplo:** Si `employees` tiene `department_id`, la API devuelve:
  - `department_id`: 2
  - `department_id_label`: "Recursos Humanos"

---

## 📂 Almacenamiento de Archivos
Los archivos subidos se organizan automáticamente:
`uploads/p{project_id}/{table}/{date}/{filename}`

- Los nombres de archivos se limpian de acentos y caracteres especiales automáticamente.
- Si existe una colisión de nombre, se añade un sufijo aleatorio para evitar sobreescritura.

---

## 📝 Ejemplos de Tablas en Demo Enterprise
- `web_pages`: Contenido CMS (hero, sobre nosotros).
- `mensajes_de_contacto`: Buzón de entrada de leads (soporta adjuntos).
- `employees`: Listado de personal con avatares.
- `servicios`: Lista de servicios con iconos de Lucide.
- `projects`: Gestión de presupuesto y estados.

---

**Nota para Agentes:** Al utilizar esta API, siempre verifica los nombres de las tablas y columnas consultando el endpoint de metadata si está disponible, o basándote en el `enterprise_demo.json` proporcionado.
