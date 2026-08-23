<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $m = $_POST['privacy_mode'] ?? 'full';
    if (in_array($m, privacy_modes(), true)) {
        set_settings([
            'privacy_mode'    => $m,
            'share_aggregate' => isset($_POST['share_aggregate']) ? '1' : '0',
        ]);
        $ok = t('saved_ok');
    }
}

page_head(t('admin_privacy'), '', true);
admin_chrome('privacidad');
?>
<section class="block">
  <div class="container" style="max-width:700px">
    <h2><?= e(t('privacy_title')) ?></h2>
    <p class="section-sub"><?= e(t('privacy_sub')) ?></p>

    <?php if ($ok): ?><div class="alert alert-ok">✅ <?= e($ok) ?></div><?php endif; ?>

    <div class="form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="choice-list">
          <?php foreach (privacy_modes() as $m): ?>
            <label class="choice">
              <input type="radio" name="privacy_mode" value="<?= e($m) ?>" <?= privacy_mode() === $m ? 'checked' : '' ?>>
              <strong><?= e(t('privacy_' . $m)) ?></strong>
              <p class="hint"><?= e(t('privacy_' . $m . '_hint')) ?></p>
            </label>
          <?php endforeach; ?>
        </div>
        <h3 style="margin:24px 0 10px;font-size:1.05rem"><?= e(t('share_title')) ?></h3>
        <label class="choice">
          <input type="checkbox" name="share_aggregate" value="1" <?= get_setting('share_aggregate', '0') === '1' ? 'checked' : '' ?>>
          <strong><?= e(t('share_label')) ?></strong>
          <p class="hint"><?= e(t('share_hint')) ?></p>
        </label>

        <button class="btn" type="submit"><?= e(t('save')) ?></button>
      </form>
    </div>
  </div>
</section>
<?php page_foot(); ?>
