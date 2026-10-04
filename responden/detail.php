<?php
/**
 * SISENSUS 2026 — Detail responden (semua peran yang telah masuk)
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_login();
$canEdit = can_edit_data();

$id = int_input('id', 0);

$stmt = db()->prepare(
    'SELECT r.*, w.nama wilayah_nama, w.tingkat wilayah_tingkat, w.kode wilayah_kode,
            pw.nama kecamatan_nama, cu.full_name pencatat
     FROM responden r
     JOIN wilayah w ON w.id = r.wilayah_id
     LEFT JOIN wilayah pw ON pw.id = w.parent_id
     LEFT JOIN users cu ON cu.id = r.created_by
     WHERE r.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    abort(404, 'Data Tidak Ditemukan', 'Responden dengan nomor tersebut tidak ada dalam basis data.');
}

$usahaRows = db()->prepare(
    'SELECT u.id, u.kode_usaha, u.nama_usaha, u.jumlah_pekerja, u.omzet_tahunan, u.status_usaha, s.nama sektor
     FROM usaha u JOIN sektor s ON s.id = u.sektor_id
     WHERE u.responden_id = ? ORDER BY u.id DESC'
);
$usahaRows->execute([$id]);
$usahaRows = $usahaRows->fetchAll();

$active = 'responden';
$page_title = 'Detail Responden';
$breadcrumb = ['Responden' => 'responden/index.php', 'Detail' => null];
require __DIR__ . '/../templates/header_app.php';
?>

<div class="page-actions">
  <?php if ($canEdit): ?>
    <a class="btn btn-primary" href="<?= e(u('responden/ubah.php?id=' . (int)$row['id'])) ?>">Ubah Data</a>
    <form method="post" action="<?= e(u('responden/hapus.php')) ?>" class="inline-form" data-confirm="Hapus responden <?= e($row['nama_lengkap']) ?>? Tindakan ini tidak dapat dibatalkan.">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <button class="btn btn-danger" type="submit">Hapus</button>
    </form>
  <?php endif; ?>
  <a class="btn btn-ghost" href="<?= e(u('responden/index.php')) ?>">← Kembali ke daftar</a>
</div>

<div class="grid-detail">
  <section class="panel">
    <h2 class="panel-title">Identitas Responden</h2>
    <dl class="detail-list">
      <div><dt>NIK</dt><dd class="td-num"><?= e($row['nik']) ?></dd></div>
      <div><dt>Nama Lengkap</dt><dd><?= e($row['nama_lengkap']) ?></dd></div>
      <div><dt>Jenis Kelamin</dt><dd><?= e($row['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan') ?></dd></div>
      <div><dt>Tanggal Lahir</dt><dd><?= e(format_tgl($row['tanggal_lahir'])) ?></dd></div>
      <div><dt>Alamat</dt><dd><?= e($row['alamat']) ?></dd></div>
      <div><dt>Wilayah</dt><dd><?= e($row['wilayah_nama']) ?> (<?= e($row['wilayah_tingkat']) ?>, kode <?= e($row['wilayah_kode']) ?><?= $row['kecamatan_nama'] ? ' · Kec. ' . e($row['kecamatan_nama']) : '' ?>)</dd></div>
      <div><dt>Telepon</dt><dd><?= e($row['telepon'] ?: '—') ?></dd></div>
      <div><dt>Pendidikan</dt><dd><?= e($row['pendidikan']) ?></dd></div>
      <div><dt>Pekerjaan Utama</dt><dd><?= e($row['pekerjaan_utama'] ?: '—') ?></dd></div>
      <div><dt>Status</dt><dd><span class="badge badge-<?= e($row['status']) ?>"><?= e($row['status']) ?></span></dd></div>
      <div><dt>Dicatat oleh</dt><dd><?= e($row['pencatat'] ?: '—') ?></dd></div>
      <div><dt>Diperbarui</dt><dd><?= e(format_dttm($row['updated_at'])) ?></dd></div>
    </dl>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h2 class="panel-title">Unit Usaha Milik Responden</h2>
      <?php if ($canEdit): ?>
        <a class="panel-link" href="<?= e(u('usaha/tambah.php?responden=' . (int)$row['id'])) ?>">+ Tambah usaha</a>
      <?php endif; ?>
    </div>
    <table class="table">
      <thead><tr><th>Kode</th><th>Nama Usaha</th><th>Sektor</th><th class="td-right">Pekerja</th><th class="td-right">Omzet</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (!$usahaRows): ?>
        <tr><td colspan="6" class="empty">Responden ini belum memiliki unit usaha terdaftar.</td></tr>
      <?php else: foreach ($usahaRows as $u): ?>
        <tr>
          <td><code class="kode"><?= e($u['kode_usaha']) ?></code></td>
          <td><a class="row-link" href="<?= e(u('usaha/detail.php?id=' . (int)$u['id'])) ?>"><?= e($u['nama_usaha']) ?></a></td>
          <td><?= e($u['sektor']) ?></td>
          <td class="td-right td-num"><?= number_format((int)$u['jumlah_pekerja'], 0, ',', '.') ?></td>
          <td class="td-right td-num"><?= e(format_rupiah($u['omzet_tahunan'])) ?></td>
          <td><span class="badge badge-<?= e($u['status_usaha']) ?>"><?= e($u['status_usaha']) ?></span></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </section>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
