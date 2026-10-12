<?php
/**
 * Borrado lógico de compras: igual que los años, una compra borrada se puede
 * restaurar desde el panel.
 *
 * El número de compra deja de ser único en la base: una compra borrada conserva
 * su número y una nueva puede usarlo. La regla «un número por año» sigue, pero
 * solo entre las compras vigentes, y la revisa admin/compra.php.
 *
 * Cada paso se salta si ya está hecho, así que la migración se puede repetir.
 * Sin ADD COLUMN IF NOT EXISTS: MySQL 8 no lo acepta.
 */
mig_add_col('purchases', 'deleted', 'TINYINT(1) NOT NULL DEFAULT 0');

// El índice nuevo va antes de quitar el único: la llave foránea de year_id
// necesita siempre un índice que empiece por esa columna.
if (!mig_index_exists('purchases', 'idx_year_num')) {
    mig_run('ALTER TABLE purchases ADD INDEX idx_year_num (year_id, num)');
}
if (mig_index_exists('purchases', 'uq_year_num')) {
    mig_run('ALTER TABLE purchases DROP INDEX uq_year_num');
}
