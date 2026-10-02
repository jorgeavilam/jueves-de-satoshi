<?php
/**
 * Arma los .zip de una versión para subirlos y descomprimirlos en el servidor.
 *
 *   php scripts/empaquetar.php            la versión de JDS_VERSION, desde su etiqueta
 *   php scripts/empaquetar.php --head     desde el último commit, sin etiqueta (pruebas)
 *
 * Deja tres archivos en ../dist/<versión>/ (fuera del repositorio):
 *
 *   jueves-de-satoshi.zip               instalación nueva: el sitio completo
 *   jueves-de-satoshi-actualizacion.zip una instalación que ya existe: sin
 *                                       install.php (se borra al instalar y no
 *                                       debe volver) ni el .htaccess de la raíz
 *                                       (cPanel guarda ahí la versión de PHP)
 *   jueves-de-satoshi-maestro.zip       la actualización más el módulo del hub,
 *                                       si el repositorio jds-hub está al lado.
 *                                       Es privado: nunca se publica.
 *
 * El contenido sale de `git archive`, no del disco: solo entra lo versionado.
 * Así nunca viaja un config.php, una imagen subida por el dueño o un archivo
 * de prueba olvidado. Los zips no llevan carpeta raíz: se descomprimen directo
 * en public_html.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!class_exists('ZipArchive')) { fwrite(STDERR, "Falta la extensión zip de PHP.\n"); exit(1); }

$app  = realpath(__DIR__ . '/..');
$root = dirname($app);
$hub  = $root . '/jds-hub';
$head = in_array('--head', $argv, true);

preg_match("/const JDS_VERSION\\s*=\\s*'([0-9.]+)'/", (string)file_get_contents($app . '/includes/functions.php'), $m);
$ver = $m[1] ?? '';
if ($ver === '') { fwrite(STDERR, "No encontré JDS_VERSION en includes/functions.php.\n"); exit(1); }

function sh(string $cmd): array {
    exec($cmd . ' 2>&1', $out, $code);
    return [$code, implode("\n", $out)];
}

// De dónde sale el código: la etiqueta de la versión, o HEAD si se pidió
$ref = 'v' . $ver;
[$code] = sh('git -C ' . escapeshellarg($app) . ' rev-parse -q --verify ' . escapeshellarg('refs/tags/' . $ref));
if ($code !== 0) {
    if (!$head) {
        fwrite(STDERR, "No existe la etiqueta $ref. Haz commit y `git tag $ref`, o usa --head para un paquete de prueba.\n");
        exit(1);
    }
    $ref = 'HEAD';
}
[, $dirty] = sh('git -C ' . escapeshellarg($app) . ' status --porcelain');
if ($ref === 'HEAD' && $dirty !== '') echo "⚠️  Hay cambios sin commit: el paquete sale del último commit y no los incluye.\n";

/** Copia de un árbol de git en una carpeta temporal. */
function git_tree(string $repo, string $ref): string {
    $tmp = sys_get_temp_dir() . '/jds-pack-' . bin2hex(random_bytes(4));
    mkdir($tmp, 0700, true);
    [$code, $out] = sh('git -C ' . escapeshellarg($repo) . ' archive --format=tar ' . escapeshellarg($ref)
                     . ' | tar -x -C ' . escapeshellarg($tmp));
    if ($code !== 0) { fwrite(STDERR, $out . "\n"); exit(1); }
    return $tmp;
}

function tree_files(string $dir): array {
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile()) $out[] = str_replace('\\', '/', substr($f->getPathname(), strlen($dir) + 1));
    }
    sort($out);
    return $out;
}

/** ¿El archivo se queda fuera? Cada regla es una ruta exacta o una carpeta terminada en /. */
function excluded(string $rel, array $rules): bool {
    foreach ($rules as $r) {
        if (substr($r, -1) === '/' ? strpos($rel, $r) === 0 : $rel === $r) return true;
    }
    return false;
}

/** Arma un zip con $files (ruta en el zip => ruta en disco). */
function make_zip(string $path, array $files): array {
    @unlink($path);
    $z = new ZipArchive();
    if ($z->open($path, ZipArchive::CREATE) !== true) { fwrite(STDERR, "No pude crear $path\n"); exit(1); }
    foreach ($files as $inZip => $onDisk) $z->addFile($onDisk, $inZip);
    $z->close();
    return ['files' => count($files), 'bytes' => filesize($path), 'sha256' => hash_file('sha256', $path)];
}

// Nunca van al servidor: pruebas, este script, los ganchos de git
$never  = ['tests/', 'scripts/', '.githooks/', '.gitignore'];
// Además, fuera de una actualización
$update = ['install.php', '.htaccess'];

$src   = git_tree($app, $ref);
$files = [];
foreach (tree_files($src) as $rel) {
    if (!excluded($rel, $never)) $files[$rel] = $src . '/' . $rel;
}
$upd = array_filter($files, fn($rel) => !excluded($rel, $update), ARRAY_FILTER_USE_KEY);

// Lo que jamás debe viajar, aunque algún día se versionara por error
$forbidden = ['config.php', 'modules/hub/', 'assets/img/logo.svg', 'assets/img/jorge-avila.jpg'];
foreach (array_keys($files) as $rel) {
    if (excluded($rel, $forbidden)) { fwrite(STDERR, "❌ $rel no debe ir en la distribución.\n"); exit(1); }
}

$dist = $root . '/dist/' . $ver;
@mkdir($dist, 0755, true);
$built = [
    'jueves-de-satoshi.zip'               => make_zip($dist . '/jueves-de-satoshi.zip', $files),
    'jueves-de-satoshi-actualizacion.zip' => make_zip($dist . '/jueves-de-satoshi-actualizacion.zip', $upd),
];

// El maestro: la actualización más el módulo, con las mismas exclusiones que sync.sh
if (is_dir($hub . '/.git')) {
    [, $hubDirty] = sh('git -C ' . escapeshellarg($hub) . ' status --porcelain');
    if ($hubDirty !== '') echo "⚠️  jds-hub tiene cambios sin commit: el módulo sale de su último commit.\n";
    $hsrc = git_tree($hub, 'HEAD');
    $mst  = $upd;
    foreach (tree_files($hsrc) as $rel) {
        if (!excluded($rel, ['README.md', 'sync.sh', '.gitignore'])) $mst['modules/hub/' . $rel] = $hsrc . '/' . $rel;
    }
    $built['jueves-de-satoshi-maestro.zip'] = make_zip($dist . '/jueves-de-satoshi-maestro.zip', $mst);
}

echo "\nJueves de Satoshi $ver (desde " . ($ref === 'HEAD' ? 'HEAD' : "la etiqueta $ref") . ")\n";
echo "→ $dist\n\n";
foreach ($built as $name => $b) {
    printf("  %-38s %4d archivos  %6.1f KB  sha256 %s…\n", $name, $b['files'], $b['bytes'] / 1024, substr($b['sha256'], 0, 12));
}
echo "\nLa actualización no lleva install.php ni .htaccess. Si una versión cambia el\n"
   . ".htaccess, sus notas dicen qué líneas agregar a mano.\n";
