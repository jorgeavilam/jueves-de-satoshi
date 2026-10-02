<?php
require_once __DIR__ . '/includes/layout.php';
public_gate();

// En modo bóveda no hay dashboards públicos, pero el dueño sí entra al suyo.
if (privacy_mode() === 'vault' && !viewer_is_owner()) { header('Location: ' . SITE_URL . '/'); exit; }

$selYear = (int)($_GET['y'] ?? date('Y'));

// Con URLs limpias, la dirección vieja manda a la nueva con un 301: Google le
// pasa la autoridad a /2026 y no quedan dos URLs para el mismo año.
$reqPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (clean_urls() && isset($_GET['y']) && substr($reqPath, -9) === '/year.php') {
    header('Location: ' . year_url($selYear), true, 301); exit;
}
$yearRow = get_year($selYear);
if (!$yearRow) { header('Location: ' . SITE_URL . '/'); exit; }

$prices    = live_prices();
$purchases = get_purchases((int)$yearRow['id']);
$ys        = year_summary($purchases, $prices);
$planned   = planned_purchases($yearRow, $purchases);
$years     = get_years();
$prog      = $planned ? min(100, $ys['compras'] / $planned * 100) : 0;
$cur       = currency_code();
$dayName   = day_name(purchase_day());

// La misma frase va en la meta description, en la página y en el JSON-LD
$summary = count($purchases)
    ? year_summary_text($selYear, $ys, $planned, (float)$yearRow['weekly_amount'], show_amounts())
    : '';
$ld = [];
if (count($purchases)) {
    $vars = show_amounts()
        ? [t('ld_var_date'), t('ld_var_amount'), t('ld_var_price'), t('ld_var_sats')]
        : [t('ld_var_date'), t('ld_var_price')];
    if (!is_usd_site()) $vars[] = t('ld_var_fx');
    $ld[] = [
        '@type'               => 'Dataset',
        'name'                => t('year_page_title', (string)$selYear) . ' — ' . site_name(),
        'description'         => $summary,
        'url'                 => canonical_url(),
        'temporalCoverage'    => $purchases[0]['fecha'] . '/' . end($purchases)['fecha'],
        'creator'             => ['@id' => SITE_URL . '/#owner'],
        'isAccessibleForFree' => true,
        'variableMeasured'    => $vars,
    ];
}
page_head(t('year_page_title', (string)$selYear), count($purchases) ? year_summary_text($selYear, $ys, $planned, (float)$yearRow['weekly_amount'], show_amounts(), false) : '', false, $ld);
?>

<section class="hero">
  <div class="container">
    <h1><?= brand_name_html() ?> <span class="brand-accent"><?= $selYear ?></span></h1>
    <p class="lead">
      <?= show_amounts()
          ? e(t('year_hero_sub', money_full($yearRow['weekly_amount']), $dayName, $ys['compras'], $planned))
          : e(t('kpi_purchases')) . ': ' . fmt_int($ys['compras']) . ' ' . e(t('of_planned', $planned)) ?>
    </p>
    <div class="year-progress big"><div class="year-progress-bar" style="width:<?= round($prog, 1) ?>%"></div></div>
    <p class="kpi-sub" style="margin-top:6px"><?= e(t('year_pct_done', fmt_num($prog, 0))) ?></p>
    <?php if (count($years) > 1): ?>
    <p class="year-switch">
      <?php foreach ($years as $yr): ?>
        <a class="btn <?= (int)$yr['year'] === $selYear ? '' : 'btn-outline' ?>" href="<?= e(year_url((int)$yr['year'])) ?>"><?= (int)$yr['year'] ?></a>
      <?php endforeach; ?>
    </p>
    <?php endif; ?>
  </div>
</section>

<?php if (!count($purchases)): ?>
<section class="block"><div class="container">
  <div class="alert alert-ok"><?= e(t('year_empty')) ?></div>
</div></section>

<?php else: ?>
<section class="block" id="dashboard">
  <div class="container">
    <h2><?= e(t('dashboard_title', (string)$selYear)) ?></h2>
    <p class="year-summary"><?= e($summary) ?></p>
    <p class="section-sub"><?= show_amounts() ? e(t('dashboard_sub')) : e(t('privacy_pct_note')) ?></p>

    <?php if (show_amounts()): ?>
    <div class="kpi-grid">
      <div class="kpi kpi-highlight">
        <div class="kpi-label"><?= e(t('kpi_year_sats')) ?></div>
        <div class="kpi-value"><?= fmt_int($ys['sats']) ?></div>
        <div class="kpi-sub"><?= fmt_num($ys['btc'], 8) ?> BTC</div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><?= e(t('kpi_invested')) ?></div>
        <div class="kpi-value"><?= money($ys['invertido']) ?></div>
        <div class="kpi-sub"><?= e($cur) ?><?= is_usd_site() ? '' : ' · ' . money_usd($ys['invertido_usd'], 0) ?></div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><?= e(t('kpi_value')) ?></div>
        <div class="kpi-value"><?= money($ys['valor']) ?></div>
        <div class="kpi-sub"><?= e($cur) ?><?= is_usd_site() ? '' : ' · ' . money_usd($ys['valor_usd'], 0) ?></div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><?= e(t('kpi_return')) ?></div>
        <div class="kpi-value <?= $ys['pnl'] >= 0 ? 'pos' : 'neg' ?>"><?= fmt_pct($ys['pnl_pct']) ?></div>
        <div class="kpi-sub <?= $ys['pnl'] >= 0 ? 'pos' : 'neg' ?>"><?= ($ys['pnl'] >= 0 ? '+' : '−') . money(abs($ys['pnl'])) ?></div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><?= e(t('kpi_avg_sats')) ?></div>
        <div class="kpi-value"><?= fmt_int($ys['sats_promedio']) ?></div>
        <div class="kpi-sub"><?= e(t('kpi_cost_per_sat', money($ys['costo_por_sat'], 4))) ?></div>
      </div>
    </div>

    <div class="chart-card">
      <h3>📈 <?= e(t('chart_inv_value')) ?></h3>
      <div class="chart-hint"><?= t('chart_inv_value_hint') ?></div>
      <div class="chart-wrap"><canvas id="chartInvValor"></canvas></div>
    </div>

    <?php if (!is_usd_site()):
        $signed = function (float $v): string { return ($v >= 0 ? '+' : '−') . money(abs($v)); };
        $ofInv  = function (float $v) use ($ys): string { return $ys['invertido'] > 0 ? t('split_of_inv', fmt_pct($v / $ys['invertido'])) : ''; }; ?>
    <div class="chart-card">
      <h3>🧭 <?= e(t('chart_split')) ?></h3>
      <div class="chart-hint"><?= e(t('chart_split_hint', $cur)) ?></div>
      <div class="fx-split">
        <div>
          <div class="kpi-label"><?= e(t('split_btc')) ?></div>
          <div class="kpi-value <?= $ys['efecto_btc'] >= 0 ? 'pos' : 'neg' ?>"><?= $signed($ys['efecto_btc']) ?></div>
          <div class="kpi-sub"><?= e($ofInv($ys['efecto_btc'])) ?></div>
        </div>
        <div>
          <div class="kpi-label"><?= e(t('split_fx')) ?></div>
          <div class="kpi-value <?= $ys['efecto_fx'] >= 0 ? 'pos' : 'neg' ?>"><?= $signed($ys['efecto_fx']) ?></div>
          <div class="kpi-sub"><?= e($ofInv($ys['efecto_fx'])) ?></div>
        </div>
        <div>
          <div class="kpi-label"><?= e(t('split_fx_rate')) ?></div>
          <div class="kpi-value"><?= fmt_num($ys['tc_promedio'], 2) ?> → <?= fmt_num($ys['tc_hoy'], 2) ?></div>
          <div class="kpi-sub"><?= e(t('split_fx_rate_sub', $cur)) ?></div>
        </div>
      </div>
      <div class="chart-wrap small"><canvas id="chartEfectos"></canvas></div>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="kpi-grid">
      <div class="kpi kpi-highlight">
        <div class="kpi-label"><?= e(t('kpi_compliance')) ?></div>
        <div class="kpi-value"><?= fmt_num($prog, 0) ?>%</div>
        <div class="kpi-sub"><?= fmt_int($ys['compras']) ?> / <?= fmt_int($planned) ?></div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><?= e(t('kpi_streak')) ?></div>
        <div class="kpi-value"><?= fmt_int($ys['streak']) ?></div>
        <div class="kpi-sub"><?= e(t('kpi_streak_n', $ys['streak'])) ?></div>
      </div>
    </div>
    <?php endif; ?>

    <div class="chart-card">
      <h3>₿ <?= e(t('chart_price')) ?></h3>
      <div class="chart-hint"><?= e(t('chart_price_hint', $cur, $dayName)) ?></div>
      <div class="chart-wrap small"><canvas id="chartPrecio"></canvas></div>
    </div>

    <?php if (!is_usd_site()): ?>
    <div class="chart-card">
      <h3>💱 <?= e(t('chart_fx')) ?></h3>
      <div class="chart-hint"><?= e(t('chart_fx_hint', $cur, $dayName)) ?></div>
      <div class="chart-wrap small"><canvas id="chartTC"></canvas></div>
    </div>
    <?php endif; ?>

    <?php if (show_amounts()): ?>
    <div class="chart-card">
      <h3>⚡ <?= e(t('chart_sats')) ?></h3>
      <div class="chart-hint"><?= e(t('chart_sats_hint')) ?></div>
      <div class="chart-wrap small"><canvas id="chartSats"></canvas></div>
    </div>

    <div class="chart-card">
      <h3>🏔️ <?= e(t('chart_sats_acum')) ?></h3>
      <div class="chart-hint"><?= e(t('chart_sats_acum_hint')) ?></div>
      <div class="chart-wrap small"><canvas id="chartSatsAcum"></canvas></div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if (show_amounts()): ?>
<section class="block">
  <div class="container">
    <h2><?= e(t('history_title', (string)$selYear)) ?></h2>
    <p class="section-sub"><?= e(t('history_sub')) ?></p>
    <div class="table-wrap">
      <table class="compras">
        <thead>
          <tr>
            <th><?= e(t('th_num')) ?></th>
            <th><?= e(t('th_date')) ?></th>
            <th><?= e(t('th_amount', $cur)) ?></th>
            <th><?= e(t('th_fee')) ?></th>
            <th><?= e(t('th_net', $cur)) ?></th>
            <th><?= e(t('th_price', $cur)) ?></th>
            <?php if (!is_usd_site()): ?>
            <th><?= e(t('th_price_usd')) ?></th>
            <th><?= e(t('th_rate', $cur)) ?></th>
            <th><?= e(t('th_amount_usd')) ?></th>
            <?php endif; ?>
            <th><?= e(t('th_sats')) ?></th>
            <th><?= e(t('th_sats_acum')) ?></th>
            <th><?= e(t('th_post')) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_reverse($purchases) as $p): ?>
          <tr>
            <td><?= (int)$p['num'] ?></td>
            <td><?= e(fmt_fecha($p['fecha'])) ?></td>
            <td><?= money($p['monto_local']) ?></td>
            <td><?= fmt_num($p['comision_pct'] * 100, 2) ?>%</td>
            <td><?= money($p['monto_neto'], 2) ?></td>
            <td><?= money($p['precio_local_btc']) ?></td>
            <?php if (!is_usd_site()): ?>
            <td><?= money_usd($p['precio_usd_btc'], 0) ?></td>
            <td><?= fmt_num($p['tipo_cambio_usd'], 2) ?></td>
            <td><?= money_usd($p['monto_usd']) ?></td>
            <?php endif; ?>
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
<?php endif; ?>

<script>
window.JDS = <?= json_encode([
    'locale'    => tr('locale_js'),
    'symbol'    => currency_symbol(),
    'currency'  => $cur,
    'amounts'   => show_amounts(),
    'labels'    => array_map(fn($p) => fmt_fecha_corta($p['fecha']), $purchases),
    'inversion' => show_amounts() ? array_map(fn($p) => round($p['inversion_acum'], 2), $purchases) : [],
    'valor'     => show_amounts() ? array_map(fn($p) => round($p['valor_acum'], 2), $purchases) : [],
    'precioBtc' => array_map(fn($p) => (float)$p['precio_local_btc'], $purchases),
    'tipoCambio'=> is_usd_site() ? [] : array_map(fn($p) => (float)$p['tipo_cambio_usd'], $purchases),
    'sats'      => show_amounts() ? array_map(fn($p) => (int)$p['sats'], $purchases) : [],
    'satsAcum'  => show_amounts() ? array_map(fn($p) => (int)$p['sats_acum'], $purchases) : [],
    'efectoBtc' => show_amounts() && !is_usd_site() ? array_map(fn($p) => round($p['efecto_btc_acum'], 2), $purchases) : [],
    'efectoFx'  => show_amounts() && !is_usd_site() ? array_map(fn($p) => round($p['efecto_fx_acum'], 2), $purchases) : [],
    'urls'      => show_amounts() ? array_map(fn($p) => $p['x_post_url'], $purchases) : [],
    'i18n'      => [
        'inv'      => t('inv_label', $cur),
        'val'      => t('val_label', $cur),
        'price'    => t('price_label', $cur),
        'fx'       => t('fx_label', $cur),
        'splitBtc' => t('split_btc'),
        'splitFx'  => t('split_fx'),
        'splitTot' => t('split_total'),
        'sats'     => t('sats_label'),
        'satsAcum' => t('sats_acum_label'),
        'clickPost'=> t('th_post'),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/charts.js?v=<?= e(JDS_VERSION) ?>"></script>
<?php endif; ?>

<?php page_foot(); ?>
