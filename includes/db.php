<?php
$jds_config = __DIR__ . '/../config.php';

if (!is_readable($jds_config)) {
    // Sin config.php no hay instalación. Mandamos al instalador en lugar de
    // reventar con un error de PHP que nadie sabe interpretar.
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if (substr($base, -6) === '/admin') $base = substr($base, 0, -6);
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalación pendiente</title>'
       . '<style>body{font-family:-apple-system,Segoe UI,Roboto,sans-serif;background:#faf7f2;color:#1a1a1a;'
       . 'display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px}'
       . 'div{max-width:460px;background:#fff;border:1px solid #ece4d8;border-radius:14px;padding:32px;text-align:center}'
       . 'a{display:inline-block;margin-top:18px;background:#F7931A;color:#fff;padding:11px 26px;border-radius:10px;'
       . 'text-decoration:none;font-weight:700}</style></head><body><div>'
       . '<h1 style="font-size:1.3rem;margin:0 0 10px">Instalación pendiente</h1>'
       . '<p style="color:#5a5a5a;margin:0">Este sitio todavía no tiene configuración. '
       . 'Ejecuta el instalador para crearla.</p>'
       . '<a href="' . htmlspecialchars($base . '/install.php', ENT_QUOTES) . '">Instalar</a>'
       . '</div></body></html>';
    exit;
}

require_once $jds_config;

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $ex) {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            // Sin detalles de credenciales en la respuesta pública.
            exit("No se pudo conectar a la base de datos. Revisa config.php.\n");
        }
    }
    return $pdo;
}
