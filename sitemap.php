<?php
/** Sitemap con las páginas que esta instalación realmente publica. */
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');

$urls = [];
if (site_ready() && privacy_mode() !== 'vault') {
    // lastmod = la última compra: es lo único que cambia la portada y cada año
    $last = [];
    foreach (db()->query('SELECT y.year, MAX(p.fecha) AS f FROM years y JOIN purchases p ON p.year_id = y.id WHERE y.deleted = 0 GROUP BY y.year') as $r) {
        $last[(int)$r['year']] = $r['f'];
    }
    $urls[] = ['loc' => SITE_URL . '/', 'pri' => '1.0', 'mod' => $last ? max($last) : null];
    foreach (get_years() as $yr) {
        $urls[] = ['loc' => year_url((int)$yr['year']), 'pri' => '0.8', 'mod' => $last[(int)$yr['year']] ?? null];
    }
    if (get_setting('ejercicio_mode', 'link') === 'own') {
        $urls[] = ['loc' => SITE_URL . '/acerca.php', 'pri' => '0.7'];
    }
    if (selected_tool('exchange') || selected_tool('wallet')) {
        $urls[] = ['loc' => SITE_URL . '/herramientas.php', 'pri' => '0.6'];
    }
    $urls[] = ['loc' => SITE_URL . '/contacto.php', 'pri' => '0.5'];
    if (is_hub()) $urls[] = ['loc' => SITE_URL . '/red.php', 'pri' => '0.9'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    $mod = !empty($u['mod']) ? '<lastmod>' . e($u['mod']) . '</lastmod>' : '';
    echo "  <url><loc>" . e($u['loc']) . "</loc>$mod<priority>{$u['pri']}</priority></url>\n";
}
echo '</urlset>';
