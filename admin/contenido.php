<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

/** Argumentos que necesita cada plantilla para renderizarse. */
function tpl_args(string $key): array {
    $args = [
        'hero_title' => [day_name(purchase_day())],
        'hero_lead'  => [day_name(purchase_day()), site_name()],
    ];
    return $args[$key] ?? [];
}

/** Texto plantilla de un bloque, ya resuelto en el idioma del sitio. */
function tpl_text(string $key): string {
    return t('tpl_' . $key, ...tpl_args($key));
}

/** La plantilla en otro idioma, para mostrarla de guía en el campo de traducción. */
function tpl_text_in(string $key, string $locale): string {
    $prev = $GLOBALS['jds_locale'] ?? null;
    $GLOBALS['jds_locale'] = $locale;
    $out = tpl_text($key);
    if ($prev === null) unset($GLOBALS['jds_locale']); else $GLOBALS['jds_locale'] = $prev;
    return $out;
}

// Idiomas además del principal: cada bloque tiene un campo de traducción por idioma
$extraLocs = array_values(array_diff(site_locales(), [site_locale()]));

$ok = ''; $error = '';

// Volver un bloque a su versión estándar
if (($_GET['restore'] ?? '') !== '') {
    if (!hash_equals(csrf_token(), $_GET['t'] ?? '')) die(t('csrf_bad'));
    $key = $_GET['restore'];
    if (in_array($key, content_keys(), true)) {
        set_content($key, '', true);
        header('Location: ' . SITE_URL . '/admin/contenido.php?msg=ok'); exit;
    }
}
if (($_GET['msg'] ?? '') === 'ok') $ok = t('saved_ok');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $mode = ($_POST['ejercicio_mode'] ?? 'link') === 'own' ? 'own' : 'link';
    set_setting('ejercicio_mode', $mode);

    foreach (content_keys() as $key) {
        $raw = trim((string)($_POST['c_' . $key] ?? ''));
        $val = clean_html($raw);
        // Si quedó vacío o idéntico a la plantilla, sigue siendo el texto estándar.
        if ($val === '' || $val === clean_html(tpl_text($key))) {
            set_content($key, '', true);
        } else {
            set_content($key, $val, false);
        }
        foreach ($extraLocs as $l) {
            set_content_tr($key, $l, clean_html(trim((string)($_POST['tr_' . $l . '_' . $key] ?? ''))));
        }
    }
    $ok = t('saved_ok');
}

$mode = get_setting('ejercicio_mode', 'link');

/** Etiqueta de cada bloque y si conviene un campo alto. */
$blocks = [
    'hero_title'          => ['label' => t('content_hero_title'),          'rows' => 2],
    'hero_lead'           => ['label' => t('content_hero_lead'),           'rows' => 3],
    'home_about'          => ['label' => t('content_home_about'),          'rows' => 3],
    'ejercicio_title'     => ['label' => t('content_ejercicio_title'),     'rows' => 2],
    'ejercicio_body'      => ['label' => t('content_ejercicio_body'),      'rows' => 10],
    'contacto_intro'      => ['label' => t('content_contacto_intro'),      'rows' => 3],
    'herramientas_intro'  => ['label' => t('content_herramientas_intro'),  'rows' => 3],
    'vault_message'       => ['label' => t('content_vault_message'),       'rows' => 3],
];

page_head(t('admin_content'), '', true);
admin_chrome('contenido');
?>
<section class="block">
  <div class="container" style="max-width:820px">
    <h2><?= e(t('content_title')) ?></h2>
    <p class="section-sub"><?= e(t('content_sub')) ?></p>

    <?php if ($ok): ?><div class="alert alert-ok">✅ <?= e($ok) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>
    <?php foreach ($extraLocs as $l): $pend = count(content_untranslated($l)); ?>
      <div class="alert <?= $pend ? 'alert-warn' : 'alert-ok' ?>">
        <?= $pend ? '🌐 ' . e(t('content_tr_pending', $pend, t('lang_name_' . $l), t('lang_name_' . site_locale())))
                  : '✅ ' . e(t('content_tr_done', t('lang_name_' . $l))) ?>
      </div>
    <?php endforeach; ?>

    <div class="form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <h3 style="margin:0 0 10px;font-size:1.05rem"><?= e(t('content_ejercicio_mode')) ?></h3>
        <div class="choice-list">
          <label class="choice">
            <input type="radio" name="ejercicio_mode" value="link" <?= $mode === 'link' ? 'checked' : '' ?>>
            <strong><?= e(t('content_mode_link')) ?></strong>
            <p class="hint"><?= e(t('content_mode_link_hint')) ?></p>
          </label>
          <label class="choice">
            <input type="radio" name="ejercicio_mode" value="own" <?= $mode === 'own' ? 'checked' : '' ?>>
            <strong><?= e(t('content_mode_own')) ?></strong>
            <p class="hint"><?= e(t('content_mode_own_hint')) ?></p>
          </label>
        </div>

        <p class="hint" style="margin:22px 0 14px"><?= e(t('content_html_hint')) ?></p>

        <?php foreach ($blocks as $key => $b): $custom = content_is_custom($key); ?>
          <div class="form-group">
            <label style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
              <span><?= e($b['label']) ?></span>
              <span style="font-weight:600;font-size:0.78rem;color:<?= $custom ? 'var(--green)' : 'var(--text-soft)' ?>">
                <?= $custom ? '✅ ' . e(t('content_custom_badge')) : '◻️ ' . e(t('content_default_badge')) ?>
                <?php if ($custom): ?>
                  · <a href="?restore=<?= e($key) ?>&t=<?= csrf_token() ?>"><?= e(t('content_restore')) ?></a>
                <?php endif; ?>
              </span>
            </label>
            <textarea name="c_<?= e($key) ?>" rows="<?= (int)$b['rows'] ?>"><?= e($custom ? content_all()[$key]['cvalue'] : tpl_text($key)) ?></textarea>
          </div>
          <?php foreach ($extraLocs as $l): $tr = content_tr($key, $l); ?>
            <div class="form-group tr-field">
              <label style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <span>↳ <?= e(t('content_tr_label', t('lang_name_' . $l))) ?></span>
                <span style="font-weight:600;font-size:0.78rem;color:<?= $tr !== '' ? 'var(--green)' : 'var(--text-soft)' ?>">
                  <?= $tr !== '' ? '✅ ' . e(t('content_tr_ok'))
                     : ($custom ? '◻️ ' . e(t('content_tr_missing', t('lang_name_' . site_locale())))
                                : '◻️ ' . e(t('content_tr_template'))) ?>
                </span>
              </label>
              <textarea name="tr_<?= e($l) ?>_<?= e($key) ?>" rows="<?= (int)$b['rows'] ?>" lang="<?= e($l) ?>"
                        placeholder="<?= e(strip_tags(tpl_text_in($key, $l))) ?>"><?= e($tr) ?></textarea>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>

        <button class="btn" type="submit"><?= e(t('save')) ?></button>
      </form>
    </div>
  </div>
</section>
<?php page_foot(); ?>
