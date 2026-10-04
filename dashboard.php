<?php
/**
 * SISENSUS 2026 — Dasbor
 * Ringkasan agregat: jumlah data, distribusi sektor, wilayah, dan aktivitas.
 */

require_once __DIR__ . '/app/bootstrap.php';
$user = require_login();
$isAdmin = ($user['role'] === 'admin');

// ---- Angka utama --------------------------------------------------------
$totalResponden = (int)db()->query('SELECT COUNT(*) c FROM responden')->fetch()['c'];
$totalUsaha     = (int)db()->query('SELECT COUNT(*) c FROM usaha')->fetch()['c'];
$totalPekerja   = (int)db()->query('SELECT COALESCE(SUM(jumlah_pekerja),0) c FROM usaha')->fetch()['c'];
$totalOmzet     = (float)db()->query('SELECT COALESCE(SUM(omzet_tahunan),0) c FROM usaha')->fetch()['c'];
$usahaBaru      = (int)db()->query("SELECT COUNT(*) c FROM usaha WHERE status_usaha = 'baru'")->fetch()['c'];
$usahaTutup     = (int)db()->query("SELECT COUNT(*) c FROM usaha WHERE status_usaha = 'tutup'")->fetch()['c'];

// ---- Distribusi per sektor ----------------------------------------------
$perSektor = db()->query(
    'SELECT s.kode, s.nama, COUNT(u.id) jumlah, COALESCE(SUM(u.omzet_tahunan),0) omzet
     FROM sektor s
     LEFT JOIN usaha u ON u.sektor_id = s.id
     GROUP BY s.id, s.kode, s.nama
     ORDER BY jumlah DESC, s.kode ASC'
)->fetchAll();

// ---- Responden per kecamatan --------------------------------------------
$perKec = db()->query(
    "SELECT w.nama, COUNT(r.id) jumlah
     FROM wilayah w
     LEFT JOIN responden r ON r.wilayah_id IN (
        SELECT w2.id FROM wilayah w2 WHERE w2.id = w.id OR w2.parent_id = w.id
     )
     WHERE w.tingkat = 'kecamatan'
     GROUP BY w.id, w.nama
     ORDER BY jumlah DESC, w.nama ASC"
)->fetchAll();

// ---- Usaha omzet terbesar ------------------------------------------------
$topUsaha = db()->query(
    'SELECT u.id, u.kode_usaha, u.nama_usaha, u.omzet_tahunan, s.nama sektor
     FROM usaha u JOIN sektor s ON s.id = u.sektor_id
     ORDER BY u.omzet_tahunan DESC LIMIT 5'
)->fetchAll();

// ---- Aktivitas terakhir --------------------------------------------------
$recentAudit = $isAdmin
    ? db()->query(
        'SELECT username_snapshot, aksi, entitas, entitas_id, created_at
         FROM audit_log ORDER BY id DESC LIMIT 8'
      )->fetchAll()
    : [];

$active = 'dashboard';
$page_title = 'Dasbor';
$breadcrumb = ['Dasbor' => null];
require __DIR__ . '/templates/header_app.php';
?>

<section class="statgrid">
  <div class="statcard">
    <p class="statcard-label">Responden Terdaftar</p>
    <p class="statcard-value"><?= number_format($totalResponden, 0, ',', '.') ?></p>
    <p class="statcard-sub">kepala usaha / rumah tangga</p>
  </div>
  <div class="statcard">
    <p class="statcard-label">Unit Usaha Terdata</p>
    <p class="statcard-value"><?= number_format($totalUsaha, 0, ',', '.') ?></p>
    <p class="statcard-sub"><?= $usahaBaru ?> baru · <?= $usahaTutup ?> tutup</p>
  </div>
  <div class="statcard">
    <p class="statcard-label">Tenaga Kerja Terserap</p>
    <p class="statcard-value"><?= number_format($totalPekerja, 0, ',', '.') ?></p>
    <p class="statcard-sub">akumulasi seluruh unit usaha</p>
  </div>
  <div class="statcard">
    <p class="statcard-label">Total Omzet Tahunan</p>
    <p class="statcard-value"><?= e(format_rupiah($totalOmzet)) ?></p>
    <p class="statcard-sub">seluruh unit usaha terdata</p>
  </div>
</section>

<div class="grid-2">
  <section class="panel">
    <div class="panel-head">
      <h2 class="panel-title">Unit Usaha per Sektor</h2>
      <a class="panel-link" href="<?= e(u('laporan.php')) ?>">Lihat laporan →</a>
    </div>
    <?php if (!$perSektor): ?>
      <p class="empty">Belum ada data sektor.</p>
    <?php else: ?>
      <div class="bars">
        <?php
        $maxSektor = max(array_map(fn($r) => (int)$r['jumlah'], $perSektor)) ?: 1;
        foreach ($perSektor as $i => $row):
            $pct = (int)round($row['jumlah'] / $maxSektor * 100);
        ?>
        <div class="bar-row">
          <span class="bar-label" title="<?= e($row['nama']) ?>"><?= e($row['kode']) ?> · <?= e($row['nama']) ?></span>
          <span class="bar-track"><span class="bar-fill<?= $i === 0 ? ' bar-accent' : '' ?> w-<?= min(100, max(2, $pct)) ?>"></span></span>
          <span class="bar-value"><?= number_format((int)$row['jumlah'], 0, ',', '.') ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h2 class="panel-title">Responden per Kecamatan</h2>
    </div>
    <?php if (!$perKec): ?>
      <p class="empty">Belum ada data wilayah.</p>
    <?php else: ?>
      <div class="bars">
        <?php
        $maxKec = max(array_map(fn($r) => (int)$r['jumlah'], $perKec)) ?: 1;
        foreach ($perKec as $i => $row):
            $pct = (int)round($row['jumlah'] / $maxKec * 100);
        ?>
        <div class="bar-row">
          <span class="bar-label"><?= e($row['nama']) ?></span>
          <span class="bar-track"><span class="bar-fill<?= $i === 0 ? ' bar-accent' : '' ?> w-<?= min(100, max(2, $pct)) ?>"></span></span>
          <span class="bar-value"><?= number_format((int)$row['jumlah'], 0, ',', '.') ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<div class="grid-2">
  <section class="panel">
    <div class="panel-head">
      <h2 class="panel-title">Omzet Terbesar</h2>
      <a class="panel-link" href="<?= e(u('usaha/index.php')) ?>">Semua usaha →</a>
    </div>
    <table class="table">
      <thead><tr><th>Kode</th><th>Nama Usaha</th><th class="td-right">Omzet</th></tr></thead>
      <tbody>
      <?php foreach ($topUsaha as $row): ?>
        <tr>
          <td><code class="kode"><?= e($row['kode_usaha']) ?></code></td>
          <td><a class="row-link" href="<?= e(u('usaha/detail.php?id=' . (int)$row['id'])) ?>"><?= e($row['nama_usaha']) ?></a></td>
          <td class="td-right td-num"><?= e(format_rupiah($row['omzet_tahunan'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h2 class="panel-title"><?= $isAdmin ? 'Aktivitas Sistem Terbaru' : 'Ringkasan Peran Anda' ?></h2>
    </div>
    <?php if ($isAdmin): ?>
      <table class="table">
        <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Objek</th></tr></thead>
        <tbody>
        <?php foreach ($recentAudit as $row): ?>
          <tr>
            <td class="td-num"><?= e(format_dttm($row['created_at'])) ?></td>
            <td><?= e($row['username_snapshot']) ?></td>
            <td><code class="kode"><?= e($row['aksi']) ?></code></td>
            <td><?= e(trim(($row['entitas'] ?? '-') . ($row['entitas_id'] !== null ? ' #' . $row['entitas_id'] : ''))) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="role-note">
        <p class="role-badge"><?= e(role_label($user['role'])) ?></p>
        <p>
          Sebagai <strong><?= e(role_label($user['role'])) ?></strong>, Anda dapat
          <?php if ($user['role'] === 'operator'): ?>
            menambah, mengubah, dan menghapus data responden serta usaha, lalu melihat laporan rekapitulasi.
          <?php else: ?>
            memantau seluruh data pendataan dan laporan rekapitulasi. Perubahan data dilakukan oleh petugas/operator.
          <?php endif; ?>
        </p>
        <p class="role-note-foot">Terakhir masuk: aktif saat ini · IP: <?= e(client_ip()) ?></p>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
