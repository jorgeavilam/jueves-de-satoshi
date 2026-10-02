<?php
/**
 * Imagen para compartir: /og.php (el recorrido completo) o /og.php?y=2026.
 *
 * Misma privacidad que llms.txt: siempre la vista PÚBLICA, aunque el dueño
 * tenga sesión; en «solo porcentajes» no lleva montos ni rendimiento, y en
 * bóveda no existe.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/sharecard.php';
jds_boot();

if (!site_ready() || privacy_mode() === 'vault' || !og_card_available()) { http_response_code(404); exit; }

$amounts = privacy_mode() === 'full';
$prices  = live_prices();
$year    = isset($_GET['y']) ? (int)$_GET['y'] : 0;
$card    = [
    'site'   => site_name(),
    'host'   => preg_replace('#^https?://#', '', SITE_URL),
    'owner'  => owner_name(),
    'accent' => accent_color(),
];

if ($year) {
    $yr = get_year($year);
    if (!$yr) { http_response_code(404); exit; }
    $p = get_purchases((int)$yr['id']);
    $s = year_summary($p, $prices);
    $planned = planned_purchases($yr, $p);
    $card['subtitle'] = t('year_page_title', (string)$year);
    $card['prices']   = array_map(fn($r) => (float)$r['precio_local_btc'], $p);
    if (!$p) {
        $card['line'] = t('og_empty');
    } elseif ($amounts) {
        $card += ['big' => fmt_pct($s['pnl_pct']), 'tone' => $s['pnl'] >= 0 ? 'pos' : 'neg',
                  'line' => t('og_line_year', fmt_int($s['sats']), $s['compras'], $planned)];
    } else {
        $card += ['big' => $s['compras'] . ' / ' . $planned, 'tone' => 'text',
                  'line' => t('og_line_year_pct', $s['streak'])];
    }
} else {
    $g = site_totals($prices);
    $card['subtitle'] = t('home_title_suffix');
    $card['prices']   = $g['prices'];
    if (!$g['compras']) {
        $card['line'] = t('og_empty');
    } elseif ($amounts) {
        $pct = $g['inv'] > 0 ? ($g['val'] - $g['inv']) / $g['inv'] : 0;
        $card += ['big' => fmt_pct($pct), 'tone' => $pct >= 0 ? 'pos' : 'neg',
                  'line' => t('og_line_home', fmt_int($g['sats']), $g['compras'], fmt_fecha_corta_anio($g['first']))];
    } else {
        $card += ['big' => $g['compras'] . ' / ' . $g['planned'], 'tone' => 'text',
                  'line' => t('og_line_home_pct', day_name(purchase_day()), fmt_fecha_corta_anio($g['first']), $g['streak'])];
    }
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');
echo og_render($card);
