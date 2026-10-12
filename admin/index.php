<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/demo.php';

// Logout: con token, para que otra página no pueda cerrar la sesión con un enlace
if (($_GET['action'] ?? '') === 'logout') {
    if (csrf_ok((string)($_GET['t'] ?? ''))) logout();
    header('Location: ' . SITE_URL . '/'); exit;
}

// Login
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    check_csrf();
    if (($wait = login_wait_seconds()) > 0) {
        $loginError = t('admin_login_wait', $wait);
    } elseif (!try_login($_POST['usuario'] ?? '', $_POST['password'] ?? '')) {
        login_record(false);
        usleep(500000); // frena fuerza bruta
        $loginError = t('admin_login_bad');
    } else {
        login_record(true);
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
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
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

// Borrar compra: lógico, se puede restaurar (sin la migración 005 no se borra nada)
if (($_GET['action'] ?? '') === 'borrar' && isset($_GET['id'])) {
    if (!hash_equals(csrf_token(), $_GET['t'] ?? '')) die(t('csrf_bad'));
    if (!purchases_soft_delete()) {
        header('Location: ' . SITE_URL . '/admin/index.php?y=' . $selYear . '&msg=upgrade'); exit;
    }
    $st = db()->prepare('UPDATE purchases SET deleted = 1 WHERE id = ?');
    $st->execute([(int)$_GET['id']]);
    header('Location: ' . SITE_URL . '/admin/index.php?y=' . $selYear . '&msg=borrada'); exit;
}

// Restaurar compra, si su número no lo ocupa ya otra compra vigente
if (($_GET['action'] ?? '') === 'restaurar' && isset($_GET['id']) && purchases_soft_delete()) {
    if (!hash_equals(csrf_token(), $_GET['t'] ?? '')) die(t('csrf_bad'));
    $st = db()->prepare('SELECT * FROM purchases WHERE id = ? AND deleted = 1');
    $st->execute([(int)$_GET['id']]);
    $del = $st->fetch();
    $msg = 'restaurada';
    if ($del && purchase_num_taken((int)$del['year_id'], (int)$del['num'])) {
        $msg = 'ocupada&num=' . (int)$del['num'];
    } elseif ($del) {
        db()->prepare('UPDATE purchases SET deleted = 0 WHERE id = ?')->execute([(int)$del['id']]);
    }
    header('Location: ' . SITE_URL . '/admin/index.php?y=' . $selYear . '&msg=' . $msg); exit;
}

// Borrar los datos de ejemplo
if (($_GET['action'] ?? '') === 'quitar-demo') {
    if (!hash_equals(csrf_token(), $_GET['t'] ?? '')) die(t('csrf_bad'));
    delete_demo_data();
    header('Location: ' . SITE_URL . '/admin/index.php?msg=demo'); exit;
}

$purchases = $yearRow ? get_purchases((int)$yearRow['id']) : [];
$deleted   = $yearRow ? get_deleted_purchases((int)$yearRow['id']) : [];
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
    <?php if ($msg === 'restaurada'): ?><div class="alert alert-ok">✅ <?= e(t('admin_purchase_restored')) ?></div><?php endif; ?>
    <?php if ($msg === 'ocupada'): ?><div class="alert alert-err"><?= e(t('admin_restore_taken', (int)($_GET['num'] ?? 0))) ?></div><?php endif; ?>
    <?php if ($msg === 'upgrade'): ?><div class="alert alert-err"><?= e(t('admin_delete_needs_upgrade')) ?></div><?php endif; ?>
    <?php if ($msg === 'demo'): ?><div class="alert alert-ok">🗑️ <?= e(t('saved_ok')) ?></div><?php endif; ?>

    <?php if (has_demo_data()): ?>
      <div class="alert alert-warn">
        <?= e(t('inst_demo_hint')) ?>
        <a href="?action=quitar-demo&t=<?= csrf_token() ?>" style="margin-left:8px"
           data-confirm="<?= e(t('inst_demo')) ?>?"><?= e(t('delete')) ?> →</a>
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
                 data-confirm="<?= e(t('admin_confirm_delete_purchase', (int)$p['num'])) ?>"><?= e(t('delete')) ?></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($deleted): ?>
    <h3 style="margin-top:28px">🗑️ <?= e(t('admin_deleted_purchases')) ?></h3>
    <p class="section-sub"><?= e(t('admin_deleted_purchases_hint')) ?></p>
    <div class="table-wrap">
      <table class="compras">
        <thead>
          <tr>
            <th><?= e(t('th_num')) ?></th>
            <th><?= e(t('th_date')) ?></th>
            <th><?= e(t('th_amount', $cur)) ?></th>
            <th><?= e(t('th_sats')) ?></th>
            <th><?= e(t('admin_actions')) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($deleted as $p): ?>
          <tr style="opacity:0.7">
            <td><?= (int)$p['num'] ?></td>
            <td><?= e(fmt_fecha($p['fecha'])) ?></td>
            <td><?= money($p['monto_local']) ?></td>
            <td><?= fmt_int($p['sats']) ?></td>
            <td><a href="?y=<?= $selYear ?>&action=restaurar&id=<?= (int)$p['id'] ?>&t=<?= csrf_token() ?>"><?= e(t('restore')) ?></a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php page_foot(); ?>
