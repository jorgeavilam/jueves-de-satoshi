<?php
require_once __DIR__ . '/includes/layout.php';
public_gate();

$sent = false; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sin saltos de línea: evita inyección de cabeceras en el asunto del correo
    $nombre   = trim(str_replace(["\r", "\n"], ' ', $_POST['nombre'] ?? ''));
    $email    = trim($_POST['email'] ?? '');
    $mensaje  = trim($_POST['mensaje'] ?? '');
    $honeypot = $_POST['website'] ?? ''; // campo oculto anti-bots

    if ($honeypot !== '') {
        $sent = true; // bot: fingir éxito sin enviar
    } elseif ($nombre === '' || $mensaje === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('contact_err_fields');
    } else {
        if (!recaptcha_ok($_POST['g-recaptcha-response'] ?? '')) {
            $error = t('contact_err_captcha');
        } else {
            $body = t('contact_form_name') . ": $nombre\n"
                  . t('contact_form_email') . ": $email\n\n"
                  . t('contact_form_msg') . ":\n$mensaje\n\n--\n" . t('contact_sent_from') . ' ' . SITE_URL;
            $sent = contact_send(t('contact_subject', site_name(), $nombre), $body, $email);
            if (!$sent) $error = t('contact_err_send');
        }
    }
}

$socials     = owner_socials();
$publicEmail = get_setting('owner_email_public', '');
$bio         = owner_bio();
$captchaKey  = defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : '';

page_head(t('contact_title'));
?>
<div class="content-page">
  <h1><?= e(t('contact_title')) ?></h1>

  <div class="profile-card">
    <img src="<?= e(owner_avatar_url()) ?>" alt="<?= e(owner_name()) ?>" width="110" height="110">
    <div>
      <h2><?= e(owner_name()) ?></h2>
      <?php if ($bio !== ''): ?><p class="bio"><?= e($bio) ?></p><?php endif; ?>
      <?php if ($publicEmail !== ''): ?>
        <p class="profile-mail"><a href="mailto:<?= e($publicEmail) ?>"><?= e($publicEmail) ?></a></p>
      <?php endif; ?>
      <?php if ($socials): ?>
      <div class="social-links dark-on-light">
        <?php foreach ($socials as $s): ?>
          <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener me" title="<?= e($s['label']) ?>"><?= $s['glyph'] ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <p><?= content('contacto_intro') ?></p>

  <?php if ($sent): ?>
    <div class="alert alert-ok"><?= e(t('contact_ok')) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-err"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!$sent): ?>
  <div class="form-card">
    <form method="post" id="contactForm">
      <div class="form-grid">
        <div class="form-group">
          <label for="nombre"><?= e(t('contact_form_name')) ?></label>
          <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?= e($_POST['nombre'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="email"><?= e(t('contact_form_email')) ?></label>
          <input type="email" id="email" name="email" required maxlength="150" value="<?= e($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label for="mensaje"><?= e(t('contact_form_msg')) ?></label>
        <textarea id="mensaje" name="mensaje" rows="6" required maxlength="3000"><?= e($_POST['mensaje'] ?? '') ?></textarea>
      </div>
      <div class="hp-field" aria-hidden="true">
        <label><?= e(t('contact_hp')) ?></label>
        <input type="text" name="website" tabindex="-1" autocomplete="off">
      </div>
      <?php if ($captchaKey !== ''): ?>
        <input type="hidden" name="g-recaptcha-response" id="gRecaptchaResponse">
      <?php endif; ?>
      <button type="submit" class="btn"><?= e(t('contact_form_send')) ?></button>
    </form>
  </div>
  <?php endif; ?>
</div>

<?php if ($captchaKey !== '' && !$sent): ?>
<script src="https://www.google.com/recaptcha/api.js?render=<?= e($captchaKey) ?>"></script>
<script>
document.getElementById('contactForm').addEventListener('submit', function (e) {
  var hidden = document.getElementById('gRecaptchaResponse');
  if (hidden.value) return; // ya tenemos token
  e.preventDefault();
  var form = this;
  grecaptcha.ready(function () {
    grecaptcha.execute('<?= e($captchaKey) ?>', { action: 'contacto' }).then(function (token) {
      hidden.value = token;
      form.submit();
    });
  });
});
</script>
<?php endif; ?>
<?php page_foot(); ?>
