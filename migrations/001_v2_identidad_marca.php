<?php
/**
 * v1 → v2.
 *
 * Mueve la identidad, la marca y los textos del código a la base de datos, y
 * generaliza las columnas de moneda. No borra ni convierte ninguna compra: solo
 * renombra columnas y agrega llaves de configuración.
 *
 * A propósito NO copia identidad desde config.php. Toda instalación v1 mostraba
 * el nombre, la bio y las redes del autor original porque venían escritos en las
 * plantillas; heredarlos aquí repetiría ese error. El sitio queda cerrado con un
 * aviso de "instalación pendiente" hasta que su dueño escriba quién es.
 *
 * El sitio maestro recupera sus propios textos desde modules/hub/seed-master.php,
 * que vive en el repositorio privado del hub y por eso no viaja en esta copia.
 */

/* --- 1. Tablas nuevas --- */
mig_run('CREATE TABLE IF NOT EXISTS content (
  ckey VARCHAR(60) NOT NULL,
  cvalue TEXT NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (ckey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

/* --- 2. settings.svalue pasa a TEXT: los textos largos ya no caben en 500 --- */
mig_run('ALTER TABLE settings MODIFY svalue TEXT NOT NULL');
mig_run('ALTER TABLE settings MODIFY skey VARCHAR(60) NOT NULL');

/* --- 3. Columnas de moneda: de "MXN" a "la moneda del sitio" --- */
mig_rename_col('years', 'weekly_amount_mxn', 'weekly_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00');
mig_rename_col('purchases', 'monto_mxn', 'monto_local', 'DECIMAL(12,2) NOT NULL');
mig_rename_col('purchases', 'precio_mxn_btc', 'precio_local_btc', 'DECIMAL(18,2) NOT NULL');
mig_rename_col('purchases', 'tipo_cambio', 'tipo_cambio_usd', 'DECIMAL(12,6) NOT NULL DEFAULT 1');

/* --- 4. El TC de respaldo deja de llamarse "mxn" --- */
$viejo = db()->query("SELECT svalue FROM settings WHERE skey = 'fallback_usd_mxn'")->fetchColumn();
if ($viejo !== false) {
    $st = db()->prepare("INSERT IGNORE INTO settings (skey, svalue) VALUES ('fallback_usd_local', ?)");
    $st->execute([$viejo]);
    mig_run("DELETE FROM settings WHERE skey = 'fallback_usd_mxn'");
}

/* --- 5. El caché de precios cambió de forma: se descarta y se vuelve a pedir --- */
mig_run("DELETE FROM settings WHERE skey = 'price_cache'");

/* --- 6. Semilla del sitio maestro, si este servidor tiene el módulo hub.
       Va ANTES de los defaults para que el INSERT IGNORE respete sus valores. --- */
$seedFile = __DIR__ . '/../modules/hub/seed-master.php';
if (is_readable($seedFile)) require $seedFile;

/* --- 7. Defaults. INSERT IGNORE respeta cualquier valor que ya exista --- */
$defaults = [
    'installed'          => '1',   // ya tenía datos: es una instalación real
    'locale'             => 'es',
    'currency'           => 'MXN',
    'purchase_day'       => '4',
    'accent_color'       => '#F7931A',
    'skin'               => 'classic',
    'font_pair'          => 'system',
    'logo_mode'          => 'mono',
    'hero_vehicle'       => 'coin',
    'privacy_mode'       => 'full',
    'ejercicio_mode'     => 'link', // no duplicar el texto del sitio original
    'tool_exchange'      => 'aureo',
    'tool_wallet'        => 'wos',
    'owner_name'         => '',
    'owner_bio'          => '',
    'owner_avatar'       => '',
    'site_name'          => '',
    'fallback_btc_usd'   => '60000',
    'fallback_usd_local' => '17.5',
];
$st = db()->prepare('INSERT IGNORE INTO settings (skey, svalue) VALUES (?, ?)');
foreach ($defaults as $k => $v) $st->execute([$k, $v]);
