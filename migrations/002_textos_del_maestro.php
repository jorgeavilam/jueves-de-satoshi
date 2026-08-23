<?php
/**
 * Las plantillas de contenido se reescribieron con la voz de un nodo.
 *
 * Antes, el titular de la portada y el bloque «¿Qué es...?» eran la redacción
 * del sitio maestro viviendo en `lang/`: cualquier instalación que no los
 * tocara publicaba el titular del autor original, palabra por palabra.
 *
 * Este paso vuelve a correr la semilla del hub para que el maestro conserve su
 * redacción como texto PROPIO. En un nodo no hace nada: el módulo no existe y
 * las plantillas nuevas ya están escritas para él.
 */
$seedFile = __DIR__ . '/../modules/hub/seed-master.php';
if (is_readable($seedFile)) require $seedFile;
