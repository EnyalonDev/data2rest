# ☁️ Sistema de Backups en la Nube con Google Drive

Data2Rest integra una sincronización con **Google Drive** usando un script intermedio en **Google Apps Script**. 
Esto permite subir tus copias de seguridad sin exponer claves API complejas.

---

## 🛠️ Instrucciones Definitivas (Script Único)

Sigue estos pasos exactos para configurar el script que recibirá los archivos. **Ignora cualquier versión anterior.**

### Paso 1: Configurar Google Apps Script

1. Ve a [script.google.com](https://script.google.com/) e inicia sesión.
2. Haz clic en **"Nuevo proyecto"**.
3. **Borra todo el código** que aparece en el editor.
4. **Copia y Pega** el siguiente código EXACTAMENTE como está:

```javascript
function doPost(e) {
  // Configuración de respuesta JSON estándar
  var output = ContentService.createTextOutput();
  output.setMimeType(ContentService.MimeType.JSON);
  
  try {
    // --- CONFIGURACIÓN OPCIONAL: ID DE CARPETA DESTINO ---
    // Si quieres guardar en una carpeta específica, reemplaza "root" 
    // por el ID de la carpeta (lo que sale en la URL después de folders/...)
    // Ejemplo: var FOLDER_ID = "1aBcD_eFgH-iJkLmNoP";
    var FOLDER_ID = "root"; 
    // -----------------------------------------------------

    // 1. Leer el contenido JSON enviado por Data2Rest + Base64
    var postData = JSON.parse(e.postData.contents);
    
    var filename = postData.filename || 'backup_desconocido.zip';
    var mimeType = postData.mimeType || 'application/zip';
    var base64Data = postData.data;
    
    // 2. Decodificar Base64 a Blob
    var decoded = Utilities.base64Decode(base64Data);
    var blob = Utilities.newBlob(decoded, mimeType, filename);
    
    // 3. Obtener carpeta destino
    var folder;
    if (FOLDER_ID === "root") {
      folder = DriveApp.getRootFolder();
    } else {
      try {
        folder = DriveApp.getFolderById(FOLDER_ID);
      } catch (err) {
        // Fallback si la ID no existe
        folder = DriveApp.getRootFolder(); 
        filename = "[ERROR_FOLDER_ID] " + filename;
      }
    }
    
    // 4. Crear el archivo en Drive
    var file = folder.createFile(blob);
    
    // 5. Responder ÉXITO
    output.setContent(JSON.stringify({
      status: 'success',
      fileId: file.getId(),
      fileUrl: file.getUrl(),
      filename: file.getName(),
      folder: folder.getName()
    }));
    
  } catch (error) {
    // Manejo de errores
    output.setContent(JSON.stringify({
      status: 'error',
      message: error.toString(),
      stack: error.stack
    }));
  }
  
  return output;
}
```

### Paso 2: Desplegar (Deployment) - ¡Importante!

Para que Data2Rest tenga permiso de enviar datos, debes desplegarlo correctamente:

1. Haz clic en el botón azul **"Implementar"** (o "Deploy") arriba a la derecha > **"Nueva implementación"**.
2. Haz clic en el engranaje ⚙️ junto a "Seleccionar tipo" y elige **"Aplicación web"**.
3. Configura lo siguiente:
   *   **Descripción**: `Data2Rest Backup`
   *   **Ejecutar como**: `Yo` (tu email)
   *   **Quién tiene acceso**: `Cualquier usuario` (Anyone) ⚠️ **CRUCIAL**
4. Haz clic en **Implementar**.
5. Google te pedirá **Autorizar acceso**.
   *   Elige tu cuenta.
   *   Si sale "Google no ha verificado esta aplicación", haz clic en **Configuración avanzada** > **Ir a Proyecto (no seguro)** > Permitir.
6. Copia la **URL de la aplicación web** (termina en `/exec`).

### Paso 3: Pegar en Data2Rest

1. Vuelve a tu panel de **Data2Rest**.
2. Ve a **System Tools > Backups** > **Cloud Config**.
3. Pega la URL que copiaste.
4. Guarda y prueba subir un backup.

---

## ❓ Preguntas Frecuentes

**¿Por qué "Cualquier usuario"?**
Data2Rest es un servidor externo, no eres tú navegando con tu cuenta de Google logueada. Para que el servidor pueda "hablar" con el script sin pedirte login cada vez (lo cual es imposible en background), el script debe ser público para recibir datos, pero **solo tú** (el dueño del script) tienes acceso al código y a donde se guardan los archivos (tu Drive). Es seguro porque la URL es secreta (como una contraseña).

**Error "File too large"**
Google Apps Script tiene un límite de tiempo de ejecución y memoria. Data2Rest limita las subidas por este método a **20 MB**. Si tu base de datos es más grande, usa **rclone** en tu servidor (instrucciones abajo).

## 🚀 Método Alternativo: Rclone (Para usuarios avanzados)

Si tienes acceso root a tu servidor y bases de datos > 20MB:

1. Instala rclone: `curl https://rclone.org/install.sh | sudo bash`
2. `rclone config` > New Remote > "drive" > Google Drive.
3. Cronjob: `0 3 * * * rclone sync /ruta/a/data2rest/data/backups remote:backups`
