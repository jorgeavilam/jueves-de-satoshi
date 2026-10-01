<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/demo.php';

// Logout
if (($_GET['action'] ?? '') === 'logout') { logout(); header('Location: ' . SITE_URL . '/'); exit; }

// Login
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!try_login($_POST['usuario'] ?? '', $_POST['password'] ?? '')) {
        usleep(500000); // frena fuerza bruta
        $loginError = t('admin_login_bad');
    } else {
        header('Location: ' . SITE_URL . '/admin/index.php'); exit;
    }
}

if (!is_logged_in()) {
    page_head(t('admin_login_title'), '', true);
    ?>
    <div class="container login-box">
      <div class="form-card">
        <h2 style="margin-bottom:16px">🔐 <?= e(t('admin_login_title')) ?></h2>
        <?php if ($loginError): ?><div class="alert alert-err"><?= e($loginError) ?></div><?php endif; ?>
        <form method="post">
          <div class="form-group"><label><?= e(t('admin_user')) ?></label><input type="text" name="usuario" required autofocus autocomplete="username"></div>
          <div class="form-group"><label><?= e(t('admin_pass')) ?></label><input type="password" name="password" required autocomplete="current-password"></div>
          <button class="btn" type="submit" name="login" value="1"><?= e(t('admin_enter')) ?></button>
        </form>
        <p style="margin-top:14px;font-size:0.9rem"><a href="<?= e(SITE_URL) ?>/admin/recuperar.php"><?= e(t('reset_forgot')) ?></a></p>
      </div>
    </div>
    <?php
    page_foot(); exit;
}

$years   = get_years();
$selYear = isset($_GET['y']) ? (int)$_GET['y'] : (count($years) ? (int)end($years)['year'] : (int)date('Y'));
$yearRow = get_year($selYear);

// Borrar compra
if (($_GET['action'] ?? '') === 'borrar' && isset($_GET['id'])) {
    if (!hash_equals(csrf_token(), $_GET['t'] ?? '')) die(t('csrf_bad'));
    $st = db()->prepare('DELETE FROM purchases WHERE id = ?');
    $st->execute([(int)$_GET['id']]);
    header('Location: ' . SITE_URL . '/admin/index.php?y=' . $selYear . '&msg=borrada'); exit;
}

// Borrar los datos de ejemplo
if (($_GET['action'] ?? '') === 'quitar-demo') {
    if (!hash_equals(csrf_token(), $_GET['t'] ?? '')) die(t('csrf_bad'));
    delete_demo_data();
    header('Location: ' . SITE_URL . '/admin/index.php?msg=demo'); exit;
}

$purchases = $yearRow ? get_purchases((int)$yearRow['id']) : [];
$msg = $_GET['msg'] ?? '';
$cur = currency_code();

page_head(t('admin_purchases'), '', true);
admin_chrome('compras');
?>
<section class="block">
  <div class="container">
    <h2><?= e(t('admin_purchases')) ?> <span class="brand-accent"><?= $selYear ?></span></h2>
    <p class="section-sub">
      <?php foreach ($years as $yr): ?>
        <a href="?y=<?= (int)$yr['year'] ?>" <?= (int)$yr['year'] === $selYear ? 'style="font-weight:800;color:var(--accent)"' : '' ?>><?= (int)$yr['year'] ?></a> &nbsp;
      <?php endforeach; ?>
    </p>

    <?php if ($msg === 'guardada'): ?><div class="alert alert-ok">✅ <?= e(t('admin_purchase_saved')) ?></div><?php endif; ?>
    <?php if ($msg === 'borrada'): ?><div class="alert alert-ok">🗑️ <?= e(t('admin_purchase_deleted')) ?></div><?php endif; ?>
    <?php if ($msg === 'demo'): ?><div class="alert alert-ok">🗑️ <?= e(t('saved_ok')) ?></div><?php endif; ?>

    <?php if (has_demo_data()): ?>
      <div class="alert alert-warn">
        <?= e(t('inst_demo_hint')) ?>
        <a href="?action=quitar-demo&t=<?= csrf_token() ?>" style="margin-left:8px"
           onclick="return confirm('<?= e(t('inst_demo')) ?>?')"><?= e(t('delete')) ?> →</a>
      </div>
    <?php endif; ?>

    <?php if (!$yearRow): ?>
      <div class="alert alert-err">
        <?= e(t('admin_no_year', $selYear)) ?>
        <a href="<?= SITE_URL ?>/admin/anio.php"><?= e(t('admin_create_year_here')) ?></a>.
      </div>
    <?php else: ?>
    <p style="margin-bottom:18px">
      <a class="btn" href="<?= SITE_URL ?>/admin/compra.php?y=<?= $selYear ?>">➕ <?= e(t('admin_add_purchase')) ?></a>
    </p>

    <div class="table-wrap">
      <table class="compras">
        <thead>
          <tr>
            <th><?= e(t('th_num')) ?></th>
            <th><?= e(t('th_date')) ?></th>
            <th><?= e(t('th_amount', $cur)) ?></th>
            <th><?= e(t('th_fee')) ?></th>
            <th><?= e(t('th_price', $cur)) ?></th>
            <?php if (!is_usd_site()): ?><th><?= e(t('th_rate', $cur)) ?></th><th><?= e(t('th_amount_usd')) ?></th><?php endif; ?>
            <th><?= e(t('th_sats')) ?></th>
            <th><?= e(t('th_post')) ?></th>
            <th><?= e(t('admin_actions')) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_reverse($purchases) as $p): ?>
          <tr>
            <td><?= (int)$p['num'] ?></td>
            <td><?= e(fmt_fecha($p['fecha'])) ?></td>
            <td><?= money($p['monto_local']) ?></td>
            <td><?= fmt_num($p['comision_pct'] * 100, 2) ?>%</td>
            <td><?= money($p['precio_local_btc']) ?></td>
            <?php if (!is_usd_site()): ?>
              <td><?= fmt_num($p['tipo_cambio_usd'], 4) ?></td>
              <td><?= money_usd($p['monto_usd']) ?></td>
            <?php endif; ?>
            <td><?= fmt_int($p['sats']) ?></td>
            <td><?php if ($p['x_post_url']): ?><a class="x-link" href="<?= e($p['x_post_url']) ?>" target="_blank" rel="noopener">𝕏</a><?php endif; ?></td>
            <td>
              <a href="<?= SITE_URL ?>/admin/compra.php?id=<?= (int)$p['id'] ?>"><?= e(t('edit')) ?></a> ·
              <a href="?y=<?= $selYear ?>&action=borrar&id=<?= (int)$p['id'] ?>&t=<?= csrf_token() ?>"
                 onclick="return confirm('<?= e(t('admin_confirm_delete_purchase', (int)$p['num'])) ?>')"><?= e(t('delete')) ?></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php page_foot(); ?>
