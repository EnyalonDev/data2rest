# 🚀 Guía Paso a Paso: Frontend con Next.js

## 📋 Índice

1. [Crear Proyecto Next.js](#1-crear-proyecto-nextjs)
2. [Configurar Variables de Entorno](#2-configurar-variables-de-entorno)
3. [Instalar Dependencias](#3-instalar-dependencias)
4. [Crear Estructura de Archivos](#4-crear-estructura-de-archivos)
5. [Implementar Componentes](#5-implementar-componentes)
6. [Probar Localmente](#6-probar-localmente)
7. [Desplegar en Vercel](#7-desplegar-en-vercel)

---

## 1. Crear Proyecto Next.js

```bash
# Crear proyecto
npx create-next-app@latest mi-sitio-prueba

# Opciones recomendadas:
# ✓ TypeScript: Yes
# ✓ ESLint: Yes
# ✓ Tailwind CSS: Yes
# ✓ src/ directory: No
# ✓ App Router: Yes
# ✓ Import alias: No

# Entrar al proyecto
cd mi-sitio-prueba
```

---

## 2. Configurar Variables de Entorno

Crear archivo `.env.local`:

```bash
# Data2Rest
NEXT_PUBLIC_DATA2REST_URL=http://localhost:8000
NEXT_PUBLIC_PROJECT_ID=1

# Google OAuth (te daré estos valores después)
NEXT_PUBLIC_GOOGLE_CLIENT_ID=TU_CLIENT_ID_AQUI
```

> **Nota:** Por ahora usa `http://localhost:8000` para Data2Rest. Cambiaremos a la URL real cuando esté desplegado.

---

## 3. Instalar Dependencias

```bash
# No necesitas instalar nada adicional
# Next.js ya incluye todo lo necesario
```

---

## 4. Crear Estructura de Archivos

### **Paso 4.1: Crear carpetas**

```bash
mkdir -p lib
mkdir -p components
mkdir -p app/auth/login
mkdir -p app/auth/callback
mkdir -p app/dashboard
mkdir -p app/api/auth/verify
```

---

## 5. Implementar Componentes

### **Paso 5.1: Cliente API**

Crear `lib/api-client.ts`:

```typescript
export class ApiClient {
  private baseUrl: string;
  private projectId: string;
  private token: string | null;

  constructor() {
    this.baseUrl = process.env.NEXT_PUBLIC_DATA2REST_URL!;
    this.projectId = process.env.NEXT_PUBLIC_PROJECT_ID!;
    
    // Verificar si estamos en el navegador
    if (typeof window !== 'undefined') {
      this.token = localStorage.getItem('auth_token');
    } else {
      this.token = null;
    }
  }

  async get<T>(table: string, filters?: Record<string, any>): Promise<T> {
    const params = new URLSearchParams(filters);
    
    const response = await fetch(
      `${this.baseUrl}/api/v1/external/${this.projectId}/${table}?${params}`,
      {
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'X-Project-ID': this.projectId
        }
      }
    );

    if (!response.ok) throw new Error('Error al obtener datos');
    const data = await response.json();
    return data.data;
  }
}
```

---

### **Paso 5.2: Hook de Autenticación**

Crear `lib/auth.ts`:

```typescript
'use client';

import { useState, useEffect } from 'react';

interface User {
  id: number;
  email: string;
  name: string;
  permissions: {
    pages: string[];
  };
}

export function useAuth() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    const userData = localStorage.getItem('user');

    if (token && userData) {
      setUser(JSON.parse(userData));
    }
    setLoading(false);
  }, []);

  const logout = () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    setUser(null);
    window.location.href = '/auth/login';
  };

  return { user, loading, logout };
}
```

---

### **Paso 5.3: Botón de Login con Google**

Crear `components/GoogleLoginButton.tsx`:

```tsx
'use client';

export default function GoogleLoginButton() {
  const handleLogin = () => {
    const clientId = process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID;
    const redirectUri = `${window.location.origin}/auth/callback`;
    const projectId = process.env.NEXT_PUBLIC_PROJECT_ID;
    
    const params = new URLSearchParams({
      client_id: clientId!,
      redirect_uri: redirectUri,
      response_type: 'code',
      scope: 'email profile',
      state: projectId!,
      access_type: 'offline',
      prompt: 'consent'
    });

    window.location.href = `https://accounts.google.com/o/oauth2/v2/auth?${params}`;
  };

  return (
    <button
      onClick={handleLogin}
      className="flex items-center gap-3 bg-white border border-gray-300 rounded-lg px-6 py-3 hover:bg-gray-50 transition shadow-sm"
    >
      <svg className="w-5 h-5" viewBox="0 0 24 24">
        <path
          fill="#4285F4"
          d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
        />
        <path
          fill="#34A853"
          d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
        />
        <path
          fill="#FBBC05"
          d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
        />
        <path
          fill="#EA4335"
          d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
        />
      </svg>
      <span className="font-medium text-gray-700">
        Continuar con Google
      </span>
    </button>
  );
}
```

---

### **Paso 5.4: Página de Login**

Crear `app/auth/login/page.tsx`:

```tsx
import GoogleLoginButton from '@/components/GoogleLoginButton';

export default function LoginPage() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
      <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">
            Bienvenido
          </h1>
          <p className="text-gray-600">
            Inicia sesión para continuar
          </p>
        </div>

        <div className="flex justify-center">
          <GoogleLoginButton />
        </div>

        <p className="text-center text-sm text-gray-500 mt-8">
          Al continuar, aceptas nuestros términos y condiciones
        </p>
      </div>
    </div>
  );
}
```

---

### **Paso 5.5: API Route para Verificar**

Crear `app/api/auth/verify/route.ts`:

```typescript
import { NextRequest, NextResponse } from 'next/server';

export async function POST(request: NextRequest) {
  try {
    const { code, redirect_uri, project_id } = await request.json();

    const response = await fetch(
      `${process.env.NEXT_PUBLIC_DATA2REST_URL}/api/v1/auth/google/verify`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Project-ID': project_id || process.env.NEXT_PUBLIC_PROJECT_ID!,
        },
        body: JSON.stringify({
          code,
          redirect_uri
        })
      }
    );

    const data = await response.json();
    return NextResponse.json(data, { status: response.status });
  } catch (error) {
    return NextResponse.json(
      { success: false, error: 'Error interno del servidor' },
      { status: 500 }
    );
  }
}
```

---

### **Paso 5.6: Página de Callback**

Crear `app/auth/callback/page.tsx`:

```tsx
'use client';

import { useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';

export default function AuthCallback() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const code = searchParams.get('code');
    const state = searchParams.get('state');
    
    if (!code) {
      setError('No se recibió código de autorización');
      return;
    }

    verifyWithData2Rest(code, state);
  }, [searchParams]);

  const verifyWithData2Rest = async (code: string, projectId: string | null) => {
    try {
      const response = await fetch('/api/auth/verify', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          code,
          redirect_uri: `${window.location.origin}/auth/callback`,
          project_id: projectId || process.env.NEXT_PUBLIC_PROJECT_ID
        })
      });

      const data = await response.json();

      if (data.success) {
        localStorage.setItem('auth_token', data.data.token);
        localStorage.setItem('user', JSON.stringify(data.data.user));
        router.push('/dashboard');
      } else {
        setError(data.error || 'Error de autenticación');
      }
    } catch (err) {
      setError('Error al conectar con el servidor');
    }
  };

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="bg-red-50 border border-red-200 rounded-lg p-6 max-w-md">
          <h2 className="text-red-800 font-semibold mb-2">Error de Autenticación</h2>
          <p className="text-red-600 mb-4">{error}</p>
          <button
            onClick={() => router.push('/auth/login')}
            className="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
          >
            Volver a intentar
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center">
      <div className="text-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p className="text-gray-600">Verificando credenciales...</p>
      </div>
    </div>
  );
}
```

---

### **Paso 5.7: Dashboard Protegido**

Crear `app/dashboard/page.tsx`:

```tsx
'use client';

import { useAuth } from '@/lib/auth';
import { useEffect } from 'react';
import { useRouter } from 'next/navigation';

export default function Dashboard() {
  const { user, loading, logout } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !user) {
      router.push('/auth/login');
    }
  }, [user, loading, router]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (!user) {
    return null;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16 items-center">
            <h1 className="text-xl font-bold text-gray-900">Mi Sitio</h1>
            <div className="flex items-center gap-4">
              <span className="text-gray-700">{user.name}</span>
              <button
                onClick={logout}
                className="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
              >
                Cerrar Sesión
              </button>
            </div>
          </div>
        </div>
      </nav>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-2xl font-bold mb-4">Dashboard</h2>
          <p className="text-gray-600 mb-4">
            ¡Bienvenido, {user.name}!
          </p>
          
          <div className="bg-blue-50 border border-blue-200 rounded p-4">
            <h3 className="font-semibold text-blue-900 mb-2">Información del Usuario</h3>
            <p className="text-sm text-blue-800">Email: {user.email}</p>
            <p className="text-sm text-blue-800">ID: {user.id}</p>
          </div>
        </div>
      </main>
    </div>
  );
}
```

---

### **Paso 5.8: Página Principal**

Modificar `app/page.tsx`:

```tsx
import Link from 'next/link';

export default function Home() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-purple-50 to-pink-100">
      <div className="text-center">
        <h1 className="text-6xl font-bold text-gray-900 mb-4">
          Mi Sitio de Prueba
        </h1>
        <p className="text-xl text-gray-600 mb-8">
          Autenticación con Google OAuth
        </p>
        <Link
          href="/auth/login"
          className="bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700 transition inline-block"
        >
          Iniciar Sesión
        </Link>
      </div>
    </div>
  );
}
```

---

## 6. Probar Localmente

```bash
# Iniciar servidor de desarrollo
npm run dev

# Abrir en navegador
# http://localhost:3000
```

**Flujo de prueba:**
1. Ir a `http://localhost:3000`
2. Clic en "Iniciar Sesión"
3. Clic en "Continuar con Google"
4. (Por ahora dará error porque falta configurar Google OAuth y el backend)

---

## 7. Desplegar en Vercel

### **Paso 7.1: Subir a GitHub**

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/tu-usuario/mi-sitio-prueba.git
git push -u origin main
```

### **Paso 7.2: Conectar con Vercel**

1. Ir a [vercel.com](https://vercel.com)
2. Clic en "Add New Project"
3. Importar tu repositorio de GitHub
4. Configurar variables de entorno:
   - `NEXT_PUBLIC_DATA2REST_URL`
   - `NEXT_PUBLIC_PROJECT_ID`
   - `NEXT_PUBLIC_GOOGLE_CLIENT_ID`
5. Clic en "Deploy"

---

## ✅ Checklist de Implementación

- [ ] Proyecto Next.js creado
- [ ] Variables de entorno configuradas
- [ ] Componentes implementados
- [ ] Probado localmente
- [ ] Subido a GitHub
- [ ] Desplegado en Vercel

---

## 🔄 Próximos Pasos

Una vez que tengas esto listo, te daré:
1. Las credenciales de Google OAuth
2. El `PROJECT_ID` correcto
3. La URL de Data2Rest en producción

**Documento creado:** 2026-01-24  
**Versión:** 1.0
