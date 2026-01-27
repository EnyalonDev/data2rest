# 🎭 Sistema de Roles y Permisos para Frontend

## 🎯 Objetivo

Implementar control de acceso granular en los sitios web (frontend) basado en roles de usuario, permitiendo diferentes niveles de acceso a páginas, componentes y datos.

---

## 📋 Caso de Uso: Clínica Veterinaria

### **Roles Definidos:**

1. **👑 Dueño/Administrador del Proyecto**
   - Acceso total a todas las funcionalidades
   - Gestión de usuarios, configuración, reportes
   - Acceso a todos los datos de todos los clientes

2. **👨‍⚕️ Veterinario/Staff**
   - Gestión de citas
   - Acceso a historias clínicas de todas las mascotas
   - Creación de tratamientos y recetas
   - Sin acceso a configuración del sistema

3. **👤 Cliente**
   - Solo ve sus propias mascotas
   - Solo ve sus propias citas
   - Solo ve historias clínicas de sus mascotas
   - Sin acceso a datos de otros clientes

---

## 🏗️ Arquitectura de Permisos

### **Estructura en `project_users.external_permissions`:**

```json
{
    "role": "client|staff|admin",
    "pages": ["dashboard", "appointments", "pets"],
    "data_access": {
        "scope": "own|all",
        "filters": {
            "pets": "owner_id = user_id",
            "appointments": "client_id = user_id"
        }
    },
    "actions": {
        "pets": ["read"],
        "appointments": ["read", "create"],
        "treatments": []
    }
}
```

### **Ejemplo por Rol:**

#### **Cliente (María):**
```json
{
    "role": "client",
    "pages": ["dashboard", "my-pets", "my-appointments"],
    "data_access": {
        "scope": "own",
        "filters": {
            "pets": "owner_id = 5",
            "appointments": "client_id = 5",
            "medical_records": "pet.owner_id = 5"
        }
    },
    "actions": {
        "pets": ["read"],
        "appointments": ["read", "create", "cancel"],
        "medical_records": ["read"]
    }
}
```

#### **Veterinario (Dr. Juan):**
```json
{
    "role": "staff",
    "pages": ["dashboard", "all-appointments", "all-pets", "treatments"],
    "data_access": {
        "scope": "all",
        "filters": {}
    },
    "actions": {
        "pets": ["read", "update"],
        "appointments": ["read", "create", "update", "delete"],
        "medical_records": ["read", "create", "update"],
        "treatments": ["read", "create", "update"]
    }
}
```

#### **Administrador (Dueño):**
```json
{
    "role": "admin",
    "pages": ["*"],
    "data_access": {
        "scope": "all",
        "filters": {}
    },
    "actions": {
        "*": ["read", "create", "update", "delete"]
    }
}
```

---

## 🔧 Implementación en Data2Rest

### **1. Endpoint Mejorado: Verificar Token con Permisos**

**Ruta:** `POST /api/v1/auth/verify-token`

**Respuesta Mejorada:**
```json
{
    "success": true,
    "data": {
        "user_id": 5,
        "email": "maria@gmail.com",
        "project_id": 1,
        "role": "client",
        "permissions": {
            "pages": ["dashboard", "my-pets", "my-appointments"],
            "data_access": {
                "scope": "own",
                "filters": {
                    "pets": "owner_id = 5",
                    "appointments": "client_id = 5"
                }
            },
            "actions": {
                "pets": ["read"],
                "appointments": ["read", "create", "cancel"]
            }
        },
        "expires_at": "2026-01-25T22:00:00Z"
    }
}
```

---

### **2. Endpoint: Obtener Datos Filtrados por Rol**

**Ruta:** `GET /api/v1/external/{project_id}/{table}`

**Headers:**
```
Authorization: Bearer {token}
X-Project-ID: {project_id}
```

**Ejemplo - Cliente solicita mascotas:**
```
GET /api/v1/external/1/pets
```

**Proceso en Data2Rest:**
```php
// 1. Validar token y obtener permisos
$user = $this->validateToken($token);

// 2. Verificar acceso a la tabla
if (!in_array('pets', $user['permissions']['actions'])) {
    return $this->json(['error' => 'Sin acceso a esta tabla'], 403);
}

// 3. Aplicar filtros según scope
if ($user['permissions']['data_access']['scope'] === 'own') {
    $filter = $user['permissions']['data_access']['filters']['pets'];
    // Reemplazar user_id con el ID real
    $filter = str_replace('user_id', $user['user_id'], $filter);
    
    // Query: SELECT * FROM pets WHERE owner_id = 5
    $stmt = $db->prepare("SELECT * FROM pets WHERE $filter");
} else {
    // Admin/Staff: sin filtros
    $stmt = $db->query("SELECT * FROM pets");
}
```

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "id": 10,
            "name": "Firulais",
            "owner_id": 5,
            "species": "dog"
        }
    ]
}
```

---

## 🌐 Implementación en Frontend (Vercel)

### **1. Hook Mejorado: `useAuth` con Roles**

**Archivo:** `lib/auth.ts`

```typescript
interface User {
  id: number;
  email: string;
  name: string;
  role: 'client' | 'staff' | 'admin';
  permissions: {
    pages: string[];
    data_access: {
      scope: 'own' | 'all';
      filters: Record<string, string>;
    };
    actions: Record<string, string[]>;
  };
}

export function useAuth() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  // ... código de carga de usuario

  const hasPageAccess = (page: string): boolean => {
    if (!user) return false;
    if (user.permissions.pages.includes('*')) return true;
    return user.permissions.pages.includes(page);
  };

  const hasAction = (resource: string, action: string): boolean => {
    if (!user) return false;
    const actions = user.permissions.actions[resource] || [];
    if (actions.includes('*')) return true;
    return actions.includes(action);
  };

  const isRole = (role: string | string[]): boolean => {
    if (!user) return false;
    if (Array.isArray(role)) {
      return role.includes(user.role);
    }
    return user.role === role;
  };

  return { 
    user, 
    loading, 
    logout, 
    hasPageAccess, 
    hasAction,
    isRole 
  };
}
```

---

### **2. Componente: Protección por Rol**

**Archivo:** `components/RoleGuard.tsx`

```tsx
'use client';

import { useAuth } from '@/lib/auth';
import { useRouter } from 'next/navigation';
import { useEffect } from 'react';

interface RoleGuardProps {
  allowedRoles: string[];
  children: React.ReactNode;
  fallback?: React.ReactNode;
}

export default function RoleGuard({ 
  allowedRoles, 
  children, 
  fallback 
}: RoleGuardProps) {
  const { user, loading, isRole } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !user) {
      router.push('/auth/login');
    }
  }, [user, loading, router]);

  if (loading) {
    return <div>Cargando...</div>;
  }

  if (!user || !isRole(allowedRoles)) {
    return fallback || <div>No tienes acceso a esta sección</div>;
  }

  return <>{children}</>;
}
```

**Uso:**
```tsx
// Página solo para admin
<RoleGuard allowedRoles={['admin']}>
  <AdminPanel />
</RoleGuard>

// Página para staff y admin
<RoleGuard allowedRoles={['staff', 'admin']}>
  <AllAppointments />
</RoleGuard>
```

---

### **3. Componente: Mostrar/Ocultar según Permisos**

**Archivo:** `components/Can.tsx`

```tsx
'use client';

import { useAuth } from '@/lib/auth';

interface CanProps {
  do: string;
  on: string;
  children: React.ReactNode;
  fallback?: React.ReactNode;
}

export default function Can({ do: action, on: resource, children, fallback }: CanProps) {
  const { hasAction } = useAuth();

  if (!hasAction(resource, action)) {
    return fallback ? <>{fallback}</> : null;
  }

  return <>{children}</>;
}
```

**Uso:**
```tsx
// Solo mostrar botón si puede crear citas
<Can do="create" on="appointments">
  <button>Nueva Cita</button>
</Can>

// Solo mostrar botón editar si puede actualizar
<Can do="update" on="pets">
  <button>Editar Mascota</button>
</Can>

// Con fallback
<Can 
  do="delete" 
  on="appointments" 
  fallback={<span className="text-gray-400">No puedes cancelar</span>}
>
  <button className="text-red-600">Cancelar Cita</button>
</Can>
```

---

### **4. Cliente API con Filtros Automáticos**

**Archivo:** `lib/api-client.ts`

```typescript
export class ApiClient {
  private baseUrl: string;
  private projectId: string;
  private token: string | null;

  constructor() {
    this.baseUrl = process.env.NEXT_PUBLIC_DATA2REST_URL!;
    this.projectId = process.env.NEXT_PUBLIC_PROJECT_ID!;
    this.token = localStorage.getItem('auth_token');
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

    if (!response.ok) {
      throw new Error('Error al obtener datos');
    }

    const data = await response.json();
    return data.data;
  }

  async create<T>(table: string, data: any): Promise<T> {
    const response = await fetch(
      `${this.baseUrl}/api/v1/external/${this.projectId}/${table}`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'X-Project-ID': this.projectId,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      }
    );

    if (!response.ok) {
      throw new Error('Error al crear registro');
    }

    const result = await response.json();
    return result.data;
  }
}
```

---

### **5. Ejemplo Completo: Dashboard de Cliente**

**Archivo:** `app/dashboard/page.tsx`

```tsx
'use client';

import { useAuth } from '@/lib/auth';
import { ApiClient } from '@/lib/api-client';
import { useEffect, useState } from 'react';
import RoleGuard from '@/components/RoleGuard';
import Can from '@/components/Can';

interface Pet {
  id: number;
  name: string;
  species: string;
  breed: string;
}

interface Appointment {
  id: number;
  date: string;
  reason: string;
  status: string;
}

export default function Dashboard() {
  const { user } = useAuth();
  const [pets, setPets] = useState<Pet[]>([]);
  const [appointments, setAppointments] = useState<Appointment[]>([]);
  const api = new ApiClient();

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    // El backend automáticamente filtra por owner_id si es cliente
    const petsData = await api.get<Pet[]>('pets');
    const appointmentsData = await api.get<Appointment[]>('appointments');
    
    setPets(petsData);
    setAppointments(appointmentsData);
  };

  return (
    <RoleGuard allowedRoles={['client', 'staff', 'admin']}>
      <div className="container mx-auto p-6">
        <h1 className="text-3xl font-bold mb-6">
          Bienvenido, {user?.name}
        </h1>

        {/* Sección de Mascotas */}
        <section className="mb-8">
          <div className="flex justify-between items-center mb-4">
            <h2 className="text-2xl font-semibold">Mis Mascotas</h2>
            
            <Can do="create" on="pets">
              <button className="bg-blue-600 text-white px-4 py-2 rounded">
                Registrar Mascota
              </button>
            </Can>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {pets.map(pet => (
              <div key={pet.id} className="bg-white p-4 rounded-lg shadow">
                <h3 className="font-bold text-lg">{pet.name}</h3>
                <p className="text-gray-600">{pet.species} - {pet.breed}</p>
                
                <div className="mt-4 flex gap-2">
                  <Can do="read" on="medical_records">
                    <button className="text-blue-600">Ver Historial</button>
                  </Can>
                  
                  <Can do="update" on="pets">
                    <button className="text-green-600">Editar</button>
                  </Can>
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* Sección de Citas */}
        <section>
          <div className="flex justify-between items-center mb-4">
            <h2 className="text-2xl font-semibold">Mis Citas</h2>
            
            <Can do="create" on="appointments">
              <button className="bg-green-600 text-white px-4 py-2 rounded">
                Agendar Cita
              </button>
            </Can>
          </div>

          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left">Fecha</th>
                  <th className="px-6 py-3 text-left">Motivo</th>
                  <th className="px-6 py-3 text-left">Estado</th>
                  <th className="px-6 py-3 text-left">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {appointments.map(apt => (
                  <tr key={apt.id} className="border-t">
                    <td className="px-6 py-4">{apt.date}</td>
                    <td className="px-6 py-4">{apt.reason}</td>
                    <td className="px-6 py-4">
                      <span className={`px-2 py-1 rounded text-sm ${
                        apt.status === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                      }`}>
                        {apt.status}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <Can do="cancel" on="appointments">
                        <button className="text-red-600">Cancelar</button>
                      </Can>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>

        {/* Solo para Admin/Staff */}
        <Can do="read" on="reports">
          <section className="mt-8">
            <h2 className="text-2xl font-semibold mb-4">Reportes</h2>
            <div className="bg-white p-6 rounded-lg shadow">
              <p>Estadísticas y reportes del sistema...</p>
            </div>
          </section>
        </Can>
      </div>
    </RoleGuard>
  );
}
```

---

## 📊 Configuración en Data2Rest

### **Panel de Administración - Configurar Permisos de Usuario**

**Vista:** `Proyectos → Usuarios → Editar Usuario`

```
┌─────────────────────────────────────────────────────┐
│ Configurar Acceso Externo - María (Cliente)        │
├─────────────────────────────────────────────────────┤
│                                                     │
│ ☑ Habilitar acceso externo al sitio web            │
│                                                     │
│ Rol:                                                │
│ ● Cliente  ○ Staff  ○ Administrador                │
│                                                     │
│ Páginas Permitidas:                                 │
│ ☑ Dashboard                                         │
│ ☑ Mis Mascotas                                      │
│ ☑ Mis Citas                                         │
│ ☐ Todos los Clientes (solo admin/staff)            │
│ ☐ Reportes (solo admin)                            │
│                                                     │
│ Permisos sobre Datos:                               │
│ Alcance: ● Solo sus datos  ○ Todos los datos       │
│                                                     │
│ Acciones Permitidas:                                │
│                                                     │
│ Mascotas:                                           │
│ ☑ Ver  ☐ Crear  ☐ Editar  ☐ Eliminar              │
│                                                     │
│ Citas:                                              │
│ ☑ Ver  ☑ Crear  ☑ Cancelar  ☐ Eliminar            │
│                                                     │
│ Historias Clínicas:                                 │
│ ☑ Ver  ☐ Crear  ☐ Editar  ☐ Eliminar              │
│                                                     │
│ [Guardar Configuración]                             │
└─────────────────────────────────────────────────────┘
```

---

## ✅ Resumen

### **Funcionalidades Implementadas:**

✅ **Roles por usuario** (cliente, staff, admin)  
✅ **Control de acceso a páginas** específicas  
✅ **Filtrado automático de datos** según rol  
✅ **Permisos granulares** por acción (CRUD)  
✅ **Componentes reutilizables** para protección  
✅ **Configuración visual** en panel admin  

### **Ejemplo Clínica Veterinaria:**

| Rol | Ve sus datos | Ve todos los datos | Puede crear | Puede editar |
|-----|--------------|-------------------|-------------|--------------|
| **Cliente** | ✅ | ❌ | Citas | - |
| **Veterinario** | ✅ | ✅ | Citas, Tratamientos | Historiales |
| **Admin** | ✅ | ✅ | Todo | Todo |

**Documento creado:** 2026-01-24  
**Versión:** 1.0
