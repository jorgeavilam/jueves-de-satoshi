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

/**
 * Huecos de la interfaz pública que un módulo puede llenar.
 *
 * El módulo declara jds_<nombre>_slot(string $slot): string y devuelve HTML.
 * En un nodo no hay módulos: los huecos quedan vacíos y la página es idéntica.
 * Huecos: 'banner' (bajo el encabezado), 'home_coaster' (bajo la montaña rusa),
 * 'year_end' (al final de las gráficas del año).
 */
function module_slot(string $slot): string {
    modules_boot(); // los módulos se cargan a demanda; en un nodo no hay nada que cargar
    $out = '';
    foreach (glob(__DIR__ . '/../modules/*/module.php') ?: [] as $f) {
        $fn = 'jds_' . basename(dirname($f)) . '_slot';
        if (function_exists($fn)) $out .= (string)$fn($slot);
    }
    return $out;
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
