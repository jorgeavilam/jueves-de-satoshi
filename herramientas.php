<?php
require_once __DIR__ . '/includes/layout.php';
public_gate();

$exchange = selected_tool('exchange');
$wallet   = selected_tool('wallet');
if (!$exchange && !$wallet) { header('Location: ' . SITE_URL . '/'); exit; }

page_head(t('nav_herramientas'));

/** Tarjeta de una herramienta. */
function tool_card(array $tool, string $kicker): void {
    ?>
    <div class="tool-card">
      <div class="tool-icon"><?= e($tool['icon']) ?></div>
      <div>
        <div class="tool-kicker"><?= e($kicker) ?></div>
        <h3><?= e($tool['name']) ?></h3>
        <p><?= e($tool['desc']) ?></p>
        <?php if ($tool['custodial'] === true): ?>
          <p class="tool-note"><?= e(t('tools_custodial')) ?></p>
        <?php elseif ($tool['custodial'] === false): ?>
          <p class="tool-note"><?= e(t('tools_self')) ?></p>
        <?php endif; ?>
        <?php if ($tool['url'] !== ''): ?>
          <a class="btn btn-outline" href="<?= e($tool['url']) ?>" target="_blank" rel="noopener"><?= e(t('tools_visit')) ?></a>
        <?php endif; ?>
        <?php if ($tool['x'] !== ''): ?>
          <a class="tool-x" href="https://x.com/<?= e($tool['x']) ?>" target="_blank" rel="noopener">@<?= e($tool['x']) ?></a>
        <?php endif; ?>
      </div>
    </div>
    <?php
}
?>
<div class="content-page">
  <h1><?= e(t('tools_title')) ?></h1>
  <p><?= content('herramientas_intro') ?></p>

  <?php if ($exchange) tool_card($exchange, t('tools_exchange')); ?>
  <?php if ($wallet) tool_card($wallet, t('tools_wallet')); ?>

  <?php if (show_progress()): ?>
  <div class="tool-card">
    <div class="tool-icon">📊</div>
    <div>
      <div class="tool-kicker"><?= e(t('tools_site')) ?></div>
      <h3><?= e(site_name()) ?></h3>
      <p><?= e(t('tools_site_body')) ?></p>
      <a class="btn btn-outline" href="<?= SITE_URL ?>/"><?= e(t('cta_dashboard')) ?></a>
    </div>
  </div>
  <?php endif; ?>

  <div class="quote-card"><?= t('tools_reminder') ?></div>
</div>
<?php page_foot(); ?>
