<?php
/**
 * La red — pantalla del nodo.
 *
 * Desde aquí cualquier instalación puede pedir su lugar en el directorio del
 * sitio maestro, ver en qué va su solicitud y decidir si comparte su pulso.
 *
 * Existe porque el registro solo ocurría en el instalador: quien actualizaba
 * desde la v1 se quedaba sin manera de entrar al directorio.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

// En el maestro esta pantalla no aplica: el directorio es suyo.
if (is_hub()) { header('Location: ' . SITE_URL . '/admin/index.php'); exit; }

$ok = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $do = $_POST['do'] ?? '';

    if ($do === 'share') {
        set_setting('share_aggregate', isset($_POST['share_aggregate']) ? '1' : '0');
        $ok = t('saved_ok');
    } elseif ($do === 'request') {
        if (!site_ready()) {
            $error = t('net_err_not_ready');
        } else {
            [$sent, $res] = network_request();
            if ($sent) {
                set_setting('share_aggregate', '1');
                $ok = t('net_sent');
            } else {
                $error = t('net_err_' . (in_array($res, ['unreachable', 'rate_limited', 'bad_url'], true) ? $res : 'rejected'));
            }
        }
    }
}

$requested = get_setting('network_requested', '');
$state     = $requested !== '' ? network_status() : ['ok' => true, 'status' => 'none'];
$status    = $state['status'] ?? 'unreachable';
$hubHost   = parse_url(HUB_URL, PHP_URL_HOST) ?: HUB_URL;

page_head(t('admin_network'), '', true);
admin_chrome('red');
?>
<section class="block">
  <div class="container" style="max-width:720px">
    <h2><?= e(t('net_title')) ?></h2>
    <p class="section-sub"><?= e(t('net_sub', $hubHost)) ?></p>

    <?php if ($ok): ?><div class="alert alert-ok">✅ <?= e($ok) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>
    <?php if (!site_ready()): ?><div class="alert alert-warn"><?= e(t('net_err_not_ready')) ?></div><?php endif; ?>

    <div class="form-card" style="margin-bottom:22px">
      <div class="uniq-head">
        <div>
          <strong style="font-size:0.78rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-soft)">
            <?= e(t('net_status')) ?>
          </strong>
          <p style="margin:2px 0 0;font-size:1.1rem;font-weight:700">
            <?php
            $icon = ['none' => '◻️', 'pending' => '⏳', 'active' => '✅', 'rejected' => '🚫', 'unreachable' => '⚠️'][$status] ?? '◻️';
            echo $icon . ' ' . e(t('net_status_' . (in_array($status, ['none','pending','active','rejected'], true) ? $status : 'unreachable')));
            ?>
          </p>
          <?php if ($requested !== ''): ?>
            <p class="hint" style="margin-top:4px"><?= e(t('net_requested_on', fmt_fecha(substr($requested, 0, 10)))) ?></p>
          <?php endif; ?>
          <?php if ($status === 'active' && !empty($state['last_seen'])): ?>
            <p class="hint"><?= e(t('net_last_seen') . ': ' . fmt_fecha(substr((string)$state['last_seen'], 0, 10))) ?></p>
          <?php endif; ?>
        </div>
        <?php if ($status === 'active'): ?>
          <a class="btn btn-outline btn-small" href="<?= e(HUB_URL) ?>/red.php" target="_blank" rel="noopener"><?= e(t('net_see_directory')) ?> ↗</a>
        <?php endif; ?>
      </div>

      <?php if ($status === 'none' || $status === 'unreachable'): ?>
        <p class="hint" style="margin:16px 0 12px"><?= e(t('net_what_we_send')) ?></p>
        <ul class="uniq-list" style="margin-bottom:16px">
          <li><span><?= e(t('brand_name')) ?></span><span><?= e(site_name()) ?></span></li>
          <li><span><?= e(t('id_name')) ?></span><span><?= e(owner_name()) ?></span></li>
          <li><span><?= e(t('inst_site_url')) ?></span><span><?= e(SITE_URL) ?></span></li>
          <li><span><?= e(t('brand_locale')) ?> · <?= e(t('brand_currency')) ?></span><span><?= e(current_locale()) ?> · <?= e(currency_code()) ?></span></li>
        </ul>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <button class="btn" name="do" value="request" <?= site_ready() ? '' : 'disabled' ?>><?= e(t('net_request')) ?></button>
        </form>
        <p class="hint" style="margin-top:12px"><?= e(t('net_badge_auto')) ?></p>
      <?php elseif ($status === 'pending'): ?>
        <p class="hint" style="margin-top:14px"><?= e(t('net_pending_hint', $hubHost)) ?></p>
      <?php elseif ($status === 'rejected'): ?>
        <p class="hint" style="margin-top:14px"><?= e(t('net_rejected_hint')) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <h3 style="margin:0 0 10px;font-size:1.05rem"><?= e(t('share_title')) ?></h3>
        <label class="choice">
          <input type="checkbox" name="share_aggregate" value="1" <?= get_setting('share_aggregate', '0') === '1' ? 'checked' : '' ?>>
          <strong><?= e(t('share_label')) ?></strong>
          <p class="hint"><?= e(t('share_hint')) ?></p>
        </label>
        <button class="btn" name="do" value="share"><?= e(t('save')) ?></button>
        <a class="btn btn-outline" href="<?= SITE_URL ?>/api.php" target="_blank" rel="noopener"><?= e(t('net_see_payload')) ?> ↗</a>
      </form>
    </div>
  </div>
</section>
<?php page_foot(); ?>
