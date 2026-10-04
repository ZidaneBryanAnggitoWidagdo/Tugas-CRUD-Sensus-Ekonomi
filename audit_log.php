<?php
/**
 * SISENSUS 2026 — Audit log sistem (khusus admin)
 */

require_once __DIR__ . '/app/bootstrap.php';
require_role(['admin']);

$q       = str_input('q', '');
$aksi    = str_input('aksi', '');
$page    = max(1, int_input('page', 1));
$perPage = 25;

$where = []; $params = [];
if ($q !== '') {
    $like = '%' . like_escape($q) . '%';
    $where[] = '(username_snapshot LIKE :like1 OR detail LIKE :like2)';
    $params['like1'] = $like; $params['like2'] = $like;
}
if ($aksi !== '') {
    $where[] = 'aksi = :aksi';
    $params['aksi'] = $aksi;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = db()->prepare("SELECT COUNT(*) c FROM audit_log {$whereSql}");
foreach ($params as $k => $v) { $countStmt->bindValue(':' . $k, $v); }
$countStmt->execute();
$total = (int)$countStmt->fetch()['c'];
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
    "SELECT a.*, u.username
     FROM audit_log a LEFT JOIN users u ON u.id = a.user_id
     {$whereSql}
     ORDER BY a.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
foreach ($params as $k => $v) { $stmt->bindValue(':' . $k, $v); }
$stmt->execute();
$rows = $stmt->fetchAll();

$aksiList = db()->query('SELECT DISTINCT aksi FROM audit_log ORDER BY aksi')->fetchAll();

$active = 'audit';
$page_title = 'Audit Log';
$breadcrumb = ['Audit Log' => null];
require __DIR__ . '/templates/header_app.php';
?>

<section class="panel">
  <form class="filterbar" method="get" action="<?= e(u('audit_log.php')) ?>">
    <div class="filter-field filter-grow">
      <label for="q">Cari</label>
      <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Pengguna atau detail kejadian…">
    </div>
    <div class="filter-field">
      <label for="aksi">Jenis Aksi</label>
      <select id="aksi" name="aksi">
        <option value="">— Semua —</option>
        <?php foreach ($aksiList as $a): ?>
          <option value="<?= e($a['aksi']) ?>"<?= $aksi === $a['aksi'] ? ' selected' : '' ?>><?= e($a['aksi']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-actions">
      <button class="btn" type="submit">Terapkan</button>
      <?php if ($q !== '' || $aksi !== ''): ?>
        <a class="btn btn-ghost" href="<?= e(u('audit_log.php')) ?>">Bersihkan</a>
      <?php endif; ?>
    </div>
  </form>

  <p class="result-count">Menampilkan <strong><?= count($rows) ?></strong> dari <strong><?= number_format($total, 0, ',', '.') ?></strong> catatan audit</p>

  <table class="table table-log">
    <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Objek</th><th>Detail</th><th>IP</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="6" class="empty">Belum ada catatan audit yang cocok.</td></tr>
    <?php else: foreach ($rows as $row): ?>
      <tr>
        <td class="td-num td-nowrap"><?= e(format_dttm($row['created_at'])) ?></td>
        <td><?= e($row['username_snapshot']) ?></td>
        <td>
          <?php
          $aksiClass = ['LOGIN_SUCCESS' => 'ok', 'LOGIN_FAIL' => 'bad', 'CSRF_FAIL' => 'bad',
                        'ACCESS_DENIED' => 'bad', 'DELETE_RESPONDEN' => 'warn', 'DELETE_USAHA' => 'warn',
                        'DELETE_USER' => 'warn'][$row['aksi']] ?? 'info';
          ?>
          <code class="kode kode-<?= e($aksiClass) ?>"><?= e($row['aksi']) ?></code>
        </td>
        <td><?= e(trim(($row['entitas'] ?? '') . ($row['entitas_id'] !== null ? ' #' . $row['entitas_id'] : '')) ?: '—') ?></td>
        <td class="td-detail"><?= e($row['detail'] ?: '—') ?></td>
        <td class="td-num"><?= e($row['ip_address'] ?: '—') ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>

  <?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Paginasi">
      <?php
      $mk = fn(int $p) => u('audit_log.php?' . http_build_query(['q' => $q, 'aksi' => $aksi, 'page' => $p]));
      ?>
      <?php if ($page > 1): ?><a href="<?= e($mk($page - 1)) ?>">‹ Sebelumnya</a><?php endif; ?>
      <span class="pagination-info">Halaman <?= $page ?> / <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= e($mk($page + 1)) ?>">Berikutnya ›</a><?php endif; ?>
    </nav>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/templates/footer.php'; ?>
