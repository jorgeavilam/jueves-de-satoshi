<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$error = ''; $ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $current = $_POST['actual'] ?? '';
    $new     = $_POST['nueva'] ?? '';
    $confirm = $_POST['confirmar'] ?? '';
    if ($new !== $confirm) {
        $error = 'La nueva contraseña y su confirmación no coinciden.';
    } else {
        $error = change_password((int)$_SESSION['jds_user_id'], $current, $new);
        $ok = $error === '';
    }
}

page_head('Admin — Cambiar contraseña');
?>
<div class="admin-bar">
  <div class="container">
    <span><a href="<?= SITE_URL ?>/admin/index.php">← Volver a compras</a></span>
    <span>Usuario: <?= e($_SESSION['jds_username'] ?? '') ?></span>
  </div>
</div>

<section class="block">
  <div class="container" style="max-width:480px">
    <h2>🔑 Cambiar <span class="brand-accent">contraseña</span></h2>
    <p class="section-sub">Mínimo 8 caracteres. Usa una contraseña fuerte y única.</p>

    <?php if ($ok): ?><div class="alert alert-ok">✅ Contraseña actualizada correctamente.</div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>

    <div class="form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-group">
          <label>Contraseña actual</label>
          <input type="password" name="actual" required autocomplete="current-password">
        </div>
        <div class="form-group">
          <label>Nueva contraseña</label>
          <input type="password" name="nueva" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label>Confirmar nueva contraseña</label>
          <input type="password" name="confirmar" required minlength="8" autocomplete="new-password">
        </div>
        <button class="btn" type="submit">Cambiar contraseña</button>
      </form>
    </div>
  </div>
</section>
<?php page_foot(); ?>
