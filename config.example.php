<?php
/**
 * Jueves de Satoshi — Configuración (v2)
 *
 * Este archivo guarda SOLO secretos e infraestructura.
 * La identidad, la marca y los textos del sitio NO viven aquí: viven en la
 * base de datos y se editan desde el panel de administración.
 *
 * Normalmente no necesitas tocar este archivo: el instalador (install.php)
 * lo genera por ti. IMPORTANTE: config.php nunca debe subirse a un repositorio.
 */

// --- Base de datos ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'usuario_satoshi');
define('DB_USER', 'usuario_satoshi');
define('DB_PASS', 'TU_PASSWORD_AQUI');

// --- Dirección de esta instalación (sin slash final) ---
define('SITE_URL', 'https://satoshi.tu-dominio.com');

// --- Formulario de contacto ---
define('CONTACT_EMAIL', 'tu-correo@ejemplo.com'); // destino; nunca se muestra en el sitio
define('CONTACT_FROM', 'no-reply@tu-dominio.com'); // remitente; debe existir en tu hosting

// --- Precio en vivo de BTC (CoinGecko, gratis, sin API key) ---
define('PRICE_CACHE_MINUTES', 10);

// --- Tracking y anti-spam (deja en '' para desactivar) ---
define('GA4_ID', '');        // ej. 'G-XXXXXXXXXX'
define('GTM_ID', '');        // ej. 'GTM-XXXXXXX'
define('META_PIXEL_ID', ''); // ej. '1234567890'
define('RECAPTCHA_SITE_KEY', '');
define('RECAPTCHA_SECRET', '');

// --- Sitio maestro del proyecto ---
// Los nodos se registran aquí y enlazan de vuelta en el badge de atribución.
// No lo cambies salvo que estés operando tu propia red.
define('HUB_URL', 'https://satoshi.jorgeavila.com');
