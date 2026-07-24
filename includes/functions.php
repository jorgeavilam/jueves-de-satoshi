<?php
require_once __DIR__ . '/db.php';

const SATS_PER_BTC = 100000000;

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function fmt_money(float $n, int $dec = 2): string { return number_format($n, $dec); }
function fmt_int(float $n): string { return number_format($n, 0); }
function fmt_pct(float $n): string { return ($n >= 0 ? '+' : '') . number_format($n * 100, 2) . '%'; }

/** Fecha corta sin año, para los ejes de las gráficas (ej. "19 mar"). */
function fmt_fecha_corta(string $date): string {
    $meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($date);
    return date('j', $t) . ' ' . $meses[(int)date('n', $t)];
}

function fmt_fecha(string $date): string {
    $meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($date);
    return date('j', $t) . ' ' . $meses[(int)date('n', $t)] . ' ' . date('Y', $t);
}

/* ---------- Settings ---------- */
function get_setting(string $key, string $default = ''): string {
    $st = db()->prepare('SELECT svalue FROM settings WHERE skey = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

function set_setting(string $key, string $value): void {
    $st = db()->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
    $st->execute([$key, $value]);
}

/* ---------- Precio en vivo (CoinGecko con caché) ---------- */
function live_prices(): array {
    $cached = get_setting('price_cache');
    if ($cached) {
        $c = json_decode($cached, true);
        if ($c && (time() - ($c['ts'] ?? 0)) < PRICE_CACHE_MINUTES * 60) {
            return $c;
        }
    }
    $data = null;
    $ctx = stream_context_create(['http' => [
        'timeout' => 6,
        'header' => "User-Agent: JuevesDeSatoshi/1.0 (+" . SITE_URL . ")\r\nAccept: application/json\r\n",
    ]]);
    $json = @file_get_contents('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd,mxn', false, $ctx);
    if ($json) {
        $r = json_decode($json, true);
        if (isset($r['bitcoin']['usd'], $r['bitcoin']['mxn'])) {
            $data = [
                'btc_usd' => (float)$r['bitcoin']['usd'],
                'btc_mxn' => (float)$r['bitcoin']['mxn'],
                'usd_mxn' => (float)$r['bitcoin']['mxn'] / (float)$r['bitcoin']['usd'],
                'ts' => time(),
                'live' => true,
            ];
            set_setting('price_cache', json_encode($data));
        }
    }
    if (!$data) {
        // Respaldo: último caché aunque esté vencido, o valores manuales
        if ($cached && ($c = json_decode($cached, true))) { $c['live'] = false; return $c; }
        $btc_usd = (float)get_setting('fallback_btc_usd', '60000');
        $usd_mxn = (float)get_setting('fallback_usd_mxn', '17.5');
        $data = ['btc_usd' => $btc_usd, 'btc_mxn' => $btc_usd * $usd_mxn, 'usd_mxn' => $usd_mxn, 'ts' => time(), 'live' => false];
    }
    return $data;
}

/* ---------- Datos ---------- */
function get_years(bool $includeDeleted = false): array {
    $where = $includeDeleted ? '' : 'WHERE deleted = 0';
    return db()->query("SELECT * FROM years $where ORDER BY year ASC")->fetchAll();
}

function get_year(int $year, bool $includeDeleted = false): ?array {
    $sql = 'SELECT * FROM years WHERE year = ?' . ($includeDeleted ? '' : ' AND deleted = 0');
    $st = db()->prepare($sql);
    $st->execute([$year]);
    return $st->fetch() ?: null;
}

/**
 * Compras de un año con columnas derivadas y acumulados.
 * Regla de negocio: se registra el TIPO DE CAMBIO y el monto USD se CALCULA.
 * Corrección clave: valor_acum_mxn usa el precio histórico de BTC de CADA fecha,
 * no el precio actual — así la gráfica refleja la fluctuación real del valor.
 */
function get_purchases(int $yearId): array {
    $st = db()->prepare('SELECT * FROM purchases WHERE year_id = ? ORDER BY num ASC');
    $st->execute([$yearId]);
    $rows = $st->fetchAll();

    $accSats = 0; $accMxn = 0.0;
    foreach ($rows as &$r) {
        $r['comision_mxn']  = $r['monto_mxn'] * $r['comision_pct'];
        $r['monto_neto']    = $r['monto_mxn'] - $r['comision_mxn'];
        $r['precio_usd_btc'] = $r['precio_mxn_btc'] / $r['tipo_cambio'];
        $r['monto_usd']     = $r['monto_mxn'] / $r['tipo_cambio']; // calculado, no capturado
        $accSats += (int)$r['sats'];
        $accMxn  += (float)$r['monto_mxn'];
        $r['sats_acum'] = $accSats;
        $r['inversion_acum_mxn'] = $accMxn;
        // Valor de la posición ESE día, al precio de BTC de ESE día:
        $r['valor_acum_mxn'] = $accSats / SATS_PER_BTC * $r['precio_mxn_btc'];
        $r['valor_acum_usd'] = $r['valor_acum_mxn'] / $r['tipo_cambio'];
    }
    return $rows;
}

/**
 * Jueves planeados del año, calculados automáticamente:
 * desde la fecha de la PRIMERA compra del año hasta el 31 de diciembre.
 * Si el año aún no tiene compras, se usa el valor manual planned_weeks.
 */
function planned_thursdays(array $yearRow, array $purchases): int {
    if (!count($purchases)) return (int)$yearRow['planned_weeks'];
    $d = new DateTime($purchases[0]['fecha']);
    $end = new DateTime($yearRow['year'] . '-12-31');
    $c = 0;
    while ($d <= $end) {
        if ((int)$d->format('N') === 4) $c++;
        $d->modify('+1 day');
    }
    return max($c, count($purchases));
}

/** Resumen de un año (con valoración a precio actual). */
function year_summary(array $purchases, array $prices): array {
    $n = count($purchases);
    $sats = $n ? end($purchases)['sats_acum'] : 0;
    $inv  = $n ? end($purchases)['inversion_acum_mxn'] : 0.0;
    $com  = array_sum(array_column($purchases, 'comision_mxn'));
    $invUsd = array_sum(array_column($purchases, 'monto_usd'));
    $valMxn = $sats / SATS_PER_BTC * $prices['btc_mxn'];
    $valUsd = $sats / SATS_PER_BTC * $prices['btc_usd'];
    return [
        'compras' => $n,
        'sats' => $sats,
        'btc' => $sats / SATS_PER_BTC,
        'invertido_mxn' => $inv,
        'invertido_usd' => $invUsd,
        'comisiones_mxn' => $com,
        'valor_mxn' => $valMxn,
        'valor_usd' => $valUsd,
        'pnl_mxn' => $valMxn - $inv,
        'pnl_pct' => $inv > 0 ? ($valMxn - $inv) / $inv : 0,
        'sats_promedio' => $n ? $sats / $n : 0,
        'costo_por_sat' => $sats > 0 ? $inv / $sats : 0,
    ];
}

/* ---------- Tracking snippets ---------- */
function tracking_head(): string {
    $out = '';
    if (GTM_ID) {
        $out .= "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . GTM_ID . "');</script>\n";
    }
    if (GA4_ID) {
        $out .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . GA4_ID . '"></script>' .
                "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','" . GA4_ID . "');</script>\n";
    }
    if (META_PIXEL_ID) {
        $out .= "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','" . META_PIXEL_ID . "');fbq('track','PageView');</script>\n";
    }
    return $out;
}

function tracking_body(): string {
    if (GTM_ID) {
        return '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . GTM_ID . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
    }
    return '';
}
