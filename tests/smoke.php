<?php
/**
 * Prueba de humo. Se corre a mano desde la línea de comandos:
 *
 *   php tests/smoke.php
 *
 * Revisa las tres cosas que se rompen en silencio al editar textos o geometría:
 * las llaves de idioma desalineadas, las plantillas que no renderizan (un
 * `%1$s` mal escapado tumba la portada de un nodo pero no la del maestro,
 * porque el maestro tiene texto propio), y la montaña rusa saliéndose del lienzo.
 */
require_once __DIR__ . '/../includes/functions.php';

$fallos = 0;
$ok = function (string $q, bool $bien, string $detalle = '') use (&$fallos): void {
    if (!$bien) $fallos++;
    printf("  %s %s%s\n", $bien ? '✅' : '❌', $q, $detalle !== '' ? "  ($detalle)" : '');
};

/* ---------- 1. Los archivos de idioma tienen las mismas llaves ---------- */
echo "Idiomas\n";
$base = require __DIR__ . '/../lang/es.php';
foreach (JDS_LOCALES as $loc) {
    $l = require __DIR__ . '/../lang/' . $loc . '.php';
    $faltan = array_diff(array_keys($base), array_keys($l));
    $sobran = array_diff(array_keys($l), array_keys($base));
    $ok("lang/$loc.php alineado con es", !$faltan && !$sobran,
        $faltan || $sobran ? 'faltan: ' . implode(',', $faltan) . ' sobran: ' . implode(',', $sobran) : '');
}

/* ---------- 2. Cada llave fija usada en el código existe ---------- */
echo "\nLlaves usadas en el código\n";
$ausentes = [];
$archivos = array_merge(glob(__DIR__ . '/../*.php'), glob(__DIR__ . '/../admin/*.php'), glob(__DIR__ . '/../includes/*.php'));
foreach ($archivos as $f) {
    preg_match_all('/\bt\(\s*\'([a-z0-9_]+)\'\s*[,)]/', (string)file_get_contents($f), $m);
    foreach ($m[1] as $k) {
        if (!isset($base[$k]) && substr($k, -1) !== '_') $ausentes[$k] = basename($f);
    }
}
$ok('todas las llaves fijas existen', !$ausentes, implode(', ', array_keys($ausentes)));

/* ---------- 3. Cada plantilla de contenido renderiza en todos los idiomas ---------- */
echo "\nPlantillas de contenido\n";
$args = ['hero_title' => ['jueves'], 'hero_lead' => ['jueves', 'Sitio de Prueba']];
foreach (JDS_LOCALES as $loc) {
    $GLOBALS['jds_locale'] = $loc;
    unset($GLOBALS['jds_lang_ver']);
    foreach (content_keys() as $k) {
        try {
            $out = t('tpl_' . $k, ...($args[$k] ?? []));
            $ok("[$loc] tpl_$k", trim($out) !== '' && strpos($out, '%') === false);
        } catch (Throwable $e) {
            $ok("[$loc] tpl_$k", false, $e->getMessage());
        }
    }
}
unset($GLOBALS['jds_locale'], $GLOBALS['jds_lang_ver']);

/* ---------- 3b. Los resúmenes en una frase se redactan en todos los idiomas ----------
 * Van en la meta description y en el JSON-LD: un %d de más tumba la página del año. */
echo "\nResúmenes para buscadores y LLMs\n";
$ys = ['compras' => 28, 'sats' => 2252166, 'btc' => 0.02252166, 'invertido' => 28000.0, 'valor' => 34912.0,
       'pnl_pct' => 0.2468, 'streak' => 28, 'efecto_btc' => 5454.0, 'efecto_fx' => 1457.0];
$g  = ['compras' => 28, 'planned' => 42, 'sats' => 2252166, 'inv' => 28000.0, 'val' => 34912.0, 'streak' => 28, 'first' => '2026-03-19'];
foreach (JDS_LOCALES as $loc) {
    $GLOBALS['jds_locale'] = $loc;
    unset($GLOBALS['jds_lang_ver']);
    foreach (['montos' => true, 'porcentajes' => false] as $modo => $amounts) {
        try {
            $y = year_summary_text(2026, $ys, 42, 1000.0, $amounts);
            $h = site_summary_text($g, $amounts);
            // Los porcentajes llevan su «%»; lo que no debe quedar es un marcador sin llenar
            $limpio = trim($y) !== '' && trim($h) !== '' && !preg_match('/%(\d+\$)?[sd]/', $y . $h);
            $ok("[$loc] año y portada, $modo", $limpio, mb_substr($y, 0, 70) . '…');
            if (!$amounts) $ok("[$loc] sin montos en $modo", strpos($y . $h, '$') === false && stripos($y . $h, 'sats') === false);
        } catch (Throwable $e) {
            $ok("[$loc] año y portada, $modo", false, $e->getMessage());
        }
    }
}
unset($GLOBALS['jds_locale'], $GLOBALS['jds_lang_ver']);

/* ---------- 4. La montaña rusa no se sale del lienzo ---------- */
echo "\nMontaña rusa\n";
$alturaVehiculo = 50; // el pasajero con los brazos en alto, sobre el punto del riel
$series = [
    'zigzag corto'   => [100, 200, 100, 200, 100, 200, 100, 200],
    'zigzag 52'      => array_map(fn($i) => $i % 2 ? 200 : 100, range(1, 52)),
    'pico único'     => [100, 100, 100, 900, 100, 100, 100],
    'escalón brusco' => [100, 100, 100, 100, 900, 900, 900, 900],
    'caída brusca'   => [900, 900, 900, 900, 100, 100, 100, 100],
    'solo sube'      => range(100, 900, 40),
    'cuatro compras' => [100, 180, 120, 200],
    'sin recorrido'  => [100, 180],
];
$series['tres años'] = array_map(function ($i) { return 1000000 + 900000 * ($i / 116) + 150000 * sin($i / 4); }, range(1, 116));

foreach ($series as $nombre => $serie) {
    $c = coaster_build($serie);
    preg_match_all('/,(-?[0-9.]+)/', $c['path'], $m);
    $ys = array_map('floatval', $m[1]);
    $techo = min($ys) - $alturaVehiculo;
    $piso  = max($ys);
    $ok("$nombre cabe en el lienzo", $techo > 0 && $piso <= COASTER_GROUND,
        sprintf('techo %.1f, piso %.1f', $techo, $piso));
}

// El riel nunca dibuja más de un año de compras: con varios años los primeros
// se aplanan contra el piso y el recorrido deja de leerse.
$largo = coaster_build(array_fill(0, 300, 1000000));
$ok('el riel se recorta a ' . COASTER_MAX_PTS . ' compras',
    $largo['count'] === COASTER_MAX_PTS && $largo['total'] === 300,
    sprintf('dibuja %d de %d', $largo['count'], $largo['total']));

/* ---------- 4b. Efecto Bitcoin + efecto tipo de cambio = ganancia total ---------- */
echo "\nRendimiento separado\n";
// [costo local, costo USD, valor USD, TC de hoy]: peso débil, peso fuerte, BTC abajo, sin TC
foreach ([
    'peso se debilita' => [28000, 1619.0, 1919.0, 17.69],
    'peso se fortalece' => [28000, 1550.0, 1700.0, 16.20],
    'BTC en pérdida'    => [10000, 580.0, 450.0, 18.10],
    'sitio sin compras' => [0, 0.0, 0.0, 17.5],
] as $caso => [$cl, $cu, $vu, $fx]) {
    $b = fx_breakdown($cl, $cu, $vu, $fx);
    $total = $vu * $fx - $cl;
    $ok("$caso: las partes suman la ganancia", abs($b['btc'] + $b['fx'] - $total) < 0.0001,
        sprintf('btc %.2f + tc %.2f = %.2f', $b['btc'], $b['fx'], $total));
}

/* ---------- 4c. La imagen para compartir se dibuja en los casos difíciles ---------- */
require_once __DIR__ . '/../includes/sharecard.php';
echo "\nImagen para compartir\n";
if (!og_card_available()) {
    echo "  ⚠️  sin GD con FreeType en este PHP: los sitios caen al logo o al avatar\n";
} else {
    $base = ['site' => 'Sitio de Prueba', 'subtitle' => 'Compras de Bitcoin 2026', 'host' => 'satoshi.ejemplo.com',
             'owner' => 'Nombre Apellido', 'accent' => '#F7931A', 'big' => '+24.69%', 'tone' => 'pos', 'line' => '2,252,166 sats'];
    foreach ([
        'zigzag 52'        => ['prices' => array_map(fn($i) => $i % 2 ? 900000 : 1500000, range(1, 52))],
        'tres compras'     => ['prices' => [1, 2, 3]],
        'sin compras'      => ['prices' => [], 'big' => '', 'line' => 'La primera compra está por llegar'],
        'nombre larguísimo' => ['site' => str_repeat('Satoshi los jueves ', 6), 'prices' => range(1, 30)],
        'acento negro'     => ['accent' => '#000000', 'tone' => 'neg', 'prices' => range(30, 1)],
    ] as $caso => $c) {
        try {
            $png = og_render($c + $base);
            $info = getimagesizefromstring($png);
            $ok("tarjeta: $caso", $info && $info[0] === OG_W && $info[1] === OG_H && $info['mime'] === 'image/png');
        } catch (Throwable $e) {
            $ok("tarjeta: $caso", false, $e->getMessage());
        }
    }
    [$r, $g, $b] = og_accent('#000000');
    $ok('un acento negro se aclara sobre el fondo oscuro', $r > 90 && $g > 90 && $b > 90, "rgb($r, $g, $b)");
}

/* ---------- 5. Cada clase del HTML tiene regla en el CSS ---------- */
echo "\nEstilos\n";
$css = (string)file_get_contents(__DIR__ . '/../assets/css/styles.css');

// Clases que a propósito no llevan estilo: son ganchos de JavaScript o
// atributos que el navegador ya entiende.
$sinEstilo = [
    // Ganchos de JavaScript
    'tool-select', 'other-fields', 'open', 'on',
    // Hijos de una retícula, sin estilo propio: los posiciona el padre
    'footer-col',
    // Clases del instalador, que lleva su CSS aparte en includes/setup.php
    'card', 'field', 'hint', 'actions', 'alert', 'choice', 'grid2', 'logo', 'sub',
    'muted', 'ok', 'err', 'warn', 'ghost', 'btn', 'steps', 'wrap', 'check', 'tick',
    // Modificadores que solo existen combinados (.compras los usa .table-wrap)
    'compras',
];

$plantillas = array_merge(
    glob(__DIR__ . '/../*.php'),
    glob(__DIR__ . '/../admin/*.php'),
    glob(__DIR__ . '/../includes/*.php'),
    glob(__DIR__ . '/../modules/*/*.php'),
    glob(__DIR__ . '/../modules/*/*/*.php')
);
$huerfanas = [];
foreach ($plantillas as $f) {
    $src = (string)file_get_contents($f);
    // Solo atributos class literales: los que arma PHP no se pueden leer así.
    preg_match_all('/class="([a-z0-9 _-]+)"/i', $src, $m);
    foreach ($m[1] as $lista) {
        foreach (preg_split('/\s+/', trim($lista)) as $clase) {
            if ($clase === '' || in_array($clase, $sinEstilo, true)) continue;
            if (strpos($css, '.' . $clase) === false) $huerfanas[$clase] = basename($f);
        }
    }
}
$ok('cada clase del HTML tiene regla en el CSS', !$huerfanas,
    implode(', ', array_map(function ($c, $f) { return "$c ($f)"; }, array_keys($huerfanas), $huerfanas)));

/* ---------- 6. Los archivos que el código referencia están en la distribución ---------- */
echo "\nArchivos de la distribución\n";
$raiz = dirname(__DIR__);
$necesarios = ['assets/img/avatar-placeholder.svg', 'assets/css/styles.css',
               'assets/js/app.js', 'assets/js/charts.js', 'schema.sql',
               'lang/es.php', 'lang/en.php', 'config.example.php',
               'includes/fonts/Inter-Regular.ttf', 'includes/fonts/Inter-ExtraBold.ttf', 'includes/fonts/OFL.txt'];
foreach ($necesarios as $rel) {
    $existe = is_readable($raiz . '/' . $rel);
    $versionado = true;
    if (is_dir($raiz . '/.git')) {
        // git check-ignore devuelve 0 cuando el archivo SÍ está ignorado
        exec('cd ' . escapeshellarg($raiz) . ' && git check-ignore -q ' . escapeshellarg($rel), $sal, $codigo);
        $versionado = $codigo !== 0;
    }
    $ok($rel, $existe && $versionado,
        !$existe ? 'no existe' : (!$versionado ? 'lo excluye .gitignore' : ''));
}

echo "\n" . ($fallos ? "❌ $fallos fallo(s)\n" : "✅ todo en orden\n");
exit($fallos ? 1 : 0);
