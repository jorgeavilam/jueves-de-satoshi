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
    return ''; // sin error = éxito
}

function logout(): void {
    $_SESSION = [];
    session_destroy();
}
