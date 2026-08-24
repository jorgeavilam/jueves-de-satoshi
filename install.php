<?php
/**
 * Instalador web.
 *
 * Crea config.php, importa el esquema, da de alta al administrador y guarda la
 * identidad, la marca y el contenido del sitio. No avanza sin nombre del dueño
 * ni nombre del sitio: una instalación a medias no publica nada.
 *
 * Cuando termina, BÓRRALO del servidor.
 */
define('JDS_SETUP_VERSION', '2.0.8');

require_once __DIR__ . '/includes/setup.php';     // i18n + e() + chrome, sin base de datos
require_once __DIR__ . '/includes/brandkit.php';
require_once __DIR__ . '/includes/currencies.php';

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

/* ---------- Idioma del instalador ---------- */
if (isset($_GET['lang']) && in_array($_GET['lang'], JDS_LOCALES, true)) {
    $_SESSION['inst_lang'] = $_GET['lang'];
}
i18n_force($_SESSION['inst_lang'] ?? 'es');

$CONFIG = __DIR__ . '/config.php';

/**
 * Lee config.php sin ejecutarlo. Definir sus constantes aquí estorbaría cuando
 * el instalador escriba la versión nueva y la cargue en la misma petición.
 */
function read_config(string $path): array {
    if (!is_readable($path)) return [];
    preg_match_all("/define\(\s*'([A-Z0-9_]+)'\s*,\s*'((?:[^'\\\\]|\\\\.)*)'\s*\)/", (string)file_get_contents($path), $m);
    return array_combine($m[1], array_map(fn($v) => stripslashes($v), $m[2])) ?: [];
}

function try_pdo(string $host, string $name, string $user, string $pass): PDO {
    return new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

/* ---------- ¿Ya está instalado? ---------- */
$existing = read_config($CONFIG);
if ($existing) {
    try {
        $pdo = try_pdo($existing['DB_HOST'] ?? 'localhost', $existing['DB_NAME'] ?? '', $existing['DB_USER'] ?? '', $existing['DB_PASS'] ?? '');
        $st = $pdo->query("SELECT svalue FROM settings WHERE skey = 'installed'");
        if ($st && $st->fetchColumn() === '1') {
            setup_head(t('inst_title'));
            echo '<h1>' . e(t('inst_locked')) . '</h1>';
            echo '<div class="card"><div class="actions">'
               . '<a class="btn" href="' . e(($existing['SITE_URL'] ?? '.') . '/admin/') . '">' . e(t('admin_panel')) . '</a>'
               . '<a class="btn ghost" href="upgrade.php">' . e(t('upg_title')) . '</a>'
               . '</div></div>';
            setup_foot();
            exit;
        }
    } catch (Throwable $ex) {
        // config a medias: seguimos con el instalador
    }
}

/* ---------- Estado del asistente ---------- */
$STEPS = [t('inst_s1'), t('inst_s2'), t('inst_s3'), t('inst_s4'), t('inst_s5'), t('inst_s6'), t('inst_s7')];
$LAST  = count($STEPS);

$guessUrl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://'
          . ($_SERVER['HTTP_HOST'] ?? 'localhost')
          . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$guessHost = parse_url($guessUrl, PHP_URL_HOST) ?: 'tu-dominio.com';

if (!isset($_SESSION['inst']) || !is_array($_SESSION['inst'])) {
    $_SESSION['inst'] = [
        'db_host' => $existing['DB_HOST'] ?? 'localhost',
        'db_name' => $existing['DB_NAME'] ?? '',
        'db_user' => $existing['DB_USER'] ?? '',
        'db_pass' => $existing['DB_PASS'] ?? '',
        'site_url' => $existing['SITE_URL'] ?? $guessUrl,
        'contact_email' => '',
        'contact_from'  => 'no-reply@' . $guessHost,
        'admin_user' => '', 'admin_pass' => '', 'admin_pass2' => '',
        'owner_name' => '', 'owner_bio' => '', 'owner_email_public' => '',
        'purchase_day' => '4', 'site_name' => '', 'accent_color' => '#2F7D6E',
        'skin' => 'classic', 'font_pair' => 'system', 'hero_vehicle' => 'coin',
        'currency' => 'MXN', 'timezone' => 'America/Monterrey', 'fallback_usd_local' => '',
        'ejercicio_mode' => 'link', 'load_demo' => '1',
        'privacy_mode' => 'full', 'register_network' => '1',
    ];
    foreach (array_keys(social_networks_setup()) as $k) $_SESSION['inst']['social_' . $k] = '';
}
$D = &$_SESSION['inst'];

function social_networks_setup(): array {
    return ['x' => 'X', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'facebook' => 'Facebook',
            'youtube' => 'YouTube', 'github' => 'GitHub', 'nostr' => 'Nostr'];
}

$step   = 1;
$errors = [];
$done   = false;
$manualConfig = '';

/* ---------- Captura y validación ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = max(1, min($LAST, (int)($_POST['step'] ?? 1)));
    foreach ($_POST as $k => $v) {
        if ($k === 'step' || $k === 'nav') continue;
        if (array_key_exists($k, $D)) $D[$k] = is_string($v) ? trim($v) : $v;
    }
    // Las casillas no llegan cuando están apagadas
    if ($step === 5) $D['load_demo'] = isset($_POST['load_demo']) ? '1' : '0';
    if ($step === 7) $D['register_network'] = isset($_POST['register_network']) ? '1' : '0';

    if (($_POST['nav'] ?? '') === 'prev') {
        $step = max(1, $step - 1);
    } else {
        $errors = validate_step($step, $D);
        if (!$errors) {
            if ($step === $LAST) {
                [$errors, $manualConfig] = apply_install($D, $CONFIG);
                if (!$errors) { $done = true; }
            } else {
                $step++;
            }
        }
    }
} else {
    $step = max(1, min($LAST, (int)($_GET['step'] ?? 1)));
}

function validate_step(int $step, array $D): array {
    $err = [];
    if ($step === 1) {
        if (!filter_var($D['site_url'], FILTER_VALIDATE_URL)) $err[] = t('inst_err_url');
        if (!filter_var($D['contact_email'], FILTER_VALIDATE_EMAIL)) $err[] = t('inst_err_email');
        try {
            $pdo = try_pdo($D['db_host'], $D['db_name'], $D['db_user'], $D['db_pass']);
            $st = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'settings'");
            if ((int)$st->fetchColumn() > 0) {
                $q = $pdo->query("SELECT svalue FROM settings WHERE skey = 'installed'");
                if ($q && $q->fetchColumn() === '1') $err[] = t('inst_db_exists');
            }
        } catch (Throwable $ex) {
            $err[] = t('inst_db_fail', $ex->getMessage());
        }
    }
    if ($step === 2) {
        if ($D['admin_user'] === '' || !preg_match('/^[A-Za-z0-9._-]{3,50}$/', $D['admin_user'])) $err[] = t('inst_admin_user') . ': 3-50 (A-Z, 0-9, . _ -)';
        if (strlen($D['admin_pass']) < 8 || $D['admin_pass'] !== $D['admin_pass2']) $err[] = t('inst_err_pass');
    }
    if ($step === 3) {
        if ($D['owner_name'] === '') $err[] = t('inst_err_name');
        if ($D['owner_email_public'] !== '' && !filter_var($D['owner_email_public'], FILTER_VALIDATE_EMAIL)) $err[] = t('inst_err_email');
    }
    if ($step === 4) {
        if ($D['site_name'] === '') $err[] = t('inst_err_sitename');
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $D['accent_color'])) $err[] = t('brand_accent') . ': #RRGGBB';
        if (!isset(currencies()[$D['currency']])) $err[] = t('brand_currency');
        if (!in_array($D['timezone'], timezone_identifiers_list(), true)) $err[] = t('brand_tz');
    }
    return $err;
}

/** Genera el contenido de config.php con los valores capturados. */
function config_contents(array $D): string {
    $q = fn($v) => var_export((string)$v, true);
    return "<?php\n"
        . "/**\n * Jueves de Satoshi — Configuración (generada por el instalador).\n"
        . " * Solo secretos e infraestructura. La identidad, la marca y los textos\n"
        . " * viven en la base de datos y se editan desde el panel.\n"
        . " * NUNCA subas este archivo a un repositorio.\n */\n\n"
        . "define('DB_HOST', {$q($D['db_host'])});\n"
        . "define('DB_NAME', {$q($D['db_name'])});\n"
        . "define('DB_USER', {$q($D['db_user'])});\n"
        . "define('DB_PASS', {$q($D['db_pass'])});\n\n"
        . "define('SITE_URL', {$q(rtrim($D['site_url'], '/'))});\n\n"
        . "define('CONTACT_EMAIL', {$q($D['contact_email'])});\n"
        . "define('CONTACT_FROM', {$q($D['contact_from'])});\n\n"
        . "define('PRICE_CACHE_MINUTES', 10);\n\n"
        . "define('GA4_ID', '');\ndefine('GTM_ID', '');\ndefine('META_PIXEL_ID', '');\n"
        . "define('RECAPTCHA_SITE_KEY', '');\ndefine('RECAPTCHA_SECRET', '');\n\n"
        . "define('HUB_URL', 'https://satoshi.jorgeavila.com');\n";
}

/** Ejecuta las sentencias de schema.sql una por una. */
function import_schema(PDO $pdo, string $file): void {
    require_once __DIR__ . '/includes/migrate.php';
    foreach (sql_statements((string)file_get_contents($file)) as $stmt) {
        $pdo->exec($stmt);
    }
}

/**
 * Escribe todo. Devuelve [errores, contenido de config.php para pegar a mano].
 */
function apply_install(array $D, string $configPath): array {
    $contents = config_contents($D);

    if (!is_readable($configPath) || md5_file($configPath) !== md5($contents)) {
        if (@file_put_contents($configPath, $contents) === false) {
            return [[t('inst_err_write')], $contents];
        }
        @chmod($configPath, 0640);
    }

    // Con config.php en su lugar ya podemos usar el resto del sistema.
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/migrate.php';
    require_once __DIR__ . '/includes/demo.php';

    try {
        import_schema(db(), __DIR__ . '/schema.sql');
        migrations_stamp_all();

        // Administrador
        $st = db()->prepare('INSERT INTO users (username, pass_hash) VALUES (?, ?)
                             ON DUPLICATE KEY UPDATE pass_hash = VALUES(pass_hash)');
        $st->execute([$D['admin_user'], password_hash($D['admin_pass'], PASSWORD_DEFAULT)]);

        $pairs = [
            'locale'       => $_SESSION['inst_lang'] ?? 'es',
            'timezone'     => $D['timezone'],
            'currency'     => $D['currency'],
            'owner_name'   => $D['owner_name'],
            'owner_bio'    => $D['owner_bio'],
            'owner_email_public' => $D['owner_email_public'],
            'site_name'    => $D['site_name'],
            'purchase_day' => $D['purchase_day'],
            'accent_color' => $D['accent_color'],
            'skin'         => $D['skin'],
            'skin_chosen'  => '1',
            'font_pair'    => $D['font_pair'],
            'hero_vehicle' => $D['hero_vehicle'],
            'hero_chosen'  => '1',
            'logo_mode'    => 'mono',
            'ejercicio_mode' => $D['ejercicio_mode'],
            'privacy_mode' => $D['privacy_mode'],
        'fallback_usd_local' => ((float)($D['fallback_usd_local'] ?? 0) > 0)
            ? (string)(float)$D['fallback_usd_local']
            : ($D['currency'] === 'USD' ? '1' : get_setting('fallback_usd_local', '1')),
            'installed'    => '1',
        ];
        foreach (array_keys(social_networks_setup()) as $k) {
            $pairs['social_' . $k] = filter_var($D['social_' . $k] ?? '', FILTER_VALIDATE_URL) ? $D['social_' . $k] : '';
        }
        set_settings($pairs);

        if (($D['load_demo'] ?? '0') === '1') load_demo_data(13);

        if (($D['register_network'] ?? '0') === '1') {
            // Registrarse en el directorio implica compartir el pulso agregado.
            set_setting('share_aggregate', '1');
            network_request(); // si el maestro no responde, se puede reintentar desde el panel
        }
    } catch (Throwable $ex) {
        return [[$ex->getMessage()], ''];
    }
    return [[], ''];
}

/* ---------- Pantalla final ---------- */
if ($done) {
    $siteUrl = rtrim($D['site_url'], '/');
    $u = uniqueness();
    unset($_SESSION['inst']);
    setup_head(t('inst_title'), t('inst_done'));
    ?>
    <h1><?= e(t('inst_finish_title')) ?></h1>
    <div class="card">
      <div class="alert ok"><?= t('inst_finish_body') ?></div>
      <p class="hint"><?= e(t('inst_finish_uniq', $u['pct'])) ?></p>
      <div class="actions">
        <a class="btn" href="<?= e($siteUrl) ?>/admin/"><?= e(t('inst_go_admin')) ?></a>
        <a class="btn ghost" href="<?= e($siteUrl) ?>/"><?= e(t('inst_go_site')) ?></a>
      </div>
    </div>
    <?php
    setup_foot();
    exit;
}

/* ---------- Formulario ---------- */
setup_head(t('inst_title'), t('inst_step', $step, $LAST));
setup_steps($STEPS, $step);

if ($manualConfig !== '') {
    echo '<div class="alert err">' . e(t('inst_err_write')) . '</div>';
    echo '<pre>' . e($manualConfig) . '</pre>';
}
if ($errors) {
    echo '<div class="alert err"><ul style="margin:0;padding-left:18px">';
    foreach ($errors as $er) echo '<li>' . e($er) . '</li>';
    echo '</ul></div>';
}

$v = fn(string $k) => e($D[$k] ?? '');
?>
<div class="card">
<form method="post" autocomplete="off">
<input type="hidden" name="step" value="<?= $step ?>">

<?php if ($step === 1): ?>
  <h1><?= e(t('inst_welcome')) ?></h1>
  <p class="sub">
    <?= e(t('inst_db_hint')) ?>
    &nbsp;·&nbsp;
    <a href="?lang=es">Español</a> / <a href="?lang=en">English</a>
  </p>
  <div class="grid2">
    <div class="field"><label><?= e(t('inst_db_host')) ?></label><input type="text" name="db_host" value="<?= $v('db_host') ?>" required></div>
    <div class="field"><label><?= e(t('inst_db_name')) ?></label><input type="text" name="db_name" value="<?= $v('db_name') ?>" required></div>
    <div class="field"><label><?= e(t('inst_db_user')) ?></label><input type="text" name="db_user" value="<?= $v('db_user') ?>" required></div>
    <div class="field"><label><?= e(t('inst_db_pass')) ?></label><input type="password" name="db_pass" value="<?= $v('db_pass') ?>"></div>
  </div>
  <div class="field">
    <label><?= e(t('inst_site_url')) ?></label>
    <input type="url" name="site_url" value="<?= $v('site_url') ?>" required>
    <p class="hint"><?= e(t('inst_site_url_hint')) ?></p>
  </div>
  <div class="grid2">
    <div class="field">
      <label><?= e(t('inst_contact_email')) ?></label>
      <input type="email" name="contact_email" value="<?= $v('contact_email') ?>" required>
      <p class="hint"><?= e(t('inst_contact_email_hint')) ?></p>
    </div>
    <div class="field">
      <label><?= e(t('inst_contact_from')) ?></label>
      <input type="text" name="contact_from" value="<?= $v('contact_from') ?>">
      <p class="hint"><?= e(t('inst_contact_from_hint')) ?></p>
    </div>
  </div>

<?php elseif ($step === 2): ?>
  <h1><?= e(t('inst_s2')) ?></h1>
  <p class="sub"><?= e(t('inst_admin_hint')) ?></p>
  <div class="field"><label><?= e(t('inst_admin_user')) ?></label><input type="text" name="admin_user" value="<?= $v('admin_user') ?>" required></div>
  <div class="grid2">
    <div class="field"><label><?= e(t('inst_admin_pass')) ?></label><input type="password" name="admin_pass" minlength="8" required></div>
    <div class="field"><label><?= e(t('inst_admin_pass2')) ?></label><input type="password" name="admin_pass2" minlength="8" required></div>
  </div>

<?php elseif ($step === 3): ?>
  <h1><?= e(t('id_title')) ?></h1>
  <p class="sub"><?= e(t('inst_identity_hint')) ?></p>
  <div class="field">
    <label><?= e(t('id_name')) ?></label>
    <input type="text" name="owner_name" value="<?= $v('owner_name') ?>" required maxlength="120">
    <p class="hint"><?= e(t('id_name_hint')) ?></p>
  </div>
  <div class="field"><label><?= e(t('id_bio')) ?></label><input type="text" name="owner_bio" value="<?= $v('owner_bio') ?>" maxlength="200"></div>
  <div class="field">
    <label><?= e(t('id_email_public')) ?></label>
    <input type="email" name="owner_email_public" value="<?= $v('owner_email_public') ?>">
    <p class="hint"><?= e(t('id_email_public_hint')) ?></p>
  </div>
  <h2><?= e(t('id_socials')) ?></h2>
  <p class="hint" style="margin-bottom:14px"><?= e(t('id_socials_hint')) ?></p>
  <div class="grid2">
    <?php foreach (social_networks_setup() as $k => $label): ?>
      <div class="field"><label><?= e($label) ?></label>
        <input type="url" name="social_<?= e($k) ?>" value="<?= $v('social_' . $k) ?>" placeholder="https://">
      </div>
    <?php endforeach; ?>
  </div>

<?php elseif ($step === 4): ?>
  <h1><?= e(t('brand_title')) ?></h1>
  <p class="sub"><?= e(t('inst_brand_hint')) ?></p>
  <div class="grid2">
    <div class="field">
      <label><?= e(t('brand_day')) ?></label>
      <select name="purchase_day" id="fDay">
        <?php foreach (tr('days_cap') as $i => $nm): ?>
          <option value="<?= $i ?>" <?= (int)$D['purchase_day'] === $i ? 'selected' : '' ?>><?= e($nm) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="hint"><?= e(t('brand_day_hint')) ?></p>
    </div>
    <div class="field">
      <label><?= e(t('brand_name')) ?></label>
      <input type="text" name="site_name" id="fName" value="<?= $v('site_name') ?>" required maxlength="80">
      <p class="hint" id="hName"><?= e(t('brand_name_hint', suggested_site_name((int)$D['purchase_day']))) ?></p>
    </div>
  </div>
  <div class="field">
    <label><?= e(t('brand_accent')) ?></label>
    <span style="display:flex;gap:10px;align-items:center">
      <input type="color" name="accent_color" value="<?= $v('accent_color') ?>">
      <span class="hint" style="margin:0"><?= e(t('brand_accent_hint')) ?></span>
    </span>
  </div>
  <h2><?= e(t('brand_skin')) ?></h2>
  <?php foreach (skin_catalog() as $k => $meta): ?>
    <label class="choice">
      <input type="radio" name="skin" value="<?= e($k) ?>" <?= $D['skin'] === $k ? 'checked' : '' ?>>
      <strong><?= e(kit_name(skin_catalog(), $k)) ?></strong>
      <p class="hint"><?= e(kit_desc(skin_catalog(), $k)) ?></p>
    </label>
  <?php endforeach; ?>
  <h2><?= e(t('brand_font')) ?></h2>
  <?php foreach (font_catalog() as $k => $meta): ?>
    <label class="choice">
      <input type="radio" name="font_pair" value="<?= e($k) ?>" <?= $D['font_pair'] === $k ? 'checked' : '' ?>>
      <strong><?= e(kit_name(font_catalog(), $k)) ?></strong>
      <p class="hint"><?= e(kit_desc(font_catalog(), $k)) ?></p>
    </label>
  <?php endforeach; ?>
  <h2><?= e(t('brand_hero')) ?></h2>
  <p class="hint" style="margin-bottom:12px"><?= e(t('brand_hero_hint')) ?></p>
  <div class="grid2">
    <?php foreach (vehicle_catalog() as $k => $meta): ?>
      <label class="choice">
        <input type="radio" name="hero_vehicle" value="<?= e($k) ?>" <?= $D['hero_vehicle'] === $k ? 'checked' : '' ?>>
        <?= e($meta['glyph']) ?> <strong><?= e(kit_name(vehicle_catalog(), $k)) ?></strong>
      </label>
    <?php endforeach; ?>
  </div>
  <div class="grid2">
    <div class="field">
      <label><?= e(t('brand_currency')) ?></label>
      <select name="currency">
        <?php foreach (currencies() as $code => $c): ?>
          <option value="<?= e($code) ?>" <?= $D['currency'] === $code ? 'selected' : '' ?>><?= e($code . ' — ' . $c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="hint"><?= e(t('brand_currency_hint')) ?></p>
    </div>
    <div class="field">
      <label><?= e(t('brand_tz')) ?></label>
      <select name="timezone">
        <?php foreach (timezones_setup() as $tz): ?>
          <option value="<?= e($tz) ?>" <?= $D['timezone'] === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="field" id="fxBox" style="display:none">
    <label>USD/<span id="fxCur"></span></label>
    <input type="number" name="fallback_usd_local" step="0.0001" min="0.0001" value="<?= $v('fallback_usd_local') ?>">
    <p class="hint" id="fxHint"></p>
  </div>
  <script>
  (function () {
    // El tipo de cambio de respaldo solo importa en monedas que CoinGecko no cotiza.
    var noCg = <?= json_encode(array_keys(array_filter(currencies(), fn($c) => !$c['cg']))) ?>;
    var cur = document.querySelector('select[name=currency]');
    var box = document.getElementById('fxBox'), curLbl = document.getElementById('fxCur'), fxHint = document.getElementById('fxHint');
    var fxTpl = <?= json_encode(t('inst_fx_hint', '@@'), JSON_UNESCAPED_UNICODE) ?>;
    function fx() {
      var need = noCg.indexOf(cur.value) !== -1;
      box.style.display = need ? '' : 'none';
      curLbl.textContent = cur.value;
      fxHint.textContent = fxTpl.replace('@@', cur.value);
    }
    cur.addEventListener('change', fx);
    fx();
  })();
  (function () {
    var day = document.getElementById('fDay'), name = document.getElementById('fName'), hint = document.getElementById('hName');
    var sugg = <?= json_encode(array_map('suggested_site_name', range(1, 7)), JSON_UNESCAPED_UNICODE) ?>;
    var tpl = <?= json_encode(t('brand_name_hint', '@@'), JSON_UNESCAPED_UNICODE) ?>;
    day.addEventListener('change', function () {
      var s = sugg[parseInt(day.value, 10) - 1];
      hint.textContent = tpl.replace('@@', s);
      if (!name.value) name.value = s;
    });
  })();
  </script>

<?php elseif ($step === 5): ?>
  <h1><?= e(t('content_title')) ?></h1>
  <p class="sub"><?= e(t('inst_content_hint')) ?></p>
  <h2><?= e(t('content_ejercicio_mode')) ?></h2>
  <label class="choice">
    <input type="radio" name="ejercicio_mode" value="link" <?= $D['ejercicio_mode'] === 'link' ? 'checked' : '' ?>>
    <strong><?= e(t('content_mode_link')) ?></strong>
    <p class="hint"><?= e(t('content_mode_link_hint')) ?></p>
  </label>
  <label class="choice">
    <input type="radio" name="ejercicio_mode" value="own" <?= $D['ejercicio_mode'] === 'own' ? 'checked' : '' ?>>
    <strong><?= e(t('content_mode_own')) ?></strong>
    <p class="hint"><?= e(t('content_mode_own_hint')) ?></p>
  </label>
  <h2><?= e(t('inst_demo')) ?></h2>
  <label class="choice">
    <input type="checkbox" name="load_demo" value="1" <?= $D['load_demo'] === '1' ? 'checked' : '' ?>>
    <strong><?= e(t('inst_demo')) ?></strong>
    <p class="hint"><?= e(t('inst_demo_hint')) ?></p>
  </label>

<?php elseif ($step === 6): ?>
  <h1><?= e(t('privacy_title')) ?></h1>
  <p class="sub"><?= e(t('inst_privacy_hint')) ?></p>
  <?php foreach (['full', 'percent', 'vault'] as $m): ?>
    <label class="choice">
      <input type="radio" name="privacy_mode" value="<?= $m ?>" <?= $D['privacy_mode'] === $m ? 'checked' : '' ?>>
      <strong><?= e(t('privacy_' . $m)) ?></strong>
      <p class="hint"><?= e(t('privacy_' . $m . '_hint')) ?></p>
    </label>
  <?php endforeach; ?>

<?php elseif ($step === 7): ?>
  <h1><?= e(t('inst_s7')) ?></h1>
  <p class="sub"><?= e(t('inst_network_hint')) ?></p>
  <label class="choice">
    <input type="checkbox" name="register_network" value="1" <?= $D['register_network'] === '1' ? 'checked' : '' ?>>
    <strong><?= e(t('inst_network_ask', 'satoshi.jorgeavila.com')) ?></strong>
    <p class="hint"><?= e(t('inst_network_note')) ?></p>
  </label>
<?php endif; ?>

<div class="actions">
  <?php if ($step > 1): ?><button class="btn ghost" type="submit" name="nav" value="prev"><?= e(t('inst_prev')) ?></button><?php endif; ?>
  <button class="btn" type="submit" name="nav" value="next"><?= e($step === $LAST ? t('inst_finish') : t('inst_next')) ?></button>
</div>
</form>
</div>
<?php
setup_foot();

/** Zonas horarias comunes (el instalador no puede usar functions.php todavía). */
function timezones_setup(): array {
    return [
        'America/Monterrey', 'America/Mexico_City', 'America/Tijuana', 'America/Cancun',
        'America/Bogota', 'America/Lima', 'America/Santiago', 'America/Argentina/Buenos_Aires',
        'America/Sao_Paulo', 'America/Caracas', 'America/Panama', 'America/Guatemala',
        'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
        'America/Toronto', 'Europe/Madrid', 'Europe/London', 'Europe/Berlin', 'UTC',
    ];
}
