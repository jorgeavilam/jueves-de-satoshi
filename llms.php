<?php
/**
 * llms.txt: el sitio explicado para un LLM, en Markdown (https://llmstxt.org).
 *
 * Dice lo mismo que las páginas y con la misma privacidad: en «solo
 * porcentajes» no lleva montos y en bóveda no existe. Se arma siempre con la
 * vista PÚBLICA, aunque el dueño tenga sesión abierta: lo que lee un
 * rastreador nunca debe ser su dashboard.
 */
require_once __DIR__ . '/includes/functions.php';
jds_boot();

header('Content-Type: text/plain; charset=utf-8');
if (!site_ready() || privacy_mode() === 'vault') { http_response_code(404); exit; }
header('Cache-Control: public, max-age=3600');

$amounts = privacy_mode() === 'full';
$prices  = live_prices();
$line    = function (string $s): string { return trim(preg_replace('/\s+/u', ' ', $s)); };

$g = ['compras' => 0, 'planned' => 0, 'sats' => 0, 'inv' => 0.0, 'val' => 0.0, 'streak' => 0, 'first' => ''];
$yearLines = []; $all = [];
foreach (get_years() as $yr) {
    $p = get_purchases((int)$yr['id']);
    if (!$p) continue;
    $s = year_summary($p, $prices);
    $planned = planned_purchases($yr, $p);
    $g['compras'] += $s['compras'];
    $g['planned'] += $planned;
    $g['sats']    += $s['sats'];
    $g['inv']     += $s['invertido'];
    $g['val']     += $s['valor'];
    $all = array_merge($all, $p);
    $yearLines[] = '- [' . (int)$yr['year'] . '](' . SITE_URL . '/year.php?y=' . (int)$yr['year'] . '): '
                 . $line(year_summary_text((int)$yr['year'], $s, $planned, (float)$yr['weekly_amount'], $amounts));
}
if ($all) {
    usort($all, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
    $g['first']  = $all[0]['fecha'];
    $g['streak'] = purchase_streak($all);
}

$out  = '# ' . $line(site_name()) . "\n\n";
$out .= '> ' . $line(t('meta_description', site_name())) . "\n\n";
if ($g['compras']) $out .= $line(site_summary_text($g, $amounts)) . "\n\n";
$bio = get_setting('owner_bio', '');
if ($bio !== '') $out .= $line(t('llms_about', owner_name(), $bio)) . "\n\n";
$out .= $line(t('llms_data_note', fmt_fecha(date('Y-m-d')))) . "\n";

if ($yearLines) $out .= "\n## " . t('llms_years') . "\n\n" . implode("\n", array_reverse($yearLines)) . "\n";

$out .= "\n## " . t('llms_pages') . "\n\n";
$out .= '- [' . t('nav_dashboard') . '](' . SITE_URL . '/): ' . t('llms_page_home') . "\n";
if (get_setting('ejercicio_mode', 'link') === 'own') {
    $out .= '- [' . t('nav_ejercicio') . '](' . SITE_URL . '/acerca.php): ' . t('llms_page_about') . "\n";
}
if (selected_tool('exchange') || selected_tool('wallet')) {
    $out .= '- [' . t('nav_herramientas') . '](' . SITE_URL . '/herramientas.php): ' . t('llms_page_tools') . "\n";
}
$out .= '- [' . t('nav_contacto') . '](' . SITE_URL . '/contacto.php): ' . t('llms_page_contact') . "\n";

if (is_hub()) $out .= '- [' . t('llms_network') . '](' . SITE_URL . '/red.php): ' . t('llms_page_network') . "\n";

if (!is_hub()) $out .= "\n" . $line(t('llms_based_on', HUB_PROJECT, HUB_URL)) . "\n";

echo $out;
