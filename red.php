<?php
/**
 * Directorio público de instalaciones ("El Efecto").
 *
 * Solo existe donde está el módulo hub. En un nodo devuelve 404: no es una
 * página escondida, es una página que no está instalada.
 */
require_once __DIR__ . '/includes/functions.php';

if (!module_loaded('hub')) {
    http_response_code(404);
    exit('404');
}
require __DIR__ . '/modules/hub/efecto.php';
