<?php
/**
 * Sitemap con las páginas que esta instalación realmente publica.
 *
 * Si el sitio publica más de un idioma, cada página va una vez por idioma y
 * cada entrada declara sus alternativas (xhtml:link): Google las agrupa como
 * la misma página traducida.
 */
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');

// Cada página: una función que da su URL en un idioma
$pages = [];
if (site_ready() && privacy_mode() !== 'vault') {
    // lastmod = la última compra: es lo único que cambia la portada y cada año
    $last = [];
    foreach (db()->query('SELECT y.year, MAX(p.fecha) AS f FROM years y JOIN purchases p ON p.year_id = y.id WHERE y.deleted = 0 GROUP BY y.year') as $r) {
        $last[(int)$r['year']] = $r['f'];
    }
    $path = fn(string $p) => fn(string $l) => page_url($p, $l);
    $pages[] = ['url' => $path('/'), 'pri' => '1.0', 'mod' => $last ? max($last) : null];
    foreach (get_years() as $yr) {
        $y = (int)$yr['year'];
        $pages[] = ['url' => fn(string $l) => year_url($y, $l), 'pri' => '0.8', 'mod' => $last[$y] ?? null];
    }
    if (get_setting('ejercicio_mode', 'link') === 'own') $pages[] = ['url' => $path('/acerca.php'), 'pri' => '0.7'];
    if (selected_tool('exchange') || selected_tool('wallet')) $pages[] = ['url' => $path('/herramientas.php'), 'pri' => '0.6'];
    $pages[] = ['url' => $path('/contacto.php'), 'pri' => '0.5'];
    if (is_hub()) {
        $pages[] = ['url' => $path('/red.php'), 'pri' => '0.9'];
        $pages[] = ['url' => fn(string $l) => install_page_url($l), 'pri' => '0.9'];
    }
}

$locs  = site_locales();
$multi = count($locs) > 1;
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . ($multi ? ' xmlns:xhtml="http://www.w3.org/1999/xhtml"' : '') . ">\n";
foreach ($pages as $p) {
    $mod = !empty($p['mod']) ? '<lastmod>' . e($p['mod']) . '</lastmod>' : '';
    $alts = '';
    if ($multi) {
        foreach ($locs as $l) $alts .= '<xhtml:link rel="alternate" hreflang="' . e($l) . '" href="' . e($p['url']($l)) . '"/>';
        $alts .= '<xhtml:link rel="alternate" hreflang="x-default" href="' . e($p['url']($locs[0])) . '"/>';
    }
    foreach ($locs as $l) {
        echo '  <url><loc>' . e($p['url']($l)) . "</loc>$mod<priority>{$p['pri']}</priority>$alts</url>\n";
    }
}
echo '</urlset>';
