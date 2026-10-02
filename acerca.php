<?php
require_once __DIR__ . '/includes/layout.php';
public_gate();

/**
 * Esta página solo existe si el dueño eligió escribir su propia versión.
 * En modo "link" el menú apunta directo al sitio original: así el nodo no
 * duplica ese texto ni compite con él en los buscadores.
 */
if (get_setting('ejercicio_mode', 'link') !== 'own') {
    header('Location: ' . HUB_URL . '/acerca.php');
    exit;
}

page_head(t('nav_ejercicio'));
?>
<div class="content-page">
  <h1><?= content('ejercicio_title') ?></h1>

  <?= content('ejercicio_body') ?>

  <?php if (!is_hub()): ?>
  <div class="origin-card">
    <h2><?= e(t('ejercicio_link_title')) ?></h2>
    <p><?= e(t('ejercicio_link_body', HUB_AUTHOR)) ?></p>
    <p><a class="btn btn-outline" href="<?= e(HUB_URL) ?>/acerca.php" target="_blank" rel="noopener"><?= e(t('ejercicio_link_cta')) ?> ↗</a></p>
  </div>
  <?php endif; ?>

  <p style="margin-top:30px"><a class="btn" href="<?= e(page_url('/')) ?>"><?= e(t('cta_dashboard')) ?></a></p>
</div>
<?php page_foot(); ?>
