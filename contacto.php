<?php
require_once __DIR__ . '/includes/layout.php';

$sent = false; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sin saltos de línea: evita inyección de cabeceras en el asunto del correo
    $nombre  = trim(str_replace(["\r", "\n"], ' ', $_POST['nombre'] ?? ''));
    $email   = trim($_POST['email'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');
    $honeypot = $_POST['website'] ?? ''; // campo oculto anti-bots

    if ($honeypot !== '') {
        $sent = true; // bot: fingir éxito sin enviar
    } elseif ($nombre === '' || $mensaje === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor completa todos los campos con un email válido.';
    } else {
        // reCAPTCHA v3 (si está configurado)
        $captchaOk = true;
        if (RECAPTCHA_SECRET !== '') {
            $captchaOk = false;
            $resp = @file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='
                . urlencode(RECAPTCHA_SECRET) . '&response=' . urlencode($_POST['g-recaptcha-response'] ?? ''));
            if ($resp) {
                $r = json_decode($resp, true);
                $captchaOk = !empty($r['success']) && ($r['score'] ?? 0) >= 0.5;
            }
        }
        if (!$captchaOk) {
            $error = 'No pudimos verificar que no eres un robot. Intenta de nuevo.';
        } else {
            $subject = '[Jueves de Satoshi] Mensaje de ' . $nombre;
            $body = "Nombre: $nombre\nEmail: $email\n\nMensaje:\n$mensaje\n\n--\nEnviado desde " . SITE_URL;
            $headers = 'From: ' . SITE_NAME . ' <' . CONTACT_FROM . ">\r\n"
                     . 'Reply-To: ' . $email . "\r\n"
                     . "Content-Type: text/plain; charset=UTF-8\r\n";
            $sent = @mail(CONTACT_EMAIL, $subject, $body, $headers);
            if (!$sent) $error = 'Hubo un problema al enviar. Intenta más tarde o búscame en redes.';
        }
    }
}

page_head('Contacto', 'Contacta a Jorge Avila Meléndez, autor del ejercicio Jueves de Satoshi.');
?>
<div class="content-page">
  <h1>Contacto</h1>

  <div class="profile-card">
    <img src="<?= SITE_URL ?>/assets/img/jorge-avila.jpg" alt="<?= e(SITE_AUTHOR) ?>">
    <div>
      <h2 style="margin:0 0 4px"><?= e(SITE_AUTHOR) ?></h2>
      <p class="bio">Alma de Activista, con Mente de Empresario y Corazón de Buen Samaritano.</p>
      <div class="social-links" style="margin-top:8px">
        <a href="https://x.com/<?= SOCIAL_HANDLE ?>" target="_blank" rel="noopener" title="X">𝕏</a>
        <a href="https://www.linkedin.com/in/<?= SOCIAL_HANDLE ?>" target="_blank" rel="noopener" title="LinkedIn">in</a>
        <a href="https://www.instagram.com/<?= SOCIAL_HANDLE ?>" target="_blank" rel="noopener" title="Instagram">IG</a>
        <a href="https://www.facebook.com/<?= SOCIAL_HANDLE ?>x" target="_blank" rel="noopener" title="Facebook">f</a>
        <a href="https://www.youtube.com/@<?= SOCIAL_HANDLE ?>" target="_blank" rel="noopener" title="YouTube">▶</a>
      </div>
    </div>
  </div>

  <p>¿Dudas sobre el ejercicio, comentarios o quieres platicar de Bitcoin? Encuéntrame en cualquier red como <strong>/<?= SOCIAL_HANDLE ?></strong> o escríbeme aquí:</p>

  <?php if ($sent): ?>
    <div class="alert alert-ok">✅ ¡Mensaje enviado! Te responderé pronto. Gracias por escribir.</div>
  <?php elseif ($error): ?>
    <div class="alert alert-err"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!$sent): ?>
  <div class="form-card">
    <form method="post" id="contactForm">
      <div class="form-grid">
        <div class="form-group">
          <label for="nombre">Nombre</label>
          <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?= e($_POST['nombre'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required maxlength="150" value="<?= e($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label for="mensaje">Mensaje</label>
        <textarea id="mensaje" name="mensaje" rows="6" required maxlength="3000"><?= e($_POST['mensaje'] ?? '') ?></textarea>
      </div>
      <div class="hp-field" aria-hidden="true">
        <label>No llenar este campo</label>
        <input type="text" name="website" tabindex="-1" autocomplete="off">
      </div>
      <?php if (RECAPTCHA_SITE_KEY !== ''): ?>
        <input type="hidden" name="g-recaptcha-response" id="gRecaptchaResponse">
      <?php endif; ?>
      <button type="submit" class="btn">Enviar mensaje</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<?php if (RECAPTCHA_SITE_KEY !== '' && !$sent): ?>
<script src="https://www.google.com/recaptcha/api.js?render=<?= e(RECAPTCHA_SITE_KEY) ?>"></script>
<script>
document.getElementById('contactForm').addEventListener('submit', function (e) {
  var hidden = document.getElementById('gRecaptchaResponse');
  if (hidden.value) return; // ya tenemos token
  e.preventDefault();
  var form = this;
  grecaptcha.ready(function () {
    grecaptcha.execute('<?= e(RECAPTCHA_SITE_KEY) ?>', { action: 'contacto' }).then(function (token) {
      hidden.value = token;
      form.submit();
    });
  });
});
</script>
<?php endif; ?>
<?php page_foot(); ?>
