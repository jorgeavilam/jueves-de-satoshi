<?php
require_once __DIR__ . '/includes/layout.php';
page_head('El Ejercicio', 'Qué es Jueves de Satoshi: un ejercicio público de acumulación semanal de Bitcoin iniciado el 19 de marzo de 2026 por Jorge Avila Meléndez.');
?>
<div class="content-page">
  <h1>La importancia de acumular Bitcoin<br><span class="brand-accent">Jueves de Satoshi</span></h1>

  <p>Como algunos ya saben, me encanta esto de estar creando reservas para el futuro, y realmente considero que Bitcoin es parte importante de ese futuro 🚀.</p>

  <p><em>No pretendo hacer un post más sobre qué es Bitcoin</em>, eso se los dejo de tarea (ver al final de esta página), hay mucha información en internet, o un día me pueden invitar a desayunar 😝. Lo que busco es recordarles que es importante saber qué es, y si los convence, ir acumulando un poco.</p>

  <div class="quote-card">
    Oficialmente, el 19 de marzo de 2026 inicié los <strong>Jueves de Satoshi</strong>: un ejercicio semanal en donde estaré acumulando $1,000 MXN en Bitcoin. Lo que me alcance, no más, no menos.
  </div>

  <p>Según mis cálculos, quedan 42 jueves en el 2026 (incluyendo el del arranque), así que en teoría estaría acumulando $42,000 MXN en Bitcoin, lo que significa alrededor de un 3.3% de un Bitcoin a los precios del inicio — será interesante ver si el ejercicio se queda abajo o logra superar ese porcentaje a finales de año.</p>

  <p>Para este ejercicio, estoy realizando las compras a través de <a href="https://www.aureobitcoin.com/es" target="_blank" rel="noopener">Aureo Bitcoin</a>, enviando mis compras vía Lightning a un wallet en <a href="https://www.walletofsatoshi.com/" target="_blank" rel="noopener">Wallet of Satoshi</a>.</p>

  <h2>¿Por qué esta configuración de compra?</h2>
  <ol>
    <li>Creo que es un modelo fácil de ir acumulando, sin complicaciones de exchanges ni wallets de hardware. <strong>Importante:</strong> si vas a acumular Bitcoin en serio, es recomendable que conozcas bien cómo custodiar tú mismo tus wallets.</li>
    <li>Aureo ofrece una comisión del 1% que ya incluye todos los fees y envía tu compra a tu wallet (ya sea por Lightning u on-chain): un servicio puntual con matemática simple.</li>
    <li>Estoy acumulando Bitcoin con Lightning porque tal vez a final de año haga una dispersión de lo acumulado a diferentes cuentas. Es algo que cruza por mi mente, entonces creo que puede ser buena opción — si no fuera por eso, preferiría que me envíen las compras vía on-chain.</li>
  </ol>

  <p>Cada compra se publica con sus "testigos" (capturas de la transacción) en el <a href="https://x.com/jorgeavilam/status/2034755257675227523" target="_blank" rel="noopener">hilo del ejercicio en X</a>, y queda registrada en el <a href="<?= SITE_URL ?>/">dashboard público</a> de este sitio.</p>

  <h2>📚 La tarea</h2>
  <p>Mi sugerencia es que inicies tu investigación para poder dar respuesta a estos puntos:</p>
  <ol>
    <li>¿Qué es bitcoin?</li>
    <li>¿Cuál es el máximo número de bitcoins que puede haber?</li>
    <li>¿Cómo se genera el bitcoin?</li>
    <li>¿Quién lo inventó?</li>
    <li>¿En dónde está guardado?</li>
    <li>¿Cómo se llama la unidad más pequeña de bitcoin?</li>
    <li>¿Cuál es la diferencia del bitcoin on-chain vs. en Lightning?</li>
    <li>¿Por qué habría de subir de valor?</li>
    <li>¿Qué lo hace distinto al dinero fiat?</li>
  </ol>

  <p>Inicia el viaje 🚀</p>
  <p><strong>Jorge Avila Meléndez</strong></p>

  <p style="margin-top:30px">
    <a class="btn" href="<?= SITE_URL ?>/">Ver el dashboard</a>
    <a class="btn btn-outline" href="https://x.com/jorgeavilam/status/2034755257675227523" target="_blank" rel="noopener">Ver el hilo en X</a>
  </p>
</div>
<?php page_foot(); ?>
