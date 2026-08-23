<?php
/**
 * Migraciones de base de datos.
 *
 * Cada archivo migrations/NNN_nombre.php es un paso. Los pasos aplicados se
 * anotan en schema_migrations, así que actualizar es siempre seguro: se aplica
 * solo lo que falta y nunca se pierde información que ya tenga el usuario.
 *
 * Los pasos deben ser idempotentes: revisan si la tabla o la columna ya existe
 * antes de tocarla. MySQL no sabe deshacer un ALTER dentro de una transacción,
 * así que la defensa es que volver a correr un paso no haga daño.
 */
require_once __DIR__ . '/db.php';

function mig_table_exists(string $table): bool {
    $st = db()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $st->execute([$table]);
    return (int)$st->fetchColumn() > 0;
}

function mig_col_exists(string $table, string $col): bool {
    $st = db()->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $st->execute([$table, $col]);
    return (int)$st->fetchColumn() > 0;
}

function mig_run(string $sql): void { db()->exec($sql); }

/** Renombra una columna solo si hace falta (sintaxis compatible con MySQL 5.7). */
function mig_rename_col(string $table, string $from, string $to, string $definition): void {
    if (!mig_col_exists($table, $from) || mig_col_exists($table, $to)) return;
    mig_run("ALTER TABLE `$table` CHANGE `$from` `$to` $definition");
}

function mig_add_col(string $table, string $col, string $definition): void {
    if (mig_col_exists($table, $col)) return;
    mig_run("ALTER TABLE `$table` ADD COLUMN `$col` $definition");
}

function migrations_ensure_table(): void {
    mig_run('CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(40) NOT NULL,
        applied_at DATETIME NOT NULL,
        PRIMARY KEY (version)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

/** Pasos disponibles en el código, ordenados. */
function migrations_available(): array {
    $out = [];
    foreach (glob(__DIR__ . '/../migrations/*.php') ?: [] as $f) {
        $out[basename($f, '.php')] = $f;
    }
    ksort($out);
    return $out;
}

function migrations_applied(): array {
    migrations_ensure_table();
    return db()->query('SELECT version FROM schema_migrations ORDER BY version')->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function migrations_pending(): array {
    $applied = migrations_applied();
    return array_diff_key(migrations_available(), array_flip($applied));
}

function migration_mark(string $version): void {
    $st = db()->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE version = version');
    $st->execute([$version]);
}

/** Aplica un paso. Lanza excepción si algo falla; el paso no queda marcado. */
function migration_apply(string $version, string $file): void {
    require $file;
    migration_mark($version);
}

/** Marca todo como aplicado sin ejecutar nada: para instalaciones nuevas. */
function migrations_stamp_all(): void {
    migrations_ensure_table();
    foreach (array_keys(migrations_available()) as $v) migration_mark($v);
}

/**
 * Separa un archivo .sql en sentencias.
 *
 * Un explode(';') ingenuo se rompe con un punto y coma dentro de un comentario
 * o de una cadena, y el usuario ve un error de sintaxis que no puede interpretar.
 * Este recorrido respeta comillas simples, dobles, acentos graves y comentarios
 * de línea (-- y #) y de bloque.
 */
function sql_statements(string $sql): array {
    $out = []; $cur = ''; $q = null;
    $i = 0; $n = strlen($sql);
    while ($i < $n) {
        $c = $sql[$i];
        if ($q !== null) {
            $cur .= $c;
            if ($c === '\\' && $i + 1 < $n) { $cur .= $sql[$i + 1]; $i += 2; continue; }
            if ($c === $q) $q = null;
            $i++; continue;
        }
        if ($c === "'" || $c === '"' || $c === '`') { $q = $c; $cur .= $c; $i++; continue; }
        if ($c === '-' && substr($sql, $i, 3) === '-- ') { while ($i < $n && $sql[$i] !== "\n") $i++; continue; }
        if ($c === '-' && substr($sql, $i, 2) === '--' && ($i + 2 >= $n || $sql[$i + 2] === "\n")) { $i += 2; continue; }
        if ($c === '#') { while ($i < $n && $sql[$i] !== "\n") $i++; continue; }
        if ($c === '/' && substr($sql, $i, 2) === '/*') {
            $end = strpos($sql, '*/', $i);
            $i = $end === false ? $n : $end + 2;
            continue;
        }
        if ($c === ';') { $out[] = trim($cur); $cur = ''; $i++; continue; }
        $cur .= $c; $i++;
    }
    if (trim($cur) !== '') $out[] = trim($cur);
    return array_values(array_filter($out, fn($s) => $s !== ''));
}
