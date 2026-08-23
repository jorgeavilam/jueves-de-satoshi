<?php
/**
 * Módulos opcionales.
 *
 * Un módulo es una carpeta en modules/<nombre>/ con un module.php.
 * No hay bandera de configuración que active nada: un módulo existe o no existe
 * en el servidor. El módulo `hub` (directorio de la red, aprobación de nodos)
 * vive en un repositorio privado y por eso no viene en esta distribución.
 */
function module_dir(string $name): string {
    return __DIR__ . '/../modules/' . preg_replace('/[^a-z0-9_-]/', '', $name);
}

function module_loaded(string $name): bool {
    static $c = [];
    return $c[$name] ??= is_readable(module_dir($name) . '/module.php');
}

/** Carga los módulos presentes una sola vez por petición. */
function modules_boot(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    foreach (glob(__DIR__ . '/../modules/*/module.php') ?: [] as $f) {
        require_once $f;
    }
}

/** ¿Esta instalación es el hub del proyecto? */
function is_hub(): bool {
    return module_loaded('hub');
}

/**
 * Entradas de menú que los módulos agregan al admin.
 * Cada módulo declara jds_hub_admin_menu() y devuelve [['url'=>, 'label'=>, 'badge'=>], ...]
 */
function modules_admin_menu(): array {
    modules_boot();
    $out = [];
    foreach (['hub'] as $m) {
        $fn = 'jds_' . $m . '_admin_menu';
        if (function_exists($fn)) $out = array_merge($out, (array)$fn());
    }
    return $out;
}
