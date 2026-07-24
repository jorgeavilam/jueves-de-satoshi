<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$editing = isset($_GET['id']);
$compra = null;
$yearRow = null;

if ($editing) {
    $st = db()->prepare('SELECT p.*, y.year FROM purchases p JOIN years y ON y.id = p.year_id WHERE p.id = ?');
    $st->execute([(int)$_GET['id']]);
    $compra = $st->fetch();
    if (!$compra) die('Compra no encontrada.');
    $yearRow = get_year((int)$compra['year']);
} else {
    $yearRow = get_year((int)($_GET['y'] ?? date('Y')));
    if (!$yearRow) die('Año no encontrado. Créalo primero en Años.');
}

// Sugerencias para alta: siguiente número, jueves siguiente y precio en vivo
$prices = live_prices();
$next = ['num' => 1, 'fecha' => date('Y-m-d', strtotime('thursday this week'))];
if (!$editing) {
    $st = db()->prepare('SELECT MAX(num) AS m, MAX(fecha) AS f FROM purchases WHERE year_id = ?');
    $st->execute([(int)$yearRow['id']]);
    $last = $st->fetch();
    if ($last && $last['m']) {
        $next['num'] = (int)$last['m'] + 1;
        $next['fecha'] = date('Y-m-d', strtotime($last['f'] . ' +7 days'));
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $fecha   = $_POST['fecha'] ?? '';
    $num     = (int)($_POST['num'] ?? 0);
    $monto   = (float)($_POST['monto_mxn'] ?? 0);
    $compct  = (float)($_POST['comision_pct'] ?? 0) / 100; // capturada en %
    $precio  = (float)($_POST['precio_mxn_btc'] ?? 0);
    $tc      = (float)($_POST['tipo_cambio'] ?? 0); // SE REGISTRA el tipo de cambio; el USD se calcula
    $sats    = (int)($_POST['sats'] ?? 0);
    $url     = trim($_POST['x_post_url'] ?? '');
    $notas   = trim($_POST['notas'] ?? '');

    if (!$fecha || $num < 1 || $monto <= 0 || $precio <= 0 || $tc <= 0 || $sats < 1) {
        $error = 'Revisa los campos: fecha, número, monto, precio BTC, tipo de cambio y sats son obligatorios.';
    } elseif ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
        $error = 'El link del post de X no es una URL válida.';
    } else {
        try {
            if ($editing) {
                $st = db()->prepare('UPDATE purchases SET num=?, fecha=?, monto_mxn=?, comision_pct=?, precio_mxn_btc=?, tipo_cambio=?, sats=?, x_post_url=?, notas=? WHERE id=?');
                $st->execute([$num, $fecha, $monto, $compct, $precio, $tc, $sats, $url ?: null, $notas ?: null, (int)$compra['id']]);
            } else {
                $st = db()->prepare('INSERT INTO purchases (year_id, num, fecha, monto_mxn, comision_pct, precio_mxn_btc, tipo_cambio, sats, x_post_url, notas) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $st->execute([(int)$yearRow['id'], $num, $fecha, $monto, $compct, $precio, $tc, $sats, $url ?: null, $notas ?: null]);
            }
            header('Location: ' . SITE_URL . '/admin/index.php?y=' . (int)$yearRow['year'] . '&msg=guardada'); exit;
        } catch (PDOException $ex) {
            $error = str_contains($ex->getMessage(), 'uq_year_num')
                ? "Ya existe la compra #$num en este año."
                : 'Error al guardar: ' . $ex->getMessage();
        }
    }
}

$v = fn(string $k, $def = '') => e($_POST[$k] ?? ($compra[$k] ?? $def));

page_head(($editing ? 'Editar' : 'Registrar') . ' compra');
?>
<div class="admin-bar">
  <div class="container">
    <span><a href="<?= SITE_URL ?>/admin/index.php?y=<?= (int)$yearRow['year'] ?>">← Volver a compras</a></span>
    <span>Año <?= (int)$yearRow['year'] ?></span>
  </div>
</div>

<section class="block">
  <div class="container" style="max-width:760px">
    <h2><?= $editing ? '✏️ Editar compra #' . (int)$compra['num'] : '➕ Registrar compra' ?> <span class="brand-accent"><?= (int)$yearRow['year'] ?></span></h2>
    <p class="section-sub">Se registra el <strong>tipo de cambio</strong> y el monto en USD se <strong>calcula automáticamente</strong>.
       Precio en vivo: BTC $<?= fmt_money($prices['btc_usd']) ?> USD · TC <?= number_format($prices['usd_mxn'], 4) ?></p>

    <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>

    <div class="form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-grid">
          <div class="form-group">
            <label># de compra</label>
            <input type="number" name="num" min="1" required value="<?= $v('num', $next['num']) ?>">
          </div>
          <div class="form-group">
            <label>Fecha (jueves)</label>
            <input type="date" name="fecha" required value="<?= $v('fecha', $next['fecha']) ?>">
          </div>
          <div class="form-group">
            <label>Monto de compra (MXN)</label>
            <input type="number" name="monto_mxn" step="0.01" min="1" required value="<?= $v('monto_mxn', $yearRow['weekly_amount_mxn']) ?>" id="fMonto">
          </div>
          <div class="form-group">
            <label>Comisión (%)</label>
            <input type="number" name="comision_pct" step="0.01" min="0" required
                   value="<?= e($_POST['comision_pct'] ?? ($compra ? rtrim(rtrim(number_format($compra['comision_pct'] * 100, 2), '0'), '.') : '2')) ?>">
          </div>
          <div class="form-group">
            <label>Precio BTC (MXN)</label>
            <input type="number" name="precio_mxn_btc" step="0.01" min="1" required value="<?= $v('precio_mxn_btc') ?>" id="fPrecio"
                   placeholder="ej. <?= round($prices['btc_mxn']) ?>">
          </div>
          <div class="form-group">
            <label>Tipo de cambio (USD/MXN) — se registra</label>
            <input type="number" name="tipo_cambio" step="0.0001" min="0.0001" required value="<?= $v('tipo_cambio') ?>" id="fTC"
                   placeholder="ej. <?= number_format($prices['usd_mxn'], 4) ?>">
          </div>
          <div class="form-group">
            <label>Sats recibidos</label>
            <input type="number" name="sats" min="1" required value="<?= $v('sats') ?>">
          </div>
          <div class="form-group">
            <label>Monto en USD (calculado)</label>
            <input type="text" id="fUSD" disabled style="background:var(--bg-soft)">
          </div>
        </div>
        <div class="form-group">
          <label>Link al post en X (opcional)</label>
          <input type="url" name="x_post_url" maxlength="255" value="<?= $v('x_post_url') ?>" placeholder="https://x.com/...">
        </div>
        <div class="form-group">
          <label>Notas (opcional)</label>
          <input type="text" name="notas" maxlength="500" value="<?= $v('notas') ?>">
        </div>
        <button class="btn" type="submit"><?= $editing ? 'Guardar cambios' : 'Registrar compra' ?></button>
      </form>
    </div>
  </div>
</section>

<script>
// Vista previa en vivo del USD calculado (monto / tipo de cambio)
(function () {
  var m = document.getElementById('fMonto'), tc = document.getElementById('fTC'),
      p = document.getElementById('fPrecio'), out = document.getElementById('fUSD');
  function upd() {
    var monto = parseFloat(m.value), t = parseFloat(tc.value), precio = parseFloat(p.value);
    var txt = '';
    if (monto > 0 && t > 0) txt = '$' + (monto / t).toFixed(2) + ' USD';
    if (precio > 0 && t > 0) txt += (txt ? '  ·  ' : '') + 'BTC: $' + (precio / t).toLocaleString('es-MX', {maximumFractionDigits: 0}) + ' USD';
    out.value = txt;
  }
  [m, tc, p].forEach(function (el) { el.addEventListener('input', upd); });
  upd();
})();
</script>
<?php page_foot(); ?>
