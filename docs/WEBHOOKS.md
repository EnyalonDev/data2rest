# 🎣 Guía de Webhooks en Data2Rest

Los Webhooks permiten que **Data2Rest** notifique automáticamente a otros sistemas cuando ocurren eventos importantes en tu aplicación. En lugar de tener que consultar la API constantemente (polling), Data2Rest enviará una solicitud HTTP `POST` a la URL que configures, permitiendo integraciones en tiempo real.

---

## 🚀 Eventos Disponibles

Data2Rest puede disparar webhooks para los siguientes eventos:

| Evento | Descripción |
| :--- | :--- |
| `record.created` | Se dispara cuando se crea un nuevo registro en cualquier tabla. |
| `record.updated` | Se dispara cuando se edita un registro existente. |
| `record.deleted` | Se dispara cuando se elimina un registro. |
| `media.uploaded` | Se dispara cuando se sube un nuevo archivo a la biblioteca de medios. |

---

## 📦 Estructura del Payload

Data2Rest enviará un cuerpo JSON con la siguiente estructura básica.

### Ejemplo: Payload de `record.created`

```json
{
  "event": "record.created",
  "timestamp": "2024-03-20T14:30:00+00:00",
  "payload": {
    "database_id": "mi_crm",
    "table": "clientes",
    "id": 45,
    "data": {
      "nombre": "Juan Pérez",
      "email": "juan@example.com",
      "status": "activo"
    }
  }
}
```

### Cabeceras HTTP (Headers)

Cada solicitud incluye cabeceras importantes para identificar y asegurar la petición:

*   `Content-Type`: `application/json`
*   `User-Agent`: `Data2Rest-Webhook/1.0`
*   `X-Data2Rest-Event`: El nombre del evento (ej. `record.created`)
*   `X-Data2Rest-Signature`: Una firma HMAC-SHA256 para verificar que la petición es auténtica.

---

## 🔒 Seguridad: Verificando la Firma

Para asegurar que los datos realmente vienen de su servidor Data2Rest y no de un impostor, debe verificar la firma `X-Data2Rest-Signature`.

La firma se genera creando un hash **HMAC-SHA256** del cuerpo JSON crudo (raw body) usando el **Secreto de Firma** que configuró al crear el webhook.

### Algoritmo de Verificación
1. Obtener el cuerpo crudo de la solicitud (raw body text).
2. Obtener el `secret` configurado en el panel de Data2Rest.
3. Calcular `hash_hmac('sha256', rawBooking, secret)`.
4. Comparar el hash calculado con el valor de la cabecera `X-Data2Rest-Signature`.

---

## 💡 Ejemplos de Implementación

### 1. Google Apps Script (Hoja de Cálculo)
Este es un caso de uso muy potente. Puedes usar esto para guardar cada nuevo registro de Data2Rest directamente en una Google Sheet.

1. Crea una Google Sheet.
2. Ve a **Extensiones** > **Apps Script**.
3. Pega el siguiente código:

```javascript
function doPost(e) {
  // 1. Obtener los datos
  var jsonString = e.postData.contents;
  var data = JSON.parse(jsonString);
  var evento = data.event;
  var payload = data.payload;

  // 2. (Opcional) Verificar Secreto - Google Apps Script no maneja headers raw fácilmente, 
  // pero puedes pasar el secreto en la URL si prefieres: ?secret=xyz
  
  // 3. Selección de Hoja
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  
  // 4. Lógica según evento
  if (evento === 'record.created') {
    sheet.appendRow([
      data.timestamp,
      payload.table,
      payload.id,
      JSON.stringify(payload.data) // Guardamos todo el objeto data como JSON
    ]);
  }

  // 5. Responder OK
  return ContentService.createTextOutput(JSON.stringify({status: "success"}))
    .setMimeType(ContentService.MimeType.JSON);
}
```

4. Haz clic en **Implementar** (Deploy) > **Nueva implementación**.
5. Selecciona tipo: **Aplicación web**.
6. En "Quién puede acceder", selecciona: **Cualquier persona** (necesario para que Data2Rest pueda enviar datos sin login de Google).
7. Copia la URL generada y pégala en Data2Rest.

### 2. Node.js (Express)
El estándar para servidores modernos. Incluye verificación de seguridad.

```javascript
const express = require('express');
const crypto = require('crypto');
const app = express();

// IMPORTANTE: Necesitamos el body 'raw' para verificar la firma
app.use(express.json({
  verify: (req, res, buf) => {
    req.rawBody = buf;
  }
}));

const WEBHOOK_SECRET = 'su_secreto_aqui';

app.post('/webhook/data2rest', (req, res) => {
  const signature = req.headers['x-data2rest-signature'];
  const event = req.headers['x-data2rest-event'];
  
  // 1. Verificar Firma
  const hash = crypto
    .createHmac('sha256', WEBHOOK_SECRET)
    .update(req.rawBody)
    .digest('hex');

  if (hash !== signature) {
    console.error('Firma inválida');
    return res.status(401).send('Firma inválida');
  }

  // 2. Procesar Evento
  console.log(`Recibido evento: ${event}`);
  
  if (event === 'record.created') {
    const nuevoRegistro = req.body.payload.data;
    console.log('Nuevo usuario creado:', nuevoRegistro.email);
    // Aquí podrías enviar un email de bienvenida, notificar a Slack, etc.
  }

  res.json({ received: true });
});

app.listen(3000, () => console.log('Escuchando webhooks en puerto 3000'));
```

### 3. PHP (Puro)
Ejemplo básico sin frameworks.

```php
<?php
$secret = 'su_secreto_aqui';

// 1. Obtener contenido
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_DATA2REST_SIGNATURE'] ?? '';
$event = $_SERVER['HTTP_X_DATA2REST_EVENT'] ?? '';

// 2. Verificar Firma
$calculated = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($calculated, $signature)) {
    http_response_code(401);
    die('Firma inválida');
}

// 3. Decodificar
$data = json_decode($payload, true);

// 4. Logear o procesar
if ($event === 'media.uploaded') {
    $filename = $data['payload']['filename'];
    $url = $data['payload']['url'];
    
    // Ejemplo: Guardar en un log.txt
    file_put_contents('uploads.log', "Archivo subido: $filename ($url)\n", FILE_APPEND);
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
```

---

## 🛠 Probando tus Webhooks

Si no tienes un servidor listo, te recomendamos usar herramientas gratuitas para inspeccionar las peticiones que envía Data2Rest:

1.  Ve a **[Webhook.site](https://webhook.site)**.
2.  Copia la URL única que te generan.
3.  Crea un nuevo Webhook en Data2Rest usando esa URL.
4.  Haz clic en el botón de **Test** en Data2Rest.
5.  Verás inmediatamente la estructura de datos en Webhook.site.

---

## ⚠️ Errores Comunes

*   **Error 401 / Firma Inválida**: Asegúrate de que el secreto en tu código coincida exactamente con el de Data2Rest (espacios extra pueden afectar).
*   **Timeouts**: Data2Rest espera un máximo de 3 segundos por tu respuesta. Si tu script tarda mucho (ej. enviar emails pesados), responde 200 OK primero y procesa la tarea en segundo plano.
*   **Permisos (Google Apps Script)**: Recuerda desplegar como "Usuario que accede a la aplicación web: Cualquiera" (Anyone), o Data2Rest recibirá un error 403 o la página de login de Google.
