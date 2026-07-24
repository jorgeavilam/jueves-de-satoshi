<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/layout.php';

// Logout
if (($_GET['action'] ?? '') === 'logout') { logout(); header('Location: ' . SITE_URL . '/'); exit; }

// Login
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!try_login($_POST['usuario'] ?? '', $_POST['password'] ?? '')) {
        usleep(500000); // frena fuerza bruta
        $loginError = 'Usuario o contraseña incorrectos.';
    } else {
        header('Location: ' . SITE_URL . '/admin/index.php'); exit;
    }
}

if (!is_logged_in()) {
    page_head('Admin — Acceso');
    ?>
    <div class="container login-box">
      <div class="form-card">
        <h2 style="margin-bottom:16px">🔐 Acceso de administración</h2>
        <?php if ($loginError): ?><div class="alert alert-err"><?= e($loginError) ?></div><?php endif; ?>
        <form method="post">
          <div class="form-group"><label>Usuario</label><input type="text" name="usuario" required autofocus></div>
          <div class="form-group"><label>Contraseña</label><input type="password" name="password" required></div>
          <button class="btn" type="submit" name="login" value="1">Entrar</button>
        </form>
      </div>
    </div>
    <?php
    page_foot(); exit;
}

// --- Panel ---
$years = get_years();
$selYear = isset($_GET['y']) ? (int)$_GET['y'] : (count($years) ? (int)end($years)['year'] : (int)date('Y'));
$yearRow = get_year($selYear);

// Borrar compra
if (($_GET['action'] ?? '') === 'borrar' && isset($_GET['id'])) {
    if (!hash_equals(csrf_token(), $_GET['t'] ?? '')) die('Token inválido.');
    $st = db()->prepare('DELETE FROM purchases WHERE id = ?');
    $st->execute([(int)$_GET['id']]);
    header('Location: ' . SITE_URL . '/admin/index.php?y=' . $selYear . '&msg=borrada'); exit;
}

$purchases = $yearRow ? get_purchases((int)$yearRow['id']) : [];
$msg = $_GET['msg'] ?? '';

page_head('Admin — Compras');
?>
<div class="admin-bar">
  <div class="container">
    <span>Panel de administración — <?= e(SITE_NAME) ?></span>
    <span>
      <a href="<?= SITE_URL ?>/admin/anio.php">Años</a> ·
      <a href="<?= SITE_URL ?>/admin/password.php">Contraseña</a> ·
      <a href="<?= SITE_URL ?>/" target="_blank">Ver sitio</a> ·
      <a href="?action=logout">Salir</a>
    </span>
  </div>
</div>

<section class="block">
  <div class="container">
    <h2>Compras <span class="brand-accent"><?= $selYear ?></span></h2>
    <p class="section-sub">
      <?php foreach ($years as $yr): ?>
        <a href="?y=<?= (int)$yr['year'] ?>" <?= (int)$yr['year'] === $selYear ? 'style="font-weight:800;color:var(--orange)"' : '' ?>><?= (int)$yr['year'] ?></a> &nbsp;
      <?php endforeach; ?>
    </p>

    <?php if ($msg === 'guardada'): ?><div class="alert alert-ok">✅ Compra guardada.</div><?php endif; ?>
    <?php if ($msg === 'borrada'): ?><div class="alert alert-ok">🗑️ Compra eliminada.</div><?php endif; ?>

    <?php if (!$yearRow): ?>
      <div class="alert alert-err">No existe el año <?= $selYear ?>. <a href="<?= SITE_URL ?>/admin/anio.php">Créalo aquí</a>.</div>
    <?php else: ?>
    <p style="margin-bottom:18px">
      <a class="btn" href="<?= SITE_URL ?>/admin/compra.php?y=<?= $selYear ?>">➕ Registrar compra</a>
    </p>

    <div class="table-wrap">
      <table class="compras">
        <thead>
          <tr><th>#</th><th>Fecha</th><th>Monto</th><th>Com. %</th><th>Precio BTC MXN</th><th>TC</th><th>USD (calc)</th><th>Sats</th><th>Post X</th><th>Acciones</th></tr>
        </thead>
        <tbody>
          <?php foreach (array_reverse($purchases) as $p): ?>
          <tr>
            <td><?= (int)$p['num'] ?></td>
            <td><?= fmt_fecha($p['fecha']) ?></td>
            <td>$<?= fmt_money($p['monto_mxn'], 0) ?></td>
            <td><?= number_format($p['comision_pct'] * 100, 2) ?>%</td>
            <td>$<?= fmt_money($p['precio_mxn_btc'], 0) ?></td>
            <td><?= number_format($p['tipo_cambio'], 4) ?></td>
            <td>$<?= fmt_money($p['monto_usd']) ?></td>
            <td><?= fmt_int($p['sats']) ?></td>
            <td><?php if ($p['x_post_url']): ?><a class="x-link" href="<?= e($p['x_post_url']) ?>" target="_blank">𝕏</a><?php endif; ?></td>
            <td>
              <a href="<?= SITE_URL ?>/admin/compra.php?id=<?= (int)$p['id'] ?>">Editar</a> ·
              <a href="?y=<?= $selYear ?>&action=borrar&id=<?= (int)$p['id'] ?>&t=<?= csrf_token() ?>"
                 onclick="return confirm('¿Eliminar la compra #<?= (int)$p['num'] ?>?')">Borrar</a>
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
