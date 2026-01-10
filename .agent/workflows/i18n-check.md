---
description: Sincronización automática de traducciones (i18n)
---

Este workflow automatiza la revisión y actualización de los archivos de traducción (`es.php`, `en.php`, `pt.php`) después de que se realicen cambios en el código, incluyendo la detección de textos que olvidaste traducir.

### Pasos del Workflow

1. **Detectar cambios y textos sin traducir**:
   - Primero, listar archivos modificados en el último commit:
     // turbo
     `git diff-tree --no-commit-id --name-only -r HEAD`
   - El Agente debe revisar estos archivos buscando:
     a) Nuevas llamadas a `Lang::get('clave.ejemplo')`.
     b) **Textos hardcoded**: Palabras o frases escritas directamente en el HTML/Blade o en respuestas de controladores (ej: `'Usuario guardado con éxito'`).

2. **Validación de Coincidencias**:
   - Para cada texto hardcoded encontrado, el Agente debe revisar si ese *valor* ya existe en `src/I18n/es.php`.
   - **Caso A (Existe)**: Si el texto ya tiene una clave (ej: "Aceptar" -> `common.accept`), el Agente debe proponer reemplazarlo automáticamente en el código.
   - **Caso B (No existe)**: Si el texto es nuevo, el Agente debe proponer una nueva clave lógica (ej: `users.save_success`), agregarla a los archivos PHP de idiomas y reemplazar el texto en el código.

3. **Ejecutar sincronización técnica**:
   - Ejecutar el script para asegurar que las claves existan en todos los idiomas:
     // turbo
     `php tools/i18n-sync.php`

4. **Notificar y Aplicar**:
   - Mostrar un resumen de los cambios: "X claves nuevas añadidas", "Y textos existentes reemplazados".
   - Preguntar al usuario para confirmar los cambios en el código y realizar el commit.

### Cuándo usar este workflow
- Después de cada commit que incluya lógica de negocio o vistas.
- Cuando se sospeche que faltan traducciones en algún idioma.
- Antes de realizar un despliegue a producción.
