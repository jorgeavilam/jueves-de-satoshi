<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$ok = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim($_POST['site_name'] ?? '');
    $acc  = trim($_POST['accent_color'] ?? '');
    $day  = (int)($_POST['purchase_day'] ?? 4);
    $cur  = strtoupper(trim($_POST['currency'] ?? 'MXN'));
    $loc  = $_POST['locale'] ?? 'es';
    $tz   = $_POST['timezone'] ?? 'UTC';

    if ($name === '') {
        $error = t('inst_err_sitename');
    } elseif (!preg_match('/^#[0-9A-Fa-f]{6}$/', $acc)) {
        $error = t('brand_accent') . ': #RRGGBB';
    } elseif (!isset(currencies()[$cur]) || $day < 1 || $day > 7
              || !in_array($loc, JDS_LOCALES, true) || !in_array($tz, timezones(), true)) {
        $error = t('admin_year_bad');
    } else {
        $pairs = [
            'site_name'    => mb_substr($name, 0, 80),
            'accent_color' => strtoupper($acc),
            'purchase_day' => (string)$day,
            'currency'     => $cur,
            'locale'       => $loc,
            'timezone'     => $tz,
            'skin'         => in_array($_POST['skin'] ?? '', skins(), true) ? $_POST['skin'] : skin(),
            'skin_chosen'  => '1',
            'font_pair'    => in_array($_POST['font_pair'] ?? '', font_pairs(), true) ? $_POST['font_pair'] : font_pair(),
            'hero_vehicle' => in_array($_POST['hero_vehicle'] ?? '', coaster_vehicles(), true) ? $_POST['hero_vehicle'] : hero_vehicle(),
            'hero_chosen'  => '1',
            'logo_mode'    => ($_POST['logo_mode'] ?? 'mono') === 'upload' ? 'upload' : 'mono',
        ];
        // El maestro no elige fondo: conserva el original, que ningún nodo puede tomar
        if (!is_hub()) {
            $pairs['bg_tone'] = isset(bg_catalog()[$_POST['bg_tone'] ?? '']) ? $_POST['bg_tone'] : bg_tone();
        }
        // Tipo de cambio de respaldo, para monedas que CoinGecko no cotiza
        $fb = (float)($_POST['fallback_usd_local'] ?? 0);
        if ($fb > 0) $pairs['fallback_usd_local'] = (string)$fb;

        [$file, $upErr] = admin_upload_image('logo', 'logo', 512000, true);
        if ($upErr !== '') {
            $error = t('brand_logo_upload');
        } else {
            if ($file !== '') {
                admin_delete_asset(get_setting('logo_file', ''));
                $pairs['logo_file'] = $file;
                $pairs['logo_mode'] = 'upload';
            }
            if ($pairs['logo_mode'] === 'upload' && get_setting('logo_file', '') === '' && $file === '') {
                $pairs['logo_mode'] = 'mono'; // pidió subido pero no hay archivo
            }
            // Cambió la moneda: el caché de precios ya no aplica
            if ($cur !== currency_code()) $pairs['price_cache'] = '';
            set_settings($pairs);
            $ok = t('saved_ok');
        }
    }
}

$u = uniqueness();
page_head(t('admin_brand'), '', true);
admin_chrome('marca');
?>
<section class="block">
  <div class="container" style="max-width:820px">

    <div class="uniq-card">
      <div class="uniq-head">
        <div>
          <h2 style="margin:0"><?= e(t('uniq_title', $u['pct'])) ?></h2>
          <p class="section-sub" style="margin:2px 0 0"><?= e(t('uniq_sub')) ?></p>
        </div>
        <div class="uniq-pct"><?= $u['done'] ?>/<?= $u['total'] ?></div>
      </div>
      <div class="year-progress"><div class="year-progress-bar" style="width:<?= $u['pct'] ?>%"></div></div>
      <ul class="uniq-list">
        <?php foreach ($u['items'] as $it): ?>
          <li class="<?= $it['ok'] ? 'ok' : '' ?>">
            <span><?= e(t('uniq_item_' . $it['key'])) ?></span>
            <span class="tick"><?= $it['ok'] ? '✅' : '◻️' ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <h2><?= e(t('brand_title')) ?></h2>
    <p class="section-sub"><?= e(t('brand_sub')) ?></p>

    <?php if ($ok): ?><div class="alert alert-ok">✅ <?= e($ok) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>

    <div class="form-card">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <div class="form-grid">
          <div class="form-group">
            <label><?= e(t('brand_day')) ?></label>
            <select name="purchase_day" id="fDay">
              <?php foreach (tr('days_cap') as $i => $nm): ?>
                <option value="<?= $i ?>" <?= purchase_day() === $i ? 'selected' : '' ?>><?= e($nm) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="hint"><?= e(t('brand_day_hint')) ?></p>
          </div>
          <div class="form-group">
            <label><?= e(t('brand_name')) ?></label>
            <input type="text" name="site_name" id="fName" maxlength="80" required value="<?= e(site_name()) ?>">
            <p class="hint" id="hName"><?= e(t('brand_name_hint', suggested_site_name(purchase_day()))) ?></p>
          </div>
        </div>

        <div class="form-group">
          <label><?= e(t('brand_accent')) ?></label>
          <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <input type="color" name="accent_color" value="<?= e(accent_color()) ?>">
            <span class="hint" style="margin:0"><?= e(t('brand_accent_hint')) ?></span>
          </div>
        </div>

        <h3 style="margin:24px 0 6px;font-size:1.05rem"><?= e(t('brand_bg')) ?></h3>
        <?php if (is_hub()): ?>
          <p class="hint" style="margin-bottom:20px"><?= e(t('brand_bg_hub')) ?></p>
        <?php else: ?>
        <p class="hint" style="margin-bottom:10px"><?= e(t('brand_bg_hint')) ?></p>
        <div class="choice-grid">
          <?php foreach (bg_catalog() as $k => $m): ?>
            <label class="choice">
              <input type="radio" name="bg_tone" value="<?= e($k) ?>" <?= bg_tone() === $k ? 'checked' : '' ?>>
              <strong><?= e(kit_name(bg_catalog(), $k)) ?></strong>
              <span class="bg-swatch" style="background:linear-gradient(90deg, <?= e($m['swatch'][0]) ?> 50%, <?= e($m['swatch'][1]) ?> 50%)" aria-hidden="true"></span>
              <p class="hint"><?= e(kit_desc(bg_catalog(), $k)) ?></p>
            </label>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h3 style="margin:24px 0 10px;font-size:1.05rem"><?= e(t('brand_skin')) ?></h3>
        <div class="choice-list">
          <?php foreach (skin_catalog() as $k => $m): ?>
            <label class="choice">
              <input type="radio" name="skin" value="<?= e($k) ?>" <?= skin() === $k ? 'checked' : '' ?>>
              <strong><?= e(kit_name(skin_catalog(), $k)) ?></strong>
              <p class="hint"><?= e(kit_desc(skin_catalog(), $k)) ?></p>
            </label>
          <?php endforeach; ?>
        </div>

        <h3 style="margin:24px 0 10px;font-size:1.05rem"><?= e(t('brand_font')) ?></h3>
        <div class="choice-list">
          <?php foreach (font_catalog() as $k => $m): ?>
            <label class="choice">
              <input type="radio" name="font_pair" value="<?= e($k) ?>" <?= font_pair() === $k ? 'checked' : '' ?>>
              <strong><?= e(kit_name(font_catalog(), $k)) ?></strong>
              <p class="hint"><?= e(kit_desc(font_catalog(), $k)) ?></p>
            </label>
          <?php endforeach; ?>
        </div>

        <h3 style="margin:24px 0 6px;font-size:1.05rem"><?= e(t('brand_hero')) ?></h3>
        <p class="hint" style="margin-bottom:10px"><?= e(t('brand_hero_hint')) ?></p>
        <div class="choice-grid">
          <?php foreach (vehicle_catalog() as $k => $m): ?>
            <label class="choice">
              <input type="radio" name="hero_vehicle" value="<?= e($k) ?>" <?= hero_vehicle() === $k ? 'checked' : '' ?>>
              <?= e($m['glyph']) ?> <strong><?= e(kit_name(vehicle_catalog(), $k)) ?></strong>
            </label>
          <?php endforeach; ?>
        </div>

        <h3 style="margin:24px 0 10px;font-size:1.05rem"><?= e(t('brand_logo')) ?></h3>
        <div class="choice-list">
          <label class="choice">
            <input type="radio" name="logo_mode" value="mono" <?= get_setting('logo_mode', 'mono') === 'mono' ? 'checked' : '' ?>>
            <strong><?= e(t('brand_logo_mono')) ?></strong>
            <span style="display:inline-block;vertical-align:middle;margin-left:10px"><?= monogram_svg(30) ?></span>
          </label>
          <label class="choice">
            <input type="radio" name="logo_mode" value="upload" <?= get_setting('logo_mode', 'mono') === 'upload' ? 'checked' : '' ?>>
            <strong><?= e(t('brand_logo_upload')) ?></strong>
            <input type="file" name="logo" accept="image/svg+xml,image/png,image/webp" style="margin-top:8px">
          </label>
        </div>

        <h3 style="margin:24px 0 10px;font-size:1.05rem"><?= e(t('brand_currency')) ?> · <?= e(t('brand_locale')) ?></h3>
        <div class="form-grid thirds">
          <div class="form-group">
            <label><?= e(t('brand_currency')) ?></label>
            <select name="currency">
              <?php foreach (currencies() as $code => $c): ?>
                <option value="<?= e($code) ?>" <?= currency_code() === $code ? 'selected' : '' ?>><?= e($code . ' — ' . $c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label><?= e(t('brand_locale')) ?></label>
            <select name="locale">
              <option value="es" <?= current_locale() === 'es' ? 'selected' : '' ?>>Español</option>
              <option value="en" <?= current_locale() === 'en' ? 'selected' : '' ?>>English</option>
            </select>
          </div>
          <div class="form-group">
            <label><?= e(t('brand_tz')) ?></label>
            <select name="timezone">
              <?php $tzNow = get_setting('timezone', 'UTC'); foreach (timezones() as $tz): ?>
                <option value="<?= e($tz) ?>" <?= $tzNow === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <p class="hint" style="margin:-6px 0 18px"><?= e(t('brand_currency_hint')) ?></p>

        <?php if (!currency_info()['cg']): ?>
        <div class="form-group">
          <label>USD/<?= e(currency_code()) ?></label>
          <input type="number" name="fallback_usd_local" step="0.0001" min="0.0001" value="<?= e(get_setting('fallback_usd_local', '1')) ?>">
          <p class="hint">CoinGecko no cotiza <?= e(currency_code()) ?>: el precio local se calcula con este tipo de cambio.</p>
        </div>
        <?php endif; ?>

        <button class="btn" type="submit"><?= e(t('save')) ?></button>
      </form>
    </div>
  </div>
</section>
<script>
(function () {
  var day = document.getElementById('fDay'), name = document.getElementById('fName'), hint = document.getElementById('hName');
  var sugg = <?= json_encode(array_map('suggested_site_name', range(1, 7)), JSON_UNESCAPED_UNICODE) ?>;
  var tpl = <?= json_encode(t('brand_name_hint', '@@'), JSON_UNESCAPED_UNICODE) ?>;
  day.addEventListener('change', function () {
    hint.textContent = tpl.replace('@@', sugg[parseInt(day.value, 10) - 1]);
  });
})();
</script>
<?php page_foot(); ?>
