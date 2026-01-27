# 🤖 Prompts para AI Studio (Versión Vite + React)

Estos prompts están diseñados para una **Single Page Application (SPA)** usando Vite, React y React Router v6.

## 📌 Prompt 1: Estructura Inicial (Vite)

```text
Actúa como experto en React y Vite. Quiero crear una SPA que se conecte a un backend externo (Data2Rest).

Inicializa la estructura del proyecto con estas características:
1.  **Tecnologías:** React, TypeScript, React Router v6, TailwindCSS.
2.  **Variables de Entorno:** `.env` con `VITE_DATA2REST_URL`, `VITE_PROJECT_ID`, `VITE_GOOGLE_CLIENT_ID`.
3.  **Carpetas:**
    - `src/lib`: Cliente API y AuthContext.
    - `src/components`: UI reutilizable.
    - `src/pages`: Vistas (Login, Callback, Dashboard).
    - `src/routes`: Definición de rutas protegidas.

Genera primero:
1.  `src/lib/api-client.ts`: Clase para peticiones HTTP que inyecte automáticamente el token JWT desde localStorage.
2.  `src/context/AuthContext.tsx`: Un Context Provider que maneje `user`, `loading`, `login` y `logout`.
```

---

## 📌 Prompt 2: Autenticación con Google    

```text
Implementemos el login con Google OAuth 2.0.

1.  Crea `src/components/GoogleLoginButton.tsx`. Debe redirigir al usuario a `https://accounts.google.com/o/oauth2/v2/auth` con:
    - `client_id`: (desde env)
    - `redirect_uri`: `http://localhost:5173/auth/callback` (puerto por defecto de Vite)
    - `response_type`: `code`
    - `state`: (project_id desde env)

2.  Crea la página `src/pages/auth/Login.tsx` que use ese botón.

3.  Crea la página `src/pages/auth/Callback.tsx`. Lógica:
    - Leer `?code=` de la URL.
    - Hacer fetch POST a `${VITE_DATA2REST_URL}/api/v1/auth/google/verify`.
    - Body: `{ code, redirect_uri }`.
    - Si responde OK: guardar token en localStorage y redirigir a `/dashboard`.
    - Si falla: mostrar error y botón para volver al login.
```

---

## 📌 Prompt 3: Rutas Protegidas y App

```text
Ahora configuremos el enrutamiento y la protección.

1.  Crea un componente `src/components/ProtectedRoute.tsx`.
    - Si `loading` es true, muestra un spinner.
    - Si no hay `user`, redirige a `/auth/login`.
    - Si hay usuario, renderiza el `Outlet` o el `children`.

2.  Configura `src/App.tsx` con `react-router-dom`:
    - `/auth/login` -> Public
    - `/auth/callback` -> Public
    - `/` -> Redirige a dashboard
    - `/dashboard` -> Protegido (ProtectedRoute)
```

---

## 📌 Prompt 4: Dashboard y Prueba

```text
Finalmente, crea el dashboard en `src/pages/Dashboard.tsx`.

Requisitos:
1.  Mostrar "Hola, {user.name}".
2.  Botón de Logout (usando el AuthContext).
3.  Usa el `api-client` para cargar datos de prueba (ej: `api.get('users')` o cualquier tabla que exista) y muéstralos en una lista simple para verificar la conexión.
```

## 📌 Prompt 5: vercel.json

Crea un archivo llamado `vercel.json` en la raíz del proyecto con este contenido para solucionar el error 404 en el refresh:

{
  "rewrites": [
    { "source": "/(.*)", "destination": "/" }
  ]
}