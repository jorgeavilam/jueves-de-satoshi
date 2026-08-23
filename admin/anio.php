<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$error = ''; $ok = '';
$cur = currency_code();

// Borrado lógico / restauración
if (in_array($_GET['action'] ?? '', ['borrar', 'restaurar'], true) && isset($_GET['year'])) {
    if (!hash_equals(csrf_token(), $_GET['t'] ?? '')) die(t('csrf_bad'));
    $st = db()->prepare('UPDATE years SET deleted = ? WHERE year = ?');
    $st->execute([$_GET['action'] === 'borrar' ? 1 : 0, (int)$_GET['year']]);
    header('Location: ' . SITE_URL . '/admin/anio.php?msg=' . $_GET['action']); exit;
}
if (($_GET['msg'] ?? '') === 'borrar')    $ok = t('admin_year_deleted');
if (($_GET['msg'] ?? '') === 'restaurar') $ok = t('admin_year_restored');

// Alta / edición (mismo formulario: si el año existe, se actualiza)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $year  = (int)($_POST['year'] ?? 0);
    $monto = (float)($_POST['weekly_amount'] ?? 0);
    $weeks = (int)($_POST['planned_weeks'] ?? 52);
    $notes = trim($_POST['notes'] ?? '');
    if ($year < 2009 || $year > 2100 || $monto <= 0 || $weeks < 1 || $weeks > 53) {
        $error = t('admin_year_bad');
    } else {
        try {
            $st = db()->prepare('INSERT INTO years (year, weekly_amount, planned_weeks, notes) VALUES (?,?,?,?)
                                 ON DUPLICATE KEY UPDATE weekly_amount=VALUES(weekly_amount), planned_weeks=VALUES(planned_weeks), notes=VALUES(notes)');
            $st->execute([$year, $monto, $weeks, $notes ?: null]);
            $ok = t('admin_year_saved', $year);
        } catch (PDOException $ex) {
            $error = $ex->getMessage();
        }
    }
}

$edit  = isset($_GET['edit']) ? get_year((int)$_GET['edit'], true) : null;
$years = get_years(true); // incluye borrados para poder restaurarlos

page_head(t('admin_years'), '', true);
admin_chrome('anios');
?>
<section class="block">
  <div class="container" style="max-width:760px">
    <h2><?= $edit ? '✏️ ' . e(t('admin_years')) . ' ' . (int)$edit['year'] : e(t('admin_years_title')) ?></h2>
    <p class="section-sub"><?= e(t('admin_years_sub')) ?></p>

    <?php if ($ok): ?><div class="alert alert-ok">✅ <?= e($ok) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>

    <div class="form-card" style="margin-bottom:24px">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-grid thirds">
          <div class="form-group">
            <label><?= e(t('admin_f_year')) ?></label>
            <input type="number" name="year" min="2009" max="2100" required
                   value="<?= $edit ? (int)$edit['year'] : date('Y') ?>" <?= $edit ? 'readonly style="background:var(--bg-soft)"' : '' ?>>
          </div>
          <div class="form-group">
            <label><?= e(t('admin_f_weekly', $cur)) ?></label>
            <input type="number" name="weekly_amount" step="0.01" min="0.01" required
                   value="<?= $edit ? e($edit['weekly_amount']) : '1000' ?>">
          </div>
          <div class="form-group">
            <label><?= e(t('admin_f_planned')) ?></label>
            <input type="number" name="planned_weeks" min="1" max="53" required value="<?= $edit ? (int)$edit['planned_weeks'] : '52' ?>">
          </div>
        </div>
        <div class="form-group">
          <label><?= e(t('admin_notes')) ?></label>
          <input type="text" name="notes" maxlength="500" value="<?= $edit ? e($edit['notes'] ?? '') : '' ?>">
        </div>
        <button class="btn" type="submit"><?= e(t('save')) ?></button>
        <?php if ($edit): ?><a class="btn btn-outline" href="<?= SITE_URL ?>/admin/anio.php"><?= e(t('cancel')) ?></a><?php endif; ?>
      </form>
    </div>

    <div class="table-wrap">
      <table class="compras">
        <thead><tr>
          <th><?= e(t('admin_f_year')) ?></th>
          <th><?= e(t('admin_f_weekly', $cur)) ?></th>
          <th><?= e(day_name(purchase_day(), true)) ?></th>
          <th><?= e(t('admin_notes')) ?></th>
          <th><?= e(t('admin_state')) ?></th>
          <th><?= e(t('admin_actions')) ?></th>
        </tr></thead>
        <tbody>
          <?php foreach ($years as $yr): ?>
          <tr<?= $yr['deleted'] ? ' style="opacity:0.55"' : '' ?>>
            <td><a href="<?= SITE_URL ?>/admin/index.php?y=<?= (int)$yr['year'] ?>"><?= (int)$yr['year'] ?></a></td>
            <td><?= money($yr['weekly_amount']) ?></td>
            <td><?= (int)$yr['planned_weeks'] ?></td>
            <td style="text-align:left"><?= e($yr['notes'] ?? '') ?></td>
            <td><?= $yr['deleted'] ? '🗑️ ' . e(t('admin_deleted')) : '✅ ' . e(t('admin_active')) ?></td>
            <td>
              <a href="?edit=<?= (int)$yr['year'] ?>"><?= e(t('edit')) ?></a> ·
              <?php if ($yr['deleted']): ?>
                <a href="?action=restaurar&year=<?= (int)$yr['year'] ?>&t=<?= csrf_token() ?>"><?= e(t('restore')) ?></a>
              <?php else: ?>
                <a href="?action=borrar&year=<?= (int)$yr['year'] ?>&t=<?= csrf_token() ?>"
                   onclick="return confirm('<?= e(t('admin_confirm_delete_year', (int)$yr['year'])) ?>')"><?= e(t('delete')) ?></a>
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
