<?php
/**
 * Recuperar la contraseña del admin.
 *
 * Sin token: pide el usuario y manda el enlace al correo de config.php.
 * Con token: pide la contraseña nueva.
 * Si el hosting no envía correo, queda la vía de terminal: cli/reset-password.php.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/layout.php';

if (is_logged_in()) { header('Location: ' . SITE_URL . '/admin/password.php'); exit; }

// El token pasa a la sesión y la URL queda limpia: el panel carga GA4 y el
// Pixel, y un ?token= en la dirección terminaría en sus reportes.
if (isset($_GET['token']) || isset($_GET['nuevo'])) {
    $_SESSION['reset_token'] = (string)($_GET['token'] ?? '');
    header('Location: ' . SITE_URL . '/admin/recuperar.php'); exit;
}
$token = (string)($_SESSION['reset_token'] ?? '');
$error = ''; $notice = ''; $done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if ($token === '') {
        usleep(500000);
        // El mismo aviso exista o no el usuario: la página no confirma nombres
        $notice = reset_request(trim($_POST['usuario'] ?? '')) ? t('reset_sent') : t('reset_send_fail');
    } elseif (($_POST['nueva'] ?? '') !== ($_POST['confirmar'] ?? '')) {
        $error = t('admin_pass_mismatch');
    } else {
        $error = reset_complete($token, $_POST['nueva'] ?? '');
        $done  = $error === '';
        if ($done) unset($_SESSION['reset_token']);
    }
}

$validToken = $token !== '' && !$done && reset_user_for($token) > 0;
if ($token !== '' && !$validToken && !$done) unset($_SESSION['reset_token']);

page_head(t('reset_title'), '', true);
?>
<div class="container login-box">
  <div class="form-card">
    <h2 style="margin-bottom:16px">🔑 <?= e(t('reset_title')) ?></h2>

    <?php if ($done): ?>
      <div class="alert alert-ok">✅ <?= e(t('reset_done')) ?></div>
      <a class="btn" href="<?= e(SITE_URL) ?>/admin/index.php"><?= e(t('admin_enter')) ?></a>

    <?php elseif ($token !== '' && !$validToken): ?>
      <div class="alert alert-err"><?= e(t('reset_bad_link')) ?></div>
      <a href="<?= e(SITE_URL) ?>/admin/recuperar.php?nuevo=1"><?= e(t('reset_again')) ?></a>

    <?php elseif ($validToken): ?>
      <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-group">
          <label><?= e(t('admin_pass_new')) ?></label>
          <input type="password" name="nueva" required minlength="8" autofocus autocomplete="new-password">
        </div>
        <div class="form-group">
          <label><?= e(t('admin_pass_confirm')) ?></label>
          <input type="password" name="confirmar" required minlength="8" autocomplete="new-password">
        </div>
        <button class="btn" type="submit"><?= e(t('reset_save')) ?></button>
      </form>

    <?php else: ?>
      <?php if ($notice): ?><div class="alert alert-ok"><?= e($notice) ?></div><?php endif; ?>
      <p class="section-sub"><?= e(t('reset_sub')) ?></p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-group">
          <label><?= e(t('admin_user')) ?></label>
          <input type="text" name="usuario" required autofocus autocomplete="username">
        </div>
        <button class="btn" type="submit"><?= e(t('reset_send')) ?></button>
      </form>
      <p style="margin-top:16px;font-size:0.85rem;color:var(--text-soft)"><?= e(t('reset_cli_hint')) ?></p>
      <p style="margin-top:12px"><a href="<?= e(SITE_URL) ?>/admin/index.php">← <?= e(t('admin_login_title')) ?></a></p>
    <?php endif; ?>
  </div>
</div>
<?php page_foot(); ?>
