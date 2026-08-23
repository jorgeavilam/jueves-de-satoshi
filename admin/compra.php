<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$editing = isset($_GET['id']);
$compra  = null;
$yearRow = null;

if ($editing) {
    $st = db()->prepare('SELECT p.*, y.year FROM purchases p JOIN years y ON y.id = p.year_id WHERE p.id = ?');
    $st->execute([(int)$_GET['id']]);
    $compra = $st->fetch();
    if (!$compra) die(t('not_found'));
    $yearRow = get_year((int)$compra['year'], true);
} else {
    $yearRow = get_year((int)($_GET['y'] ?? date('Y')));
    if (!$yearRow) die(t('admin_no_year', (int)($_GET['y'] ?? date('Y'))));
}

// Sugerencias para el alta: siguiente número y siguiente día de compra
$prices = live_prices();
$next   = ['num' => 1, 'fecha' => next_purchase_date()];
if (!$editing) {
    $st = db()->prepare('SELECT MAX(num) AS m, MAX(fecha) AS f FROM purchases WHERE year_id = ?');
    $st->execute([(int)$yearRow['id']]);
    $last = $st->fetch();
    if ($last && $last['m']) {
        $next['num']   = (int)$last['m'] + 1;
        $next['fecha'] = next_purchase_date($last['f']);
    }
}

$cur   = currency_code();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $fecha  = $_POST['fecha'] ?? '';
    $num    = (int)($_POST['num'] ?? 0);
    $monto  = (float)($_POST['monto_local'] ?? 0);
    $compct = (float)($_POST['comision_pct'] ?? 0) / 100; // se captura en %
    $precio = (float)($_POST['precio_local_btc'] ?? 0);
    // SE REGISTRA el tipo de cambio; el monto en USD se CALCULA
    $tc     = is_usd_site() ? 1.0 : (float)($_POST['tipo_cambio_usd'] ?? 0);
    $sats   = (int)($_POST['sats'] ?? 0);
    $url    = trim($_POST['x_post_url'] ?? '');
    $notas  = trim($_POST['notas'] ?? '');

    if (!$fecha || $num < 1 || $monto <= 0 || $precio <= 0 || $tc <= 0 || $sats < 1) {
        $error = t('admin_err_purchase');
    } elseif ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
        $error = t('admin_err_url');
    } else {
        try {
            if ($editing) {
                $st = db()->prepare('UPDATE purchases SET num=?, fecha=?, monto_local=?, comision_pct=?, precio_local_btc=?, tipo_cambio_usd=?, sats=?, x_post_url=?, notas=? WHERE id=?');
                $st->execute([$num, $fecha, $monto, $compct, $precio, $tc, $sats, $url ?: null, $notas ?: null, (int)$compra['id']]);
            } else {
                $st = db()->prepare('INSERT INTO purchases (year_id, num, fecha, monto_local, comision_pct, precio_local_btc, tipo_cambio_usd, sats, x_post_url, notas) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $st->execute([(int)$yearRow['id'], $num, $fecha, $monto, $compct, $precio, $tc, $sats, $url ?: null, $notas ?: null]);
            }
            header('Location: ' . SITE_URL . '/admin/index.php?y=' . (int)$yearRow['year'] . '&msg=guardada'); exit;
        } catch (PDOException $ex) {
            $error = strpos($ex->getMessage(), 'uq_year_num') !== false
                ? t('admin_err_dup_num', $num)
                : $ex->getMessage();
        }
    }
}

$v = fn(string $k, $def = '') => e((string)($_POST[$k] ?? ($compra[$k] ?? $def)));

page_head($editing ? t('admin_edit_purchase', (int)$compra['num']) : t('admin_add_purchase'), '', true);
admin_chrome('compras');
?>
<section class="block">
  <div class="container" style="max-width:780px">
    <h2><?= $editing ? '✏️ ' . e(t('admin_edit_purchase', (int)$compra['num'])) : '➕ ' . e(t('admin_add_purchase')) ?>
        <span class="brand-accent"><?= (int)$yearRow['year'] ?></span></h2>
    <p class="section-sub">
      <?php if (!is_usd_site()): ?><?= t('admin_rate_registered') ?><br><?php endif; ?>
      <?= e(t('admin_live_now', fmt_num($prices['btc_usd'], 0), fmt_num($prices['usd_local'], 4))) ?>
    </p>

    <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>

    <div class="form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-grid">
          <div class="form-group">
            <label><?= e(t('admin_f_num')) ?></label>
            <input type="number" name="num" min="1" required value="<?= $v('num', $next['num']) ?>">
          </div>
          <div class="form-group">
            <label><?= e(t('admin_f_date', day_name(purchase_day()))) ?></label>
            <input type="date" name="fecha" required value="<?= $v('fecha', $next['fecha']) ?>">
          </div>
          <div class="form-group">
            <label><?= e(t('admin_f_amount', $cur)) ?></label>
            <input type="number" name="monto_local" step="0.01" min="0.01" required id="fMonto"
                   value="<?= $v('monto_local', $yearRow['weekly_amount']) ?>">
          </div>
          <div class="form-group">
            <label><?= e(t('admin_f_fee')) ?></label>
            <input type="number" name="comision_pct" step="0.01" min="0" required
                   value="<?= e($_POST['comision_pct'] ?? ($compra ? rtrim(rtrim(number_format($compra['comision_pct'] * 100, 2, '.', ''), '0'), '.') : '1')) ?>">
          </div>
          <div class="form-group">
            <label><?= e(t('admin_f_price', $cur)) ?></label>
            <input type="number" name="precio_local_btc" step="0.01" min="1" required id="fPrecio"
                   value="<?= $v('precio_local_btc') ?>" placeholder="<?= e(fmt_num($prices['btc_local'], 0)) ?>">
          </div>
          <?php if (!is_usd_site()): ?>
          <div class="form-group">
            <label><?= e(t('admin_f_rate', $cur)) ?></label>
            <input type="number" name="tipo_cambio_usd" step="0.0001" min="0.0001" required id="fTC"
                   value="<?= $v('tipo_cambio_usd') ?>" placeholder="<?= e(fmt_num($prices['usd_local'], 4)) ?>">
          </div>
          <?php endif; ?>
          <div class="form-group">
            <label><?= e(t('admin_f_sats')) ?></label>
            <input type="number" name="sats" min="1" required value="<?= $v('sats') ?>">
          </div>
          <?php if (!is_usd_site()): ?>
          <div class="form-group">
            <label><?= e(t('admin_f_usd_calc')) ?></label>
            <input type="text" id="fUSD" disabled style="background:var(--bg-soft)">
          </div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label><?= e(t('admin_f_post')) ?></label>
          <input type="url" name="x_post_url" maxlength="255" value="<?= $v('x_post_url') ?>" placeholder="https://">
        </div>
        <div class="form-group">
          <label><?= e(t('admin_f_notes')) ?></label>
          <input type="text" name="notas" maxlength="500" value="<?= $v('notas') ?>">
        </div>
        <button class="btn" type="submit"><?= e($editing ? t('save') : t('admin_add_purchase')) ?></button>
        <a class="btn btn-outline" href="<?= SITE_URL ?>/admin/index.php?y=<?= (int)$yearRow['year'] ?>"><?= e(t('cancel')) ?></a>
      </form>
    </div>
  </div>
</section>

<script>
// Vista previa del USD calculado: monto / tipo de cambio
(function () {
  var m = document.getElementById('fMonto'), tc = document.getElementById('fTC'),
      p = document.getElementById('fPrecio'), out = document.getElementById('fUSD');
  if (!m || !tc || !out) return;
  var loc = <?= json_encode(tr('locale_js')) ?>;
  function upd() {
    var monto = parseFloat(m.value), t = parseFloat(tc.value), precio = parseFloat(p.value), txt = '';
    if (monto > 0 && t > 0) txt = '$' + (monto / t).toFixed(2) + ' USD';
    if (precio > 0 && t > 0) txt += (txt ? '  ·  ' : '') + 'BTC: $' + (precio / t).toLocaleString(loc, { maximumFractionDigits: 0 }) + ' USD';
    out.value = txt;
  }
  [m, tc, p].forEach(function (el) { if (el) el.addEventListener('input', upd); });
  upd();
})();
</script>
<?php page_foot(); ?>
