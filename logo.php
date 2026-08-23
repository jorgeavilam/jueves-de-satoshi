<?php
/** Monograma del sitio como SVG: sirve de logo y de favicon sin subir archivos. */
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo monogram_svg(64);
