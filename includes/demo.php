<?php
/**
 * Datos de ejemplo.
 *
 * Trece compras FICTICIAS para que una instalación nueva se vea funcionando
 * antes de tener historia propia. Se generan respetando el día de compra, la
 * moneda y el monto elegidos, y se borran completas desde el panel con un clic.
 *
 * A propósito no son las compras reales de nadie: un sitio nuevo que arranca
 * con el historial de otra persona es justo lo que queremos evitar.
 */
require_once __DIR__ . '/functions.php';

/** Recorrido de precios inventado, en dólares. Sube, baja, y vuelve a subir. */
function demo_price_walk(int $n): array {
    $base = 62000.0;
    $shape = [0.00, 0.06, 0.02, -0.05, -0.11, -0.04, 0.05, 0.13, 0.09, 0.17, 0.11, 0.21, 0.28,
              0.24, 0.31, 0.26, 0.35, 0.30, 0.38, 0.44, 0.39, 0.47, 0.52, 0.46, 0.55, 0.61];
    $out = [];
    for ($i = 0; $i < $n; $i++) {
        $f = $shape[$i % count($shape)];
        $out[] = round($base * (1 + $f), 2);
    }
    return $out;
}

/**
 * Inserta el año y las compras de ejemplo. Devuelve el año creado.
 * $weekly y la moneda se toman de la configuración ya guardada.
 */
function load_demo_data(int $count = 13): int {
    $day    = purchase_day();
    $rate   = currency_code() === 'USD' ? 1.0 : (float)get_setting('fallback_usd_local', '17.5');
    // Un monto semanal creíble en cualquier moneda: unos 60 dólares, redondeado
    // a una cifra que se vea escrita a mano y no calculada.
    $target = 60.0 * ($rate > 0 ? $rate : 1);
    $mag    = pow(10, max(0, (int)floor(log10(max($target, 1))) - 1));
    $weekly = max(1.0, round($target / $mag) * $mag);

    // Última fecha de compra: el día elegido de esta semana. Hacia atrás, semanal.
    $names = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday'];
    $last  = new DateTime($names[$day] . ' this week');
    $first = (clone $last)->modify('-' . ($count - 1) . ' weeks');
    $year  = (int)$first->format('Y');

    $st = db()->prepare('INSERT INTO years (year, weekly_amount, planned_weeks, notes) VALUES (?,?,?,?)
                         ON DUPLICATE KEY UPDATE weekly_amount = VALUES(weekly_amount), notes = VALUES(notes)');
    $st->execute([$year, $weekly, 52, 'Datos de ejemplo — bórralos cuando registres tu primera compra real.']);
    $yearId = (int)get_year($year, true)['id'];

    // Si el año ya traía compras, no las pisamos.
    $has = db()->prepare('SELECT COUNT(*) FROM purchases WHERE year_id = ?');
    $has->execute([$yearId]);
    if ((int)$has->fetchColumn() > 0) return $year;

    $prices = demo_price_walk($count);
    $ins = db()->prepare('INSERT INTO purchases (year_id, num, fecha, monto_local, comision_pct, precio_local_btc, tipo_cambio_usd, sats, notas)
                          VALUES (?,?,?,?,?,?,?,?,?)');
    $d = clone $first;
    for ($i = 0; $i < $count; $i++) {
        $precioLocal = round($prices[$i] * $rate, 2);
        $neto = $weekly * (1 - 0.01);
        $sats = (int)floor($neto / $precioLocal * SATS_PER_BTC);
        $ins->execute([$yearId, $i + 1, $d->format('Y-m-d'), $weekly, 0.0100, $precioLocal, $rate, $sats, 'Ejemplo']);
        $d->modify('+1 week');
    }

    set_settings(['demo_year' => (string)$year]);
    return $year;
}

function has_demo_data(): bool {
    return get_setting('demo_year', '') !== '';
}

/** Borra el año de ejemplo completo (las compras se van por la llave foránea). */
function delete_demo_data(): void {
    $y = (int)get_setting('demo_year', '0');
    if ($y > 0) {
        $st = db()->prepare('DELETE FROM years WHERE year = ?');
        $st->execute([$y]);
    }
    $st = db()->prepare("DELETE FROM settings WHERE skey = 'demo_year'");
    $st->execute();
    settings_all(true);
}
