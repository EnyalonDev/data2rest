# Guía de Diagnóstico: Google OAuth

## 🔍 Cómo Diagnosticar Problemas de Google OAuth

### 1. **Hacer `git pull` en Producción**

```bash
cd /home3/cne72525/public_html/d2r.nestorovallos.com
git pull origin main
```

---

### 2. **Verificar Configuración en Base de Datos**

```sql
-- Conectar a MySQL
mysql -u data2rest -p data2rest_system

-- Ver configuración de Google OAuth
SELECT key_name, value 
FROM system_settings 
WHERE key_name IN ('google_client_id', 'google_client_secret', 'google_redirect_uri', 'google_login_enabled');
```

**Valores esperados:**
- `google_client_id`: `TU_CLIENT_ID_AQUI`
- `google_client_secret`: `TU_CLIENT_SECRET_AQUI`
- `google_redirect_uri`: `https://tu-dominio.com/auth/google/callback`
- `google_login_enabled`: `1`

> **IMPORTANTE**: Nunca compartas tus credenciales de Google OAuth públicamente

---

### 3. **Verificar Logs del Servidor**

Los nuevos cambios agregan logs detallados. Revisa los logs de PHP:

```bash
# Ubicación típica de logs en cPanel
tail -f ~/public_html/d2r.nestorovallos.com/error_log

# O logs de Apache
tail -f /var/log/apache2/error.log
```

**Logs que deberías ver:**

#### Cuando haces clic en "Iniciar sesión con Google":
```
Google OAuth: Redirecting to https://accounts.google.com/o/oauth2/auth?...
```

#### Cuando Google te redirige de vuelta:
```
Google OAuth Callback: Started
Google OAuth Callback: Received code
Google OAuth: Token obtained successfully
Google OAuth: User info retrieved - Email: tu@email.com, Google ID: 123456789
```

#### Si hay un error de configuración:
```
Google OAuth Error: Client not configured
```

---

### 4. **Mensajes de Error Visuales**

Ahora verás mensajes de error en la pantalla de login. Ejemplos:

#### ❌ **Si falta configuración:**
```
Error de configuración de Google OAuth: Client ID no configurado, Client Secret no configurado
```

#### ❌ **Si el código expiró:**
```
El código de autorización ha expirado. Por favor, intenta nuevamente.
```

#### ❌ **Si hay un error de Google:**
```
Error de Google: access_denied - El usuario canceló la autorización
```

---

### 5. **Verificar Configuración de Google Cloud Console**

#### A. **URIs de Redirección Autorizados**

1. Ir a: https://console.cloud.google.com/apis/credentials
2. Seleccionar tu proyecto
3. Hacer clic en el Client ID de OAuth 2.0
4. Verificar que en **"URIs de redirección autorizados"** esté:
   ```
   https://tu-dominio.com/auth/google/callback
   ```

#### B. **Orígenes de JavaScript Autorizados**

Verificar que esté:
```
https://tu-dominio.com
```

#### C. **Pantalla de Consentimiento OAuth**

1. Ir a: https://console.cloud.google.com/apis/credentials/consent
2. Verificar que esté configurada
3. Si está en modo "Testing", agregar tu email como usuario de prueba

---

### 6. **Probar el Flujo Completo**

#### Paso 1: Limpiar Sesión
```bash
# Borrar cookies del navegador o usar modo incógnito
```

#### Paso 2: Intentar Login
1. Ir a: `https://tu-dominio.com/login`
2. Hacer clic en "Iniciar sesión con Google"
3. **Observar:**
   - ¿Te redirige a Google?
   - ¿Ves la pantalla de selección de cuenta de Google?
   - ¿Te pide permisos?
   - ¿Te redirige de vuelta?

#### Paso 3: Revisar Mensajes
- Si hay error, verás un mensaje en rojo en la pantalla de login
- Revisar los logs del servidor para más detalles

---

### 7. **Errores Comunes y Soluciones**

#### Error: "redirect_uri_mismatch"

**Causa:** El redirect_uri en la base de datos no coincide con el configurado en Google Cloud Console.

**Solución:**
```sql
UPDATE system_settings 
SET value = 'https://tu-dominio.com/auth/google/callback' 
WHERE key_name = 'google_redirect_uri';
```

#### Error: "invalid_client"

**Causa:** Client ID o Client Secret incorrectos.

**Solución:**
```sql
-- Verificar valores
SELECT key_name, value FROM system_settings 
WHERE key_name IN ('google_client_id', 'google_client_secret');

-- Actualizar si es necesario
UPDATE system_settings SET value = 'TU_CLIENT_ID' WHERE key_name = 'google_client_id';
UPDATE system_settings SET value = 'TU_CLIENT_SECRET' WHERE key_name = 'google_client_secret';
```

#### Error: "access_denied"

**Causa:** El usuario canceló la autorización o no está en la lista de usuarios de prueba.

**Solución:**
- Agregar el email a usuarios de prueba en Google Cloud Console
- O publicar la app (cambiar de "Testing" a "Production")

#### Error: "Unknown column 'google_id'"

**Causa:** La columna `google_id` no existe en la tabla `users`.

**Solución:** El código ahora se auto-repara. Verás el mensaje:
```
Configuración actualizada. Por favor, intenta iniciar sesión nuevamente.
```

---

### 8. **Verificar que la Columna `google_id` Existe**

```sql
-- MySQL
DESCRIBE users;

-- Buscar la columna google_id
-- Debería aparecer como: google_id | varchar(255) | YES | | NULL |
```

Si no existe, agregarla manualmente:
```sql
ALTER TABLE users ADD COLUMN google_id VARCHAR(255);
```

---

### 9. **Verificar Roles**

```sql
-- Ver roles disponibles
SELECT id, name FROM roles;

-- Debería haber un rol "Usuario"
-- Si no existe, el código usará role_id = 4 como fallback
```

---

### 10. **Test Manual del Flujo**

#### A. **Probar Redirect**
```bash
curl -I "https://tu-dominio.com/auth/google"
```

Deberías ver:
```
HTTP/1.1 302 Found
Location: https://accounts.google.com/o/oauth2/auth?...
```

#### B. **Verificar que Google API Client está instalado**
```bash
cd /home3/cne72525/public_html/d2r.nestorovallos.com
php -r "require 'vendor/autoload.php'; echo class_exists('Google\Client') ? 'OK' : 'FALTA';"
```

Debería mostrar: `OK`

Si muestra `FALTA`:
```bash
composer require google/apiclient:"^2.0"
```

---

## 📋 Checklist de Verificación

### Configuración de Base de Datos
- [ ] `google_client_id` configurado
- [ ] `google_client_secret` configurado
- [ ] `google_redirect_uri` = `https://tu-dominio.com/auth/google/callback`
- [ ] `google_login_enabled` = `1`
- [ ] Columna `google_id` existe en tabla `users`

### Configuración de Google Cloud Console
- [ ] URIs de redirección incluyen `https://tu-dominio.com/auth/google/callback`
- [ ] Orígenes de JavaScript incluyen `https://tu-dominio.com`
- [ ] Pantalla de consentimiento configurada
- [ ] Email agregado a usuarios de prueba (si está en modo Testing)

### Código
- [ ] `git pull` ejecutado en producción
- [ ] Google API Client instalado (`vendor/google/apiclient`)
- [ ] Logs de PHP accesibles

### Pruebas
- [ ] Redirect a Google funciona
- [ ] Pantalla de selección de cuenta aparece
- [ ] Callback funciona sin errores
- [ ] Usuario se crea/loguea correctamente

---

## 🆘 Si Nada Funciona

### Opción 1: Revisar Logs en Tiempo Real

```bash
# Terminal 1: Ver logs de PHP
tail -f ~/public_html/tu-dominio.com/error_log

# Terminal 2: Intentar login
# Navegar a https://tu-dominio.com/login
# Hacer clic en "Iniciar sesión con Google"
```

### Opción 2: Habilitar Modo Debug

Editar temporalmente `GoogleAuthController.php`:

```php
// Al inicio de redirectToGoogle()
error_log("=== GOOGLE OAUTH DEBUG ===");
error_log("Client ID: " . $settings['google_client_id'] ?? 'NO SET');
error_log("Client Secret: " . (empty($settings['google_client_secret']) ? 'NO SET' : 'SET'));
error_log("Redirect URI: " . $settings['google_redirect_uri'] ?? 'NO SET');
error_log("Enabled: " . $settings['google_login_enabled'] ?? 'NO SET');
```

### Opción 3: Verificar Manualmente

```bash
# Ver configuración actual
mysql -u data2rest -p data2rest_system -e "SELECT * FROM system_settings WHERE key_name LIKE 'google%';"
```

---

## ✅ Resultado Esperado

Después de hacer `git pull` y configurar correctamente:

1. **Click en "Iniciar sesión con Google"** → Redirige a Google
2. **Seleccionar cuenta** → Pide permisos
3. **Aceptar permisos** → Redirige de vuelta a Data2Rest
4. **Login exitoso** → Dashboard aparece

**Logs esperados:**
```
Google OAuth: Redirecting to https://accounts.google.com/o/oauth2/auth?...
Google OAuth Callback: Started
Google OAuth Callback: Received code
Google OAuth: Token obtained successfully
Google OAuth: User info retrieved - Email: tu@email.com, Google ID: 123456789
Google OAuth: Creating new user for email: tu@email.com
Google OAuth: New user created with ID: 5, username: tu
```

---

## 🔐 Seguridad

**NUNCA compartas públicamente:**
- ❌ Client ID
- ❌ Client Secret
- ❌ API Keys
- ❌ Tokens de acceso

**Guarda tus credenciales en:**
- ✅ Variables de entorno
- ✅ Base de datos (encriptadas)
- ✅ Gestores de secretos (Google Secret Manager, AWS Secrets Manager)

---

**Última actualización**: 2026-01-27
