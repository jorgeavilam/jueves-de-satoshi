<?php
require_once __DIR__ . '/../includes/functions.php';

// Cookie de sesión endurecida: inaccesible a JavaScript, solo mismo sitio,
// y exclusiva de HTTPS cuando el sitio corre con SSL.
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

function is_logged_in(): bool {
    return !empty($_SESSION['jds_admin']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . '/admin/index.php');
        exit;
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        die(t('csrf_bad'));
    }
}

function try_login(string $user, string $pass): bool {
    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([$user]);
    $u = $st->fetch();
    if ($u && password_verify($pass, $u['pass_hash'])) {
        session_regenerate_id(true);
        $_SESSION['jds_admin'] = true;
        $_SESSION['jds_user_id'] = (int)$u['id'];
        $_SESSION['jds_username'] = $u['username'];
        return true;
    }
    return false;
}

function change_password(int $userId, string $current, string $new): string {
    if (strlen($new) < 8) return t('admin_pass_short');
    $st = db()->prepare('SELECT pass_hash FROM users WHERE id = ?');
    $st->execute([$userId]);
    $hash = $st->fetchColumn();
    if (!$hash || !password_verify($current, $hash)) return t('admin_pass_wrong');
    $st = db()->prepare('UPDATE users SET pass_hash = ? WHERE id = ?');
    $st->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
    set_setting('pw_reset', ''); // un enlace pendiente ya no debe servir
    return ''; // sin error = éxito
}

/* ---------- Recuperación de contraseña ----------
 *
 * El enlace llega SOLO a CONTACT_EMAIL, que vive en config.php: quien lo pide no
 * escribe ningún correo, así que no puede desviarlo a una dirección suya. Es la
 * misma confianza que ya tiene quien puede editar config.php.
 *
 * El token vive en `settings` y no en columnas nuevas de `users` a propósito:
 * upgrade.php pide login de admin, y quien olvidó su contraseña no podría
 * aplicar la migración que necesita para recuperarla.
 * Se guarda el hash del token, nunca el token; dura 30 minutos y sirve una vez.
 */
const RESET_TTL      = 1800; // segundos de vida del enlace
const RESET_COOLDOWN = 300;  // un correo cada 5 minutos, para que nadie llene el buzón

/** Envía el enlace si el usuario existe. Devuelve false solo si mail() falla. */
function reset_request(string $username): bool {
    if (time() - (int)get_setting('pw_reset_last', '0') < RESET_COOLDOWN) return true;
    $st = db()->prepare('SELECT id, username FROM users WHERE username = ?');
    $st->execute([$username]);
    $u = $st->fetch();
    if (!$u || CONTACT_EMAIL === '') return true;

    $token = bin2hex(random_bytes(32));
    set_settings([
        'pw_reset'      => json_encode(['uid' => (int)$u['id'], 'hash' => hash('sha256', $token), 'exp' => time() + RESET_TTL]),
        'pw_reset_last' => (string)time(),
    ]);
    // SITE_URL y no HTTP_HOST: una cabecera Host falsa no puede cambiar el enlace
    $link    = SITE_URL . '/admin/recuperar.php?token=' . $token;
    $subject = t('reset_mail_subject', site_name());
    $body    = t('reset_mail_body', $u['username'], $link, (string)(RESET_TTL / 60)) . "\n\n--\n" . SITE_URL;
    $headers = 'From: ' . mb_encode_mimeheader(site_name()) . ' <' . CONTACT_FROM . ">\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";
    return @mail(CONTACT_EMAIL, mb_encode_mimeheader($subject), $body, $headers);
}

/** Id del usuario al que pertenece un token vigente, o 0. */
function reset_user_for(string $token): int {
    $r = json_decode(get_setting('pw_reset', ''), true);
    if (!is_array($r) || empty($r['hash']) || (int)($r['exp'] ?? 0) < time()) return 0;
    return hash_equals($r['hash'], hash('sha256', $token)) ? (int)$r['uid'] : 0;
}

/** Cambia la contraseña con un token vigente y lo invalida. Devuelve el error o ''. */
function reset_complete(string $token, string $new): string {
    $uid = reset_user_for($token);
    if (!$uid) return t('reset_bad_link');
    if (strlen($new) < 8) return t('admin_pass_short');
    $st = db()->prepare('UPDATE users SET pass_hash = ? WHERE id = ?');
    $st->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
    set_setting('pw_reset', '');
    return '';
}

function logout(): void {
    $_SESSION = [];
    session_destroy();
}
