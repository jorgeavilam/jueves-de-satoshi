<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $pairs = [];
    foreach (['exchange' => exchange_catalog(), 'wallet' => wallet_catalog()] as $kind => $cat) {
        $sel = $_POST['tool_' . $kind] ?? 'none';
        if (!isset($cat[$sel]) && !in_array($sel, ['none', 'other'], true)) $sel = 'none';
        $pairs['tool_' . $kind] = $sel;
        if ($sel === 'other') {
            $pairs['tool_' . $kind . '_name'] = mb_substr(trim($_POST['tool_' . $kind . '_name'] ?? ''), 0, 80);
            $url = trim($_POST['tool_' . $kind . '_url'] ?? '');
            $pairs['tool_' . $kind . '_url']  = filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
            $pairs['tool_' . $kind . '_desc'] = mb_substr(trim($_POST['tool_' . $kind . '_desc'] ?? ''), 0, 400);
        }
    }
    set_settings($pairs);
    $ok = t('saved_ok');
}

page_head(t('admin_tools'), '', true);
admin_chrome('herramientas');

/** Bloque de selección de una herramienta. */
function tool_picker(string $kind, array $cat, string $title): void {
    $sel = get_setting('tool_' . $kind, 'none');
    ?>
    <h3 style="margin:24px 0 10px;font-size:1.05rem"><?= e($title) ?></h3>
    <div class="form-group">
      <select name="tool_<?= e($kind) ?>" data-kind="<?= e($kind) ?>" class="tool-select">
        <option value="none" <?= $sel === 'none' ? 'selected' : '' ?>><?= e(t('toolsadmin_none')) ?></option>
        <?php foreach ($cat as $k => $tool): ?>
          <option value="<?= e($k) ?>" <?= $sel === $k ? 'selected' : '' ?>><?= e($tool['name']) ?></option>
        <?php endforeach; ?>
        <option value="other" <?= $sel === 'other' ? 'selected' : '' ?>><?= e(t('toolsadmin_other')) ?></option>
      </select>
    </div>
    <div class="other-fields" id="other_<?= e($kind) ?>" style="<?= $sel === 'other' ? '' : 'display:none' ?>">
      <div class="form-grid">
        <div class="form-group">
          <label><?= e(t('toolsadmin_other_name')) ?></label>
          <input type="text" name="tool_<?= e($kind) ?>_name" maxlength="80" value="<?= e(get_setting('tool_' . $kind . '_name', '')) ?>">
        </div>
        <div class="form-group">
          <label><?= e(t('toolsadmin_other_url')) ?></label>
          <input type="url" name="tool_<?= e($kind) ?>_url" value="<?= e(get_setting('tool_' . $kind . '_url', '')) ?>">
        </div>
      </div>
      <div class="form-group">
        <label><?= e(t('toolsadmin_other_desc')) ?></label>
        <textarea name="tool_<?= e($kind) ?>_desc" rows="3" maxlength="400"><?= e(get_setting('tool_' . $kind . '_desc', '')) ?></textarea>
      </div>
    </div>
    <?php
}
?>
<section class="block">
  <div class="container" style="max-width:760px">
    <h2><?= e(t('toolsadmin_title')) ?></h2>
    <p class="section-sub"><?= e(t('toolsadmin_sub')) ?></p>

    <?php if ($ok): ?><div class="alert alert-ok">✅ <?= e($ok) ?></div><?php endif; ?>

    <div class="form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <?php tool_picker('exchange', exchange_catalog(), t('toolsadmin_exchange')); ?>
        <?php tool_picker('wallet', wallet_catalog(), t('toolsadmin_wallet')); ?>
        <button class="btn" type="submit" style="margin-top:10px"><?= e(t('save')) ?></button>
      </form>
    </div>
  </div>
</section>
<script>
document.querySelectorAll('.tool-select').forEach(function (sel) {
  sel.addEventListener('change', function () {
    var box = document.getElementById('other_' + sel.dataset.kind);
    box.style.display = sel.value === 'other' ? '' : 'none';
  });
});
</script>
<?php page_foot(); ?>
