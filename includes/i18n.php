<?php
/**
 * Capa de idioma. Los textos viven en lang/<code>.php.
 * El idioma es una propiedad del sitio (settings.locale), no del visitante.
 */

const JDS_LOCALES = ['es', 'en'];

/** Escape de HTML. Vive aquí porque el instalador lo necesita antes de tener base de datos. */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Fuerza un idioma para el resto de la petición (lo usan install.php y upgrade.php). */
function i18n_force(string $code): void {
    $GLOBALS['jds_locale'] = in_array($code, JDS_LOCALES, true) ? $code : 'es';
}

function current_locale(): string {
    if (isset($GLOBALS['jds_locale'])) return $GLOBALS['jds_locale'];
    $code = 'es';
    if (function_exists('get_setting')) {
        try { $code = get_setting('locale', 'es'); } catch (Throwable $e) { $code = 'es'; }
    }
    if (!in_array($code, JDS_LOCALES, true)) $code = 'es';
    return $GLOBALS['jds_locale'] = $code;
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
