<?php
/**
 * Jueves de Satoshi — Configuración
 * Copia este archivo como config.php y ajusta los valores.
 * IMPORTANTE: config.php NUNCA debe subirse a repositorios públicos.
 */

// --- Base de datos (MySQL, cPanel) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'usuario_satoshi');
define('DB_USER', 'usuario_satoshi');
define('DB_PASS', 'TU_PASSWORD_AQUI');

// --- Sitio ---
define('SITE_NAME', 'Jueves de Satoshi');
define('SITE_URL', 'https://satoshi.jorgeavila.com'); // sin slash final
define('SITE_AUTHOR', 'Jorge Avila Meléndez');
define('SOCIAL_HANDLE', 'jorgeavilam'); // /jorgeavilam en todas las redes

// --- Admin ---
// El usuario y contraseña viven en la tabla `users` de la base de datos.
// Acceso inicial: jorge / cambiame123 — cámbiala en Admin → Contraseña al primer ingreso.

// --- Formulario de contacto ---
define('CONTACT_EMAIL', 'tu-correo@ejemplo.com'); // nunca se muestra en el sitio
define('CONTACT_FROM', 'no-reply@tu-dominio.com');  // remitente del servidor (debe existir en cPanel)

// --- Precio en vivo de BTC (CoinGecko, gratis, sin API key) ---
define('PRICE_CACHE_MINUTES', 10); // minutos de caché del precio

// --- Tracking y seguridad (deja en '' para desactivar) ---
define('GA4_ID', '');            // ej. 'G-XXXXXXXXXX'  (Google Analytics 4)
define('GTM_ID', '');            // ej. 'GTM-XXXXXXX'   (Google Tag Manager)
define('META_PIXEL_ID', '');     // ej. '1234567890'    (Meta Pixel)
define('RECAPTCHA_SITE_KEY', 'TU_RECAPTCHA_SITE_KEY_AQUI'); // Google reCAPTCHA v3
define('RECAPTCHA_SECRET', 'TU_RECAPTCHA_SECRET_AQUI');

// --- Zona horaria ---
date_default_timezone_set('America/Monterrey');
