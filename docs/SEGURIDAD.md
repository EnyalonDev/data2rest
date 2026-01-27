# Guía de Seguridad - Data2Rest

> **CRÍTICO**: Las bases de datos SQLite contienen información sensible y **NO deben ser accesibles vía web**

---

## 🚨 Problema de Seguridad

Las bases de datos SQLite (archivos `.db`, `.sqlite`, `.sqlite3`) contienen:
- Datos de clientes
- Información sensible
- Credenciales
- Registros completos

**Si son accesibles vía web, cualquiera podría descargarlas.**

---

## 🛡️ Capas de Protección Implementadas

### **Capa 1: Ubicación del Directorio** ✅

```
/opt/homebrew/var/www/data2rest/
├── public/          ← Accesible vía web
│   ├── index.php
│   └── ...
├── data/            ← NO accesible vía web (fuera de public/)
│   ├── system.db
│   └── cliente1.db
└── uploads/         ← Accesible vía web (solo media)
```

**Ventaja:** El directorio `data/` está **fuera** de `public/`, por lo que Apache/Nginx no puede servirlo directamente.

---

### **Capa 2: Archivo `.htaccess`** ✅

**Ubicación:** `data/.htaccess`

```apache
# Deny all access to database files
<Files "*.db">
    Order Allow,Deny
    Deny from all
</Files>

<Files "*.sqlite">
    Order Allow,Deny
    Deny from all
</Files>

<Files "*.sqlite3">
    Order Allow,Deny
    Deny from all
</Files>

# Deny access to this directory
Options -Indexes

# Additional protection
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule .* - [F,L]
</IfModule>
```

**Ventaja:** Incluso si alguien encuentra la ruta, Apache devolverá **403 Forbidden**.

---

### **Capa 3: Permisos de Archivos** ✅

#### **Permisos Recomendados:**

| Archivo/Directorio | Permisos | Descripción |
|-------------------|----------|-------------|
| `data/` | **750** | `rwxr-x---` - Solo owner y group |
| `*.db` | **640** | `rw-r-----` - Owner escribe, group lee |
| `uploads/` | **755** | `rwxr-xr-x` - Accesible por web server |
| Media files | **644** | `rw-r--r--` - Legibles por todos |
| `.htaccess` | **644** | `rw-r--r--` - Legible por Apache |

#### **¿Por qué 640 y no 744?**

```
640 = rw-r-----
      │││││││││
      │││└┴┴┴┴┴─ Others: NO access (más seguro)
      ││└─────── Group: Read only
      │└──────── Owner: Write
      └───────── Owner: Read

744 = rwxr--r--
      │││││││││
      │││└┴┴┴┴┴─ Others: Read (INSEGURO!)
      ││└─────── Group: NO access
      │└──────── Owner: Execute
      └───────── Owner: Read/Write
```

**640 es más seguro** porque:
- ✅ Solo el owner (PHP/Apache) puede escribir
- ✅ El group puede leer (para backups)
- ✅ **Otros usuarios NO tienen acceso**

Con 744:
- ❌ Cualquier usuario del sistema podría leer las bases de datos
- ❌ Menos seguro en servidores compartidos

---

### **Capa 4: Protección de `uploads/`** ✅

**Ubicación:** `uploads/.htaccess`

```apache
# Allow access to media files but deny PHP execution
<FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Allow common media files
<FilesMatch "\.(jpg|jpeg|png|gif|webp|avif|svg|pdf|mp4|webm|mp3|wav|zip|doc|docx|xls|xlsx)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>
```

**Ventaja:** Previene ejecución de PHP malicioso subido como "imagen".

---

## 🔧 Aplicar Seguridad

### **Opción 1: Script Automático** (Recomendado)

```bash
cd /opt/homebrew/var/www/data2rest
bash scripts/security_hardening.sh
```

**Salida esperada:**
```
╔════════════════════════════════════════════════════════════╗
║   Data2Rest - Security Hardening                          ║
╚════════════════════════════════════════════════════════════╝

🔒 Securing database directory...
✓ data/ directory: 750
✓ Database files: 640
✓ data/.htaccess: 644

📁 Securing uploads directory...
✓ uploads/ directory: 755
✓ Media files: 644
✓ Subdirectories: 755
✓ uploads/.htaccess: 644

╔════════════════════════════════════════════════════════════╗
║   ✅ SECURITY HARDENING COMPLETED                          ║
╚════════════════════════════════════════════════════════════╝
```

---

### **Opción 2: Manual**

```bash
# 1. Proteger directorio data/
chmod 750 data/
find data/ -type f -name "*.db" -exec chmod 640 {} \;
find data/ -type f -name "*.sqlite" -exec chmod 640 {} \;

# 2. Crear .htaccess en data/
cat > data/.htaccess << 'EOF'
<Files "*.db">
    Order Allow,Deny
    Deny from all
</Files>
Options -Indexes
EOF

# 3. Proteger uploads/
chmod 755 uploads/
find uploads/ -type f -exec chmod 644 {} \;

# 4. Crear .htaccess en uploads/
cat > uploads/.htaccess << 'EOF'
<FilesMatch "\.(php|php3|php4|php5|phtml)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
Options -Indexes
EOF
```

---

## ✅ Verificar Seguridad

### **Test 1: Intentar Acceder a Base de Datos**

```bash
# Intentar descargar una base de datos
curl -I https://tu-dominio.com/data/system.db
```

**Resultado esperado:**
```
HTTP/1.1 403 Forbidden
```

o

```
HTTP/1.1 404 Not Found
```

**❌ MAL (INSEGURO):**
```
HTTP/1.1 200 OK
Content-Type: application/octet-stream
```

---

### **Test 2: Verificar Permisos**

```bash
ls -la data/
```

**Resultado esperado:**
```
drwxr-x---  5 user group  4096 Jan 27 14:00 .
-rw-r-----  1 user group 12288 Jan 27 14:00 system.db
-rw-r-----  1 user group  8192 Jan 27 14:00 cliente1.db
-rw-r--r--  1 user group   256 Jan 27 14:00 .htaccess
```

**Verificar:**
- ✅ Directorios: `drwxr-x---` (750)
- ✅ Bases de datos: `-rw-r-----` (640)
- ✅ .htaccess: `-rw-r--r--` (644)

---

### **Test 3: Verificar .htaccess**

```bash
cat data/.htaccess
```

Debe contener reglas de denegación.

---

### **Test 4: Intentar Listar Directorio**

```bash
curl https://tu-dominio.com/data/
```

**Resultado esperado:**
```
403 Forbidden
```

**❌ MAL (INSEGURO):**
```
Index of /data
- system.db
- cliente1.db
```

---

## 🚀 Para Nuevas Instalaciones

### **Instalador Automático**

El instalador ahora crea automáticamente:
1. ✅ Directorio `data/` con permisos 750
2. ✅ Archivo `data/.htaccess` con reglas de protección
3. ✅ Directorio `uploads/` con permisos 755
4. ✅ Archivo `uploads/.htaccess` con protección anti-PHP

### **Post-Instalación**

Ejecutar siempre:
```bash
bash scripts/security_hardening.sh
```

---

## 🔒 Mejores Prácticas Adicionales

### **1. Mover `data/` Completamente Fuera del Web Root**

**Estructura recomendada:**
```
/var/www/
├── data2rest-data/     ← Bases de datos (fuera de web root)
│   ├── system.db
│   └── cliente1.db
└── html/               ← Web root
    └── data2rest/
        ├── public/
        └── ...
```

**Configurar en `.env`:**
```env
DB_PATH=/var/www/data2rest-data/system.db
DATA_DIR=/var/www/data2rest-data
```

---

### **2. Usar Base de Datos MySQL/PostgreSQL para Sistema**

En lugar de SQLite para `system.db`:
- ✅ No hay archivo físico accesible
- ✅ Autenticación por usuario/contraseña
- ✅ Conexión por socket o localhost

---

### **3. Backups Seguros**

```bash
# Backup con permisos restrictivos
tar -czf backup.tar.gz data/
chmod 600 backup.tar.gz

# Mover fuera del web root
mv backup.tar.gz /var/backups/data2rest/
```

---

### **4. Monitoreo de Accesos**

Revisar logs de Apache/Nginx:
```bash
# Buscar intentos de acceso a .db
grep "\.db" /var/log/apache2/access.log
grep "\.sqlite" /var/log/apache2/access.log
```

---

### **5. Nginx (Alternativa a .htaccess)**

Si usas Nginx en lugar de Apache:

```nginx
# En tu server block
location ~ /data/ {
    deny all;
    return 403;
}

location ~ \.(db|sqlite|sqlite3)$ {
    deny all;
    return 403;
}

location /uploads {
    location ~ \.(php|php3|php4|php5|phtml)$ {
        deny all;
        return 403;
    }
}
```

---

## 📋 Checklist de Seguridad

### Antes de Poner en Producción
- [ ] Ejecutar `bash scripts/security_hardening.sh`
- [ ] Verificar permisos: `ls -la data/`
- [ ] Verificar `.htaccess` existe en `data/`
- [ ] Verificar `.htaccess` existe en `uploads/`
- [ ] Test: Intentar acceder a `https://dominio.com/data/system.db`
- [ ] Test: Intentar listar `https://dominio.com/data/`
- [ ] Revisar logs de acceso

### Mantenimiento Regular
- [ ] Revisar logs semanalmente
- [ ] Verificar permisos después de actualizaciones
- [ ] Backups en ubicación segura
- [ ] Rotar logs antiguos

---

## 🆘 Si Detectas Acceso No Autorizado

### **Acción Inmediata:**

1. **Cambiar permisos inmediatamente:**
   ```bash
   chmod 750 data/
   chmod 640 data/*.db
   ```

2. **Verificar .htaccess:**
   ```bash
   cat data/.htaccess
   ```

3. **Revisar logs:**
   ```bash
   grep "\.db" /var/log/apache2/access.log | tail -100
   ```

4. **Cambiar credenciales:**
   - Cambiar contraseñas de usuarios
   - Regenerar API keys
   - Revisar actividad sospechosa

5. **Notificar a clientes** si hubo exposición de datos

---

## ✅ Resumen

**Capas de Protección:**
1. ✅ Directorio fuera de `public/`
2. ✅ Permisos 640 en archivos `.db`
3. ✅ `.htaccess` con reglas de denegación
4. ✅ Sin listado de directorios
5. ✅ Protección anti-PHP en `uploads/`

**Comando rápido:**
```bash
bash scripts/security_hardening.sh
```

**Verificación:**
```bash
curl -I https://tu-dominio.com/data/system.db
# Debe devolver: 403 Forbidden o 404 Not Found
```

---

**🔒 Tus bases de datos están ahora protegidas con múltiples capas de seguridad.**
