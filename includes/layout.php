<?php
require_once __DIR__ . '/functions.php';

function page_head(string $title, string $description = ''): void {
    $fullTitle = $title === 'Inicio' ? SITE_NAME . ' — El poder de la compra constante de Bitcoin' : $title . ' | ' . SITE_NAME;
    ?><!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($description ?: 'Jueves de Satoshi: un ejercicio público de acumulación semanal de Bitcoin por Jorge Avila Meléndez. Mira el histórico de compras y el poder de la constancia.') ?>">
<meta property="og:title" content="<?= e($fullTitle) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/assets/img/logo.svg">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/styles.css">
<script>
// Aplicar tema guardado antes del render para evitar parpadeo
(function(){var t=localStorage.getItem('jds-theme');if(t)document.documentElement.setAttribute('data-theme',t);})();
</script>
<?= tracking_head() ?>
</head>
<body>
<?= tracking_body() ?>
<header class="site-header">
  <div class="container header-inner">
    <a href="<?= SITE_URL ?>/" class="brand">
      <img src="<?= SITE_URL ?>/assets/img/logo.svg" alt="Logo Jueves de Satoshi" class="brand-logo">
      <span class="brand-name">Jueves de <span class="brand-accent">Satoshi</span></span>
    </a>
    <nav class="main-nav" id="mainNav">
      <a href="<?= SITE_URL ?>/">Dashboard</a>
      <a href="<?= SITE_URL ?>/acerca.php">El Ejercicio</a>
      <a href="<?= SITE_URL ?>/herramientas.php">Herramientas</a>
      <a href="<?= SITE_URL ?>/contacto.php">Contacto</a>
      <button id="themeToggle" class="theme-toggle" title="Cambiar tema" aria-label="Cambiar tema">
        <span class="icon-light">🌙</span><span class="icon-dark">☀️</span>
      </button>
    </nav>
    <button class="nav-burger" id="navBurger" aria-label="Menú">☰</button>
  </div>
</header>
<main>
<?php
}

function page_foot(): void {
    ?>
</main>
<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-col">
      <strong>Jueves de <span class="brand-accent">Satoshi</span></strong>
      <p>Un ejercicio público de acumulación semanal de Bitcoin.<br>La constancia es el superpoder. 🚀</p>
    </div>
    <div class="footer-col">
      <strong><?= e(SITE_AUTHOR) ?></strong>
      <p class="footer-bio">Alma de Activista, con Mente de Empresario y Corazón de Buen Samaritano.</p>
      <div class="social-links">
        <a href="https://x.com/<?= SOCIAL_HANDLE ?>" target="_blank" rel="noopener" title="X">𝕏</a>
        <a href="https://www.linkedin.com/in/<?= SOCIAL_HANDLE ?>" target="_blank" rel="noopener" title="LinkedIn">in</a>
        <a href="https://www.instagram.com/<?= SOCIAL_HANDLE ?>" target="_blank" rel="noopener" title="Instagram">IG</a>
        <a href="https://www.facebook.com/<?= SOCIAL_HANDLE ?>" target="_blank" rel="noopener" title="Facebook">f</a>
        <a href="https://www.youtube.com/@<?= SOCIAL_HANDLE ?>" target="_blank" rel="noopener" title="YouTube">▶</a>
      </div>
    </div>
    <div class="footer-col footer-disclaimer">
      <p><strong>Aviso:</strong> Este sitio documenta un ejercicio personal y educativo. No es asesoría financiera ni recomendación de inversión. Haz tu propia investigación.</p>
      <p>© <?= date('Y') ?> <?= e(SITE_AUTHOR) ?></p>
      <p>
        <a href="https://satoshi.jorgeavila.com" target="_blank" rel="noopener">Sitio oficial</a>
        ·
        <a href="https://github.com/jorgeavilam/jueves-de-satoshi" target="_blank" rel="noopener">Código en GitHub</a>
      </p>
    </div>
  </div>
</footer>
<script src="<?= SITE_URL ?>/assets/js/app.js"></script>
</body>
</html>
<?php
}
