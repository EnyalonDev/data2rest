# 🔧 Guía de Solución de Problemas - Error 500 en Servidor

## 📋 Diagnóstico Rápido

### Paso 1: Ejecutar el Diagnóstico Automático

1. Sube el archivo `diagnostic.php` a la raíz de tu servidor
2. Accede a: `http://tu-dominio.com/diagnostic.php`
3. Revisa todos los checks en rojo (✗) o amarillo (⚠)

### Paso 2: Soluciones Comunes

## 🔴 Problema 1: Permisos de Directorios

**Síntoma:** Error 500 al intentar login

**Causa:** El servidor no puede escribir en la carpeta `data/`

**Solución:**

```bash
# Conectar por SSH a tu servidor
cd /ruta/a/tu/proyecto

# Crear directorios si no existen
mkdir -p data
mkdir -p public/uploads

# Dar permisos de escritura
chmod 755 data
chmod 755 public/uploads

# Si el servidor usa www-data como usuario
chown -R www-data:www-data data
chown -R www-data:www-data public/uploads
```

---

## 🔴 Problema 2: Extensiones PHP Faltantes

**Síntoma:** Error 500 o página en blanco

**Causa:** Faltan extensiones PHP requeridas

**Solución:**

### En Ubuntu/Debian:
```bash
sudo apt-get update
sudo apt-get install php8.1-sqlite3 php8.1-pdo php8.1-mbstring
sudo systemctl restart apache2
```

### En CentOS/RHEL:
```bash
sudo yum install php-pdo php-sqlite3 php-mbstring
sudo systemctl restart httpd
```

### En cPanel:
1. Ir a "Select PHP Version"
2. Activar extensiones: `pdo`, `pdo_sqlite`, `sqlite3`, `mbstring`
3. Guardar cambios

---

## 🔴 Problema 3: mod_rewrite No Habilitado

**Síntoma:** Error 404 o 500 en todas las rutas

**Causa:** Apache mod_rewrite no está habilitado

**Solución:**

```bash
# Habilitar mod_rewrite
sudo a2enmod rewrite

# Reiniciar Apache
sudo systemctl restart apache2
```

**Verificar en .htaccess:**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    # ... resto de reglas
</IfModule>
```

---

## 🔴 Problema 4: AllowOverride No Configurado

**Síntoma:** .htaccess ignorado, error 500

**Causa:** Apache no permite .htaccess

**Solución en Apache Config:**

```apache
<Directory "/var/www/html/data2rest">
    AllowOverride All
    Require all granted
</Directory>
```

Luego reiniciar Apache:
```bash
sudo systemctl restart apache2
```

---

## 🔴 Problema 5: Versión de PHP Incorrecta

**Síntoma:** Error 500 o errores de sintaxis

**Causa:** PHP < 8.0

**Solución:**

### Verificar versión:
```bash
php -v
```

### Actualizar PHP (Ubuntu):
```bash
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install php8.1
sudo a2dismod php7.4
sudo a2enmod php8.1
sudo systemctl restart apache2
```

---

## 🔴 Problema 6: Rutas Absolutas Incorrectas

**Síntoma:** Error 500, archivos no encontrados

**Causa:** Las rutas en Config.php no coinciden con el servidor

**Solución:**

Editar `src/Core/Config.php`:

```php
private static $config = [
    'db_path' => __DIR__ . '/../../data/system.sqlite',
    'upload_dir' => __DIR__ . '/../../public/uploads/',
    'db_storage_path' => __DIR__ . '/../../data/',
];
```

Las rutas relativas con `__DIR__` deberían funcionar automáticamente.

---

## 🔴 Problema 7: Base de Datos Corrupta

**Síntoma:** Error 500 después de login exitoso

**Causa:** Base de datos SQLite corrupta

**Solución:**

```bash
# Hacer backup
cp data/system.sqlite data/system.sqlite.backup

# Eliminar y dejar que se regenere
rm data/system.sqlite

# Acceder a la aplicación para que se reinstale
```

---

## 🔴 Problema 8: Límites de PHP Muy Bajos

**Síntoma:** Error 500 al subir archivos

**Causa:** Límites de upload muy bajos

**Solución en .htaccess:**

Descomentar estas líneas en `.htaccess`:

```apache
php_value upload_max_filesize 128M
php_value post_max_size 128M
php_value max_execution_time 300
php_value max_input_time 300
```

O editar `php.ini`:
```ini
upload_max_filesize = 128M
post_max_size = 128M
max_execution_time = 300
```

---

## 📊 Verificar Logs de Error

### Apache Error Log:

```bash
# Ubuntu/Debian
sudo tail -f /var/log/apache2/error.log

# CentOS/RHEL
sudo tail -f /var/log/httpd/error_log

# cPanel
tail -f ~/public_html/error_log
```

### PHP Error Log:

Crear archivo `.user.ini` en la raíz:
```ini
error_reporting = E_ALL
display_errors = On
log_errors = On
error_log = error.log
```

---

## ✅ Checklist Final

Antes de contactar soporte, verifica:

- [ ] PHP >= 8.0
- [ ] Extensiones: pdo, pdo_sqlite, sqlite3, mbstring instaladas
- [ ] Directorio `data/` existe y es escribible (chmod 755)
- [ ] Directorio `public/uploads/` existe y es escribible
- [ ] mod_rewrite habilitado en Apache
- [ ] AllowOverride All en configuración de Apache
- [ ] .htaccess presente en raíz y en public/
- [ ] Logs de error revisados

---

## 🆘 Si Nada Funciona

1. **Activa el modo debug:**

Editar `public/index.php`, agregar al inicio:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

2. **Revisa el error exacto** en los logs

3. **Comparte el error** con el desarrollador incluyendo:
   - Mensaje de error completo
   - Resultado del diagnostic.php
   - Versión de PHP
   - Sistema operativo del servidor

---

## 📞 Contacto

Si después de seguir esta guía sigues teniendo problemas:

- 📧 Email: contacto@nestorovallos.com
- 🐙 GitHub Issues: https://github.com/enyalondev/data2rest/issues

---

**¡No olvides eliminar `diagnostic.php` después de resolver el problema!**
