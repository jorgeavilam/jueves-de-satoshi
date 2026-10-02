<?php
require_once __DIR__ . '/includes/layout.php';
public_gate();

$prices = live_prices();
$years  = get_years();

// Acumulado global y recolección del historial de precios para la montaña rusa
$global = ['sats' => 0, 'inv' => 0.0, 'compras' => 0, 'planned' => 0];
$yearCards = [];
$allPurchases = [];
foreach ($years as $yr) {
    $p = get_purchases((int)$yr['id']);
    $s = year_summary($p, $prices);
    $planned = planned_purchases($yr, $p);
    $global['sats']    += $s['sats'];
    $global['inv']     += $s['invertido'];
    $global['compras'] += $s['compras'];
    $global['planned'] += $planned;
    $yearCards[] = ['year' => $yr, 'sum' => $s, 'planned' => $planned];
    $allPurchases = array_merge($allPurchases, $p);
}
usort($allPurchases, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
$track = array_map(fn($p) => (float)$p['precio_local_btc'], $allPurchases);

$globalVal = $global['sats'] / SATS_PER_BTC * $prices['btc_local'];
$globalPnl = $globalVal - $global['inv'];
$globalPct = $global['inv'] > 0 ? $globalPnl / $global['inv'] : 0;
$globalProg = $global['planned'] ? min(100, $global['compras'] / $global['planned'] * 100) : 0;
$streak = purchase_streak($allPurchases);

/* ---------- Modo bóveda: caja fuerte para el público, detalle para el dueño ---------- */
if (privacy_mode() === 'vault' && !viewer_is_owner()) {
    page_head('', '', true);
    ?>
    <section class="vault">
      <div class="container">
        <svg class="vault-safe" viewBox="0 0 200 170" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="14" y="10" width="172" height="140" rx="12" class="vault-body"/>
          <rect x="28" y="24" width="144" height="112" rx="8" class="vault-door"/>
          <circle cx="100" cy="80" r="26" class="vault-dial"/>
          <circle cx="100" cy="80" r="6" class="vault-pin"/>
          <line x1="100" y1="80" x2="100" y2="58" class="vault-hand"/>
          <line x1="100" y1="80" x2="119" y2="91" class="vault-hand"/>
          <rect x="150" y="70" width="8" height="20" rx="4" class="vault-handle"/>
          <rect x="34" y="150" width="24" height="12" rx="3" class="vault-body"/>
          <rect x="142" y="150" width="24" height="12" rx="3" class="vault-body"/>
        </svg>
        <h1><?= e(t('vault_title')) ?></h1>
        <p class="lead"><?= content('vault_message') ?></p>
        <p class="vault-locked"><?= e(t('vault_locked')) ?></p>
        <p><a class="btn" href="<?= SITE_URL ?>/admin/"><?= e(t('vault_enter')) ?></a></p>
      </div>
    </section>
    <?php
    page_foot();
    exit;
}

$siteSummary = site_summary_text([
    'compras' => $global['compras'], 'planned' => $global['planned'], 'sats' => $global['sats'],
    'inv' => $global['inv'], 'val' => $globalVal, 'streak' => $streak,
    'first' => $allPurchases ? $allPurchases[0]['fecha'] : '',
], show_amounts());
page_head('', $siteSummary);

if (privacy_mode() === 'vault'): ?>
  <div class="container" style="padding-top:18px">
    <div class="alert alert-warn">🔒 <?= e(t('vault_owner_view')) ?></div>
  </div>
<?php endif; ?>

<section class="hero">
  <div class="container">
    <h1><?= content('hero_title', day_name(purchase_day())) ?></h1>
    <p class="lead"><?= content('hero_lead', day_name(purchase_day()), site_name()) ?></p>

    <div class="live-price" title="<?= e($prices['live'] ? t('live_price_label') : t('live_price_stale')) ?>">
      <span><span class="live-dot<?= $prices['live'] ? '' : ' stale' ?>"></span> BTC</span>
      <span><?= money_usd($prices['btc_usd'], 0) ?></span>
      <?php if (!is_usd_site()): ?>
        <span><?= money_full($prices['btc_local'], 0) ?></span>
        <span title="<?= e(t('exchange_rate')) ?>">USD/<?= e(currency_code()) ?> <?= fmt_num($prices['usd_local'], 2) ?></span>
      <?php endif; ?>
    </div>

    <?php
    // El riel se dibuja con el historial real de precios de esta instalación.
    $c = coaster_build($track);
    if (!$c['ready']) {
        $leyenda = t('coaster_station', $c['needed']);
    } elseif ($c['total'] > $c['count']) {
        $leyenda = t('coaster_caption_recent', $c['count']);
    } else {
        $leyenda = t('coaster_caption', $c['count']);
    }
    echo coaster_render($track, hero_vehicle(), e($leyenda));
    echo module_slot('home_coaster');
    ?>
  </div>
</section>

<?php if (!count($years)): ?>
<section class="block"><div class="container">
  <div class="alert alert-ok"><?= e(t('no_years')) ?></div>
</div></section>

<?php else: ?>
<section class="block">
  <div class="container">
    <h2><?= e(t('total_accumulated')) ?></h2>
    <p class="section-sub"><?= show_amounts() ? e(t('total_accumulated_sub')) : e(t('privacy_pct_note')) ?></p>

    <?php if (show_amounts()): ?>
    <div class="kpi-grid">
      <div class="kpi kpi-highlight">
        <div class="kpi-label"><?= e(t('kpi_sats')) ?></div>
        <div class="kpi-value"><?= fmt_int($global['sats']) ?></div>
        <div class="kpi-sub"><?= fmt_num($global['sats'] / SATS_PER_BTC, 8) ?> BTC</div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><?= e(t('kpi_invested')) ?></div>
        <div class="kpi-value"><?= money($global['inv']) ?></div>
        <div class="kpi-sub"><?= e(currency_code()) ?> · <?= e(t('kpi_purchases_n', fmt_int($global['compras']))) ?></div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><?= e(t('kpi_value')) ?></div>
        <div class="kpi-value"><?= money($globalVal) ?></div>
        <div class="kpi-sub"><?= e(currency_code()) ?> · <?= e(t('kpi_at_today')) ?></div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><?= e(t('kpi_return')) ?></div>
        <div class="kpi-value <?= $globalPnl >= 0 ? 'pos' : 'neg' ?>"><?= fmt_pct($globalPct) ?></div>
        <div class="kpi-sub <?= $globalPnl >= 0 ? 'pos' : 'neg' ?>"><?= ($globalPnl >= 0 ? '+' : '−') . money(abs($globalPnl)) ?></div>
      </div>
    </div>

    <?php else: ?>
    <div class="kpi-grid">
      <div class="kpi kpi-highlight">
        <div class="kpi-label"><?= e(t('kpi_compliance')) ?></div>
        <div class="kpi-value"><?= fmt_num($globalProg, 0) ?>%</div>
        <div class="year-progress"><div class="year-progress-bar" style="width:<?= round($globalProg, 1) ?>%"></div></div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><?= e(t('kpi_purchases')) ?></div>
        <div class="kpi-value"><?= fmt_int($global['compras']) ?></div>
        <div class="kpi-sub"><?= e(t('of_planned', $global['planned'])) ?></div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><?= e(t('kpi_streak')) ?></div>
        <div class="kpi-value"><?= fmt_int($streak) ?></div>
        <div class="kpi-sub"><?= e(t('kpi_streak_n', $streak)) ?></div>
      </div>
      <div class="kpi">
        <div class="kpi-label"><?= e(t('kpi_return')) ?></div>
        <div class="kpi-value muted-value">🔒</div>
        <div class="kpi-sub"><?= e(t('kpi_return_hidden')) ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="block">
  <div class="container">
    <h2><?= e(t('years_title')) ?></h2>
    <p class="section-sub"><?= e(t('years_sub')) ?></p>
    <div class="year-grid">
      <?php foreach ($yearCards as $yc):
        $yr = $yc['year']; $s = $yc['sum']; $planned = $yc['planned'];
        $prog = $planned ? min(100, $s['compras'] / $planned * 100) : 0; ?>
      <a class="year-card" href="<?= e(year_url((int)$yr['year'])) ?>">
        <div class="year-num"><?= (int)$yr['year'] ?></div>
        <div class="kpi-sub">
          <?= show_amounts()
              ? e(t('year_weekly', money_full($yr['weekly_amount']), day_name(purchase_day()), $s['compras'], $planned))
              : e(t('of_planned', $planned)) . ' · ' . fmt_int($s['compras']) ?>
        </div>
        <div class="year-progress"><div class="year-progress-bar" style="width:<?= round($prog, 1) ?>%"></div></div>
        <div class="kpi-sub" style="margin-top:4px"><?= e(t('year_progress_pct', fmt_num($prog, 0))) ?></div>
        <?php if (show_amounts()): ?>
        <div class="year-stats">
          <div><strong><?= fmt_int($s['sats']) ?></strong><span><?= e(t('stat_sats')) ?></span></div>
          <div><strong><?= money($s['invertido']) ?></strong><span><?= e(t('stat_invested')) ?></span></div>
          <div><strong class="<?= $s['pnl'] >= 0 ? 'pos' : 'neg' ?>"><?= fmt_pct($s['pnl_pct']) ?></strong><span><?= e(t('stat_return')) ?></span></div>
        </div>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="block">
  <div class="container center-block">
    <h2><?= e(t('what_is_title', site_name())) ?></h2>
    <p class="section-sub narrow"><?= content('home_about') ?></p>
    <p>
      <?php if (get_setting('ejercicio_mode', 'link') === 'own'): ?>
        <a class="btn" href="<?= SITE_URL ?>/acerca.php"><?= e(t('cta_know')) ?></a>
      <?php else: ?>
        <a class="btn" href="<?= e(HUB_URL) ?>/acerca.php" target="_blank" rel="noopener"><?= e(t('cta_know')) ?> ↗</a>
      <?php endif; ?>
      <?php if (selected_tool('exchange') || selected_tool('wallet')): ?>
        <a class="btn btn-outline" href="<?= SITE_URL ?>/herramientas.php"><?= e(t('cta_tools')) ?></a>
      <?php endif; ?>
    </p>
  </div>
</section>

<?php page_foot(); ?>
