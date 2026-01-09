# 🔐 Módulo de Autenticación

[← Volver al README principal](../README.md)

## 📋 Descripción

El **Módulo de Autenticación** proporciona un sistema completo de login, gestión de usuarios, roles y permisos basado en RBAC (Role-Based Access Control).

---

## 📁 Estructura del Módulo

```
src/Modules/Auth/
├── LoginController.php     # Gestión de login/logout
├── UserController.php      # CRUD de usuarios
└── RoleController.php      # Gestión de roles y permisos
```

---

## ✨ Características

### 🔑 Sistema de Login
- Autenticación segura con sesiones PHP
- Validación de credenciales
- Protección contra fuerza bruta
- Flash messages para feedback

### 👥 Gestión de Usuarios
- Crear, editar y eliminar usuarios
- Asignación de roles
- Permisos granulares por base de datos
- Listado y búsqueda de usuarios

### 🛡️ Control de Acceso (RBAC) - Policy Architect
- Roles personalizables (admin, user, etc.)
- **Arquitecto de Políticas**: Interfaz visual para definir permisos granulares.
- **Permisos de Gestión de Usuarios**:
    - `invite_users`: Permitir invitar/crear nuevos usuarios.
    - `edit_users`: Permitir editar perfiles existentes.
    - `delete_users`: Permitir eliminar usuarios (botón de borrado oculto si no se posee).
- **Aislamiento de Equipos**:
    - **Admins**: Ven a todos los usuarios y pueden filtrar por grupo.
    - **Usuarios**: Solo pueden ver a los miembros de su mismo grupo de trabajo.
- Validación en cada acción.

---

## 🚀 Uso

### 1. Login

Accede a `/login` e ingresa tus credenciales:

```
Usuario: admin
Contraseña: admin123
```

### 2. Gestión de Usuarios

1. Ve a **Users** en el menú principal
2. Click en "New User" para crear usuarios
3. Asigna roles y permisos
4. Guarda los cambios

### 3. Gestión de Roles

1. Ve a **Roles** en el menú
2. Crea nuevos roles o edita existentes
3. Define permisos específicos
4. Asigna roles a usuarios

### 4. Ejemplos de Implementación

#### Verificación de Permisos en PHP
```php
use App\Core\Auth;

// Requerir que el usuario esté logueado
Auth::requireLogin();

// Requerir permiso específico para una base de datos
Auth::requireDatabaseAccess($db_id);

// Verificar si tiene permiso de escritura en un módulo
if (Auth::hasPermission("module:api", "manage")) {
    // Realizar acción administrativa
}
```

#### Estructura de una Política JSON (Arquitecto de Políticas)
```json
{
  "all": false,
  "modules": {
    "databases": ["view", "manage"],
    "api": ["view"]
  },
  "databases": {
    "1": ["read", "insert", "update"],
    "2": ["view"]
  }
}
```

---

## 🔧 Controladores

### LoginController.php

**Métodos:**
- `showLoginForm()` - Muestra el formulario de login
- `login()` - Procesa el login
- `logout()` - Cierra la sesión

### UserController.php

**Métodos:**
- `index()` - Lista todos los usuarios
- `form()` - Formulario de crear/editar
- `save()` - Guarda usuario
- `delete()` - Elimina usuario

### RoleController.php

**Métodos:**
- `index()` - Lista todos los roles
- `form()` - Formulario de crear/editar rol
- `save()` - Guarda rol
- `delete()` - Elimina rol

---

## 🔒 Seguridad

### Sesiones

Las sesiones se manejan con PHP nativo y se almacenan de forma segura.

### Permisos

El sistema verifica permisos en cada acción:

```php
Auth::requirePermission("db:1", "write");
```

### Hashing de Contraseñas

Las contraseñas se hashean con `password_hash()` de PHP.

---

## 📊 Estructura de Datos

### Tabla `users`

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    username TEXT UNIQUE,
    password TEXT,
    email TEXT,
    role_id INTEGER,
    created_at DATETIME
);
```

### Tabla `roles`

```sql
CREATE TABLE roles (
    id INTEGER PRIMARY KEY,
    name TEXT UNIQUE,
    description TEXT,
    permissions TEXT
);
```

---

[← Volver al README principal](../README.md)


---

## 🚧 TODOs y Mejoras Propuestas

### 🎯 Prioridad Alta

- [ ] **Soporte de Autenticación para Múltiples Motores**
  - Autenticación contra usuarios en **MySQL/PostgreSQL**
  - Mapeo de grupos de sistema externos a roles locales
  - Sincronización de perfiles multi-plataforma

- [ ] **Autenticación de Dos Factores (2FA)**
  - TOTP con Google Authenticator
  - Códigos de respaldo
  - SMS como alternativa
  - Configuración obligatoria para admins

- [ ] **Políticas de Contraseñas**
  - Requisitos de complejidad configurables
  - Longitud mínima
  - Caracteres especiales obligatorios
  - Validación en tiempo real

- [ ] **Expiración de Contraseñas**
  - Cambio obligatorio cada X días
  - Notificaciones antes de expirar
  - Historial de contraseñas (no reutilizar)

- [ ] **Bloqueo de Cuenta**
  - Bloqueo tras N intentos fallidos
  - Desbloqueo automático tras X minutos
  - Desbloqueo manual por admin
  - Notificación al usuario

### 🔧 Prioridad Media

- [ ] **Single Sign-On (SSO)**
  - Integración con LDAP/Active Directory
  - SAML 2.0
  - OAuth con Google, Microsoft, GitHub
  - Mapeo automático de roles

- [ ] **Gestión de Sesiones Mejorada**
  - Ver sesiones activas
  - Cerrar sesiones remotamente
  - Límite de sesiones concurrentes
  - Detección de dispositivos

- [ ] **Grupos de Usuarios**
  - Organización jerárquica
  - Permisos por grupo
  - Usuarios en múltiples grupos
  - Gestión visual de grupos

- [ ] **Recuperación de Contraseña**
  - Envío de email con token
  - Link temporal de reseteo
  - Preguntas de seguridad
  - Validación de identidad

### 💡 Prioridad Baja

- [ ] **Login Social**
  - Google
  - Facebook
  - GitHub
  - LinkedIn
  - Vinculación de cuentas

- [ ] **Biometría**
  - WebAuthn para huella digital
  - Face ID / Touch ID
  - Llaves de seguridad (YubiKey)

- [ ] **Modo Invitado**
  - Acceso limitado sin registro
  - Conversión a usuario registrado
  - Permisos restringidos

- [ ] **Delegación de Permisos**
  - Usuarios pueden delegar acceso temporal
  - Permisos con fecha de expiración
  - Auditoría de delegaciones

### 🔐 Seguridad

- [ ] **Auditoría de Accesos**
  - Log de todos los logins
  - Registro de cambios de permisos
  - Detección de actividad sospechosa
  - Alertas automáticas

- [ ] **Sesiones Seguras**
  - Tokens JWT en lugar de sesiones PHP
  - Refresh tokens
  - Revocación de tokens
  - Blacklist de tokens

- [ ] **Protección contra Fuerza Bruta**
  - CAPTCHA tras X intentos
  - Delay progresivo entre intentos
  - Bloqueo temporal de IP
  - Honeypot para bots

- [ ] **Encriptación de Datos**
  - Encriptar contraseñas con bcrypt/argon2
  - Encriptar datos sensibles en BD
  - Rotación de claves de encriptación

### 📊 Monitoreo

- [ ] **Dashboard de Seguridad**
  - Intentos de login fallidos
  - Usuarios activos
  - Cambios de permisos recientes
  - Alertas de seguridad

- [ ] **Reportes de Actividad**
  - Reporte de logins por usuario
  - Accesos por horario
  - Dispositivos utilizados
  - Exportación a PDF/Excel

### 📱 UX/UI

- [ ] **Onboarding de Usuarios**
  - Tutorial interactivo
  - Tour guiado del sistema
  - Tips contextuales
  - Video tutoriales

- [ ] **Perfil de Usuario Mejorado**
  - Avatar personalizable
  - Información de contacto
  - Preferencias de notificaciones
  - Historial de actividad

- [ ] **Gestión de Preferencias**
  - Tema claro/oscuro
  - Idioma preferido
  - Zona horaria
  - Formato de fecha/hora

---

[← Volver al README principal](../README.md)
