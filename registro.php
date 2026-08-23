<?php
/**
 * Punto de registro de nodos. Recibe el POST del instalador de cada
 * instalación nueva. Solo responde donde está el módulo hub.
 */
require_once __DIR__ . '/includes/functions.php';

if (!module_loaded('hub')) {
    http_response_code(404);
    header('Content-Type: application/json');
    exit('{"ok":false,"error":"not_a_hub"}');
}
require __DIR__ . '/modules/hub/registro.php';
