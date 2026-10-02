<?php
/**
 * Capa de idioma. Los textos viven en lang/<code>.php.
 *
 * El sitio tiene un idioma principal (settings.locale) y puede publicar otros
 * (settings.site_locales). El visitante elige con ?lang= y la URL es la única
 * fuente: sin cookie, cada idioma tiene su propia dirección y Google puede
 * indexar los dos. El idioma principal nunca lleva ?lang=.
 */

const JDS_LOCALES = ['es', 'en'];

/** Escape de HTML. Vive aquí porque el instalador lo necesita antes de tener base de datos. */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Fuerza un idioma para el resto de la petición (lo usan install.php y upgrade.php). */
function i18n_force(string $code): void {
    $GLOBALS['jds_locale'] = in_array($code, JDS_LOCALES, true) ? $code : 'es';
}

/** Nombre de cada idioma en su propio idioma: así lo busca quien no lee el otro. */
const JDS_LOCALE_NAMES = ['es' => 'Español', 'en' => 'English'];

/** Idioma principal del sitio: el del panel y el de las URLs sin ?lang=. */
function site_locale(): string {
    $code = 'es';
    if (function_exists('get_setting')) {
        try { $code = get_setting('locale', 'es'); } catch (Throwable $e) { $code = 'es'; }
    }
    return in_array($code, JDS_LOCALES, true) ? $code : 'es';
}

/** Idiomas que publica el sitio, el principal primero. */
function site_locales(): array {
    $out = [site_locale()];
    if (function_exists('get_setting')) {
        try { $extra = get_setting('site_locales', ''); } catch (Throwable $e) { $extra = ''; }
        foreach (explode(',', $extra) as $c) {
            $c = trim($c);
            if (in_array($c, JDS_LOCALES, true) && !in_array($c, $out, true)) $out[] = $c;
        }
    }
    return $out;
}

/** Idioma de esta petición: el de ?lang= si el sitio lo publica; si no, el principal. */
function current_locale(): string {
    if (isset($GLOBALS['jds_locale'])) return $GLOBALS['jds_locale'];
    $code = site_locale();
    $want = $_GET['lang'] ?? '';
    if (is_string($want) && $want !== $code && in_array($want, site_locales(), true)) $code = $want;
    return $GLOBALS['jds_locale'] = $code;
}

/**
 * La misma URL en otro idioma: quita el ?lang= que traiga y agrega el pedido,
 * salvo que sea el principal. Respeta los demás parámetros y el #fragmento.
 */
function l10n_url(string $url, ?string $locale = null): string {
    $locale = $locale ?? current_locale();
    $frag = '';
    if (($p = strpos($url, '#')) !== false) { $frag = substr($url, $p); $url = substr($url, 0, $p); }
    $url = preg_replace('/([?&])lang=[^&]*(&|$)/', '$1', $url);
    $url = rtrim($url, '?&');
    if ($locale === site_locale()) return $url . $frag;
    return $url . (strpos($url, '?') === false ? '?' : '&') . 'lang=' . rawurlencode($locale) . $frag;
}

/**
 * Textos extra que aporta un módulo. Así el módulo hub lleva sus propias
 * cadenas en su repositorio, sin tocar los archivos de lang de la distribución.
 */
function i18n_add(string $code, array $strings): void {
    $GLOBALS['jds_lang_extra'][$code] = array_merge($GLOBALS['jds_lang_extra'][$code] ?? [], $strings);
    $GLOBALS['jds_lang_ver'] = ($GLOBALS['jds_lang_ver'] ?? 0) + 1;
}

function lang(): array {
    static $cache = [];
    $code = current_locale();
    $key  = $code . '#' . ($GLOBALS['jds_lang_ver'] ?? 0);
    if (!isset($cache[$key])) {
        $file = __DIR__ . '/../lang/' . $code . '.php';
        if (!is_readable($file)) $file = __DIR__ . '/../lang/es.php';
        $base = require $file;
        $extra = $GLOBALS['jds_lang_extra'][$code] ?? [];
        $cache[$key] = $extra ? array_merge($base, $extra) : $base;
    }
    return $cache[$key];
}

/** Texto traducido. Los argumentos extra se pasan a sprintf. */
function t(string $key, ...$args): string {
    $L = lang();
    $s = $L[$key] ?? ($key);
    if (!is_string($s)) return $key;
    return $args ? vsprintf($s, $args) : $s;
}

/** Valor crudo (para las llaves que guardan arreglos: meses, días). */
function tr(string $key) {
    $L = lang();
    return $L[$key] ?? null;
}

function day_name(int $iso, bool $cap = false): string {
    $a = tr($cap ? 'days_cap' : 'days');
    return $a[$iso] ?? $a[4];
}
