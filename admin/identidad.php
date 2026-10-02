<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$ok = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim($_POST['owner_name'] ?? '');
    if ($name === '') {
        $error = t('inst_err_name');
    } else {
        $pairs = [
            'owner_name' => mb_substr($name, 0, 120),
            'owner_bio'  => mb_substr(trim($_POST['owner_bio'] ?? ''), 0, 200),
        ];
        foreach (array_diff(site_locales(), [site_locale()]) as $l) {
            $pairs['owner_bio_' . $l] = mb_substr(trim($_POST['owner_bio_' . $l] ?? ''), 0, 200);
        }
        $mail = trim($_POST['owner_email_public'] ?? '');
        if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $error = t('inst_err_email');
        } else {
            $pairs['owner_email_public'] = $mail;
            foreach (array_keys(social_networks()) as $k) {
                $url = trim($_POST['social_' . $k] ?? '');
                $pairs['social_' . $k] = filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
            }
            [$file, $upErr] = admin_upload_image('avatar', 'avatar');
            if ($upErr !== '') {
                $error = t('id_avatar_hint');
            } else {
                if ($file !== '') {
                    admin_delete_asset(get_setting('owner_avatar', ''));
                    $pairs['owner_avatar'] = $file;
                }
                set_settings($pairs);
                $ok = t('saved_ok');
            }
        }
    }
}

page_head(t('admin_identity'), '', true);
admin_chrome('identidad');
?>
<section class="block">
  <div class="container" style="max-width:760px">
    <h2><?= e(t('id_title')) ?></h2>
    <p class="section-sub"><?= e(t('id_sub')) ?></p>

    <?php if ($ok): ?><div class="alert alert-ok">✅ <?= e($ok) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>
    <?php if (owner_name() === ''): ?><div class="alert alert-warn"><?= e(t('id_name_hint')) ?></div><?php endif; ?>

    <div class="form-card">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <div class="form-group">
          <label><?= e(t('id_name')) ?></label>
          <input type="text" name="owner_name" maxlength="120" required value="<?= e(owner_name()) ?>">
        </div>
        <div class="form-group">
          <label><?= e(t('id_bio')) ?></label>
          <input type="text" name="owner_bio" maxlength="200" value="<?= e(get_setting('owner_bio', '')) ?>">
        </div>
        <?php foreach (array_diff(site_locales(), [site_locale()]) as $l): ?>
          <div class="form-group tr-field">
            <label>↳ <?= e(t('content_tr_label', t('lang_name_' . $l))) ?></label>
            <input type="text" name="owner_bio_<?= e($l) ?>" maxlength="200" lang="<?= e($l) ?>" value="<?= e(get_setting('owner_bio_' . $l, '')) ?>">
            <p class="hint"><?= e(t('id_bio_tr_hint', t('lang_name_' . site_locale()))) ?></p>
          </div>
        <?php endforeach; ?>
        <div class="form-group">
          <label><?= e(t('id_email_public')) ?></label>
          <input type="email" name="owner_email_public" value="<?= e(get_setting('owner_email_public', '')) ?>">
          <p class="hint"><?= e(t('id_email_public_hint')) ?></p>
        </div>

        <div class="form-group">
          <label><?= e(t('id_avatar')) ?></label>
          <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
            <img src="<?= e(owner_avatar_url()) ?>" alt="<?= e(t('id_avatar_current')) ?>" width="72" height="72"
                 style="border-radius:50%;object-fit:cover;border:3px solid var(--accent)">
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" style="flex:1;min-width:200px">
          </div>
          <p class="hint"><?= e(t('id_avatar_hint')) ?></p>
        </div>

        <h3 style="margin:26px 0 6px;font-size:1.05rem"><?= e(t('id_socials')) ?></h3>
        <p class="hint" style="margin-bottom:14px"><?= e(t('id_socials_hint')) ?></p>
        <div class="form-grid">
          <?php foreach (social_networks() as $k => $meta): ?>
            <div class="form-group">
              <label><?= e($meta['label']) ?></label>
              <input type="url" name="social_<?= e($k) ?>" placeholder="https://" value="<?= e(get_setting('social_' . $k, '')) ?>">
            </div>
          <?php endforeach; ?>
        </div>

        <button class="btn" type="submit"><?= e(t('save')) ?></button>
      </form>
    </div>
  </div>
</section>
<?php page_foot(); ?>
