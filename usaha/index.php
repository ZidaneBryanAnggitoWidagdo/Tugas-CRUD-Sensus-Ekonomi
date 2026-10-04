<?php
/**
 * SISENSUS 2026 — Daftar usaha (semua peran dapat melihat)
 * Pencarian: kode/nama · filter sektor & status · paginasi.
 */

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_login();
$canEdit = can_edit_data();

$q       = str_input('q', '');
$sektor  = int_input('sektor', 0);
$status  = str_input('status', '');
$page    = max(1, int_input('page', 1));
$perPage = 10;

$where = []; $params = [];
if ($q !== '') {
    $like = '%' . like_escape($q) . '%';
    $where[] = '(u.kode_usaha LIKE :like1 OR u.nama_usaha LIKE :like2)';
    $params['like1'] = $like; $params['like2'] = $like;
}
if ($sektor > 0) {
    $where[] = 'u.sektor_id = :sektor';
    $params['sektor'] = $sektor;
}
if ($status !== '' && in_array($status, VALID_STATUS_USAHA, true)) {
    $where[] = 'u.status_usaha = :status';
    $params['status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = db()->prepare("SELECT COUNT(*) c FROM usaha u {$whereSql}");
foreach ($params as $k => $v) { $countStmt->bindValue(':' . $k, $v); }
$countStmt->execute();
$total = (int)$countStmt->fetch()['c'];
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
    "SELECT u.*, s.nama sektor_nama, s.kode sektor_kode, w.nama wilayah_nama, r.nama_lengkap pemilik
     FROM usaha u
     JOIN sektor s ON s.id = u.sektor_id
     JOIN wilayah w ON w.id = u.wilayah_id
     JOIN responden r ON r.id = u.responden_id
     {$whereSql}
     ORDER BY u.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
foreach ($params as $k => $v) { $stmt->bindValue(':' . $k, $v); }
$stmt->execute();
$rows = $stmt->fetchAll();

$sektorList = db()->query('SELECT id, kode, nama FROM sektor ORDER BY kode')->fetchAll();

$active = 'usaha';
$page_title = 'Usaha';
$breadcrumb = ['Usaha' => null];
require __DIR__ . '/../templates/header_app.php';
?>

<?php if ($canEdit): ?>
<div class="page-actions">
  <a class="btn btn-primary" href="<?= e(u('usaha/tambah.php')) ?>">+ Tambah Usaha</a>
</div>
<?php endif; ?>

<section class="panel">
  <form class="filterbar" method="get" action="<?= e(u('usaha/index.php')) ?>">
    <div class="filter-field filter-grow">
      <label for="q">Cari</label>
      <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Kode atau nama usaha…">
    </div>
    <div class="filter-field">
      <label for="sektor">Sektor</label>
      <select id="sektor" name="sektor">
        <option value="0">— Semua —</option>
        <?php foreach ($sektorList as $s): ?>
          <option value="<?= (int)$s['id'] ?>"<?= $sektor === (int)$s['id'] ? ' selected' : '' ?>><?= e($s['kode']) ?> · <?= e($s['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-field">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="">— Semua —</option>
        <?php foreach (VALID_STATUS_USAHA as $st): ?>
          <option value="<?= e($st) ?>"<?= $status === $st ? ' selected' : '' ?>><?= e(ucfirst($st)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-actions">
      <button class="btn" type="submit">Terapkan</button>
      <?php if ($q !== '' || $sektor > 0 || $status !== ''): ?>
        <a class="btn btn-ghost" href="<?= e(u('usaha/index.php')) ?>">Bersihkan</a>
      <?php endif; ?>
    </div>
  </form>

  <p class="result-count">Menampilkan <strong><?= count($rows) ?></strong> dari <strong><?= number_format($total, 0, ',', '.') ?></strong> unit usaha</p>

  <table class="table">
    <thead>
      <tr>
        <th>Kode</th><th>Nama Usaha</th><th>Sektor</th><th>Pemilik</th><th class="td-right">Pekerja</th><th class="td-right">Omzet</th><th>Status</th><th class="td-right">Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="8" class="empty">Tidak ada usaha yang cocok dengan pencarian.</td></tr>
    <?php else: foreach ($rows as $row): ?>
      <tr>
        <td><code class="kode"><?= e($row['kode_usaha']) ?></code></td>
        <td><a class="row-link" href="<?= e(u('usaha/detail.php?id=' . (int)$row['id'])) ?>"><?= e($row['nama_usaha']) ?></a></td>
        <td><span class="sektor-chip"><?= e($row['sektor_kode']) ?></span> <?= e($row['sektor_nama']) ?></td>
        <td><?= e($row['pemilik']) ?></td>
        <td class="td-right td-num"><?= number_format((int)$row['jumlah_pekerja'], 0, ',', '.') ?></td>
        <td class="td-right td-num"><?= e(format_rupiah($row['omzet_tahunan'])) ?></td>
        <td><span class="badge badge-<?= e($row['status_usaha']) ?>"><?= e($row['status_usaha']) ?></span></td>
        <td class="td-right row-actions">
          <a href="<?= e(u('usaha/detail.php?id=' . (int)$row['id'])) ?>">Detail</a>
          <?php if ($canEdit): ?>
            <a href="<?= e(u('usaha/ubah.php?id=' . (int)$row['id'])) ?>">Ubah</a>
            <form method="post" action="<?= e(u('usaha/hapus.php')) ?>" class="inline-form" data-confirm="Hapus unit usaha <?= e($row['nama_usaha']) ?>? Tindakan ini tidak dapat dibatalkan.">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button class="link-danger" type="submit">Hapus</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>

  <?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Paginasi">
      <?php
      $mk = fn(int $p) => u('usaha/index.php?' . http_build_query(['q' => $q, 'sektor' => $sektor, 'status' => $status, 'page' => $p]));
      ?>
      <?php if ($page > 1): ?><a href="<?= e($mk($page - 1)) ?>">‹ Sebelumnya</a><?php endif; ?>
      <span class="pagination-info">Halaman <?= $page ?> / <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= e($mk($page + 1)) ?>">Berikutnya ›</a><?php endif; ?>
    </nav>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
