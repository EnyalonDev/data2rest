# Prompts para Integración OAuth con AI Studio (Proyecto Veterinaria - ID 2)

Estos prompts están diseñados para ser ejecutados secuencialmente en Google AI Studio (Gemini 2.0 Flash Thinking). Incorporan las soluciones de seguridad y compatibilidad descubiertas.

---

## Prompt 1: Configuración Inicial y Dependencias

**Objetivo:** Preparar el entorno del Frontend (respetando lo existente).

```text
Actúa como un experto en React y Vite.
Tengo un proyecto existente DE VETERINARIA que ya consume datos de una API externa.

Necesito instalar las dependencias necesarias para implementar autenticación con Google OAuth, SIN romper lo que ya existe.

Por favor, revisa mis dependencias actuales y dame el comando npm install SOLO para lo que falte de esta lista:
- react-router-dom (si no está)
- axios (probablemente ya esté, verifícalo)
- zustand (para el auth store)
- clsx y tailwind-merge (utilidades UI)
- lucide-react (iconos)

No modifiques ningún archivo todavía.
```

---

## Prompt 2: Cliente API y Auth Store

**Objetivo:** Integrar la autenticación en el cliente API existente.

```text
Ahora vamos a configurar la comunicación con el Backend.
El Backend es Data2Rest y el ID de este proyecto es "2" (Veterinaria).

IMPORTANTE: Ya tengo una configuración de API (probablemente con Axios) funcionando para la parte pública.
No quiero borrarla. Quiero EXTENDERLA para soportar autenticación.

1. Revisa mi archivo de cliente API actual (ej: `src/lib/api.ts`, `src/api/client.js` o similar):
   - MANTÉN la Base URL existente.
   - AGREGA un interceptor nuevo: Si existe un token en localStorage ('auth-storage'), agrégalo como Header `Authorization: Bearer ...`.
   - AGREGA un interceptor nuevo: Agrega siempre el Header `X-Project-ID: 2` (si no está ya).

2. Crea el archivo `src/stores/authStore.ts` usando Zustand:
   - Debe persistir en localStorage ('auth-storage').
   - Estado: `user` (null | object), `token` (null | string), `isAuthenticated` (boolean).
   - Acciones: `setAuth(user, token)` y `logout()`.
   - El logout debe borrar el estado y redirigir a '/login'.
```

---

## Prompt 3: Componente Login (Con Redirect Fijo)

**Objetivo:** Crear la página de login que inicia el flujo Server-Side.

```text
Vamos a crear la página de Login.

Crea el archivo `src/pages/auth/Login.tsx`:
- Diseño limpio y profesional para "Plataforma Veterinaria".
- Botón "Iniciar Sesión con Google".
- IMPORTANTE: La lógica del botón debe ser una redirección de ventana (window.location.href).
- URL de destino: `${API_URL}/api/projects/2/auth/google` (Nota el ID 2).
- PARÁMETRO CRÍTICO: Debes incluir `?redirect_uri=` apuntando EXACTAMENTE a `window.location.origin + '/auth/callback'`.
  Ejemplo: `const redirectUri = window.location.origin + '/auth/callback';`
  
No uses componentes de Google SDK cliente, es un flujo server-side puro.
```

---

## Prompt 4: Callback de Google (Manejo de Respuesta)

**Objetivo:** Recibir el código de Google y canjearlo por el token.

```text
Ahora crea la página que recibe a los usuarios de vuelta de Google: `src/pages/auth/GoogleCallback.tsx`.

Lógica requerida:
1. Obtener el `code` de los parámetros de la URL.
2. Hacer POST a `/api/projects/2/auth/google/callback`.
3. BODY del POST: 
   - `code`: el código recibido.
   - `redirect_uri`: DEBE ser idéntico al usado en el Login (`window.location.origin + '/auth/callback'`).
4. Manejo de Respuesta:
   - El backend devuelve `{ data: { token, user } }`. Nota el doble 'data'.
   - Si es exitoso: usa `setAuth(user, token)` del store y redirige a `/dashboard`.
   - Si falla: muestra un mensaje de error amigable y botón para volver al Login.
```

---

## Prompt 5: Rutas y Dashboard

**Objetivo:** Proteger el acceso e integrar todo en App.tsx.

```text
Para finalizar la integración base:

1. Crea `src/components/auth/ProtectedRoute.tsx`:
   - Si `!isAuthenticated`, redirige a `/login`.
   - Si está autenticado, renderiza los hijos (Outlet o children).

2. Crea un Dashboard simple `src/pages/dashboard/DashboardHome.tsx`:
   - Mostrar mensaje de bienvenida con el nombre del usuario.
   - Botón de Logout.

3. Modifica `src/App.tsx`:
   - Mantén las rutas públicas existentes (home, servicios, etc.).
   - Agrega ruta `/login` -> Login.tsx.
   - Agrega ruta `/auth/callback` -> GoogleCallback.tsx.
   - Agrega ruta protegida `/dashboard` -> DashboardHome.tsx.
   - Asegúrate de que todo esté dentro de <BrowserRouter>.
```
