# 🎯 RESUMEN: Arreglar Apache + PHP

## ✅ LO QUE HICE

Creé **3 archivos** para solucionar Apache y gestionar PHP:

1. **`scripts/fix_apache_php.sh`** - Arregla Apache completamente
2. **`scripts/switch_php.sh`** - Cambia versiones de PHP fácilmente  
3. **`docs/APACHE_PHP_GUIDE.md`** - Guía completa de uso

---

## 🚀 EJECUTA ESTO AHORA

### Paso 1: Arreglar Apache
```bash
cd /opt/homebrew/var/www/data2rest
./scripts/fix_apache_php.sh
```

**Te pedirá contraseña** (es normal, necesita permisos de admin).

**Qué hace:**
- ✅ Detiene servicios
- ✅ Arregla permisos
- ✅ Reinstala Apache limpiamente
- ✅ Configura Apache para PHP 8.1
- ✅ Arregla el error de macOS fork
- ✅ Inicia servicios
- ✅ Verifica que funcione

**Tiempo:** ~2-3 minutos

---

### Paso 2: Verificar
Después de ejecutar el script, abre:

```
http://localhost/data2rest/public/pg_test.php
```

**Deberías ver:**
- ✅ pdo_pgsql driver is available!
- ✅ CONNECTION SUCCESSFUL!

---

### Paso 3: Probar DATA2REST
```
http://localhost/data2rest/public/admin/databases/create-form
```

Llena el formulario de PostgreSQL y haz "Test Connection".

**¡Debería funcionar!** ✨

---

## 🔄 CAMBIAR VERSIÓN DE PHP (Futuro)

Cuando necesites cambiar de versión de PHP:

```bash
cd /opt/homebrew/var/www/data2rest

# Cambiar a PHP 8.1
./scripts/switch_php.sh 8.1

# Cambiar a PHP 7.4
./scripts/switch_php.sh 7.4

# Cambiar a PHP 8.2
./scripts/switch_php.sh 8.2
```

**Qué hace:**
- ✅ Detiene servicios
- ✅ Cambia la versión de PHP
- ✅ Actualiza configuración de Apache
- ✅ Reinicia servicios
- ✅ Crea archivo de prueba

---

## 📋 COMANDOS ÚTILES

### Ver estado de servicios
```bash
brew services list
```

### Reiniciar Apache
```bash
brew services restart httpd
```

### Reiniciar PHP
```bash
brew services restart php@8.1
```

### Ver logs de Apache
```bash
tail -f /opt/homebrew/var/log/httpd/error_log
```

### Ver versión de PHP
```bash
php -v
```

---

## 🆘 SI ALGO SALE MAL

### Apache no inicia
```bash
# Ver el error
tail -50 /opt/homebrew/var/log/httpd/error_log

# Probar configuración
/opt/homebrew/bin/apachectl configtest
```

### PHP no funciona
```bash
# Verificar módulo
/opt/homebrew/bin/apachectl -M | grep php

# Debe mostrar: php_module (shared)
```

### Volver al servidor PHP integrado
```bash
cd /opt/homebrew/var/www/data2rest/public
/opt/homebrew/opt/php@8.1/bin/php -S localhost:8000
```

---

## 📚 DOCUMENTACIÓN COMPLETA

Lee la guía completa en:
```
docs/APACHE_PHP_GUIDE.md
```

Incluye:
- Troubleshooting detallado
- Mejores prácticas
- Comandos avanzados
- Recuperación de emergencia

---

## ✨ BENEFICIOS

**Antes:**
- ❌ Apache con errores
- ❌ Múltiples versiones de PHP en conflicto
- ❌ Configuración manual complicada
- ❌ No funciona PostgreSQL en web

**Después:**
- ✅ Apache funcionando correctamente
- ✅ PHP 8.1 configurado perfectamente
- ✅ PostgreSQL funcionando
- ✅ Scripts para cambiar versiones fácilmente
- ✅ Documentación completa

---

## 🎯 PRÓXIMOS PASOS

1. **Ejecuta** `./scripts/fix_apache_php.sh`
2. **Verifica** que funcione en `http://localhost/data2rest/public/`
3. **Prueba** PostgreSQL en DATA2REST
4. **Detén** el servidor PHP del puerto 8000 (ya no lo necesitas)
5. **Disfruta** de Apache funcionando correctamente

---

## 💡 NOTA IMPORTANTE

Después de arreglar Apache, tus URLs volverán a ser:

- ✅ `http://localhost/data2rest/public/...`

En lugar de:

- ❌ `http://localhost:8000/...`

---

**¿Listo para arreglar Apache?** Ejecuta:

```bash
cd /opt/homebrew/var/www/data2rest
./scripts/fix_apache_php.sh
```

Y dime cómo va. 🚀
