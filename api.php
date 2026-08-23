<?php
/**
 * Agregado público de esta instalación, para el directorio de la red.
 *
 * Es opt-in: si el dueño no lo activó en Admin → Privacidad, no devuelve nada.
 * Nunca expone montos ni el histórico: solo el pulso del ejercicio. Y respeta
 * el modo de privacidad: en "solo porcentajes" o "bóveda" tampoco van los sats.
 */
require_once __DIR__ . '/includes/functions.php';
jds_boot();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=900');

if (!site_ready() || get_setting('share_aggregate', '0') !== '1') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'not_shared']);
    exit;
}

$prices = live_prices();
$sats = 0; $compras = 0; $planned = 0; $all = [];
foreach (get_years() as $yr) {
    $p = get_purchases((int)$yr['id']);
    $s = year_summary($p, $prices);
    $sats    += $s['sats'];
    $compras += $s['compras'];
    $planned += planned_purchases($yr, $p);
    $all = array_merge($all, $p);
}
usort($all, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));

$out = [
    'ok'          => true,
    'name'        => site_name(),
    'url'         => SITE_URL,
    'owner'       => owner_name(),
    'locale'      => current_locale(),
    'currency'    => currency_code(),
    'purchase_day'=> purchase_day(),
    'purchases'   => $compras,
    'planned'     => $planned,
    'streak'      => purchase_streak($all),
    'since'       => $all ? $all[0]['fecha'] : null,
    'version'     => JDS_VERSION,
];
// Los sats solo salen si el sitio es abierto: es el dato más sensible.
if (show_amounts()) {
    $out['sats'] = $sats;
    // Solo lo que el riel dibuja: mandar años enteros de precios no sirve a nadie.
    $out['prices'] = array_slice(array_map(fn($p) => (float)$p['precio_local_btc'], $all), -COASTER_MAX_PTS);
}

echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
