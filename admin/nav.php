<?php
/** Barra y menú del panel, más el aviso del índice de unicidad. */
require_once __DIR__ . '/../includes/functions.php';

function admin_chrome(string $current): void {
    $items = [
        'compras'      => ['url' => 'index.php',       'label' => t('admin_purchases')],
        'anios'        => ['url' => 'anio.php',        'label' => t('admin_years')],
        'identidad'    => ['url' => 'identidad.php',   'label' => t('admin_identity')],
        'marca'        => ['url' => 'marca.php',       'label' => t('admin_brand')],
        'contenido'    => ['url' => 'contenido.php',   'label' => t('admin_content')],
        'herramientas' => ['url' => 'herramientas.php','label' => t('admin_tools')],
        'privacidad'   => ['url' => 'privacidad.php',  'label' => t('admin_privacy')],
        'password'     => ['url' => 'password.php',    'label' => t('admin_password')],
    ];
    // En un nodo, la pestaña de red es para pedir lugar en el directorio.
    // En el hub la aporta el módulo, que además administra la cola.
    if (!is_hub()) {
        $items['red'] = ['url' => 'red.php', 'label' => t('admin_network')];
    }
    // Los módulos instalados agregan sus propias entradas (el hub, su cola de nodos).
    foreach (modules_admin_menu() as $extra) {
        $items['mod_' . md5($extra['url'])] = $extra;
    }
    ?>
    <div class="admin-bar">
      <div class="container">
        <span><?= e(t('admin_panel')) ?> — <?= e(site_name() ?: HUB_PROJECT) ?></span>
        <span>
          <a href="<?= SITE_URL ?>/" target="_blank" rel="noopener"><?= e(t('admin_view_site')) ?></a> ·
          <a href="<?= SITE_URL ?>/admin/index.php?action=logout"><?= e(t('admin_logout')) ?></a>
        </span>
      </div>
    </div>
    <nav class="admin-nav">
      <div class="container">
        <?php foreach ($items as $key => $it): ?>
          <a class="<?= $key === $current ? 'on' : '' ?>" href="<?= e(SITE_URL . '/admin/' . $it['url']) ?>">
            <?= e($it['label']) ?><?php if (!empty($it['badge'])): ?><span class="count"><?= (int)$it['badge'] ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </nav>
    <?php
    // Archivos nuevos con la base atrasada: el sitio público sigue en pie (el
    // código tolera el esquema anterior), pero el dueño debe saberlo aquí.
    require_once __DIR__ . '/../includes/migrate.php';
    $pend = 0;
    try { $pend = count(migrations_pending()); } catch (Throwable $ex) { $pend = 0; }
    if ($pend > 0): ?>
    <div class="container" style="padding-top:18px">
      <div class="alert alert-warn">
        🛠️ <?= e(t('upgrade_pending', $pend)) ?>
        <a href="<?= SITE_URL ?>/upgrade.php" style="margin-left:8px"><?= e(t('upgrade_go')) ?> →</a>
      </div>
    </div>
    <?php endif;
    // El aviso solo aparece mientras el sitio se siga pareciendo al original.
    $u = uniqueness();
    if (!is_hub() && $u['pct'] < 70 && $current !== 'marca' && $current !== 'identidad'):
    ?>
    <div class="container" style="padding-top:18px">
      <div class="alert alert-warn">
        ⚠️ <?= e(t('uniq_banner', $u['pct'])) ?>
        <a href="<?= SITE_URL ?>/admin/marca.php" style="margin-left:8px"><?= e(t('uniq_go')) ?> →</a>
      </div>
    </div>
    <?php endif;
}

/**
 * Sube una imagen al directorio de assets. Devuelve [nombre, error].
 * Valida por contenido, no por extensión: una extensión se falsifica en un clic.
 */
function admin_upload_image(string $field, string $prefix, int $maxBytes = 1048576, bool $allowSvg = false): array {
    if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? 1) === UPLOAD_ERR_NO_FILE) {
        return ['', ''];
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) return ['', 'upload_error'];
    if ($f['size'] > $maxBytes) return ['', 'too_big'];

    $info = @getimagesize($f['tmp_name']);
    $ext  = '';
    if ($info) {
        $map = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
        $ext = $map[$info[2]] ?? '';
    } elseif ($allowSvg) {
        $head = (string)file_get_contents($f['tmp_name'], false, null, 0, 512);
        // SVG con scripts o handlers dentro no entra.
        $body = (string)file_get_contents($f['tmp_name']);
        $peligroso = '/<script|<foreignObject|<!ENTITY|on[a-z]+\s*=|(href|src)\s*=\s*["\']?\s*(javascript|data):/i';
        if (stripos($head, '<svg') !== false && !preg_match($peligroso, $body)) {
            $ext = 'svg';
        }
    }
    if ($ext === '') return ['', 'bad_type'];

    $name = $prefix . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = __DIR__ . '/../assets/img/' . $name;
    if (!@move_uploaded_file($f['tmp_name'], $dest)) return ['', 'move_failed'];
    @chmod($dest, 0644);
    return [$name, ''];
}

/** Borra un archivo de assets/img si existe (para reemplazar avatar o logo). */
function admin_delete_asset(string $name): void {
    if ($name === '') return;
    $p = __DIR__ . '/../assets/img/' . basename($name);
    if (is_file($p) && basename($name) !== 'avatar-placeholder.svg') @unlink($p);
}
