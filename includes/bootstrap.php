<?php
/**
 * Comprobación de versión de PHP.
 *
 * Va antes que cualquier otra cosa y está escrita con sintaxis vieja a
 * propósito: tiene que poder ejecutarse en el PHP que quiera estar corriendo
 * para poder explicar por qué no alcanza. Un hosting compartido con PHP viejo
 * daba una pantalla en blanco con error 500 y ninguna pista.
 *
 * Nota: esto atrapa funciones que no existen, no errores de sintaxis. Por eso el
 * código evita a propósito construcciones de PHP 8 (match, str_contains): un
 * error de análisis no lo puede rescatar ninguna comprobación.
 */
define('JDS_PHP_MINIMO', '7.4.0');

if (version_compare(PHP_VERSION, JDS_PHP_MINIMO, '<')) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>PHP demasiado viejo</title><style>'
       . 'body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#faf7f2;color:#1a1a1a;'
       . 'display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px;line-height:1.6}'
       . 'div{max-width:520px;background:#fff;border:1px solid #ece4d8;border-radius:14px;padding:32px}'
       . 'h1{font-size:1.25rem;margin:0 0 12px}code{background:#faf7f2;border:1px solid #ece4d8;'
       . 'border-radius:4px;padding:.1em .35em;font-size:.9em}p{margin:0 0 12px;color:#4a4a4a}'
       . '</style></head><body><div>'
       . '<h1>Este sitio necesita una versión más nueva de PHP</h1>'
       . '<p>Tu servidor corre <code>PHP ' . htmlspecialchars(PHP_VERSION, ENT_QUOTES) . '</code> '
       . 'y se necesita <code>' . JDS_PHP_MINIMO . '</code> o superior. Recomendado: PHP 8.1 o más nuevo.</p>'
       . '<p>En cPanel se cambia en <strong>MultiPHP Manager</strong>: elige tu dominio, '
       . 'selecciona la versión y guarda. No se pierde nada.</p>'
       . '</div></body></html>';
    exit;
}
