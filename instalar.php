<?php
/**
 * «Monta el tuyo»: la invitación a instalar tu propia versión.
 *
 * Solo existe donde está el módulo hub. Invitar a descargar la app es cosa del
 * sitio maestro: en un nodo parecería que la app es de su dueño. Ahí devuelve
 * 404, igual que red.php.
 */
require_once __DIR__ . '/includes/functions.php';

if (!module_loaded('hub')) {
    http_response_code(404);
    exit('404');
}
require __DIR__ . '/modules/hub/instalar.php';
