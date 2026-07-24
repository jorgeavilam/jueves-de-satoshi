<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$error = ''; $ok = '';

// Borrado lógico / restauración
if (in_array($_GET['action'] ?? '', ['borrar', 'restaurar'], true) && isset($_GET['year'])) {
    if (!hash_equals(csrf_token(), $_GET['t'] ?? '')) die('Token inválido.');
    $st = db()->prepare('UPDATE years SET deleted = ? WHERE year = ?');
    $st->execute([$_GET['action'] === 'borrar' ? 1 : 0, (int)$_GET['year']]);
    header('Location: ' . SITE_URL . '/admin/anio.php?msg=' . $_GET['action']); exit;
}
if (($_GET['msg'] ?? '') === 'borrar') $ok = 'Año marcado como borrado (recuperable con "Restaurar").';
if (($_GET['msg'] ?? '') === 'restaurar') $ok = 'Año restaurado.';

// Alta / edición (mismo formulario: si el año existe, se actualiza)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $year = (int)($_POST['year'] ?? 0);
    $monto = (float)($_POST['weekly_amount_mxn'] ?? 0);
    $weeks = (int)($_POST['planned_weeks'] ?? 52);
    $notes = trim($_POST['notes'] ?? '');
    if ($year < 2020 || $year > 2100 || $monto <= 0 || $weeks < 1 || $weeks > 53) {
        $error = 'Revisa los datos del año.';
    } else {
        try {
            $st = db()->prepare('INSERT INTO years (year, weekly_amount_mxn, planned_weeks, notes) VALUES (?,?,?,?)
                                 ON DUPLICATE KEY UPDATE weekly_amount_mxn=VALUES(weekly_amount_mxn), planned_weeks=VALUES(planned_weeks), notes=VALUES(notes)');
            $st->execute([$year, $monto, $weeks, $notes ?: null]);
            $ok = "Año $year guardado.";
        } catch (PDOException $ex) {
            $error = 'Error: ' . $ex->getMessage();
        }
    }
}

// Pre-llenado para edición
$edit = null;
if (isset($_GET['edit'])) $edit = get_year((int)$_GET['edit'], true);

$years = get_years(true); // incluye borrados para poder restaurarlos
page_head('Admin — Años');
?>
<div class="admin-bar">
  <div class="container">
    <span><a href="<?= SITE_URL ?>/admin/index.php">← Volver a compras</a></span>
    <span>Gestión de años</span>
  </div>
</div>

<section class="block">
  <div class="container" style="max-width:720px">
    <h2><?= $edit ? '✏️ Editar año ' . (int)$edit['year'] : 'Años del ejercicio' ?> <span class="brand-accent"></span></h2>
    <p class="section-sub">Cada año tiene su propio monto semanal. Los jueves planeados se calculan solos en cuanto hay compras; el valor manual solo aplica mientras el año está vacío.</p>

    <?php if ($ok): ?><div class="alert alert-ok">✅ <?= e($ok) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>

    <div class="form-card" style="margin-bottom:24px">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-grid">
          <div class="form-group"><label>Año</label>
            <input type="number" name="year" min="2020" max="2100" required
                   value="<?= $edit ? (int)$edit['year'] : date('Y') ?>" <?= $edit ? 'readonly style="background:var(--bg-soft)"' : '' ?>>
          </div>
          <div class="form-group"><label>Monto semanal (MXN)</label>
            <input type="number" name="weekly_amount_mxn" step="0.01" min="1" required value="<?= $edit ? e($edit['weekly_amount_mxn']) : '1000' ?>">
          </div>
          <div class="form-group"><label>Jueves planeados (solo si no hay compras)</label>
            <input type="number" name="planned_weeks" min="1" max="53" required value="<?= $edit ? (int)$edit['planned_weeks'] : '52' ?>">
          </div>
        </div>
        <div class="form-group"><label>Notas (opcional)</label>
          <input type="text" name="notes" maxlength="500" value="<?= $edit ? e($edit['notes'] ?? '') : '' ?>">
        </div>
        <button class="btn" type="submit"><?= $edit ? 'Guardar cambios' : 'Guardar año' ?></button>
        <?php if ($edit): ?><a class="btn btn-outline" href="<?= SITE_URL ?>/admin/anio.php">Cancelar</a><?php endif; ?>
      </form>
    </div>

    <div class="table-wrap">
      <table class="compras">
        <thead><tr><th>Año</th><th>Monto semanal</th><th>Jueves</th><th>Notas</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($years as $yr): ?>
          <tr<?= $yr['deleted'] ? ' style="opacity:0.55"' : '' ?>>
            <td><a href="<?= SITE_URL ?>/admin/index.php?y=<?= (int)$yr['year'] ?>"><?= (int)$yr['year'] ?></a></td>
            <td>$<?= fmt_money($yr['weekly_amount_mxn'], 0) ?> MXN</td>
            <td><?= (int)$yr['planned_weeks'] ?></td>
            <td style="text-align:left"><?= e($yr['notes'] ?? '') ?></td>
            <td><?= $yr['deleted'] ? '🗑️ Borrado' : '✅ Activo' ?></td>
            <td>
              <a href="?edit=<?= (int)$yr['year'] ?>">Editar</a> ·
              <?php if ($yr['deleted']): ?>
                <a href="?action=restaurar&year=<?= (int)$yr['year'] ?>&t=<?= csrf_token() ?>">Restaurar</a>
              <?php else: ?>
                <a href="?action=borrar&year=<?= (int)$yr['year'] ?>&t=<?= csrf_token() ?>"
                   onclick="return confirm('¿Borrar el año <?= (int)$yr['year'] ?>? Desaparecerá del sitio público, pero podrás restaurarlo aquí (las compras no se tocan).')">Borrar</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php page_foot(); ?>
