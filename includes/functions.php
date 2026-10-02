<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/currencies.php';
require_once __DIR__ . '/brandkit.php';
require_once __DIR__ . '/modules.php';
require_once __DIR__ . '/tools.php';
require_once __DIR__ . '/coaster.php';

/**
 * Defaults de config.php.
 *
 * Una instalación v1 que se actualiza trae un config.php que no conoce las
 * constantes nuevas. En lugar de reventar, se rellenan aquí con lo razonable.
 */
if (!defined('HUB_URL'))             define('HUB_URL', 'https://satoshi.jorgeavila.com');
if (!defined('PRICE_CACHE_MINUTES')) define('PRICE_CACHE_MINUTES', 10);
if (!defined('CONTACT_EMAIL'))       define('CONTACT_EMAIL', '');
if (!defined('CONTACT_FROM'))        define('CONTACT_FROM', 'no-reply@' . (parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost'));
if (!defined('GA4_ID'))              define('GA4_ID', '');
if (!defined('GTM_ID'))              define('GTM_ID', '');
if (!defined('META_PIXEL_ID'))       define('META_PIXEL_ID', '');
if (!defined('RECAPTCHA_SITE_KEY'))  define('RECAPTCHA_SITE_KEY', '');
if (!defined('RECAPTCHA_SECRET'))    define('RECAPTCHA_SECRET', '');

const JDS_VERSION  = '2.4.1';
const SATS_PER_BTC = 100000000;

/** Naranja Bitcoin: el acento del sitio maestro. Sirve de referencia, no de default. */
const HUB_ACCENT  = '#F7931A';

/** Datos del proyecto original. La atribución del badge se apoya en esto. */
const HUB_PROJECT = 'Jueves de Satoshi';
const HUB_AUTHOR  = 'Jorge Avila Meléndez';
const HUB_REPO    = 'https://github.com/jorgeavilam/jueves-de-satoshi';

/* ---------- Números, dinero y fechas (según idioma y moneda) ---------- */

function fmt_num(float $n, int $dec = 0): string {
    return number_format($n, $dec, t('dec_sep'), t('thou_sep'));
}

function fmt_int(float $n): string { return fmt_num($n, 0); }

function fmt_pct(float $n, int $dec = 2): string {
    return ($n >= 0 ? '+' : '−') . fmt_num(abs($n) * 100, $dec) . '%';
}

/** Monto en la moneda del sitio: "$1,000". */
function money(float $n, int $dec = 0): string {
    return currency_symbol() . fmt_num($n, $dec);
}

/** Monto con código de moneda: "$1,000 MXN". */
function money_full(float $n, int $dec = 0): string {
    return money($n, $dec) . ' ' . currency_code();
}

function money_usd(float $n, int $dec = 2): string {
    return '$' . fmt_num($n, $dec) . ' USD';
}

/** Fecha corta sin año, para los ejes de las gráficas ("19 mar"). */
function fmt_fecha_corta(string $date): string {
    $m = tr('months_short');
    $ts = strtotime($date);
    return date('j', $ts) . ' ' . $m[(int)date('n', $ts)];
}

function fmt_fecha(string $date): string {
    return fmt_fecha_corta($date) . ' ' . date('Y', strtotime($date));
}

/** Mes y año, sin día: para decir «desde mar 2026». */
function fmt_fecha_corta_anio(string $date): string {
    $m = tr('months_short');
    $ts = strtotime($date);
    return $m[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/* ---------- Settings ---------- */

/** Todas las llaves en una sola consulta: el layout lee una docena por página. */
function settings_all(bool $reload = false): array {
    static $cache = null;
    if ($cache === null || $reload) {
        $cache = [];
        try {
            foreach (db()->query('SELECT skey, svalue FROM settings') as $r) {
                $cache[$r['skey']] = $r['svalue'];
            }
        } catch (Throwable $ex) {
            $cache = [];
        }
    }
    return $cache;
}

function get_setting(string $key, string $default = ''): string {
    $all = settings_all();
    return array_key_exists($key, $all) && $all[$key] !== '' ? $all[$key] : $default;
}

/** Igual que get_setting pero devuelve la cadena vacía si así está guardada. */
function get_setting_raw(string $key, string $default = ''): string {
    $all = settings_all();
    return array_key_exists($key, $all) ? $all[$key] : $default;
}

function set_setting(string $key, string $value): void {
    $st = db()->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
    $st->execute([$key, $value]);
    settings_all(true);
}

function set_settings(array $pairs): void {
    $st = db()->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
    foreach ($pairs as $k => $v) $st->execute([$k, (string)$v]);
    settings_all(true);
}

/* ---------- Contenido editable ---------- */

function content_all(bool $reload = false): array {
    static $cache = null;
    if ($cache === null || $reload) {
        $cache = [];
        try {
            foreach (db()->query('SELECT ckey, cvalue, is_default FROM content') as $r) {
                $cache[$r['ckey']] = $r;
            }
        } catch (Throwable $ex) {
            $cache = [];
        }
    }
    return $cache;
}

/** ¿El dueño ya escribió su propia versión de este bloque? */
function content_is_custom(string $key): bool {
    $row = content_all()[$key] ?? null;
    return $row && (int)$row['is_default'] === 0 && trim((string)$row['cvalue']) !== '';
}

/**
 * Texto de un bloque. Si sigue en su versión estándar se devuelve la plantilla
 * del idioma activo (así el default siempre habla el idioma del sitio);
 * si el dueño lo personalizó, se devuelve su texto tal cual.
 */
function content(string $key, ...$args): string {
    if (content_is_custom($key)) return content_all()[$key]['cvalue'];
    // Las plantillas se imprimen como HTML y los argumentos son datos del dueño
    // (el nombre del sitio, por ejemplo): se escapan antes de interpolarlos.
    $safe = array_map(fn($a) => is_string($a) ? e($a) : $a, $args);
    return t('tpl_' . $key, ...$safe);
}

function set_content(string $key, string $value, bool $isDefault = false): void {
    $st = db()->prepare('INSERT INTO content (ckey, cvalue, is_default) VALUES (?,?,?)
                         ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue), is_default = VALUES(is_default)');
    $st->execute([$key, $value, $isDefault ? 1 : 0]);
    content_all(true);
}

function content_keys(): array {
    return ['hero_title', 'hero_lead', 'home_about', 'ejercicio_title', 'ejercicio_body',
            'contacto_intro', 'herramientas_intro', 'vault_message'];
}

/**
 * Limpia el HTML que escribe el dueño: deja el formato de texto y quita
 * cualquier cosa ejecutable. No es un editor rico, es un campo de texto honesto.
 */
function clean_html(string $html): string {
    $html = strip_tags($html, '<p><br><strong><b><em><i><a><span><ul><ol><li><h2><h3><blockquote>');
    $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/(href\s*=\s*["\']?)\s*(javascript|data|vbscript):/i', '$1#', $html);
    return trim($html);
}

/* ---------- Identidad y marca ---------- */

function owner_name(): string  { return get_setting('owner_name', ''); }
function site_name(): string   { return get_setting('site_name', ''); }
function purchase_day(): int   { $d = (int)get_setting('purchase_day', '4'); return ($d >= 1 && $d <= 7) ? $d : 4; }
function accent_color(): string {
    $c = get_setting('accent_color', HUB_ACCENT);
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $c) ? $c : HUB_ACCENT;
}
function skin(): string { $s = get_setting('skin', 'classic'); return in_array($s, skins(), true) ? $s : 'classic'; }
/** Fondo del sitio: '' en el maestro (usa el original); en un nodo, uno del catálogo. */
function bg_tone(): string {
    if (is_hub()) return '';
    $b = get_setting('bg_tone', 'arena');
    return isset(bg_catalog()[$b]) ? $b : 'arena';
}
function font_pair(): string { $f = get_setting('font_pair', 'system'); return in_array($f, font_pairs(), true) ? $f : 'system'; }
function hero_vehicle(): string {
    $v = get_setting('hero_vehicle', 'coin');
    return in_array($v, coaster_vehicles(), true) ? $v : 'coin';
}

/** Redes sociales del dueño. Solo las que tienen URL se muestran. */
function social_networks(): array {
    return [
        'x'         => ['label' => 'X',        'glyph' => '𝕏'],
        'linkedin'  => ['label' => 'LinkedIn', 'glyph' => 'in'],
        'instagram' => ['label' => 'Instagram','glyph' => 'IG'],
        'facebook'  => ['label' => 'Facebook', 'glyph' => 'f'],
        'youtube'   => ['label' => 'YouTube',  'glyph' => '▶'],
        'github'    => ['label' => 'GitHub',   'glyph' => 'GH'],
        'nostr'     => ['label' => 'Nostr',    'glyph' => '⚡'],
    ];
}

function owner_socials(): array {
    $out = [];
    foreach (social_networks() as $key => $meta) {
        $url = get_setting('social_' . $key, '');
        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            $out[$key] = $meta + ['url' => $url];
        }
    }
    return $out;
}

function owner_avatar_url(): string {
    $f = get_setting('owner_avatar', '');
    if ($f !== '' && is_readable(__DIR__ . '/../assets/img/' . $f)) {
        return SITE_URL . '/assets/img/' . rawurlencode($f);
    }
    return SITE_URL . '/assets/img/avatar-placeholder.svg';
}

function has_own_avatar(): bool {
    $f = get_setting('owner_avatar', '');
    return $f !== '' && is_readable(__DIR__ . '/../assets/img/' . $f);
}

/** Iniciales para el monograma: dos letras a partir del nombre del sitio. */
function brand_initials(): string {
    $src = site_name() ?: owner_name() ?: 'JS';
    $words = preg_split('/\s+/', trim($src)) ?: [];
    $words = array_values(array_filter($words, fn($w) => mb_strlen($w) > 2 || count($words) < 2));
    $a = mb_substr($words[0] ?? 'J', 0, 1);
    $b = mb_substr($words[count($words) - 1] ?? 'S', 0, 1);
    if (count($words) < 2) $b = mb_substr($words[0] ?? 'JS', 1, 1) ?: '';
    return mb_strtoupper($a . $b);
}

/** Monograma SVG: el logo por default de cualquier instalación nueva. */
function monogram_svg(int $size = 38): string {
    $ini = e(brand_initials());
    $acc = e(accent_color());
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="' . $size . '" height="' . $size . '" role="img">'
         . '<rect width="64" height="64" rx="14" fill="' . $acc . '"/>'
         . '<text x="32" y="43" text-anchor="middle" font-family="Helvetica,Arial,sans-serif" font-size="30" font-weight="700" fill="#fff">' . $ini . '</text>'
         . '</svg>';
}

function logo_url(): string {
    if (get_setting('logo_mode', 'mono') === 'upload') {
        $f = get_setting('logo_file', '');
        if ($f !== '' && is_readable(__DIR__ . '/../assets/img/' . $f)) {
            return SITE_URL . '/assets/img/' . rawurlencode($f);
        }
    }
    return SITE_URL . '/logo.php';
}

/* ---------- Color ---------- */

function hex_to_rgb(string $hex): array {
    $hex = ltrim($hex, '#');
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

/** Aclara ($amt > 0) u oscurece ($amt < 0) un color. Para derivar el hover del acento. */
function color_shade(string $hex, float $amt): string {
    [$r, $g, $b] = hex_to_rgb($hex);
    $f = fn($c) => (int)max(0, min(255, $amt >= 0 ? $c + (255 - $c) * $amt : $c * (1 + $amt)));
    return sprintf('#%02X%02X%02X', $f($r), $f($g), $f($b));
}

/** Luminancia relativa: decide si el texto sobre el acento va blanco o negro. */
function is_light_color(string $hex): bool {
    [$r, $g, $b] = hex_to_rgb($hex);
    return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) > 150;
}

/* ---------- Estado de la instalación ---------- */

function is_installed(): bool { return get_setting('installed', '0') === '1'; }

/**
 * Un sitio "listo" tiene dueño y nombre. Mientras no los tenga, las páginas
 * públicas no publican nada: el default de una instalación a medias es el
 * silencio, no los datos de otra persona.
 */
function site_ready(): bool {
    return is_installed() && owner_name() !== '' && site_name() !== '';
}

/* ---------- Privacidad ---------- */

function privacy_modes(): array { return ['full', 'percent', 'vault']; }
function privacy_mode(): string {
    $m = get_setting('privacy_mode', 'full');
    return in_array($m, privacy_modes(), true) ? $m : 'full';
}
/**
 * ¿Quien mira es el dueño con sesión abierta?
 *
 * Solo se pregunta en modo bóveda: abrir sesión en cada visita pública costaría
 * una cookie y una escritura de disco por página, y no hace falta. Si no hay
 * cookie de sesión, ni se intenta.
 */
function viewer_is_owner(): bool {
    static $is = null;
    if ($is !== null) return $is;
    if (session_status() === PHP_SESSION_ACTIVE) return $is = !empty($_SESSION['jds_admin']);
    if (empty($_COOKIE[session_name()])) return $is = false;
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    @session_start();
    return $is = !empty($_SESSION['jds_admin']);
}

/** ¿Se pueden mostrar montos, sats y precios? */
function show_amounts(): bool {
    $m = privacy_mode();
    if ($m === 'full')  return true;
    if ($m === 'vault') return viewer_is_owner(); // el dueño sí ve su portafolio
    return false;
}

/** ¿Se puede mostrar algo del ejercicio? */
function show_progress(): bool {
    return privacy_mode() !== 'vault' || viewer_is_owner();
}

/* ---------- Precio en vivo ---------- */

function live_prices(): array {
    $code = currency_code();
    $info = currency_info($code);
    $cached = get_setting('price_cache');

    if ($cached && ($c = json_decode($cached, true)) && ($c['cur'] ?? '') === $code) {
        if ((time() - ($c['ts'] ?? 0)) < PRICE_CACHE_MINUTES * 60) return $c;
    }

    $vs = 'usd' . ($info['cg'] && $code !== 'USD' ? ',' . strtolower($code) : '');
    $ctx = stream_context_create(['http' => [
        'timeout' => 6,
        'header'  => "User-Agent: JuevesDeSatoshi/" . JDS_VERSION . " (+" . SITE_URL . ")\r\nAccept: application/json\r\n",
    ]]);
    $json = @file_get_contents('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=' . $vs, false, $ctx);

    $data = null;
    if ($json) {
        $r = json_decode($json, true);
        $usd = $r['bitcoin']['usd'] ?? null;
        if ($usd) {
            if ($code === 'USD') {
                $local = (float)$usd; $rate = 1.0;
            } elseif ($info['cg'] && isset($r['bitcoin'][strtolower($code)])) {
                $local = (float)$r['bitcoin'][strtolower($code)];
                $rate  = $local / (float)$usd;
            } else {
                // CoinGecko no cotiza esta moneda: se usa el TC de respaldo del panel.
                $rate  = (float)get_setting('fallback_usd_local', '1');
                $local = (float)$usd * $rate;
            }
            $data = ['btc_usd' => (float)$usd, 'btc_local' => $local, 'usd_local' => $rate,
                     'cur' => $code, 'ts' => time(), 'live' => true];
            set_setting('price_cache', json_encode($data));
        }
    }

    if (!$data) {
        if ($cached && ($c = json_decode($cached, true)) && ($c['cur'] ?? '') === $code) {
            $c['live'] = false;
            return $c;
        }
        $usd  = (float)get_setting('fallback_btc_usd', '60000');
        $rate = $code === 'USD' ? 1.0 : (float)get_setting('fallback_usd_local', '17.5');
        $data = ['btc_usd' => $usd, 'btc_local' => $usd * $rate, 'usd_local' => $rate,
                 'cur' => $code, 'ts' => time(), 'live' => false];
    }
    return $data;
}

/* ---------- Correo ---------- */

/** reCAPTCHA v3. Si el sitio no tiene clave secreta, no hay nada que verificar. */
function recaptcha_ok(string $token): bool {
    if (!defined('RECAPTCHA_SECRET') || RECAPTCHA_SECRET === '') return true;
    $resp = @file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='
        . urlencode(RECAPTCHA_SECRET) . '&response=' . urlencode($token));
    if (!$resp) return false;
    $r = json_decode($resp, true);
    return !empty($r['success']) && ($r['score'] ?? 0) >= 0.5;
}

/**
 * Correo al dueño del sitio (CONTACT_EMAIL). Lo usan el formulario de contacto
 * y los formularios de los módulos. El asunto se limpia de saltos de línea
 * —inyección de cabeceras— y el Reply-To solo va si es un correo válido.
 */
function contact_send(string $subject, string $body, string $replyTo = ''): bool {
    if (CONTACT_EMAIL === '') return false;
    $subject = trim(str_replace(["\r", "\n"], ' ', $subject));
    $headers = 'From: ' . mb_encode_mimeheader(site_name()) . ' <' . CONTACT_FROM . ">\r\n"
             . (filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? 'Reply-To: ' . $replyTo . "\r\n" : '')
             . "Content-Type: text/plain; charset=UTF-8\r\n";
    return @mail(CONTACT_EMAIL, mb_encode_mimeheader($subject), $body, $headers);
}

/* ---------- URLs ---------- */

/**
 * ¿Están activas las URLs limpias (/2026)?
 *
 * Las enciende la regla del .htaccess con [E=JDS_CLEAN_URLS:1]; después de un
 * rewrite, Apache le antepone REDIRECT_ al nombre. Sin la regla —un .htaccess
 * que no se actualizó, o `php -S` en local— el sitio sigue con year.php?y=.
 */
function clean_urls(): bool {
    return !empty($_SERVER['JDS_CLEAN_URLS']) || !empty($_SERVER['REDIRECT_JDS_CLEAN_URLS']);
}

function year_url(int $year): string {
    return clean_urls() ? SITE_URL . '/' . $year : SITE_URL . '/year.php?y=' . $year;
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
 *
 * Regla de negocio: se registra el TIPO DE CAMBIO y el monto en USD se CALCULA.
 * Y valor_acum usa el precio de BTC de CADA fecha, no el precio actual, para que
 * la gráfica refleje la fluctuación real del recorrido.
 */
function get_purchases(int $yearId): array {
    $st = db()->prepare('SELECT * FROM purchases WHERE year_id = ? ORDER BY num ASC');
    $st->execute([$yearId]);
    $rows = $st->fetchAll();

    $accSats = 0; $accMoney = 0.0; $accUsd = 0.0;
    foreach ($rows as &$r) {
        $tc = (float)$r['tipo_cambio_usd'] ?: 1.0;
        $r['comision_monto'] = (float)$r['monto_local'] * (float)$r['comision_pct'];
        $r['monto_neto']     = (float)$r['monto_local'] - $r['comision_monto'];
        $r['precio_usd_btc'] = (float)$r['precio_local_btc'] / $tc;
        $r['monto_usd']      = (float)$r['monto_local'] / $tc; // calculado, no capturado
        $accSats  += (int)$r['sats'];
        $accMoney += (float)$r['monto_local'];
        $r['sats_acum']      = $accSats;
        $r['inversion_acum'] = $accMoney;
        $r['valor_acum']     = $accSats / SATS_PER_BTC * (float)$r['precio_local_btc'];
        $r['valor_acum_usd'] = $r['valor_acum'] / $tc;
        $accUsd += $r['monto_usd'];
        $r['inversion_acum_usd'] = $accUsd;
        $fx = fx_breakdown($accMoney, $accUsd, $r['valor_acum_usd'], $tc);
        $r['efecto_btc_acum'] = $fx['btc'];
        $r['efecto_fx_acum']  = $fx['fx'];
    }
    return $rows;
}

/**
 * Separa la ganancia en moneda local en lo que puso Bitcoin y lo que puso la moneda.
 *
 *   efecto Bitcoin       = (valor USD − costo USD) × TC de hoy
 *   efecto tipo de cambio = costo USD × (TC de hoy − TC promedio ponderado)
 *
 * El TC promedio ponderado es costo local / costo USD: lo que de verdad costó
 * cada dólar invertido. Las dos partes suman exacto la ganancia local, sin
 * residuo. Las comisiones quedan dentro del efecto Bitcoin: compraron menos sats.
 */
function fx_breakdown(float $costLocal, float $costUsd, float $valueUsd, float $fxNow): array {
    return [
        'btc'      => ($valueUsd - $costUsd) * $fxNow,
        'fx'       => $costUsd * $fxNow - $costLocal,
        'fx_avg'   => $costUsd > 0 ? $costLocal / $costUsd : 0.0,
    ];
}

/**
 * Compras planeadas del año: desde la fecha de la PRIMERA compra hasta el
 * 31 de diciembre, contando los días de compra del sitio (lunes, jueves...).
 * Si el año aún no tiene compras se usa el valor manual planned_weeks.
 */
function planned_purchases(array $yearRow, array $purchases): int {
    if (!count($purchases)) return (int)$yearRow['planned_weeks'];
    $day = purchase_day();
    $d   = new DateTime($purchases[0]['fecha']);
    $end = new DateTime($yearRow['year'] . '-12-31');
    $c = 0;
    while ($d <= $end) {
        if ((int)$d->format('N') === $day) $c++;
        $d->modify('+1 day');
    }
    return max($c, count($purchases));
}

/** Compras consecutivas al final del histórico, sin semanas saltadas. */
function purchase_streak(array $purchases): int {
    $n = count($purchases);
    if ($n === 0) return 0;
    $streak = 1;
    for ($i = $n - 1; $i > 0; $i--) {
        $a = strtotime($purchases[$i]['fecha']);
        $b = strtotime($purchases[$i - 1]['fecha']);
        $days = (int)round(($a - $b) / 86400);
        if ($days >= 6 && $days <= 8) $streak++;
        else break;
    }
    return $streak;
}

function year_summary(array $purchases, array $prices): array {
    $n = count($purchases);
    $sats = $n ? end($purchases)['sats_acum'] : 0;
    $inv  = $n ? end($purchases)['inversion_acum'] : 0.0;
    $val  = $sats / SATS_PER_BTC * $prices['btc_local'];
    $invUsd = array_sum(array_column($purchases, 'monto_usd'));
    $fx   = fx_breakdown($inv, $invUsd, $sats / SATS_PER_BTC * $prices['btc_usd'], (float)$prices['usd_local']);
    return [
        'compras'       => $n,
        'sats'          => $sats,
        'btc'           => $sats / SATS_PER_BTC,
        'invertido'     => $inv,
        'invertido_usd' => $invUsd,
        'comisiones'    => array_sum(array_column($purchases, 'comision_monto')),
        'valor'         => $val,
        'valor_usd'     => $sats / SATS_PER_BTC * $prices['btc_usd'],
        'pnl'           => $val - $inv,
        'pnl_pct'       => $inv > 0 ? ($val - $inv) / $inv : 0,
        'sats_promedio' => $n ? $sats / $n : 0,
        'costo_por_sat' => $sats > 0 ? $inv / $sats : 0,
        'streak'        => purchase_streak($purchases),
        'efecto_btc'    => $fx['btc'],
        'efecto_fx'     => $fx['fx'],
        'tc_promedio'   => $fx['fx_avg'],
        'tc_hoy'        => (float)$prices['usd_local'],
    ];
}

/**
 * El año en una frase: la meta description, el párrafo visible, el JSON-LD y
 * llms.txt dicen lo mismo. Es lo que un buscador o un LLM cita, así que lleva
 * números y no adjetivos. Quien llama decide $amounts según la privacidad.
 */
function year_summary_text(int $year, array $ys, int $planned, float $weekly, bool $amounts, bool $withFx = true): string {
    $day = day_name(purchase_day());
    if (!$amounts) {
        return t('ysum_pct', (string)$year, owner_name(), $ys['compras'], $planned, $day, $ys['streak']);
    }
    $s = t('ysum_amounts', (string)$year, owner_name(), $ys['compras'], $planned, money_full($weekly), $day,
           fmt_int($ys['sats']), fmt_num($ys['btc'], 8), money_full($ys['invertido']), money_full($ys['valor']),
           fmt_pct($ys['pnl_pct']));
    if ($withFx && !is_usd_site() && $ys['invertido'] > 0) {
        $s .= ' ' . t('ysum_fx', fmt_pct($ys['efecto_btc'] / $ys['invertido']), fmt_pct($ys['efecto_fx'] / $ys['invertido']));
    }
    return $s;
}

/**
 * El recorrido completo: lo que site_summary_text() necesita, más los precios
 * de todas las compras en orden para dibujar el riel.
 */
function site_totals(array $prices): array {
    $g = ['compras' => 0, 'planned' => 0, 'sats' => 0, 'inv' => 0.0, 'val' => 0.0, 'streak' => 0, 'first' => '', 'prices' => [], 'years' => []];
    $all = [];
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
        $g['years'][] = ['row' => $yr, 'sum' => $s, 'planned' => $planned];
        $all = array_merge($all, $p);
    }
    if ($all) {
        usort($all, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
        $g['first']  = $all[0]['fecha'];
        $g['streak'] = purchase_streak($all);
        $g['prices'] = array_map(fn($p) => (float)$p['precio_local_btc'], $all);
    }
    return $g;
}

/**
 * Todo el recorrido en una frase, para la portada y llms.txt.
 * $g: compras, planned, sats, inv, val, streak y first (fecha de la primera compra).
 */
function site_summary_text(array $g, bool $amounts): string {
    if (empty($g['compras'])) return '';
    $since = fmt_fecha_corta_anio($g['first']);
    if (!$amounts) {
        return t('hsum_pct', owner_name(), $g['compras'], $g['planned'], day_name(purchase_day()), $since, $g['streak']);
    }
    $pct = $g['inv'] > 0 ? ($g['val'] - $g['inv']) / $g['inv'] : 0;
    return t('hsum_amounts', owner_name(), $g['compras'], day_name(purchase_day()), $since,
             fmt_int($g['sats']), money_full($g['inv']), money_full($g['val']), fmt_pct($pct));
}

/** Próxima fecha de compra sugerida en el admin. */
function next_purchase_date(?string $lastDate = null): string {
    if ($lastDate) return date('Y-m-d', strtotime($lastDate . ' +7 days'));
    $names = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday'];
    return date('Y-m-d', strtotime($names[purchase_day()] . ' this week'));
}

/* ---------- La red ---------- */

/**
 * Identificador de esta instalación frente al maestro. Se genera una vez y
 * sirve para consultar el estado del registro sin que nadie pueda espiar el de
 * otro sitio.
 */
function network_token(): string {
    $t = get_setting('node_token', '');
    if ($t === '') {
        $t = bin2hex(random_bytes(16));
        set_setting('node_token', $t);
    }
    return $t;
}

/** Lo único que se le manda al maestro. Ni montos, ni sats, ni correo. */
function network_payload(): array {
    return [
        'url'      => SITE_URL,
        'name'     => site_name(),
        'owner'    => owner_name(),
        'locale'   => current_locale(),
        'currency' => currency_code(),
        'version'  => JDS_VERSION,
        'token'    => network_token(),
    ];
}

/** Pide lugar en el directorio del maestro. Devuelve [ok, estado o error]. */
function network_request(): array {
    if (!site_ready()) return [false, 'not_ready'];
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'timeout'       => 8,
        'ignore_errors' => true,
        'header'        => "Content-Type: application/x-www-form-urlencoded\r\n"
                         . "User-Agent: JuevesDeSatoshi/" . JDS_VERSION . " (node)\r\n",
        'content'       => http_build_query(network_payload()),
    ]]);
    $res = @file_get_contents(HUB_URL . '/registro.php', false, $ctx);
    if ($res === false) return [false, 'unreachable'];
    $d = json_decode($res, true);
    if (!is_array($d) || empty($d['ok'])) return [false, (string)($d['error'] ?? 'rejected')];
    set_setting('network_requested', date('c'));
    return [true, (string)($d['status'] ?? 'pending')];
}

/** Consulta al maestro en qué va la solicitud. */
function network_status(): array {
    if (get_setting('network_requested', '') === '') return ['ok' => true, 'status' => 'none'];
    $ctx = stream_context_create(['http' => [
        'timeout'       => 6,
        'ignore_errors' => true,
        'header'        => "User-Agent: JuevesDeSatoshi/" . JDS_VERSION . " (node)\r\n",
    ]]);
    $q = http_build_query(['estado' => 1, 'url' => SITE_URL, 'token' => network_token()]);
    $res = @file_get_contents(HUB_URL . '/registro.php?' . $q, false, $ctx);
    $d = $res ? json_decode($res, true) : null;
    return is_array($d) && !empty($d['ok']) ? $d : ['ok' => false, 'status' => 'unreachable'];
}

/* ---------- Índice de unicidad ---------- */

/**
 * Diez puntos que separan una instalación de la plantilla. No es un adorno:
 * es la deuda de personalización, visible cada vez que el dueño entra al panel.
 */
function uniqueness(): array {
    // El maestro no se mide contra sí mismo: su nombre y su acento son el original.
    $hub = is_hub();
    $items = [
        ['key' => 'name',    'ok' => $hub || (site_name() !== '' && mb_stripos(site_name(), HUB_PROJECT) === false), 'go' => 'marca.php'],
        ['key' => 'accent',  'ok' => $hub || strtoupper(accent_color()) !== strtoupper(HUB_ACCENT),                  'go' => 'marca.php'],
        ['key' => 'skin',    'ok' => get_setting('skin_chosen', '0') === '1',                                       'go' => 'marca.php'],
        ['key' => 'logo',    'ok' => get_setting('logo_mode', 'mono') === 'upload' || brand_initials() !== 'JS',    'go' => 'marca.php'],
        ['key' => 'hero',    'ok' => get_setting('hero_chosen', '0') === '1',                                       'go' => 'marca.php'],
        ['key' => 'owner',   'ok' => owner_name() !== '',                                                           'go' => 'identidad.php'],
        ['key' => 'bio',     'ok' => get_setting('owner_bio', '') !== '',                                           'go' => 'identidad.php'],
        ['key' => 'avatar',  'ok' => has_own_avatar(),                                                              'go' => 'identidad.php'],
        ['key' => 'socials', 'ok' => count(owner_socials()) > 0,                                                    'go' => 'identidad.php'],
        ['key' => 'texts',   'ok' => count(array_filter(content_keys(), 'content_is_custom')) >= 2,                 'go' => 'contenido.php'],
    ];
    $done = count(array_filter($items, fn($i) => $i['ok']));
    return ['items' => $items, 'done' => $done, 'total' => count($items),
            'pct' => (int)round($done / count($items) * 100)];
}

/* ---------- Tracking ---------- */

function tracking_head(): string {
    $out = '';
    if (defined('GTM_ID') && GTM_ID) {
        $out .= "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . GTM_ID . "');</script>\n";
    }
    if (defined('GA4_ID') && GA4_ID) {
        $out .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . GA4_ID . '"></script>' .
                "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','" . GA4_ID . "');</script>\n";
    }
    if (defined('META_PIXEL_ID') && META_PIXEL_ID) {
        $out .= "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','" . META_PIXEL_ID . "');fbq('track','PageView');</script>\n";
    }
    return $out;
}

function tracking_body(): string {
    if (defined('GTM_ID') && GTM_ID) {
        return '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . GTM_ID . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
    }
    return '';
}

/* ---------- Zona horaria ---------- */

/** Zonas comunes, para no lanzarle al usuario las 400 de PHP. */
function timezones(): array {
    return [
        'America/Monterrey', 'America/Mexico_City', 'America/Tijuana', 'America/Cancun',
        'America/Bogota', 'America/Lima', 'America/Santiago', 'America/Argentina/Buenos_Aires',
        'America/Sao_Paulo', 'America/Caracas', 'America/Panama', 'America/Guatemala',
        'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
        'America/Toronto', 'Europe/Madrid', 'Europe/London', 'Europe/Berlin', 'UTC',
    ];
}

/**
 * Arranque del sitio: fija la zona horaria guardada en la base de datos.
 * Se llama solo al principio de cada página; si la base no responde todavía
 * (durante la instalación) simplemente no hace nada.
 */
function jds_boot(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $tz = get_setting('timezone', '');
        if ($tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
            date_default_timezone_set($tz);
            return;
        }
    } catch (Throwable $ex) {
        // sin base de datos aún
    }
    if (!ini_get('date.timezone')) date_default_timezone_set('UTC');
}
