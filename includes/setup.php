<?php
/**
 * Chrome mínimo para el instalador y el actualizador.
 *
 * A propósito NO usa el layout del sitio ni la base de datos: estas dos páginas
 * tienen que funcionar cuando todavía no hay configuración, o en medio de una
 * migración donde las tablas están a medio camino.
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/i18n.php';

if (!defined('JDS_SETUP_VERSION')) define('JDS_SETUP_VERSION', '2.0.0');

function setup_head(string $title, string $step = ''): void {
    $accent = '#F7931A';
    ?><!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?></title>
<style>
  :root {
    --bg:#F7F5F1; --card:#FFFFFF; --ink:#1A1A1A; --soft:#5F5A52; --line:#E4DED4;
    --accent:<?= $accent ?>; --ok:#1F7A4D; --err:#B3392C; --radius:12px;
  }
  @media (prefers-color-scheme: dark) {
    :root { --bg:#14120F; --card:#1D1A16; --ink:#F0EBE3; --soft:#A79E92; --line:#332E27;
            --ok:#4FB37C; --err:#DE7565; }
  }
  * { box-sizing:border-box; }
  body { margin:0; background:var(--bg); color:var(--ink); line-height:1.6;
         font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }
  .wrap { max-width:680px; margin:0 auto; padding:40px 20px 80px; }
  .logo { display:flex; align-items:center; gap:12px; margin-bottom:8px; }
  .logo span { font-weight:800; font-size:1.05rem; letter-spacing:-.3px; }
  .logo svg { flex-shrink:0; }
  h1 { font-size:1.7rem; letter-spacing:-.5px; margin:14px 0 6px; }
  h2 { font-size:1.15rem; margin:26px 0 8px; }
  p.sub { color:var(--soft); margin:0 0 22px; }
  .steps { display:flex; flex-wrap:wrap; gap:6px; margin:18px 0 26px; padding:0; list-style:none; }
  .steps li { font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; font-weight:700;
              color:var(--soft); border:1px solid var(--line); border-radius:100px; padding:3px 11px; background:var(--card); }
  .steps li.on { background:var(--accent); border-color:var(--accent); color:#fff; }
  .steps li.past { border-color:var(--accent); color:var(--accent); }
  .card { background:var(--card); border:1px solid var(--line); border-radius:var(--radius); padding:26px; }
  label { display:block; font-weight:600; font-size:.9rem; margin:0 0 5px; }
  .hint { font-size:.83rem; color:var(--soft); margin:4px 0 0; }
  .field { margin-bottom:18px; }
  input[type=text], input[type=email], input[type=password], input[type=url], input[type=number], select, textarea {
    width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:8px;
    background:var(--bg); color:var(--ink); font:inherit; font-size:.98rem;
  }
  input:focus, select:focus, textarea:focus { outline:2px solid var(--accent); border-color:var(--accent); }
  input[type=color] { width:64px; height:40px; padding:2px; border:1px solid var(--line); border-radius:8px; background:var(--bg); }
  .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:0 16px; }
  @media (max-width:600px) { .grid2 { grid-template-columns:1fr; } }
  .btn { display:inline-block; background:var(--accent); color:#fff; border:none; border-radius:9px;
         padding:11px 26px; font:inherit; font-weight:700; cursor:pointer; text-decoration:none; }
  .btn:hover { filter:brightness(.93); }
  .btn.ghost { background:none; color:var(--accent); border:2px solid var(--accent); }
  .actions { display:flex; gap:10px; align-items:center; margin-top:24px; flex-wrap:wrap; }
  .alert { border-radius:9px; padding:12px 15px; margin-bottom:18px; font-size:.93rem; font-weight:600; }
  .alert.ok  { background:rgba(31,122,77,.12); color:var(--ok); border:1px solid var(--ok); }
  .alert.err { background:rgba(179,57,44,.1); color:var(--err); border:1px solid var(--err); }
  .alert.warn{ background:rgba(247,147,26,.12); color:var(--accent); border:1px solid var(--accent); }
  .choice { display:block; border:1px solid var(--line); border-radius:10px; padding:13px 15px; margin-bottom:10px; cursor:pointer; }
  .choice:hover { border-color:var(--accent); }
  .choice input { margin-right:9px; }
  .choice strong { font-size:.97rem; }
  .choice .hint { margin-top:3px; }
  code { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.86em;
         background:var(--bg); border:1px solid var(--line); border-radius:4px; padding:.1em .35em; }
  pre { background:var(--bg); border:1px solid var(--line); border-radius:8px; padding:14px;
        overflow-x:auto; font-size:.8rem; line-height:1.5; }
  ul.check { list-style:none; padding:0; margin:14px 0 0; }
  ul.check li { padding:6px 0; border-bottom:1px solid var(--line); font-size:.92rem;
                display:flex; justify-content:space-between; gap:12px; }
  ul.check li:last-child { border-bottom:none; }
  .muted { color:var(--soft); }
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <svg width="34" height="34" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <rect width="64" height="64" rx="14" fill="<?= $accent ?>"/>
      <text x="32" y="44" text-anchor="middle" font-family="Helvetica,Arial,sans-serif" font-size="32" font-weight="700" fill="#fff">₿</text>
    </svg>
    <span>Jueves de Satoshi <span class="muted" style="font-weight:600">v<?= JDS_SETUP_VERSION ?></span></span>
  </div>
  <?php if ($step !== ''): ?><p class="sub" style="margin:0"><?= e($step) ?></p><?php endif; ?>
<?php
}

function setup_foot(): void {
    ?>
</div>
</body>
</html>
<?php
}

/** Barra de pasos del instalador. */
function setup_steps(array $labels, int $current): void {
    echo '<ul class="steps">';
    foreach ($labels as $i => $l) {
        $cls = $i + 1 === $current ? 'on' : ($i + 1 < $current ? 'past' : '');
        echo '<li class="' . $cls . '">' . e($l) . '</li>';
    }
    echo '</ul>';
}
