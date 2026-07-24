<?php
require_once __DIR__ . '/includes/layout.php';

$selYear = (int)($_GET['y'] ?? date('Y'));
$yearRow = get_year($selYear);
if (!$yearRow) { header('Location: ' . SITE_URL . '/'); exit; }

$prices = live_prices();
$purchases = get_purchases((int)$yearRow['id']);
$ys = year_summary($purchases, $prices);
$planned = planned_thursdays($yearRow, $purchases);
$years = get_years();

page_head('Año ' . $selYear, "Dashboard del año $selYear del ejercicio Jueves de Satoshi: compras semanales de Bitcoin, gráficas del recorrido e histórico completo.");
?>

<section class="hero hero-year">
  <div class="container">
    <h1>Jueves de Satoshi <span class="brand-accent"><?= $selYear ?></span></h1>
    <p class="lead">$<?= fmt_money($yearRow['weekly_amount_mxn'], 0) ?> MXN cada jueves · <?= $ys['compras'] ?> de <?= $planned ?> compras realizadas</p>
    <div class="year-progress big">
      <div class="year-progress-bar" style="width:<?= $planned ? round($ys['compras'] / $planned * 100, 1) : 0 ?>%"></div>
    </div>
    <p class="kpi-sub" style="margin-top:6px"><?= $planned ? round($ys['compras'] / $planned * 100) : 0 ?>% del año recorrido</p>
    <?php if (count($years) > 1): ?>
    <p style="margin-top:14px">
      <?php foreach ($years as $yr): ?>
        <a class="btn <?= (int)$yr['year'] === $selYear ? '' : 'btn-outline' ?>" style="margin:0 4px" href="?y=<?= (int)$yr['year'] ?>"><?= (int)$yr['year'] ?></a>
      <?php endforeach; ?>
    </p>
    <?php endif; ?>
  </div>
</section>

<?php if (count($purchases)): ?>
<section class="block" id="dashboard">
  <div class="container">
    <h2>Dashboard <span class="brand-accent"><?= $selYear ?></span></h2>
    <p class="section-sub">Explora el recorrido: los sustos de las caídas y lo bonito de las subidas. Haz clic en cualquier punto para abrir el post de esa compra en X.</p>

    <div class="kpi-grid">
      <div class="kpi kpi-highlight">
        <div class="kpi-label">Sats del año</div>
        <div class="kpi-value"><?= fmt_int($ys['sats']) ?></div>
        <div class="kpi-sub"><?= number_format($ys['btc'], 8) ?> BTC</div>
      </div>
      <div class="kpi">
        <div class="kpi-label">Invertido</div>
        <div class="kpi-value">$<?= fmt_money($ys['invertido_mxn'], 0) ?></div>
        <div class="kpi-sub">MXN ($<?= fmt_money($ys['invertido_usd'], 0) ?> USD)</div>
      </div>
      <div class="kpi">
        <div class="kpi-label">Valor actual</div>
        <div class="kpi-value">$<?= fmt_money($ys['valor_mxn'], 0) ?></div>
        <div class="kpi-sub">MXN ($<?= fmt_money($ys['valor_usd'], 0) ?> USD)</div>
      </div>
      <div class="kpi">
        <div class="kpi-label">Rendimiento</div>
        <div class="kpi-value <?= $ys['pnl_mxn'] >= 0 ? 'pos' : 'neg' ?>"><?= fmt_pct($ys['pnl_pct']) ?></div>
        <div class="kpi-sub"><?= ($ys['pnl_mxn'] >= 0 ? '+' : '−') ?>$<?= fmt_money(abs($ys['pnl_mxn']), 0) ?> MXN</div>
      </div>
      <div class="kpi">
        <div class="kpi-label">Promedio sats/compra</div>
        <div class="kpi-value"><?= fmt_int($ys['sats_promedio']) ?></div>
        <div class="kpi-sub">Costo: $<?= number_format($ys['costo_por_sat'], 4) ?> MXN/sat</div>
      </div>
    </div>

    <div class="chart-card">
      <h3>📈 Inversión vs Valor de la posición</h3>
      <div class="chart-hint">Cada punto valúa los sats acumulados al precio que tenía Bitcoin <strong>ese día</strong> — pasa el mouse para ver el PnL de cada fecha.</div>
      <div class="chart-wrap"><canvas id="chartInvValor"></canvas></div>
    </div>

    <div class="chart-card">
      <h3>₿ Precio de Bitcoin en cada compra</h3>
      <div class="chart-hint">El precio en MXN cada jueves de compra.</div>
      <div class="chart-wrap small"><canvas id="chartPrecio"></canvas></div>
    </div>

    <div class="chart-card">
      <h3>⚡ Sats recibidos por compra</h3>
      <div class="chart-hint">Cuando el precio baja, los mismos pesos compran más sats.</div>
      <div class="chart-wrap small"><canvas id="chartSats"></canvas></div>
    </div>

    <div class="chart-card">
      <h3>🏔️ Sats acumulados</h3>
      <div class="chart-hint">La montaña que solo crece: el acumulado semana a semana.</div>
      <div class="chart-wrap small"><canvas id="chartSatsAcum"></canvas></div>
    </div>
  </div>
</section>

<section class="block">
  <div class="container">
    <h2>Histórico de <span class="brand-accent">compras <?= $selYear ?></span></h2>
    <p class="section-sub">Cada compra está documentada públicamente en X con sus testigos de transacción.</p>
    <div class="table-wrap">
      <table class="compras">
        <thead>
          <tr>
            <th>#</th><th>Fecha</th><th>Monto (MXN)</th><th>Comisión</th><th>Neto (MXN)</th>
            <th>Precio BTC (MXN)</th><th>Precio BTC (USD)</th><th>TC USD/MXN</th><th>Monto (USD)</th>
            <th>Sats</th><th>Sats acum.</th><th>Post</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_reverse($purchases) as $p): ?>
          <tr>
            <td><?= (int)$p['num'] ?></td>
            <td><?= fmt_fecha($p['fecha']) ?></td>
            <td>$<?= fmt_money($p['monto_mxn'], 0) ?></td>
            <td><?= number_format($p['comision_pct'] * 100, 2) ?>%</td>
            <td>$<?= fmt_money($p['monto_neto']) ?></td>
            <td>$<?= fmt_money($p['precio_mxn_btc'], 0) ?></td>
            <td>$<?= fmt_money($p['precio_usd_btc'], 0) ?></td>
            <td><?= number_format($p['tipo_cambio'], 2) ?></td>
            <td>$<?= fmt_money($p['monto_usd']) ?></td>
            <td><?= fmt_int($p['sats']) ?></td>
            <td><?= fmt_int($p['sats_acum']) ?></td>
            <td><?php if ($p['x_post_url']): ?><a class="x-link" href="<?= e($p['x_post_url']) ?>" target="_blank" rel="noopener">𝕏</a><?php endif; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<script>
window.JDS = <?= json_encode([
    'labels' => array_map(fn($p) => fmt_fecha_corta($p['fecha']), $purchases),
    'inversion' => array_map(fn($p) => round($p['inversion_acum_mxn'], 2), $purchases),
    'valor' => array_map(fn($p) => round($p['valor_acum_mxn'], 2), $purchases),
    'precioBtcMxn' => array_map(fn($p) => (float)$p['precio_mxn_btc'], $purchases),
    'sats' => array_map(fn($p) => (int)$p['sats'], $purchases),
    'satsAcum' => array_map(fn($p) => (int)$p['sats_acum'], $purchases),
    'urls' => array_map(fn($p) => $p['x_post_url'], $purchases),
], JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/charts.js"></script>
<?php else: ?>
<section class="block"><div class="container">
  <div class="alert alert-ok">Este año aún no tiene compras registradas. ¡El viaje está por comenzar! 🚀</div>
</div></section>
<?php endif; ?>

<?php page_foot(); ?>
