# Solución: Instalar PDO PostgreSQL en PHP

## 🔴 EL PROBLEMA

Tu servidor web PHP **NO tiene la extensión pdo_pgsql instalada**.

Cuando intentas acceder a cualquier página que use PostgreSQL, PHP crashea con `ERR_EMPTY_RESPONSE`.

---

## ✅ LA SOLUCIÓN

### Opción 1: Reinstalar PHP con soporte PostgreSQL (RECOMENDADO)

```bash
# 1. Reinstalar PHP con todas las extensiones
brew reinstall php

# 2. Instalar extensión PostgreSQL
brew install php-pgsql 2>/dev/null || echo "Already included in PHP"

# 3. Reiniciar PHP-FPM
brew services restart php

# 4. Verificar que funciona
php -m | grep pdo_pgsql
```

### Opción 2: Usar ServBay (si lo tienes instalado)

Si estás usando ServBay:

1. Abre ServBay
2. Ve a "PHP" → "Extensions"
3. Busca "pgsql" o "pdo_pgsql"
4. Actívala
5. Reinicia ServBay

### Opción 3: Compilar extensión manualmente (AVANZADO)

```bash
# Solo si las opciones anteriores no funcionan
pecl install pdo_pgsql
```

---

## 🧪 VERIFICAR QUE FUNCIONA

Después de instalar, ejecuta:

```bash
php -m | grep -i pdo
```

**Debes ver:**
```
PDO
pdo_dblib
pdo_mysql
pdo_pgsql    ← ESTE ES EL IMPORTANTE
pdo_sqlite
```

Si ves `pdo_pgsql` en la lista, ¡funcionó!

---

## 🌐 VERIFICAR EN EL SERVIDOR WEB

Después de instalar, accede a:

```
http://localhost/data2rest/public/pg_test.php
```

**Debería mostrar:**
```
✓ pdo_pgsql driver is available!
✓ CONNECTION SUCCESSFUL!
```

---

## 📝 NOTA IMPORTANTE

El problema NO es:
- ❌ PostgreSQL (está instalado y funcionando)
- ❌ La base de datos mi_tienda (existe)
- ❌ Las credenciales (son correctas)
- ❌ El código de DATA2REST (está bien)

El problema ES:
- ✅ PHP del servidor web no tiene extensión pdo_pgsql

---

## 🚀 EJECUTA ESTO AHORA

```bash
# Copia y pega estos comandos en tu terminal:

cd /opt/homebrew/var/www/data2rest

# Reinstalar PHP
brew reinstall php

# Reiniciar PHP-FPM
brew services restart php

# Verificar
php -m | grep pdo_pgsql

# Si sale "pdo_pgsql", ¡listo!
```

---

## ❓ SI AÚN NO FUNCIONA

Si después de reinstalar PHP aún no funciona, dime:

1. ¿Qué servidor web usas? (Apache, Nginx, ServBay, MAMP, etc.)
2. Ejecuta: `which php` y dime qué sale
3. Ejecuta: `php -v` y dime la versión

Con esa info te ayudaré a configurar el PHP correcto.
