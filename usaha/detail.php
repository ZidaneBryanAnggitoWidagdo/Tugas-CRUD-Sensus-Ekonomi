<?php
/**
 * SISENSUS 2026 — Detail unit usaha (semua peran yang telah masuk)
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_login();
$canEdit = can_edit_data();

$id = int_input('id', 0);

$stmt = db()->prepare(
    'SELECT u.*, s.kode sektor_kode, s.nama sektor_nama, w.nama wilayah_nama, w.tingkat wilayah_tingkat,
            r.nik pemilik_nik, r.nama_lengkap pemilik_nama, r.telepon pemilik_telepon,
            cu.full_name pencatat
     FROM usaha u
     JOIN sektor s ON s.id = u.sektor_id
     JOIN wilayah w ON w.id = u.wilayah_id
     JOIN responden r ON r.id = u.responden_id
     LEFT JOIN users cu ON cu.id = u.created_by
     WHERE u.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    abort(404, 'Data Tidak Ditemukan', 'Unit usaha dengan nomor tersebut tidak ada dalam basis data.');
}

$active = 'usaha';
$page_title = 'Detail Usaha';
$breadcrumb = ['Usaha' => 'usaha/index.php', 'Detail' => null];
require __DIR__ . '/../templates/header_app.php';
?>

<div class="page-actions">
  <?php if ($canEdit): ?>
    <a class="btn btn-primary" href="<?= e(u('usaha/ubah.php?id=' . (int)$row['id'])) ?>">Ubah Data</a>
    <form method="post" action="<?= e(u('usaha/hapus.php')) ?>" class="inline-form" data-confirm="Hapus unit usaha <?= e($row['nama_usaha']) ?>? Tindakan ini tidak dapat dibatalkan.">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <button class="btn btn-danger" type="submit">Hapus</button>
    </form>
  <?php endif; ?>
  <a class="btn btn-ghost" href="<?= e(u('usaha/index.php')) ?>">← Kembali ke daftar</a>
</div>

<div class="grid-detail">
  <section class="panel">
    <h2 class="panel-title">Profil Unit Usaha</h2>
    <dl class="detail-list">
      <div><dt>Kode Usaha</dt><dd><code class="kode"><?= e($row['kode_usaha']) ?></code></dd></div>
      <div><dt>Nama Usaha</dt><dd><?= e($row['nama_usaha']) ?></dd></div>
      <div><dt>Sektor</dt><dd><span class="sektor-chip"><?= e($row['sektor_kode']) ?></span> <?= e($row['sektor_nama']) ?></dd></div>
      <div><dt>Alamat Usaha</dt><dd><?= e($row['alamat_usaha']) ?></dd></div>
      <div><dt>Wilayah</dt><dd><?= e($row['wilayah_nama']) ?> (<?= e($row['wilayah_tingkat']) ?>)</dd></div>
      <div><dt>Tahun Mulai</dt><dd class="td-num"><?= e((string)$row['tahun_mulai']) ?></dd></div>
      <div><dt>Jumlah Pekerja</dt><dd class="td-num"><?= number_format((int)$row['jumlah_pekerja'], 0, ',', '.') ?> orang</dd></div>
      <div><dt>Omzet Tahunan</dt><dd class="td-num"><?= e(format_rupiah($row['omzet_tahunan'])) ?></dd></div>
      <div><dt>Status</dt><dd><span class="badge badge-<?= e($row['status_usaha']) ?>"><?= e($row['status_usaha']) ?></span></dd></div>
      <div><dt>Keterangan</dt><dd><?= nl2br(e($row['keterangan'] ?: '—')) ?></dd></div>
      <div><dt>Dicatat oleh</dt><dd><?= e($row['pencatat'] ?: '—') ?></dd></div>
      <div><dt>Diperbarui</dt><dd><?= e(format_dttm($row['updated_at'])) ?></dd></div>
    </dl>
  </section>

  <section class="panel">
    <h2 class="panel-title">Data Pemilik (Responden)</h2>
    <dl class="detail-list">
      <div><dt>Nama</dt><dd>
        <a class="row-link" href="<?= e(u('responden/detail.php?id=' . (int)$row['responden_id'])) ?>"><?= e($row['pemilik_nama']) ?></a>
      </dd></div>
      <div><dt>NIK</dt><dd class="td-num"><?= e($row['pemilik_nik']) ?></dd></div>
      <div><dt>Telepon</dt><dd><?= e($row['pemilik_telepon'] ?: '—') ?></dd></div>
    </dl>
  </section>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
