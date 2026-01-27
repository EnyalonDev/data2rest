# 📚 Documentación: Autenticación para Sitios Externos

Esta carpeta contiene la documentación completa del sistema de autenticación con Google OAuth para sitios web externos que consumen datos de Data2Rest.

---

## 📋 Índice de Documentos

### **1. Plan de Integración** 
[`01-plan-integracion-google-oauth.md`](./01-plan-integracion-google-oauth.md)

Plan técnico completo de implementación del sistema de autenticación con Google OAuth. Incluye:
- Arquitectura del sistema
- Estructura de base de datos (extensión de tablas existentes)
- Endpoints API necesarios
- Implementación en backend (Data2Rest)
- Implementación en frontend (Vercel/Next.js)
- Configuración y seguridad

---

### **2. Seguridad y Casos de Uso**
[`02-seguridad-y-casos-uso.md`](./02-seguridad-y-casos-uso.md)

Explicación detallada del sistema de seguridad multi-proyecto. Incluye:
- Validaciones en 3 niveles
- Casos de uso con múltiples proyectos
- Protección contra suplantación
- Flujos completos con diagramas
- Matriz de permisos de ejemplo

---

### **3. Sistema de Roles en Frontend**
[`03-sistema-roles-frontend.md`](./03-sistema-roles-frontend.md)

Sistema de control de acceso basado en roles (RBAC) para el frontend. Incluye:
- Arquitectura de permisos granulares
- Roles: Admin, Staff, Cliente
- Componentes React reutilizables (`RoleGuard`, `Can`)
- Filtrado automático de datos por rol
- Ejemplo completo: Clínica Veterinaria

---

### **4. Compatibilidad Multi-BD y Autenticación Híbrida**
[`04-compatibilidad-y-auth-hibrida.md`](./04-compatibilidad-y-auth-hibrida.md)

Compatibilidad con múltiples motores de base de datos y métodos de autenticación. Incluye:
- Soporte para SQLite, MySQL, PostgreSQL
- Script de migración automático
- Autenticación híbrida: Google OAuth + Email/Contraseña
- Endpoints de registro y login tradicional

---

### **5. Multi-Proyecto con Múltiples Roles**
[`05-multi-proyecto-multi-rol.md`](./05-multi-proyecto-multi-rol.md)

Explicación de cómo un usuario puede tener diferentes roles en múltiples proyectos. Incluye:
- Usuario en múltiples proyectos
- Diferentes roles por proyecto
- Separación de contextos
- Manejo de usuarios existentes
- Código de detección y asignación

---

### **6. Administración de Usuarios y Roles**
[`06-administracion-usuarios-roles.md`](./06-administracion-usuarios-roles.md)

Guía de administración de usuarios desde el panel de Data2Rest. Incluye:
- Wireframes de vistas de gestión
- Flujo paso a paso para asignar roles
- Configuración de permisos granulares
- Código del controlador y vistas Blade
- Diferenciación entre roles internos vs externos

---

## 🎯 Orden de Lectura Recomendado

Para entender completamente el sistema, se recomienda leer en este orden:

1. **Plan de Integración** - Visión general y arquitectura
2. **Seguridad y Casos de Uso** - Cómo funciona la seguridad
3. **Multi-Proyecto Multi-Rol** - Casos de uso complejos
4. **Sistema de Roles Frontend** - Implementación en sitios web
5. **Administración de Usuarios** - Gestión desde Data2Rest
6. **Compatibilidad Multi-BD** - Detalles técnicos adicionales

---

## 🚀 Resumen Ejecutivo

### **¿Qué Permite Este Sistema?**

✅ Autenticación con Google OAuth en sitios web externos  
✅ Autenticación tradicional con email/contraseña  
✅ Un usuario puede acceder a múltiples sitios web  
✅ Diferentes roles por sitio (Admin, Staff, Cliente)  
✅ Permisos granulares por recurso y acción  
✅ Filtrado automático de datos según rol  
✅ Gestión centralizada desde Data2Rest  
✅ Compatible con SQLite, MySQL, PostgreSQL  

### **Componentes Principales:**

**Backend (Data2Rest):**
- Extensión de tablas `projects` y `project_users`
- Nueva tabla `project_sessions`
- Controlador `ProjectAuthController`
- Endpoints API de autenticación
- Panel de administración de usuarios

**Frontend (Sitios Web):**
- Componentes de login (Google + Tradicional)
- Sistema de roles y permisos
- Middleware de protección de rutas
- Cliente API con filtros automáticos
- Componentes reutilizables (`RoleGuard`, `Can`)

---

## 📞 Soporte

Para dudas durante la implementación, consultar los documentos específicos o revisar los ejemplos de código incluidos en cada sección.

---

**Documentación creada:** 2026-01-24  
**Versión:** 1.0
