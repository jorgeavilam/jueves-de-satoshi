<?php
require_once __DIR__ . '/functions.php';

jds_boot();

/**
 * Puerta de las páginas públicas.
 *
 * Si la instalación todavía no tiene dueño ni nombre, el sitio no publica nada.
 * El default de una instalación a medias es el silencio, no los datos de otra
 * persona. El panel de administración sigue funcionando siempre.
 */
function public_gate(): void {
    if (site_ready()) return;
    http_response_code(503);
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    ?><!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= e(t('inst_title')) ?></title>
<style>
 body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
      background:#F7F5F1;color:#1A1A1A;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif}
 @media (prefers-color-scheme:dark){body{background:#14120F;color:#F0EBE3}}
 div{max-width:440px;text-align:center}
 h1{font-size:1.35rem;margin:0 0 10px}
 p{color:#6B655C;margin:0 0 20px}
 a{display:inline-block;background:#F7931A;color:#fff;text-decoration:none;font-weight:700;
   padding:11px 26px;border-radius:10px}
</style></head><body><div>
 <h1>🔧 <?= e(t('inst_title')) ?></h1>
 <p><?= e(t('id_name_hint')) ?></p>
 <a href="<?= e($base . '/admin/identidad.php') ?>"><?= e(t('admin_panel')) ?></a>
</div></body></html><?php
    exit;
}

function page_head(string $title, string $description = '', bool $noindex = false): void {
    $site   = site_name() ?: HUB_PROJECT;
    $full   = $title === '' ? $site . ' — ' . t('home_title_suffix') : $title . ' | ' . $site;
    $accent = accent_color();
    [$ar, $ag, $ab] = hex_to_rgb($accent);
    $fonts  = font_stacks(font_pair());
    $gf     = font_catalog()[font_pair()]['css'] ?? '';
    ?><!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>" data-theme="<?= skin() === 'terminal' ? 'dark' : 'light' ?>" data-skin="<?= e(skin()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($full) ?></title>
<meta name="description" content="<?= e($description ?: t('meta_description', $site)) ?>">
<?php if ($noindex || !show_progress()): ?><meta name="robots" content="noindex, follow">
<?php endif; ?>
<meta property="og:title" content="<?= e($full) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($site) ?>">
<meta property="og:url" content="<?= e(SITE_URL) ?>">
<meta property="og:description" content="<?= e($description ?: t('meta_description', $site)) ?>">
<link rel="canonical" href="<?= e(SITE_URL . ($_SERVER['SCRIPT_NAME'] === '/index.php' ? '/' : $_SERVER['SCRIPT_NAME'])) ?>">
<link rel="icon" href="<?= e(logo_url()) ?>">
<?php if ($gf): ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?<?= e($gf) ?>&display=swap">
<?php endif; ?>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/styles.css?v=<?= e(JDS_VERSION) ?>">
<style>
:root {
  --accent: <?= e($accent) ?>;
  --accent-dark: <?= e(color_shade($accent, -0.18)) ?>;
  --accent-rgb: <?= (int)$ar ?>, <?= (int)$ag ?>, <?= (int)$ab ?>;
  --on-accent: <?= is_light_color($accent) ? '#1A1A1A' : '#FFFFFF' ?>;
  --font-head: <?= $fonts['head'] ?>;
  --font-body: <?= $fonts['body'] ?>;
}
[data-theme="dark"] { --accent-dark: <?= e(color_shade($accent, 0.22)) ?>; }
</style>
<script>
// Tema guardado antes del render, para que no parpadee
(function(){var t=localStorage.getItem('jds-theme');if(t)document.documentElement.setAttribute('data-theme',t);})();
</script>
<?= tracking_head() ?>
<?= json_ld() ?>
</head>
<body>
<?= tracking_body() ?>
<header class="site-header">
  <div class="container header-inner">
    <a href="<?= SITE_URL ?>/" class="brand">
      <?php if (get_setting('logo_mode', 'mono') === 'upload'): ?>
        <img src="<?= e(logo_url()) ?>" alt="<?= e(t('logo_alt', $site)) ?>" class="brand-logo">
      <?php else: ?>
        <span class="brand-logo"><?= monogram_svg(38) ?></span>
      <?php endif; ?>
      <span class="brand-name"><?= brand_name_html() ?></span>
    </a>
    <nav class="main-nav" id="mainNav">
      <a href="<?= SITE_URL ?>/"><?= e(t('nav_dashboard')) ?></a>
      <?php if (get_setting('ejercicio_mode', 'link') === 'own'): ?>
        <a href="<?= SITE_URL ?>/acerca.php"><?= e(t('nav_ejercicio')) ?></a>
      <?php else: ?>
        <a href="<?= e(HUB_URL) ?>/acerca.php" target="_blank" rel="noopener">
          <?= e(t('nav_ejercicio')) ?> <span class="ext" aria-hidden="true">↗</span>
        </a>
      <?php endif; ?>
      <?php if (selected_tool('exchange') || selected_tool('wallet')): ?>
        <a href="<?= SITE_URL ?>/herramientas.php"><?= e(t('nav_herramientas')) ?></a>
      <?php endif; ?>
      <a href="<?= SITE_URL ?>/contacto.php"><?= e(t('nav_contacto')) ?></a>
      <button id="themeToggle" class="theme-toggle" title="<?= e(t('nav_theme')) ?>" aria-label="<?= e(t('nav_theme')) ?>">
        <span class="icon-light">🌙</span><span class="icon-dark">☀️</span>
      </button>
    </nav>
    <button class="nav-burger" id="navBurger" aria-label="<?= e(t('nav_menu')) ?>">☰</button>
  </div>
</header>
<main>
<?php
}

/**
 * El nombre del sitio con la última palabra en color de acento.
 * Funciona igual con "Jueves de Satoshi" que con "Sats de los Martes".
 */
function brand_name_html(): string {
    $name = site_name() ?: HUB_PROJECT;
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) < 2) return '<span class="brand-accent">' . e($name) . '</span>';
    $last = array_pop($parts);
    return e(implode(' ', $parts)) . ' <span class="brand-accent">' . e($last) . '</span>';
}

/**
 * Datos estructurados. Declara al dueño de ESTE sitio como autor, y con
 * isBasedOn deja constancia de que el software y el método vienen del maestro.
 * Nunca un canonical cruzado: eso borraría a los nodos del índice de Google.
 */
function json_ld(): string {
    if (!site_ready()) return '';
    $data = [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => site_name(),
        'url'      => SITE_URL,
        'inLanguage' => current_locale(),
        'author'   => array_filter([
            '@type' => 'Person',
            'name'  => owner_name(),
            'description' => get_setting('owner_bio', '') ?: null,
            'sameAs' => array_values(array_map(fn($s) => $s['url'], owner_socials())) ?: null,
        ]),
    ];
    if (!is_hub()) {
        $data['isBasedOn'] = ['@type' => 'WebSite', 'name' => HUB_PROJECT, 'url' => HUB_URL];
    }
    return '<script type="application/ld+json">'
         . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)
         . "</script>\n";
}

function page_foot(): void {
    $socials = owner_socials();
    $bio = get_setting('owner_bio', '');
    ?>
</main>
<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-col">
      <strong><?= brand_name_html() ?></strong>
      <p><?= e(t('footer_tagline')) ?></p>
    </div>
    <div class="footer-col">
      <strong><?= e(owner_name()) ?></strong>
      <?php if ($bio !== ''): ?><p class="footer-bio"><?= e($bio) ?></p><?php endif; ?>
      <?php if ($socials): ?>
      <div class="social-links">
        <?php foreach ($socials as $s): ?>
          <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener me" title="<?= e($s['label']) ?>"><?= $s['glyph'] ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="footer-col footer-disclaimer">
      <p><strong><?= e(t('footer_disclaimer_title')) ?></strong> <?= e(t('footer_disclaimer')) ?></p>
      <p>© <?= date('Y') ?> <?= e(owner_name()) ?> <span class="footer-version">v<?= e(JDS_VERSION) ?></span></p>
      <p class="footer-links">
        <?php if (is_hub()): ?>
          <?php // Los dos enlaces van en un solo ítem del flex, para que el
                // separador no se convierta en un renglón aparte. ?>
          <span class="footer-row">
            <a href="<?= SITE_URL ?>/red.php"><?= e(t('footer_network')) ?></a>
            <span aria-hidden="true">·</span>
            <a href="<?= e(HUB_REPO) ?>" target="_blank" rel="noopener"><?= e(t('footer_code')) ?></a>
          </span>
        <?php else: ?>
          <a class="badge" href="<?= e(HUB_URL) ?>" target="_blank" rel="noopener"><?= e(t('footer_badge', HUB_PROJECT)) ?></a>
          <span class="badge-by"><?= e(t('footer_badge_by', HUB_AUTHOR)) ?></span>
        <?php endif; ?>
      </p>
    </div>
  </div>
</footer>
<script src="<?= SITE_URL ?>/assets/js/app.js?v=<?= e(JDS_VERSION) ?>"></script>
</body>
</html>
<?php
}
