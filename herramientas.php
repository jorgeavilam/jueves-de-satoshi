<?php
require_once __DIR__ . '/includes/layout.php';
page_head('Herramientas', 'Las herramientas usadas en el ejercicio Jueves de Satoshi: Aureo Bitcoin para la compra y Wallet of Satoshi para la recepción vía Lightning.');
?>
<div class="content-page">
  <h1>Herramientas del <span class="brand-accent">ejercicio</span></h1>
  <p>Estas son las herramientas que uso cada jueves para realizar y recibir las compras. La configuración busca ser simple, transparente y replicable por cualquier persona.</p>

  <div class="tool-card">
    <div class="tool-icon">🪙</div>
    <div>
      <h3>Aureo Bitcoin</h3>
      <p>Plataforma mexicana para comprar Bitcoin de forma puntual. Cobra una comisión del 2%* que ya incluye todos los fees, y envía la compra directamente a tu wallet, ya sea por Lightning u on-chain. Matemática simple, sin sorpresas.</p>
      <p style="font-size:0.85rem;color:var(--text-soft)">*La comisión puede variar; en el histórico de compras se registra la comisión real de cada operación.</p>
      <a class="btn btn-outline" href="https://www.aureobitcoin.com/es" target="_blank" rel="noopener">aureobitcoin.com</a>
      <a href="https://x.com/AureoBitcoin" target="_blank" rel="noopener" style="margin-left:12px">@AureoBitcoin en X</a>
    </div>
  </div>

  <div class="tool-card">
    <div class="tool-icon">⚡</div>
    <div>
      <h3>Wallet of Satoshi</h3>
      <p>Wallet Lightning sencillo y amigable donde recibo cada compra semanal. Ideal para empezar sin complicaciones técnicas. Las compras llegan en segundos vía la red Lightning.</p>
      <p style="font-size:0.85rem;color:var(--text-soft)">Importante: es un wallet custodial. Si vas a acumular Bitcoin en serio, aprende a custodiar tus propias llaves.</p>
      <a class="btn btn-outline" href="https://www.walletofsatoshi.com/" target="_blank" rel="noopener">walletofsatoshi.com</a>
      <a href="https://x.com/walletofsatoshi" target="_blank" rel="noopener" style="margin-left:12px">@walletofsatoshi en X</a>
    </div>
  </div>

  <div class="tool-card">
    <div class="tool-icon">📊</div>
    <div>
      <h3>Este sitio</h3>
      <p>El dashboard público donde registro y comparto cada compra: el histórico completo, las gráficas del recorrido y los enlaces a los posts en X con los testigos de cada transacción.</p>
      <a class="btn btn-outline" href="<?= SITE_URL ?>/">Ver el dashboard</a>
    </div>
  </div>

  <div class="quote-card">
    <strong>Recordatorio:</strong> esto no es asesoría financiera. Es un ejercicio personal y educativo, documentado en público. Investiga, aprende, y decide por ti mismo.
  </div>
</div>
<?php page_foot(); ?>
