# 🌐 Data2Rest Studio - Demo Frontend

Este es el cliente oficial de demostración para **Data2Rest**, una Single Page Application (SPA) ultra-moderna construida con **TypeScript** y **Vite**, diseñada para interactuar con la API REST de Data2Rest.

## ✨ Características
- 🟦 **TypeScript Nativo**: Código robusto y autocompletado inteligente.
- 🧩 **Arquitectura de Componentes**: Estructura modular y escalable.
- ⚡ **Vite**: Desarrollo instantáneo y hot-reloading.
- 💎 **Premium UI**: Diseño moderno con efectos Glassmorphism y Dark Mode.
- 🛰️ **Integración Total**: Consumo completo de endpoints de Data2Rest (CRUD, subida de archivos, edición dinámica).

## 🚀 Instalación Local

1. Asegúrate de tener instalado **Node.js** (v18.0 o superior).
2. Entra en la carpeta del demo:
   ```bash
   cd demo-frontend
   ```
3. Instala las dependencias:
   ```bash
   npm install
   ```
4. Inicia el servidor de desarrollo:
   ```bash
   npm run dev
   ```
5. Abre en tu navegador: `http://localhost:3000`

## ⚙️ Configuración de la API

El archivo `src/services/api.ts` contiene la configuración de conexión. Por defecto apunta a:
- **Base URL**: `http://localhost:8000/api/v1/data2rest`
- **Integración**: Asegúrate de tener tu servidor backend PHP corriendo.

## 📦 Despliegue en Vercel

Puedes desplegar este frontend de forma sencilla en [Vercel](https://vercel.com):

1. **Instala el CLI de Vercel** (opcional) o conecta tu repositorio de Git.
2. **Importa el proyecto**: Selecciona la carpeta `demo-frontend`.
3. **Comandos de Vercel**:
   - **Framework Preset**: Vite
   - **Build Command**: `npm run build`
   - **Output Directory**: `dist`
4. **Variables de Entorno**: Si has externalizado la API Key, agrégala en los ajustes de Vercel.

---

## 🏗️ Estructura del Proyecto
- `/src/components`: Módulos de la UI (Hero, About, Services, Contact).
- `/src/services`: Lógica de comunicación con la API.
- `/src/utils`: Ayudantes (toasts, validaciones).
- `/src/types.ts`: Definiciones de interfaces TypeScript.

---

## 🔗 Repositorio

- **GitHub**: [github.com/enyalondev/data2rest](https://github.com/enyalondev/data2rest)
- **Issues**: [Reportar un problema](https://github.com/enyalondev/data2rest/issues)
- **Documentación completa**: Ver el [README principal](../README.md)
