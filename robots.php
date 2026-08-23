<?php
/** robots.txt con la dirección de esta instalación. */
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: text/plain; charset=utf-8');

// Un sitio a medio instalar, o en modo bóveda, no se indexa.
if (!site_ready() || privacy_mode() === 'vault') {
    echo "User-agent: *\nDisallow: /\n";
    exit;
}
?>
User-agent: *
Disallow: /admin/
Disallow: /install.php
Disallow: /upgrade.php
Disallow: /api.php
Allow: /

Sitemap: <?= SITE_URL ?>/sitemap.xml
