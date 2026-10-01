<?php
/**
 * Recuperación de emergencia de la contraseña del admin, por terminal.
 *
 *   php cli/reset-password.php            lista los usuarios
 *   php cli/reset-password.php USUARIO    le asigna una contraseña temporal
 *
 * Es la vía para cuando el hosting no envía correo. Quien puede correr PHP en el
 * servidor ya puede leer config.php, así que no abre ninguna puerta nueva. La
 * contraseña temporal se genera aquí y no se escribe en la línea de comandos,
 * para que no quede en el historial del shell.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../includes/functions.php';

$user = $argv[1] ?? '';
if ($user === '') {
    echo "Uso: php cli/reset-password.php USUARIO\n\nUsuarios:\n";
    foreach (db()->query('SELECT username FROM users ORDER BY id') as $r) echo '  ' . $r['username'] . "\n";
    exit(1);
}

$st = db()->prepare('SELECT id FROM users WHERE username = ?');
$st->execute([$user]);
$id = (int)$st->fetchColumn();
if (!$id) { fwrite(STDERR, "No existe el usuario «{$user}».\n"); exit(1); }

$temp = substr(strtr(base64_encode(random_bytes(12)), '+/', 'kx'), 0, 16);
$st = db()->prepare('UPDATE users SET pass_hash = ? WHERE id = ?');
$st->execute([password_hash($temp, PASSWORD_DEFAULT), $id]);
set_setting('pw_reset', '');

echo "Contraseña temporal de «{$user}»: {$temp}\n";
echo "Entra en " . SITE_URL . "/admin/ y cámbiala en Contraseña.\n";
