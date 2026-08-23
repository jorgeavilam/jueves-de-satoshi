<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$error = ''; $ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $current = $_POST['actual'] ?? '';
    $new     = $_POST['nueva'] ?? '';
    $confirm = $_POST['confirmar'] ?? '';
    if ($new !== $confirm) {
        $error = t('admin_pass_mismatch');
    } else {
        $error = change_password((int)$_SESSION['jds_user_id'], $current, $new);
        $ok = $error === '';
    }
}

page_head(t('admin_password'), '', true);
admin_chrome('password');
?>
<section class="block">
  <div class="container" style="max-width:480px">
    <h2>🔑 <?= e(t('admin_pass_title')) ?></h2>
    <p class="section-sub"><?= e(t('admin_pass_sub')) ?></p>

    <?php if ($ok): ?><div class="alert alert-ok">✅ <?= e(t('admin_pass_ok')) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>

    <div class="form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-group">
          <label><?= e(t('admin_pass_current')) ?></label>
          <input type="password" name="actual" required autocomplete="current-password">
        </div>
        <div class="form-group">
          <label><?= e(t('admin_pass_new')) ?></label>
          <input type="password" name="nueva" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label><?= e(t('admin_pass_confirm')) ?></label>
          <input type="password" name="confirmar" required minlength="8" autocomplete="new-password">
        </div>
        <button class="btn" type="submit"><?= e(t('admin_pass_title')) ?></button>
      </form>
    </div>
  </div>
</section>
<?php page_foot(); ?>
