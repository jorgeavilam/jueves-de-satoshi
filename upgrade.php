<?php
/**
 * Actualizador de base de datos.
 *
 * Aplica los pasos de migrations/ que todavía no se hayan aplicado. Es seguro
 * ejecutarlo varias veces: los pasos ya aplicados quedan anotados en
 * schema_migrations y no se repiten.
 *
 * Requiere entrar con el usuario administrador del sitio.
 */
define('JDS_SETUP_VERSION', '2.0.7');

require_once __DIR__ . '/includes/migrate.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/setup.php';

jds_boot();
i18n_force(get_setting('locale', 'es'));

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

/* ---------- Acceso ---------- */
$loginError = '';
if (($_POST['do'] ?? '') === 'login') {
    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([$_POST['usuario'] ?? '']);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['pass_hash'])) {
        session_regenerate_id(true);
        $_SESSION['jds_admin']    = true;
        $_SESSION['jds_user_id']  = (int)$u['id'];
        $_SESSION['jds_username'] = $u['username'];
    } else {
        usleep(500000);
        $loginError = t('admin_login_bad');
    }
}

if (empty($_SESSION['jds_admin'])) {
    setup_head(t('upg_title'));
    echo '<h1>' . e(t('upg_title')) . '</h1><p class="sub">' . e(t('upg_login')) . '</p>';
    echo '<div class="card">';
    if ($loginError) echo '<div class="alert err">' . e($loginError) . '</div>';
    echo '<form method="post"><input type="hidden" name="do" value="login">'
       . '<div class="field"><label>' . e(t('admin_user')) . '</label><input type="text" name="usuario" required autofocus></div>'
       . '<div class="field"><label>' . e(t('admin_pass')) . '</label><input type="password" name="password" required></div>'
       . '<div class="actions"><button class="btn" type="submit">' . e(t('admin_enter')) . '</button></div>'
       . '</form></div>';
    setup_foot();
    exit;
}

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

/* ---------- Estado ---------- */
$pending = migrations_pending();
$applied = migrations_applied();
$results = [];
$failed  = '';

if (($_POST['do'] ?? '') === 'run') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $failed = t('csrf_bad');
    } else {
        foreach ($pending as $version => $file) {
            try {
                migration_apply($version, $file);
                $results[] = ['v' => $version, 'ok' => true, 'msg' => ''];
            } catch (Throwable $ex) {
                $results[] = ['v' => $version, 'ok' => false, 'msg' => $ex->getMessage()];
                $failed = $ex->getMessage();
                break; // no seguimos encadenando pasos sobre un esquema a medias
            }
        }
        settings_all(true);
        content_all(true);
        $pending = migrations_pending();
    }
}

setup_head(t('upg_title'));
?>
<h1><?= e(t('upg_title')) ?></h1>
<p class="sub">
  <?= e(t('upg_current')) ?>: <strong><?= $applied ? e(end($applied)) : 'v1' ?></strong> ·
  <?= e(t('upg_available')) ?>: <strong><?= e(JDS_VERSION) ?></strong>
</p>

<div class="card">
<?php if ($results): ?>
  <?php if ($failed): ?>
    <div class="alert err"><?= e(t('upg_fail', $failed)) ?></div>
  <?php else: ?>
    <div class="alert ok"><?= e(t('upg_ok')) ?></div>
  <?php endif; ?>
  <ul class="check">
    <?php foreach ($results as $r): ?>
      <li><span><?= e($r['v']) ?></span><span class="<?= $r['ok'] ? '' : 'muted' ?>"><?= $r['ok'] ? '✅' : '⚠️ ' . e($r['msg']) ?></span></li>
    <?php endforeach; ?>
  </ul>
  <?php if (!$failed): ?>
    <p class="hint" style="margin-top:16px"><?= e(t('upg_after')) ?></p>
    <div class="actions">
      <a class="btn" href="<?= e(SITE_URL) ?>/admin/identidad.php"><?= e(t('upg_review_identity')) ?></a>
      <?php if (!is_hub()): ?>
        <a class="btn ghost" href="<?= e(SITE_URL) ?>/admin/red.php"><?= e(t('net_request')) ?></a>
      <?php endif; ?>
      <a class="btn ghost" href="<?= e(SITE_URL) ?>/admin/"><?= e(t('admin_panel')) ?></a>
    </div>
  <?php endif; ?>

<?php elseif (!$pending): ?>
  <div class="alert ok"><?= e(t('upg_none')) ?></div>
  <div class="actions"><a class="btn" href="<?= e(SITE_URL) ?>/admin/"><?= e(t('admin_panel')) ?></a></div>

<?php else: ?>
  <div class="alert warn"><?= e(t('upg_backup')) ?></div>
  <h2><?= e(t('upg_pending')) ?></h2>
  <ul class="check">
    <?php foreach ($pending as $v => $f): ?>
      <li><span><?= e($v) ?></span><span class="muted"><?= e(t('uniq_pending')) ?></span></li>
    <?php endforeach; ?>
  </ul>
  <form method="post" class="actions">
    <input type="hidden" name="do" value="run">
    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
    <button class="btn" type="submit"><?= e(t('upg_run')) ?></button>
  </form>
<?php endif; ?>
</div>
<?php setup_foot(); ?>
