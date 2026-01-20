# TODO: Mejoras de Procesamiento de Imágenes (Imagick)

Esta es la lista de funcionalidades sugeridas para potenciar el manejo de medios en Data2Rest utilizando la librería Imagick.

## 1. Generación de Múltiples Tamaños (Thumbnails)
- [ ] Crear miniaturas automáticas al subir una imagen (150x150, 600x600).
- [ ] Implementar un sistema de sufijos (ej. `imagen-thumb.webp`) para identificar versiones.
- [ ] Actualizar el Media Manager para usar estas miniaturas en la vista de cuadrícula.

## 2. Previsualización de PDFs
- [ ] Capturar la primera página de un archivo PDF y convertirla a imagen.
- [ ] Mostrar esta imagen como miniatura en el explorador de archivos.
- [ ] Facilitar la identificación visual de documentos sin abrirlos.

## 3. Watermarking (Marcas de Agua)
- [ ] Permitir configurar un logo o texto de marca de agua desde los ajustes.
- [ ] Aplicar automáticamente la marca de agua a las imágenes subidas (opcional por carpeta).
- [ ] Soporte para opacidad y posicionamiento (centro, esquinas).

## 4. Recorte Inteligente (Smart Cropping)
- [ ] Utilizar algoritmos de entropía de Imagick para detectar el centro de interés.
- [ ] Asegurar que el recorte automático para perfiles o miniaturas no corte sujetos principales.

## 5. Extracción de Paleta de Colores
- [ ] Analizar las imágenes subidas para extraer sus 5 colores predominantes.
- [ ] Guardar estos colores como metadatos para permitir interfaces dinámicas en el frontend.

## 6. Soporte de Formatos Profesionales
- [ ] Habilitar la conversión automática de archivos `.psd`, `.tiff` y `.bmp` a formatos web (`webp`, `avif`).
- [ ] Permitir que diseñadores suban sus archivos de trabajo y el sistema genere la versión visualizable al instante.

## 7. Blur-up Placeholders (LCP Optimization)
- [ ] Generar una versión de bajísima resolución (10-20px) con desenfoque gaussiano.
- [ ] Servir esta miniatura como base para técnicas de carga progresiva en el frontend.
