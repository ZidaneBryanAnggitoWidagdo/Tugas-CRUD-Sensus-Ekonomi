<?php
/**
 * SISENSUS 2026 — Daftar responden (semua peran dapat melihat)
 * Pencarian: NIK / nama / alamat · filter wilayah · paginasi.
 */

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_login();
$canEdit = can_edit_data();

$q        = str_input('q', '');
$wilayah  = int_input('wilayah', 0);
$page     = max(1, int_input('page', 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($q !== '') {
    $like = '%' . like_escape($q) . '%';
    $where[] = '(r.nik LIKE :like1 OR r.nama_lengkap LIKE :like2 OR r.alamat LIKE :like3)';
    $params['like1'] = $like; $params['like2'] = $like; $params['like3'] = $like;
}
if ($wilayah > 0) {
    // Cocokkan wilayah langsung atau wilayah anak (kelurahan di kecamatan tsb).
    // Catatan: dengan EMULATE_PREPARES=false, satu nama parameter hanya boleh
    // dipakai satu kali — maka :wid dan :wid2 dibedakan.
    $where[] = '(r.wilayah_id = :wid OR r.wilayah_id IN (SELECT id FROM wilayah WHERE parent_id = :wid2))';
    $params['wid'] = $wilayah;
    $params['wid2'] = $wilayah;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Hitung total baris (parameter dibinding eksplisit, bukan interpolasi string).
$countStmt = db()->prepare("SELECT COUNT(*) c FROM responden r {$whereSql}");
foreach ($params as $k => $v) { $countStmt->bindValue(':' . $k, $v); }
$countStmt->execute();
$total = (int)$countStmt->fetch()['c'];
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT r.*, w.nama wilayah_nama, w.tingkat wilayah_tingkat, cu.full_name pencatat
        FROM responden r
        JOIN wilayah w ON w.id = r.wilayah_id
        LEFT JOIN users cu ON cu.id = r.created_by
        {$whereSql}
        ORDER BY r.id DESC
        LIMIT {$perPage} OFFSET {$offset}";
$stmt = db()->prepare($sql);
foreach ($params as $k => $v) { $stmt->bindValue(':' . $k, $v); }
$stmt->execute();
$rows = $stmt->fetchAll();

$kecamatan = db()->query("SELECT id, nama FROM wilayah WHERE tingkat = 'kecamatan' ORDER BY nama")->fetchAll();

$active = 'responden';
$page_title = 'Responden';
$breadcrumb = ['Responden' => null];
require __DIR__ . '/../templates/header_app.php';
?>

<?php if ($canEdit): ?>
<div class="page-actions">
  <a class="btn btn-primary" href="<?= e(u('responden/tambah.php')) ?>">+ Tambah Responden</a>
</div>
<?php endif; ?>

<section class="panel">
  <form class="filterbar" method="get" action="<?= e(u('responden/index.php')) ?>">
    <div class="filter-field filter-grow">
      <label for="q">Cari</label>
      <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="NIK, nama, atau alamat…">
    </div>
    <div class="filter-field">
      <label for="wilayah">Kecamatan</label>
      <select id="wilayah" name="wilayah">
        <option value="0">— Semua —</option>
        <?php foreach ($kecamatan as $k): ?>
          <option value="<?= (int)$k['id'] ?>"<?= $wilayah === (int)$k['id'] ? ' selected' : '' ?>><?= e($k['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-actions">
      <button class="btn" type="submit">Terapkan</button>
      <?php if ($q !== '' || $wilayah > 0): ?>
        <a class="btn btn-ghost" href="<?= e(u('responden/index.php')) ?>">Bersihkan</a>
      <?php endif; ?>
    </div>
  </form>

  <p class="result-count">Menampilkan <strong><?= count($rows) ?></strong> dari <strong><?= number_format($total, 0, ',', '.') ?></strong> responden</p>

  <table class="table">
    <thead>
      <tr>
        <th>NIK</th><th>Nama</th><th>Wilayah</th><th>Pendidikan</th><th>Status</th><th class="td-right">Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="6" class="empty">Tidak ada responden yang cocok dengan pencarian.</td></tr>
    <?php else: foreach ($rows as $row): ?>
      <tr>
        <td class="td-num"><?= e($row['nik']) ?></td>
        <td><a class="row-link" href="<?= e(u('responden/detail.php?id=' . (int)$row['id'])) ?>"><?= e($row['nama_lengkap']) ?></a></td>
        <td><?= e($row['wilayah_nama']) ?></td>
        <td><?= e($row['pendidikan']) ?></td>
        <td><span class="badge badge-<?= e($row['status']) ?>"><?= e($row['status']) ?></span></td>
        <td class="td-right row-actions">
          <a href="<?= e(u('responden/detail.php?id=' . (int)$row['id'])) ?>">Detail</a>
          <?php if ($canEdit): ?>
            <a href="<?= e(u('responden/ubah.php?id=' . (int)$row['id'])) ?>">Ubah</a>
            <form method="post" action="<?= e(u('responden/hapus.php')) ?>" class="inline-form" data-confirm="Hapus responden <?= e($row['nama_lengkap']) ?>? Tindakan ini tidak dapat dibatalkan.">
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
      $mk = fn(int $p) => u('responden/index.php?' . http_build_query(['q' => $q, 'wilayah' => $wilayah, 'page' => $p]));
      ?>
      <?php if ($page > 1): ?><a href="<?= e($mk($page - 1)) ?>">‹ Sebelumnya</a><?php endif; ?>
      <span class="pagination-info">Halaman <?= $page ?> / <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= e($mk($page + 1)) ?>">Berikutnya ›</a><?php endif; ?>
    </nav>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
