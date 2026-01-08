# 🚀 Data2Rest - Sistema de Gestión de Bases de Datos y APIs REST

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

**Data2Rest** nació de una necesidad real: acelerar el desarrollo de backends para aplicaciones web y móviles. Mi objetivo era que, al momento de diseñar la base de datos, los endpoints necesarios estuvieran listos de inmediato. De esa visión surgió este sistema sencillo y práctico, ideal para desarrolladores independientes, estudiantes y equipos pequeños que necesitan lanzar MVPs o prototipos productivos en cuestión de minutos.

Con Data2Rest, el backend se adapta a tu idea y no al revés. Olvídate de buscar ejemplos genéricos de APIs que no encajan con tu proyecto; aquí tú diseñas tu estructura de datos y el sistema genera automáticamente los endpoints REST listos para consumir desde cualquier cliente.

### 🎯 Objetivo del Proyecto
Proveer una herramienta de código abierto que elimine la fricción inicial al crear backends, reduciendo errores repetitivos y permitiendo a los desarrolladores centrarse en lo que realmente importa: el diseño de sus datos y la lógica de su negocio.

**Ideal para:**
*   👨‍💻 **Desarrolladores Independientes**: Crea prototipos y apps completas sin escribir boilerplate.
*   🎓 **Estudiantes**: Aprende sobre estructuras de datos y APIs viendo resultados inmediatos.
*   🚀 **MVPs y Startups**: Valida tus ideas de negocio con un backend funcional en tiempo récord.
*   👥 **Equipos Pequeños**: Mejora la productividad compartiendo un núcleo de datos unificado.

---

## 📋 Tabla de Contenidos

- [Características Principales](#-características-principales)
- [Requisitos del Sistema](#-requisitos-del-sistema)
- [Instalación](#-instalación)
- [Arquitectura del Sistema](#-arquitectura-del-sistema)
- [Módulos](#-módulos)
- [Configuración](#-configuración)
- [Uso Básico](#-uso-básico)
- [Seguridad](#-seguridad)
- [Contribuir](#-contribuir)
- [Licencia](#-licencia)
- [Créditos](#-créditos)

---

## ✨ Características Principales

### 🗄️ Gestión de Bases de Datos
- **Creación dinámica** de bases de datos SQLite
- **Gestión visual** de tablas y campos
- **CRUD completo** con interfaz intuitiva
- **Configuración de campos** con tipos de datos personalizados
- **Gestión de archivos** y medios integrada

### 🔌 API REST Automática
- **Generación automática** de endpoints REST para cada tabla
- **Documentación interactiva** tipo Swagger
- **Autenticación por API Keys**
- **Soporte completo** para GET, POST, PUT, PATCH, DELETE
- **Filtrado y paginación** de resultados

### 🔐 Sistema de Autenticación y Autorización
- **Login seguro** con sesiones PHP
- **Control de acceso basado en roles** (RBAC)
- **Gestión de usuarios y grupos**
- **Permisos granulares** por base de datos
- **Sistema de flash messages** con modales elegantes

### 🎨 Interfaz Moderna
- **Diseño dark mode** con efectos glassmorphism
- **Responsive design** optimizado para móviles
- **Animaciones fluidas** y micro-interacciones
- **Tailwind CSS** para estilos consistentes
- **Tipografía premium** con Google Fonts (Outfit)
- **Soporte Multi-idioma (i18n)**: Español, Inglés y Portugués integrados

### 🛠️ Diagnóstico y Herramientas
- **Información del Servidor**: Panel de monitoreo de variables de entorno (PHP, límites de subida, etc.)
- **Instalador Automático**: Configuración inicial sin intervención manual
- **Carga de Demo**: Sistema de despliegue de datos de prueba empresarial

---

## 💻 Requisitos del Sistema

- **PHP**: 8.0 o superior
- **SQLite**: 3.x
- **Apache**: 2.4+ con mod_rewrite habilitado
- **Extensiones PHP requeridas**:
  - `pdo_sqlite`
  - `session`
  - `json`

---

## 🚀 Instalación

### Instalación Automática (Recomendada)

1. **Clona o descarga** el proyecto en tu servidor web:
   ```bash
   cd /opt/homebrew/var/www/
   git clone https://github.com/enyalondev/data2rest.git data2rest
   ```

2. **Configura Apache** para permitir `.htaccess`:
   ```apache
   <Directory "/opt/homebrew/var/www/data2rest">
       AllowOverride All
       Require all granted
   </Directory>
   ```

3. **Reinicia Apache**:
   ```bash
   brew services restart httpd
   ```

4. **Accede a la aplicación** en tu navegador:
   ```
   http://localhost/data2rest/
   ```

5. **Instalación automática**: El sistema detectará que es la primera vez y creará automáticamente:
   - Base de datos del sistema (`data/system.sqlite`)
   - Usuario administrador por defecto
   - Estructura de tablas necesarias

### Credenciales por Defecto

Al finalizar la instalación automática, podrás acceder con las siguientes credenciales:

```
Usuario: admin
Contraseña: admin123
```

⚠️ **AVISO DE SEGURIDAD**: Aunque parezca un paso obvio, se **recomienda encarecidamente cambiar la contraseña** inmediatamente después de tu primer acceso para proteger la integridad de tu sistema y tus datos.

---

## 🏗️ Arquitectura del Sistema

```
data2rest/
├── public/                 # Punto de entrada público
│   ├── index.php          # Router principal
│   └── uploads/           # Archivos subidos
├── src/
│   ├── Core/              # Núcleo del sistema
│   │   ├── Auth.php       # Autenticación y autorización
│   │   ├── Config.php     # Configuración global
│   │   ├── Database.php   # Conexión a BD
│   │   ├── Installer.php  # Instalador automático
│   │   └── Router.php     # Sistema de rutas
│   ├── Modules/           # Módulos funcionales
│   │   ├── Api/           # → Ver docs/API.md
│   │   ├── Auth/          # → Ver docs/AUTH.md
│   │   └── Database/      # → Ver docs/DATABASE.md
│   └── Views/             # Vistas y templates
│       ├── admin/         # Panel de administración
│       ├── auth/          # Vistas de autenticación
│       └── partials/      # Componentes reutilizables
├── data/                  # Bases de datos del sistema
│   └── system.sqlite      # BD principal
└── docs/                  # Documentación detallada
    ├── API.md             # Módulo de API REST
    ├── AUTH.md            # Módulo de autenticación
    └── DATABASE.md        # Módulo de bases de datos
```

---

## 📦 Módulos

El sistema está organizado en módulos independientes y bien documentados:

### 1. [Módulo de API REST](docs/API.md)
Generación automática de endpoints REST con documentación interactiva y ejemplos multiplataforma.
- Controladores REST (GET, POST, PUT, DELETE)
- Gestión de API Keys con validación de seguridad
- Documentación dinámica con ejemplos prácticos
- **Ejemplos incluidos**: cURL, JavaScript, Python

### 2. [Módulo de Autenticación](docs/AUTH.md)
Sistema completo de login, usuarios, roles y permisos granulares.
- Gestión de perfiles de usuario
- Arquitecto de Políticas (Permisos por tabla y acción)
- Grupos de trabajo y jerarquías
- **Casos de uso**: Creación de roles restringidos, gestión de equipos

### 3. [Módulo de Bases de Datos](docs/DATABASE.md)
Gestión visual integral de bases de datos SQLite y flujos de datos.
- Diseño de esquemas (Tablas y Columnas)
- Tipos de datos avanzados e interfaces de carga
- CRUD dinámico con validaciones
- **Tutoriales**: Configuración de relaciones, gestión de archivos multimedia

---

## ⚙️ Configuración

### Archivo de Configuración

El archivo `src/Core/Config.php` contiene la configuración principal:

```php
private static $config = [
    'db_path' => __DIR__ . '/../../data/system.sqlite',
    'app_name' => 'Data2Rest',
    'base_url' => '',
    'upload_dir' => __DIR__ . '/../../public/uploads/',
    'allowed_roles' => ['admin', 'user'],
];
```

### Variables Configurables

- **db_path**: Ruta a la base de datos del sistema
- **app_name**: Nombre de la aplicación
- **upload_dir**: Directorio para archivos subidos
- **allowed_roles**: Roles permitidos en el sistema

---

## 📖 Uso Básico

### 1. Crear una Base de Datos

1. Accede a **Databases** en el menú principal
2. Completa el formulario "Initialize New Node"
3. Ingresa nombre y descripción
4. Click en "Create Database"

### 2. Crear Tablas

1. Selecciona una base de datos
2. Click en "View Tables"
3. Ingresa el nombre de la tabla
4. Click en "Create Table"

### 3. Configurar Campos

1. Click en el ícono de configuración (⚙️) de la tabla
2. Agrega campos con sus tipos de datos
3. Configura opciones especiales (file upload, textarea, etc.)

### 4. Gestionar Datos (CRUD)

1. Click en "Enter Segment" en una tabla
2. Usa el botón "New Entry" para crear registros
3. Edita o elimina registros existentes

### 5. Generar API REST

Las APIs se generan automáticamente para cada tabla:

```
GET    /api/v1/{database}/{table}        # Listar todos
GET    /api/v1/{database}/{table}/{id}   # Obtener uno
POST   /api/v1/{database}/{table}        # Crear
PUT    /api/v1/{database}/{table}/{id}   # Actualizar completo
PATCH  /api/v1/{database}/{table}/{id}   # Actualizar parcial
DELETE /api/v1/{database}/{table}/{id}   # Eliminar
```

### 6. Ver Documentación de API

1. Accede a **API Docs** en el menú
2. Selecciona una base de datos
3. Consulta endpoints y ejemplos de uso

---

## 🔒 Seguridad

### Mejores Prácticas Implementadas

✅ **Autenticación de sesiones** con PHP nativo
✅ **Preparación de consultas SQL** (Prepared Statements)
✅ **Escape de HTML** en todas las salidas
✅ **Validación de permisos** en cada acción
✅ **API Keys** para acceso a endpoints REST
✅ **Control de acceso basado en roles** (RBAC)

### Recomendaciones Adicionales

1. **Cambia las credenciales por defecto** inmediatamente
2. **Usa HTTPS** en producción
3. **Configura permisos de archivos** apropiadamente:
   ```bash
   chmod 755 /opt/homebrew/var/www/data2rest
   chmod 644 /opt/homebrew/var/www/data2rest/data/*.sqlite
   ```
4. **Mantén PHP actualizado** a la última versión estable
5. **Revisa logs regularmente** para detectar actividad sospechosa

---

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

---

## 👨‍💻 Créditos

**Desarrollado por:** **EnyalonDev - Néstor Ovallos Cañas**

- 🌐 Website: [nestorovallos.com](https://nestorovallos.com)
- 📧 Email: contacto@nestorovallos.com
- 💼 LinkedIn: [Néstor Ovallos](https://linkedin.com/in/nestorovallos)
- 🐙 GitHub: [github.com/enyalondev/data2rest](https://github.com/enyalondev/data2rest)

---

## 🆘 Soporte

Si encuentras algún problema o tienes preguntas:

1. Revisa la [documentación de módulos](docs/)
2. Abre un [Issue](https://github.com/enyalondev/data2rest/issues)
3. Contacta al desarrollador

---

**¡Gracias por usar Data2Rest!** 🚀


---

## 🚧 TODOs y Mejoras Propuestas

### 🎯 Prioridad Alta

- [ ] **Soporte Multi-Motor de Base de Datos**
  - Implementación de drivers para **MySQL, PostgreSQL y MariaDB**
  - Migración transparente entre motores
  - Soporte para bases de datos remotas
  - Panel de configuración de conexiones externas

- [ ] **Sistema de Backup Automático**
  - Implementar backups programados de bases de datos
  - Exportación a SQL/JSON
  - Restauración desde backups
  - Almacenamiento en la nube (S3, Google Cloud)

- [ ] **Logs y Auditoría**
  - Sistema de logging completo
  - Registro de todas las acciones de usuarios
  - Visualización de logs en el panel
  - Alertas de actividad sospechosa

- [ ] **Autenticación de Dos Factores (2FA)**
  - Soporte para TOTP (Google Authenticator)
  - Códigos de respaldo
  - Configuración por usuario

- [ ] **Rate Limiting**
  - Límite de peticiones por API Key
  - Protección contra DDoS
  - Configuración personalizable por endpoint

### 🔧 Prioridad Media

- [ ] **Exportación de Datos**
  - Exportar tablas a CSV/Excel
  - Exportar bases de datos completas
  - Importación masiva desde archivos

- [ ] **Búsqueda Avanzada**
  - Búsqueda full-text en registros
  - Filtros combinados
  - Búsqueda global en todas las tablas

- [ ] **Webhooks**
  - Notificaciones en tiempo real
  - Eventos personalizables (create, update, delete)
  - Integración con servicios externos

- [ ] **Versionado de Datos**
  - Historial de cambios en registros
  - Rollback a versiones anteriores
  - Comparación de versiones

- [ ] **Dashboard Mejorado**
  - Gráficos y estadísticas
  - Widgets personalizables
  - Métricas en tiempo real

### 💡 Prioridad Baja

- [ ] **Temas Personalizables**
  - Modo claro/oscuro configurable
  - Paletas de colores personalizadas
  - Logo y branding personalizado


- [ ] **Notificaciones Push**
  - Notificaciones en navegador
  - Alertas de eventos importantes
  - Configuración de preferencias

- [ ] **API GraphQL**
  - Alternativa a REST API
  - Consultas flexibles
  - Subscripciones en tiempo real

- [ ] **Modo Offline**
  - Service Workers para PWA
  - Sincronización cuando vuelve la conexión
  - Cache de datos locales

### 🔐 Seguridad

- [ ] **Encriptación de Datos Sensibles**
  - Encriptar campos específicos en BD
  - Gestión de claves de encriptación
  - Cumplimiento GDPR

- [ ] **Políticas de Contraseñas**
  - Requisitos de complejidad
  - Expiración de contraseñas
  - Historial de contraseñas

- [ ] **Sesiones Seguras**
  - Timeout configurable
  - Cierre de sesión en múltiples dispositivos
  - Detección de sesiones concurrentes

### 📱 UX/UI

- [ ] **Modo Responsive Mejorado**
  - Optimización para tablets
  - Gestos táctiles
  - Menú hamburguesa mejorado

- [ ] **Atajos de Teclado**
  - Navegación rápida
  - Acciones comunes con teclas
  - Ayuda de atajos (?)

- [ ] **Drag & Drop**
  - Subida de archivos arrastrando
  - Reordenamiento de elementos
  - Organización visual

### 🧪 Testing

- [ ] **Tests Unitarios**
  - PHPUnit para backend
  - Cobertura de código >80%
  - Tests automatizados en CI/CD

- [ ] **Tests de Integración**
  - Pruebas de API completas
  - Validación de flujos de usuario
  - Tests de seguridad

### 📚 Documentación

- [ ] **Video Tutoriales**
  - Guías paso a paso
  - Casos de uso comunes
  - Canal de YouTube

- [ ] **API Reference Completa**
  - Documentación OpenAPI/Swagger
  - Ejemplos en múltiples lenguajes
  - Playground interactivo

---

## 💬 Contribuciones

¿Tienes ideas para mejorar el proyecto? 

1. Revisa la lista de TODOs
2. Abre un Issue para discutir la mejora
3. Crea un Pull Request con tu implementación

---
