# Guía de Actualización en Producción

## 🚀 Proceso de Actualización

### **Paso 1: Git Pull**
```bash
cd /home3/cne72525/public_html/d2r.nestorovallos.com
git pull origin main
```

### **Paso 2: Ejecutar Security Hardening** ⚠️ **IMPORTANTE**
```bash
bash scripts/security_hardening.sh
```

**¿Por qué es necesario?**
- `git pull` solo descarga archivos
- NO cambia permisos automáticamente
- Los archivos `.htaccess` se descargan, pero los permisos de las bases de datos NO

---

## 📋 Checklist Post-Actualización

Después de cada `git pull`, ejecutar:

```bash
# 1. Actualizar código
git pull origin main

# 2. Aplicar seguridad
bash scripts/security_hardening.sh

# 3. Verificar (opcional)
ls -la data/
curl -I https://d2r.nestorovallos.com/data/system.db
```

---

## 🔄 Automatización Futura (Opcional)

### **Opción A: Git Hook Post-Merge**

Crear archivo `.git/hooks/post-merge`:

```bash
#!/bin/bash
echo "🔒 Aplicando seguridad automáticamente..."
bash scripts/security_hardening.sh
```

Hacer ejecutable:
```bash
chmod +x .git/hooks/post-merge
```

**Ventaja:** Se ejecuta automáticamente después de `git pull`

---

### **Opción B: Script de Actualización Todo-en-Uno**

Crear `scripts/update.sh`:

```bash
#!/bin/bash
echo "📥 Actualizando código..."
git pull origin main

echo ""
echo "🔒 Aplicando seguridad..."
bash scripts/security_hardening.sh

echo ""
echo "✅ Actualización completa!"
```

Usar:
```bash
bash scripts/update.sh
```

---

## ⚠️ Casos Especiales

### **Si Agregas Nuevas Bases de Datos**

Después de crear una nueva base de datos:
```bash
bash scripts/security_hardening.sh
```

### **Si Subes Archivos Manualmente**

Después de subir archivos vía FTP/cPanel:
```bash
bash scripts/security_hardening.sh
```

### **Después de Restaurar un Backup**

```bash
bash scripts/security_hardening.sh
```

---

## 🎯 Resumen

| Acción | Comando Necesario |
|--------|-------------------|
| `git pull` | ✅ `bash scripts/security_hardening.sh` |
| Nueva base de datos | ✅ `bash scripts/security_hardening.sh` |
| Subir archivos FTP | ✅ `bash scripts/security_hardening.sh` |
| Restaurar backup | ✅ `bash scripts/security_hardening.sh` |
| Instalación nueva | ✅ `bash scripts/security_hardening.sh` |

---

## 🔍 Verificación Rápida

```bash
# Ver permisos actuales
ls -la data/ | head -5

# Debe mostrar:
# drwxr-x---  (750)  data/
# -rw-r-----  (640)  *.db
```

---

**Regla de oro:** Después de **cualquier cambio en archivos**, ejecutar `bash scripts/security_hardening.sh`
