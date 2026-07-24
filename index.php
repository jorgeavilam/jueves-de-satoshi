<?php
require_once __DIR__ . '/includes/layout.php';

$prices = live_prices();
$years = get_years();

// Acumulado global (todos los años)
$global = ['sats' => 0, 'inv_mxn' => 0.0, 'compras' => 0];
$yearCards = [];
foreach ($years as $yr) {
    $p = get_purchases((int)$yr['id']);
    $s = year_summary($p, $prices);
    $global['sats'] += $s['sats'];
    $global['inv_mxn'] += $s['invertido_mxn'];
    $global['compras'] += $s['compras'];
    $yearCards[] = ['year' => $yr, 'sum' => $s, 'planned' => planned_thursdays($yr, $p)];
}
$globalValMxn = $global['sats'] / SATS_PER_BTC * $prices['btc_mxn'];
$globalPnl = $globalValMxn - $global['inv_mxn'];
$globalPct = $global['inv_mxn'] > 0 ? $globalPnl / $global['inv_mxn'] : 0;

page_head('Inicio');
?>

<section class="hero">
  <div class="container">
    <h1>El poder de la <span class="brand-accent">compra constante</span></h1>
    <p class="lead">Cada jueves, una compra de Bitcoin. Sin importar el precio, sin emociones, sin
    pausa. Este es el registro público y transparente del ejercicio <strong>Jueves de Satoshi</strong>.</p>
    <div class="live-price">
      <span><span class="live-dot"></span> BTC</span>
      <span>$<?= fmt_money($prices['btc_usd']) ?> USD</span>
      <span>$<?= fmt_money($prices['btc_mxn']) ?> MXN</span>
      <span title="Tipo de cambio">USD/MXN <?= fmt_money($prices['usd_mxn'], 2) ?></span>
    </div>

    <!-- Montaña rusa: el viaje del hodler — sube, baja, y se divierte -->
    <div class="coaster" aria-hidden="true">
      <svg viewBox="0 0 900 230" xmlns="http://www.w3.org/2000/svg" class="coaster-svg">
        <defs>
          <path id="trackPath" d="M -40,150 C 60,150 90,55 170,55 C 250,55 270,185 350,185 C 410,185 430,35 510,35 C 590,35 610,160 690,160 C 760,160 780,90 860,90 C 900,90 920,110 940,110"/>
        </defs>
        <!-- postes de la estructura -->
        <g class="coaster-posts">
          <line x1="170" y1="55" x2="170" y2="215"/>
          <line x1="350" y1="185" x2="350" y2="215"/>
          <line x1="510" y1="35" x2="510" y2="215"/>
          <line x1="690" y1="160" x2="690" y2="215"/>
          <line x1="860" y1="90" x2="860" y2="215"/>
          <line x1="-20" y1="215" x2="920" y2="215" class="coaster-ground"/>
        </g>
        <!-- riel -->
        <use href="#trackPath" class="coaster-track"/>
        <!-- carrito: moneda ₿ con pasajero feliz, brazos arriba -->
        <g class="coaster-cart">
          <g transform="translate(0,-26)">
            <!-- pasajero -->
            <line x1="-9" y1="-13" x2="-16" y2="-24" class="cart-arm"/>
            <line x1="9" y1="-13" x2="16" y2="-24" class="cart-arm"/>
            <circle cx="0" cy="-18" r="6" class="cart-head"/>
            <path d="M -3,-17 Q 0,-14 3,-17" class="cart-smile"/>
            <!-- carrito moneda -->
            <circle cx="0" cy="0" r="14" class="cart-coin"/>
            <text x="0" y="5.5" text-anchor="middle" class="cart-b">₿</text>
            <circle cx="-9" cy="13" r="4" class="cart-wheel"/>
            <circle cx="9" cy="13" r="4" class="cart-wheel"/>
          </g>
          <animateMotion dur="9s" repeatCount="indefinite" rotate="auto" keyPoints="0;1" keyTimes="0;1" calcMode="linear">
            <mpath href="#trackPath"/>
          </animateMotion>
        </g>
      </svg>
      <p class="coaster-caption">Así se siente acumular: subidas, bajadas… ¡disfruta el viaje! 🎢</p>
    </div>
  </div>
</section>

<section class="block">
  <div class="container">
    <h2>Acumulado <span class="brand-accent">total</span></h2>
    <p class="section-sub">La suma de todos los años del ejercicio, valuada al precio actual de Bitcoin.</p>
    <div class="kpi-grid">
      <div class="kpi kpi-highlight">
        <div class="kpi-label">Sats acumulados</div>
        <div class="kpi-value"><?= fmt_int($global['sats']) ?></div>
        <div class="kpi-sub"><?= number_format($global['sats'] / SATS_PER_BTC, 8) ?> BTC</div>
      </div>
      <div class="kpi">
        <div class="kpi-label">Total invertido</div>
        <div class="kpi-value">$<?= fmt_money($global['inv_mxn'], 0) ?></div>
        <div class="kpi-sub">MXN · <?= $global['compras'] ?> compras</div>
      </div>
      <div class="kpi">
        <div class="kpi-label">Valor actual</div>
        <div class="kpi-value">$<?= fmt_money($globalValMxn, 0) ?></div>
        <div class="kpi-sub">MXN al precio de hoy</div>
      </div>
      <div class="kpi">
        <div class="kpi-label">Rendimiento</div>
        <div class="kpi-value <?= $globalPnl >= 0 ? 'pos' : 'neg' ?>"><?= fmt_pct($globalPct) ?></div>
        <div class="kpi-sub <?= $globalPnl >= 0 ? 'pos' : 'neg' ?>"><?= ($globalPnl >= 0 ? '+' : '−') ?>$<?= fmt_money(abs($globalPnl), 0) ?> MXN</div>
      </div>
    </div>
  </div>
</section>

<section class="block">
  <div class="container">
    <h2>Los <span class="brand-accent">años</span> del ejercicio</h2>
    <p class="section-sub">Haz clic en un año para explorar su dashboard completo: gráficas del recorrido e histórico de compras.</p>
    <div class="year-grid">
      <?php foreach ($yearCards as $yc): $yr = $yc['year']; $s = $yc['sum']; $planned = $yc['planned'];
            $prog = $planned ? min(100, $s['compras'] / $planned * 100) : 0; ?>
      <a class="year-card" href="<?= SITE_URL ?>/year.php?y=<?= (int)$yr['year'] ?>">
        <div class="year-num"><?= (int)$yr['year'] ?></div>
        <div class="kpi-sub">$<?= fmt_money($yr['weekly_amount_mxn'], 0) ?> MXN semanales · <?= $s['compras'] ?> de <?= $planned ?> compras</div>
        <div class="year-progress">
          <div class="year-progress-bar" style="width:<?= round($prog, 1) ?>%"></div>
        </div>
        <div class="kpi-sub" style="margin-top:4px"><?= round($prog) ?>% del año</div>
        <div class="year-stats">
          <div><strong><?= fmt_int($s['sats']) ?></strong><span>sats</span></div>
          <div><strong>$<?= fmt_money($s['invertido_mxn'], 0) ?></strong><span>invertido</span></div>
          <div><strong class="<?= $s['pnl_mxn'] >= 0 ? 'pos' : 'neg' ?>"><?= fmt_pct($s['pnl_pct']) ?></strong><span>rendimiento</span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="block">
  <div class="container" style="text-align:center">
    <h2>¿Qué es <span class="brand-accent">Jueves de Satoshi</span>?</h2>
    <p class="section-sub" style="max-width:620px;margin-left:auto;margin-right:auto">Un ejercicio semanal y público de acumulación de Bitcoin: la misma cantidad, el mismo día, sin importar el precio. El objetivo es mostrar el poder de una pequeña compra constante a través del tiempo.</p>
    <p>
      <a class="btn" href="<?= SITE_URL ?>/acerca.php">Conoce el ejercicio</a>
      <a class="btn btn-outline" href="<?= SITE_URL ?>/herramientas.php">Las herramientas</a>
    </p>
  </div>
</section>

<?php page_foot(); ?>
