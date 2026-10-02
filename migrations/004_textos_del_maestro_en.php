<?php
/**
 * El sitio maestro publica también en inglés.
 *
 * Vuelve a correr la semilla del hub, ahora que existe content_tr (migración
 * 003): le agrega al maestro sus textos y su bio en inglés y activa el idioma.
 * La semilla nunca pisa un valor ya escrito. En un nodo no hace nada: el
 * módulo no existe, y cada dueño activa sus idiomas desde Admin → Marca.
 */
$seedFile = __DIR__ . '/../modules/hub/seed-master.php';
if (is_readable($seedFile)) require $seedFile;
