# 🖼️ Módulo de Media Library

[← Volver al README principal](../README.md)

## 📋 Descripción

El **Módulo de Media Library** es una solución integral para la gestión de activos digitales dentro de Data2Rest. Permite no solo organizar y visualizar archivos, sino también realizar ediciones avanzadas de imágenes, gestionar la papelera de reciclaje y rastrear el uso de archivos en todas las bases de datos del sistema.

---

## ✨ Características Principales

### 📁 Organización y Visualización
- **Navegación por Carpetas**: Estructura organizada por fechas y tablas.
- **Vistas Duales**: Alterna entre vista de **Mosaico (Grid)** y **Lista (List)** para mayor comodidad.
- **Breadcrumbs Dinámicos**: Navegación rápida entre directorios con una barra de ruta compacta.
- **Búsqueda en Tiempo Real**: Filtra tus archivos instantáneamente por nombre.

### 🎨 Editor de Imágenes Profesional
Integración nativa potente para manipulación de imágenes sin salir del panel:
- **Recorte (Crop)**: Ajuste de dimensiones con previsualización en tiempo real.
- **Redimensionamiento**: Ajuste de ancho y alto manteniendo la proporción.
- **Filtros Artísticos**: Gris, Sepia, Invertir, Vintage, Dramático, Desenfoque y Enfoque.
- **Optimización**: Control de calidad (JPEG/WebP) para equilibrar peso y nitidez.
- **Seguridad**: Opción de **"Guardar como copia"** activa por defecto para proteger originales.

### 🗑️ Gestión de Papelera y Retención
- **Borrado Seguro**: Los archivos eliminados se mueven a una papelera `.trash`.
- **Restauración en un Click**: Recupera archivos borrados accidentalmente a su ubicación original.
- **Purga Automática**: Configura cuántos días deben permanecer los archivos en la papelera antes de ser eliminados definitivamente.

### 📊 Rastreador de Uso (Usage Tracker)
- **Detección de Huérfanos**: Identifica archivos que no están siendo usados en ninguna tabla.
- **Mapa de Referencias**: Visualiza exactamente en qué base de datos y tabla está referenciado cada archivo antes de borrarlo.

### 🛠️ Herramientas de Desarrollo y Mantenimiento
- **Super Refresh**: Botón para forzar la recarga de la interfaz ignorando la caché del navegador.
- **Limpieza de Caché**: Herramienta para purgar archivos temporales y optimizar el servidor.

---

## 🚀 Uso del Editor de Imágenes

1. Selecciona una **imagen** en la galería.
2. En el panel derecho (Inspector), haz clic en el botón **Edit (Lápiz)**.
3. El modal del editor se abrirá con las siguientes opciones:
   - **Transformar**: Usa el ratón para seleccionar el área de recorte.
   - **Filtros**: Elige entre más de 8 efectos artísticos.
   - **Dimensiones**: Cambia el tamaño manualmente.
   - **Calidad**: Ajusta el deslizador de optimización.
4. Haz clic en **Guardar Cambios**. Si "Guardar como copia" está marcado, se creará un nuevo archivo con el sufijo `-edited`.

---

## 🔧 Detalles Técnicos

### Ubicación de Archivos
```
public/uploads/
├── YYYY-MM-DD/     # Organización por fecha
├── .trash/         # Papelera de reciclaje
└── [tablas]/       # Archivos específicos de módulos
```

### Controlador Principal
`src/Modules/Media/MediaController.php`

**Métodos Clave:**
- `list()`: Escaneo y listado de archivos con metadatos.
- `edit()`: Procesamiento de imágenes usando la librería **GD** de PHP.
- `usage()`: Algoritmo de búsqueda cruzada en múltiples bases de datos SQLite.
- `bulkDelete()`, `restore()`, `purge()`: Gestión de ciclo de vida de archivos.

---

## 🔒 Seguridad e Integridad

- **Prevención de Directory Traversal**: Validación estricta de rutas para evitar acceso fuera de `uploads/`.
- **Validación de Mime-Types**: Solo se permiten tipos de archivos seguros y editables.
- **Permisos Granulares**: El acceso a la Media Library requiere permisos específicos de módulo.

---

[← Volver al README principal](../README.md)
