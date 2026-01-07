<?php
/**
 * Data2Rest Bridge
 * Redirige a la carpeta pública usando una ruta relativa para evitar
 * perder el subdirectorio en la URL.
 */
header('Location: public/');
exit;
