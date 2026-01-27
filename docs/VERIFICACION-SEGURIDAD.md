# Verificación de Protección Web - Guía Rápida

## 🎯 Directorios Protegidos

| Directorio | Estado | Acceso Web | Protección |
|------------|--------|------------|------------|
| `public/` | ✅ Accesible | Permitido | Solo este debe ser accesible |
| `data/` | 🔒 Protegido | **403 Forbidden** | Bases de datos |
| `src/` | 🔒 Protegido | **403 Forbidden** | Código fuente |
| `vendor/` | 🔒 Protegido | **403 Forbidden** | Dependencias |
| `scripts/` | 🔒 Protegido | **403 Forbidden** | Scripts de mantenimiento |
| `uploads/` | ⚠️ Parcial | Media: ✅ / PHP: ❌ | Solo archivos media |

---

## 🧪 Tests de Verificación

### **Test 1: Intentar Acceder a Base de Datos**
```bash
curl -I https://tu-dominio.com/data/system.db
```
**✅ Correcto:** `HTTP/1.1 403 Forbidden`  
**❌ Inseguro:** `HTTP/1.1 200 OK`

---

### **Test 2: Intentar Acceder a Código Fuente**
```bash
curl -I https://tu-dominio.com/src/Core/Database.php
```
**✅ Correcto:** `HTTP/1.1 403 Forbidden`  
**❌ Inseguro:** `HTTP/1.1 200 OK` (se vería el código PHP)

---

### **Test 3: Intentar Acceder a Vendor**
```bash
curl -I https://tu-dominio.com/vendor/autoload.php
```
**✅ Correcto:** `HTTP/1.1 403 Forbidden`  
**❌ Inseguro:** `HTTP/1.1 200 OK`

---

### **Test 4: Intentar Acceder a Scripts**
```bash
curl -I https://tu-dominio.com/scripts/security_hardening.sh
```
**✅ Correcto:** `HTTP/1.1 403 Forbidden`  
**❌ Inseguro:** `HTTP/1.1 200 OK`

---

### **Test 5: Intentar Listar Directorios**
```bash
curl https://tu-dominio.com/data/
curl https://tu-dominio.com/src/
```
**✅ Correcto:** `403 Forbidden` (sin listado de archivos)  
**❌ Inseguro:** Lista de archivos visible

---

### **Test 6: Verificar Uploads (Media OK, PHP NO)**
```bash
# Imagen debe funcionar
curl -I https://tu-dominio.com/uploads/imagen.jpg

# PHP debe estar bloqueado
curl -I https://tu-dominio.com/uploads/malicioso.php
```
**✅ Correcto:**  
- Imagen: `HTTP/1.1 200 OK`  
- PHP: `HTTP/1.1 403 Forbidden`

---

## 🔧 Aplicar Protección

### **En Producción:**
```bash
cd /home3/cne72525/public_html/d2r.nestorovallos.com
git pull origin main
bash scripts/security_hardening.sh
```

### **Verificar:**
```bash
# Ver archivos .htaccess creados
ls -la data/.htaccess
ls -la src/.htaccess
ls -la vendor/.htaccess
ls -la scripts/.htaccess
ls -la uploads/.htaccess

# Todos deben existir
```

---

## 📋 Checklist de Seguridad

### Archivos .htaccess
- [ ] `data/.htaccess` existe
- [ ] `src/.htaccess` existe
- [ ] `vendor/.htaccess` existe
- [ ] `scripts/.htaccess` existe
- [ ] `uploads/.htaccess` existe

### Tests de Acceso
- [ ] `data/` devuelve 403
- [ ] `src/` devuelve 403
- [ ] `vendor/` devuelve 403
- [ ] `scripts/` devuelve 403
- [ ] `uploads/*.jpg` devuelve 200
- [ ] `uploads/*.php` devuelve 403

### Permisos
- [ ] `data/` tiene permisos 750
- [ ] `*.db` tienen permisos 640
- [ ] `.htaccess` tienen permisos 644

---

## 🚨 Si Algo Falla

### **Si ves código fuente en el navegador:**
```bash
# Ejecutar inmediatamente
bash scripts/security_hardening.sh

# Verificar que .htaccess existe
ls -la src/.htaccess

# Si no existe, el script lo creará
```

### **Si .htaccess no funciona:**

Verificar que Apache tiene `AllowOverride` habilitado:
```apache
# En httpd.conf o virtual host
<Directory "/path/to/data2rest">
    AllowOverride All
</Directory>
```

---

## ✅ Resumen de Protecciones

**6 Capas de Seguridad:**
1. ✅ Directorios fuera de `public/`
2. ✅ Permisos restrictivos (640 para .db)
3. ✅ `.htaccess` en `data/`, `src/`, `vendor/`, `scripts/`
4. ✅ Sin listado de directorios (`Options -Indexes`)
5. ✅ PHP bloqueado en `uploads/`
6. ✅ Código fuente protegido

**Qué está protegido:**
- 🔒 Bases de datos (`.db`, `.sqlite`)
- 🔒 Código fuente PHP (`.php`)
- 🔒 Dependencias (`vendor/`)
- 🔒 Scripts de mantenimiento
- 🔒 Configuración (`.env`)

**Qué es accesible:**
- ✅ Aplicación web (`public/`)
- ✅ Archivos media (`uploads/*.jpg`, etc.)
- ✅ Assets públicos (CSS, JS)

---

**Ejecutar después de cada actualización:**
```bash
bash scripts/update.sh
```

O manualmente:
```bash
git pull origin main
bash scripts/security_hardening.sh
```
