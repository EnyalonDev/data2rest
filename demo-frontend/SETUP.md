# 🚀 Guía de Configuración del Frontend Demo

## 📋 Requisitos Previos

1. ✅ Backend corriendo en `http://localhost/data2rest`
2. ✅ Base de datos demo cargada ("Modern Enterprise ERP")
3. ✅ API Key creada en el panel de administración

---

## 🔧 Configuración Paso a Paso

### **Paso 1: Crear una API Key**

1. Accede al backend: `http://localhost/data2rest`
2. Login con:
   ```
   Usuario: admin
   Contraseña: admin123
   ```
3. Ve al menú **"API Docs"** o **"API Keys"**
4. Click en **"Create New API Key"** o **"Generate API Key"**
5. Copia el valor generado (ejemplo: `abc123-def456-ghi789`)

---

### **Paso 2: Configurar Variables de Entorno**

1. En la carpeta `demo-frontend/`, crea un archivo `.env`:

```bash
cd demo-frontend
cp .env.example .env
```

2. Edita el archivo `.env` y actualiza los valores:

```env
VITE_API_BASE_URL=http://localhost/data2rest/api/v1/modern-enterprise-erp
VITE_API_KEY=TU_API_KEY_AQUI
```

**Reemplaza `TU_API_KEY_AQUI` con la API Key que creaste en el Paso 1.**

---

### **Paso 3: Reiniciar el Servidor de Desarrollo**

```bash
# Detén el servidor actual (Ctrl+C)
# Luego reinicia:
npm run dev
```

---

### **Paso 4: Acceder al Frontend**

Abre tu navegador en: `http://localhost:5173`

---

## 🔍 Verificación

### **Probar la API manualmente:**

```bash
# Reemplaza YOUR_API_KEY con tu API Key real
curl "http://localhost/data2rest/api/v1/modern-enterprise-erp/web_pages" \
  -H "X-API-Key: YOUR_API_KEY"
```

Deberías ver una respuesta JSON con los datos de las páginas.

---

## ❌ Solución de Problemas

### **Problema: "Invalid or inactive API Key"**

**Solución:**
1. Verifica que la API Key esté activa en el panel de administración
2. Asegúrate de copiar la API Key completa (sin espacios)
3. Verifica que el archivo `.env` tenga el formato correcto

---

### **Problema: "Failed to fetch" o errores de CORS**

**Solución:**
1. Verifica que el backend esté corriendo: `http://localhost/data2rest`
2. Asegúrate de que Apache esté corriendo:
   ```bash
   brew services list | grep httpd
   ```
3. Si Apache no está corriendo:
   ```bash
   brew services start httpd
   ```

---

### **Problema: "404 Not Found" en las rutas de API**

**Solución:**
1. Verifica el nombre de la base de datos en el backend
2. El nombre debe estar en formato "slug" (minúsculas, guiones)
3. Ejemplo: "Modern Enterprise ERP" → `modern-enterprise-erp`

Para verificar el nombre correcto:
```bash
sqlite3 ../data/system.sqlite "SELECT name FROM databases"
```

---

### **Problema: No se muestran los servicios**

**Solución:**
1. Verifica que la tabla `servicios` exista en la base de datos demo
2. Verifica que haya datos en la tabla:
   ```bash
   sqlite3 ../data/enterprise_demo_*.sqlite "SELECT * FROM servicios"
   ```

---

## 📝 Estructura de URLs

El frontend hace peticiones a estas rutas:

```
GET  /api/v1/modern-enterprise-erp/web_pages/1        # Hero
GET  /api/v1/modern-enterprise-erp/web_pages/2        # About
GET  /api/v1/modern-enterprise-erp/servicios          # Services
POST /api/v1/modern-enterprise-erp/mensajes_de_contacto  # Contact Form
```

Todas requieren el header: `X-API-Key: TU_API_KEY`

---

## 🎯 Checklist Final

Antes de que el frontend funcione, verifica:

- [ ] Backend corriendo en `http://localhost/data2rest`
- [ ] Base de datos demo cargada
- [ ] API Key creada y activa
- [ ] Archivo `.env` creado con la API Key correcta
- [ ] Servidor de desarrollo reiniciado después de crear `.env`
- [ ] Navegador abierto en `http://localhost:5173`

---

## 🚀 ¡Listo!

Si seguiste todos los pasos, el frontend debería estar funcionando correctamente y mostrando:

- ✅ Hero section con datos dinámicos
- ✅ About section
- ✅ Services section con los 3 servicios
- ✅ Formulario de contacto funcional

---

## 📞 Soporte

Si sigues teniendo problemas, revisa:
1. La consola del navegador (F12) para ver errores específicos
2. Los logs de Apache
3. La consola del terminal donde corre `npm run dev`
