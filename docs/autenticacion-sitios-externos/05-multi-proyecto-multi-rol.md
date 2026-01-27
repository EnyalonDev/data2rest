# 🔄 Multi-Proyecto: Un Usuario, Múltiples Roles

## ✅ Respuesta Directa

**SÍ, está completamente considerado en el plan.** Un usuario puede:

1. ✅ Estar en múltiples proyectos
2. ✅ Tener **diferentes roles** en cada proyecto
3. ✅ Acceder a cada sitio web con el rol correspondiente
4. ✅ **NO ver datos** de proyectos donde no está asignado como cliente

---

## 📊 Caso de Uso Real: Juan el Administrador-Cliente

### **Perfil de Juan:**

```
Juan (user_id: 10)
├── Rol en Data2Rest: Administrador de Proyectos
├── Proyectos que administra:
│   ├── Proyecto 5: Blog Corporativo
│   ├── Proyecto 6: Sistema CRM
│   └── Proyecto 7: Portal Educativo
│
└── Proyectos donde es CLIENTE:
    ├── Proyecto 1: Clínica Veterinaria
    └── Proyecto 3: Tienda de Electrodomésticos
```

---

## 🗄️ Estructura en Base de Datos

### **Tabla `users`:**

| id | username | email | role_id | status |
|----|----------|-------|---------|--------|
| 10 | juan_admin | juan@empresa.com | 2 (Admin) | 1 |

### **Tabla `project_users` (Relación Multi-Rol):**

| project_id | user_id | permissions (interno) | external_permissions (sitio web) | external_access_enabled |
|------------|---------|----------------------|----------------------------------|------------------------|
| 1 (Clínica) | 10 | `{"admin": false}` | `{"role":"client","pages":["dashboard","my-pets"],"data_access":{"scope":"own"}}` | 1 |
| 3 (Tienda) | 10 | `{"admin": false}` | `{"role":"client","pages":["dashboard","my-orders"],"data_access":{"scope":"own"}}` | 1 |
| 5 (Blog) | 10 | `{"admin": true}` | `null` | 0 |
| 6 (CRM) | 10 | `{"admin": true}` | `null` | 0 |
| 7 (Portal) | 10 | `{"admin": true}` | `null` | 0 |

---

## 🎯 Flujos de Autenticación por Contexto

### **Escenario 1: Juan accede a la Clínica Veterinaria**

```
1. Juan visita: clinica-vet.com
2. Hace login con Google
3. Data2Rest verifica:
   ✓ project_id = 1 (Clínica)
   ✓ user_id = 10 (Juan)
   ✓ Busca en project_users: (1, 10)
   ✓ external_access_enabled = 1
   ✓ external_permissions = {"role":"client",...}

4. Genera token JWT:
   {
     "user_id": 10,
     "project_id": 1,
     "role": "client",
     "permissions": {
       "pages": ["dashboard", "my-pets"],
       "data_access": {"scope": "own"}
     }
   }

5. Juan accede al sitio como CLIENTE
   → Solo ve SUS mascotas
   → Solo ve SUS citas
   → NO ve datos de otros clientes
   → NO ve panel de administración
```

---

### **Escenario 2: Juan accede a la Tienda de Electrodomésticos**

```
1. Juan visita: tienda-electro.com
2. Hace login con Google (mismo usuario)
3. Data2Rest verifica:
   ✓ project_id = 3 (Tienda)
   ✓ user_id = 10 (Juan)
   ✓ Busca en project_users: (3, 10)
   ✓ external_access_enabled = 1
   ✓ external_permissions = {"role":"client",...}

4. Genera token JWT DIFERENTE:
   {
     "user_id": 10,
     "project_id": 3,  ← DIFERENTE
     "role": "client",
     "permissions": {
       "pages": ["dashboard", "my-orders"],
       "data_access": {"scope": "own"}
     }
   }

5. Juan accede al sitio como CLIENTE
   → Solo ve SUS pedidos
   → Solo ve SU historial de compras
   → NO ve datos de otros clientes
   → NO ve panel de administración
```

---

### **Escenario 3: Juan accede a Data2Rest (Panel Admin)**

```
1. Juan visita: data2rest.com/admin
2. Hace login con sus credenciales de Data2Rest
3. Data2Rest verifica:
   ✓ user_id = 10
   ✓ role_id = 2 (Administrador)
   ✓ Carga proyectos asignados

4. Juan ve en el panel:
   ├── Proyecto 5: Blog Corporativo     ← Puede administrar
   ├── Proyecto 6: Sistema CRM          ← Puede administrar
   └── Proyecto 7: Portal Educativo     ← Puede administrar

   NO ve:
   ✗ Proyecto 1: Clínica (no es admin)
   ✗ Proyecto 3: Tienda (no es admin)

5. Juan administra sus proyectos:
   → Gestiona bases de datos
   → Configura API keys
   → Ve reportes
   → NO ve datos de clínica ni tienda
```

---

## 🔐 Separación de Contextos

### **Clave: Tokens JWT Específicos por Proyecto**

```
Token para Clínica:
{
  "project_id": 1,
  "role": "client",
  "data_access": {"scope": "own"}
}

Token para Tienda:
{
  "project_id": 3,
  "role": "client",
  "data_access": {"scope": "own"}
}

Sesión en Data2Rest:
{
  "role_id": 2,
  "permissions": {"admin": true},
  "projects": [5, 6, 7]
}
```

**Resultado:** Juan tiene **3 contextos separados** que nunca se mezclan.

---

## 🆕 Caso: Usuario Ya Registrado se Une a Nuevo Proyecto

### **Escenario: María ya es cliente de la Clínica**

```
Tabla users:
| id | email              |
|----|--------------------|
| 5  | maria@gmail.com    |

Tabla project_users:
| project_id | user_id | external_permissions |
|------------|---------|---------------------|
| 1 (Clínica)| 5       | {"role":"client"}   |
```

### **María se registra en la Tienda:**

```
1. María visita: tienda-electro.com/auth/register
2. Completa formulario con: maria@gmail.com
3. Data2Rest recibe:
   POST /api/v1/auth/register
   Headers: X-Project-ID: 3
   Body: { email: "maria@gmail.com", password: "..." }

4. Data2Rest verifica:
   SELECT id FROM users WHERE email = 'maria@gmail.com'
   → Encuentra user_id = 5 (ya existe)

5. Data2Rest NO crea usuario nuevo
   En su lugar, crea SOLO la relación:
   
   INSERT INTO project_users (project_id, user_id, external_access_enabled)
   VALUES (3, 5, 0)  ← Pendiente de aprobación

6. Administrador de la Tienda aprueba:
   UPDATE project_users SET
     external_permissions = '{"role":"client",...}',
     external_access_enabled = 1
   WHERE project_id = 3 AND user_id = 5;

7. Ahora María puede acceder a AMBOS sitios:
```

**Resultado en `project_users`:**

| project_id | user_id | external_permissions | external_access_enabled |
|------------|---------|---------------------|------------------------|
| 1 (Clínica)| 5       | `{"role":"client","pages":["dashboard","my-pets"]}` | 1 |
| 3 (Tienda) | 5       | `{"role":"client","pages":["dashboard","my-orders"]}` | 1 |

---

## 📋 Matriz de Acceso - Ejemplo Completo

### **Usuarios:**

| ID | Nombre | Email | Rol en Data2Rest |
|----|--------|-------|------------------|
| 5 | María | maria@gmail.com | Usuario |
| 10 | Juan | juan@empresa.com | Administrador |
| 15 | Ana | ana@gmail.com | Usuario |

### **Proyectos:**

| ID | Nombre | Tipo |
|----|--------|------|
| 1 | Clínica Veterinaria | Sitio Web |
| 3 | Tienda Electrodomésticos | Sitio Web |
| 5 | Blog Corporativo | Sitio Web |

### **Matriz de Acceso:**

| Usuario | Clínica (1) | Tienda (3) | Blog (5) | Data2Rest Admin |
|---------|-------------|------------|----------|-----------------|
| **María** | ✅ Cliente<br>Ve: sus mascotas | ✅ Cliente<br>Ve: sus pedidos | ❌ Sin acceso | ❌ Sin acceso |
| **Juan** | ✅ Cliente<br>Ve: sus mascotas | ✅ Cliente<br>Ve: sus pedidos | ✅ Admin<br>Gestiona todo | ✅ Admin<br>Proyectos 5,6,7 |
| **Ana** | ❌ Sin acceso | ✅ Cliente<br>Ve: sus pedidos | ❌ Sin acceso | ❌ Sin acceso |

---

## 🔄 Código: Manejo de Usuario Existente

### **En `ProjectAuthController::register()`:**

```php
public function register()
{
    $projectId = $_SERVER['HTTP_X_PROJECT_ID'];
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'];
    $password = $data['password'];
    
    $db = Database::getInstance()->getConnection();
    
    // 1. Verificar si el email ya existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Usuario YA EXISTE
        $userId = $existingUser['id'];
        
        // 2. Verificar si ya está en este proyecto
        $checkProject = $db->prepare("
            SELECT id FROM project_users 
            WHERE project_id = ? AND user_id = ?
        ");
        $checkProject->execute([$projectId, $userId]);
        
        if ($checkProject->fetch()) {
            return $this->json([
                'error' => 'Ya estás registrado en este proyecto'
            ], 409);
        }
        
        // 3. Agregar al nuevo proyecto (pendiente aprobación)
        $insertRelation = $db->prepare("
            INSERT INTO project_users 
            (project_id, user_id, external_access_enabled, assigned_at)
            VALUES (?, ?, 0, ?)
        ");
        $insertRelation->execute([$projectId, $userId, date('Y-m-d H:i:s')]);
        
        return $this->json([
            'success' => true,
            'message' => 'Solicitud enviada. Espera aprobación del administrador.',
            'user_id' => $userId
        ], 201);
        
    } else {
        // Usuario NUEVO - crear en users
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $username = explode('@', $email)[0];
        
        $insertUser = $db->prepare("
            INSERT INTO users (username, email, password, role_id, status)
            VALUES (?, ?, ?, 4, 0)
        ");
        $insertUser->execute([$username, $email, $hashedPassword]);
        $userId = $db->lastInsertId();
        
        // Agregar al proyecto
        $insertRelation = $db->prepare("
            INSERT INTO project_users 
            (project_id, user_id, external_access_enabled, assigned_at)
            VALUES (?, ?, 0, ?)
        ");
        $insertRelation->execute([$projectId, $userId, date('Y-m-d H:i:s')]);
        
        return $this->json([
            'success' => true,
            'message' => 'Usuario registrado. Espera aprobación.',
            'user_id' => $userId
        ], 201);
    }
}
```

---

## ✅ Resumen de Respuestas

### **Pregunta 1: Usuario registrado en Clínica se registra en Tienda**

**Respuesta:** 
- ✅ NO se duplica el usuario
- ✅ Se crea SOLO una nueva relación en `project_users`
- ✅ Puede acceder a ambos sitios con roles independientes

### **Pregunta 2: Administrador que es cliente de Clínica y Tienda**

**Respuesta:**
- ✅ En Data2Rest: Ve solo proyectos 5, 6, 7 (que administra)
- ✅ En Clínica: Accede como cliente, ve solo SUS datos
- ✅ En Tienda: Accede como cliente, ve solo SUS datos
- ✅ **NO ve datos administrativos** de Clínica ni Tienda
- ✅ Cada contexto está **completamente separado**

### **Clave del Sistema:**

**El token JWT incluye el `project_id` y el `role` específico:**

```json
{
  "project_id": 1,
  "role": "client",
  "data_access": {"scope": "own"}
}
```

Esto garantiza que Juan como cliente de la Clínica **nunca** vea datos administrativos, aunque sea administrador en otros proyectos.

---

**Documento creado:** 2026-01-24  
**Versión:** 1.0
