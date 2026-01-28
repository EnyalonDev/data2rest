# Prompts para Next.js + Google OAuth (PROXIED VERSION)

Estos prompts están diseñados para **Next.js (App Router)** y usan **Rewrites** para ocultar la URL del backend.

---

## Prompt 1: Configuración de Proxy y Entorno

**Objetivo:** Configurar el sistema para que el navegador no vea la URL real del backend.

```text
Actúa como un Senior Full Stack Developer. Vamos a migrar el flujo de autenticación de Mundo Jácome a Next.js (App Router).

1. Crea 'next.config.mjs' para configurar un Proxy (Rewrites):
   - Redirige '/api-proxy/:path*' hacia 'https://d2r.nestorovallos.com/api/v1/:path*'
   - Redirige '/auth-gate/:path*' hacia 'https://d2r.nestorovallos.com/api/projects/2/auth/:path*'

2. Sigue las reglas de mi archivo "system para aistudio.md":
   - Crea 'src/constants/content.ts'.
   - ENV_SETTINGS debe usar rutas RELATIVAS:
     API_BASE_URL: "/api-proxy"
     AUTH_GATE_URL: "/auth-gate"
     PROJECT_ID: "2"

3. Crea 'src/lib/api.ts' usando Axios, apuntando a ENV_SETTINGS.API_BASE_URL.
   - Header 'X-Project-ID' fijo en "2".
   - Interceptor para inyectar 'Authorization: Bearer [token]' desde Zustand.
```

---

## Prompt 2: El Auth Store (Zustand)

```text
Crea 'src/stores/authStore.ts' usando Zustand con persistencia:
- Estado: user, token, isAuthenticated.
- Acciones: setAuth(user, token), logout().
- Maneja el hydration para evitar errores de SSR en Next.js.
```

---

## Prompt 3: Página de Login (Proxied)

```text
Crea la página de Login en 'src/app/login/page.tsx' (use client):

1. Diseño premium de Veterinaria.
2. El botón "Google Login" debe redirigir al Proxy interno:
   const handleGoogle = () => {
     // Usamos el Proxy configurado en next.config
     const backendAuthUrl = `${window.location.origin}/auth-gate/google`;
     const redirectUri = encodeURIComponent(`${window.location.origin}/auth/callback`);
     window.location.href = `${backendAuthUrl}?redirect_uri=${redirectUri}`;
   };

3. El navegador solo verá la petición a tu propio dominio (/auth-gate/google).
4. Agrega el <DebugPanel /> si ENV_SETTINGS.SHOW_DEBUG_PANEL es true.
```

---

## Prompt 4: Página de Callback (Proxied)

```text
Crea 'src/app/auth/callback/page.tsx' (use client):

Lógica:
1. Obtén el 'code' de los parámetros de la URL.
2. Haz POST a la ruta proxied: `${window.location.origin}/auth-gate/google/callback`.
3. Envía en el Body: { code, redirect_uri: `${window.location.origin}/auth/callback` }.
4. Si es exitoso, guarda en el authStore y redirige a '/dashboard'.
5. Si hay error, loguea en el DebugPanel.
```

---

## Prompt 5: Seguridad y Layout

```text
Protege el acceso:

1. Crea 'src/app/dashboard/layout.tsx':
   - Verifica 'isAuthenticated'. Si no, 'redirect("/login")'.

2. Crea un DashboardHome básico en 'src/app/dashboard/page.tsx'.

3. Genera un archivo 'vercel.json' que incluya las cabeceras de seguridad (CSP) y confirme que los rewrites funcionen correctamente en producción.
```
