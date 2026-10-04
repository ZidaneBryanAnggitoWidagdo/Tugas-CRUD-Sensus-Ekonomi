<?php
/**
 * SISENSUS 2026 — Laporan rekapitulasi + ekspor CSV
 * Ekspor CSV dilindungi dari injeksi rumus (csv_safe).
 */

require_once __DIR__ . '/app/bootstrap.php';
require_login();

/** Tulis satu baris CSV dengan parameter eksplisit (stabil lintas versi PHP). */
function csv_row($stream, array $fields): void
{
    fputcsv($stream, $fields, ',', '"', '\\');
}

$export = str_input('export');

// ---------- Ekspor CSV ----------
if ($export !== '') {
    $fname = 'laporan-sensus-' . $export . '-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

    if ($export === 'sektor') {
        csv_row($out, ['Kode', 'Sektor Lapangan Usaha', 'Jumlah Usaha', 'Total Pekerja', 'Total Omzet Tahunan (Rp)']);
        $rows = db()->query(
            'SELECT s.kode, s.nama, COUNT(u.id) jml, COALESCE(SUM(u.jumlah_pekerja),0) pkj,
                    COALESCE(SUM(u.omzet_tahunan),0) omzet
             FROM sektor s LEFT JOIN usaha u ON u.sektor_id = s.id
             GROUP BY s.id, s.kode, s.nama ORDER BY s.kode'
        )->fetchAll();
        foreach ($rows as $r) {
            csv_row($out, [
                csv_safe($r['kode']), csv_safe($r['nama']),
                (int)$r['jml'], (int)$r['pkj'], number_format((float)$r['omzet'], 2, '.', ''),
            ]);
        }
        audit_log('EXPORT_CSV', 'laporan', null, 'Ekspor rekap per sektor');
    } elseif ($export === 'wilayah') {
        csv_row($out, ['Kecamatan', 'Jumlah Responden', 'Jumlah Usaha', 'Total Omzet Tahunan (Rp)']);
        $rows = db()->query(
            "SELECT w.nama,
                    (SELECT COUNT(*) FROM responden r WHERE r.wilayah_id IN
                        (SELECT w2.id FROM wilayah w2 WHERE w2.id = w.id OR w2.parent_id = w.id)) jml_r,
                    (SELECT COUNT(*) FROM usaha u WHERE u.wilayah_id IN
                        (SELECT w2.id FROM wilayah w2 WHERE w2.id = w.id OR w2.parent_id = w.id)) jml_u,
                    (SELECT COALESCE(SUM(u2.omzet_tahunan),0) FROM usaha u2 WHERE u2.wilayah_id IN
                        (SELECT w2.id FROM wilayah w2 WHERE w2.id = w.id OR w2.parent_id = w.id)) omzet
             FROM wilayah w WHERE w.tingkat = 'kecamatan' ORDER BY w.nama"
        )->fetchAll();
        foreach ($rows as $r) {
            csv_row($out, [
                csv_safe($r['nama']), (int)$r['jml_r'], (int)$r['jml_u'],
                number_format((float)$r['omzet'], 2, '.', ''),
            ]);
        }
        audit_log('EXPORT_CSV', 'laporan', null, 'Ekspor rekap per wilayah');
    } elseif ($export === 'usaha') {
        csv_row($out, ['Kode Usaha', 'Nama Usaha', 'Sektor', 'Kecamatan', 'Pemilik', 'Tahun Mulai', 'Pekerja', 'Omzet (Rp)', 'Status']);
        $rows = db()->query(
            "SELECT u.kode_usaha, u.nama_usaha, s.nama sektor, w.nama wil, r.nama_lengkap pemilik,
                    u.tahun_mulai, u.jumlah_pekerja, u.omzet_tahunan, u.status_usaha
             FROM usaha u
             JOIN sektor s ON s.id = u.sektor_id
             JOIN wilayah w ON w.id = u.wilayah_id
             JOIN responden r ON r.id = u.responden_id
             ORDER BY u.omzet_tahunan DESC"
        )->fetchAll();
        foreach ($rows as $r) {
            csv_row($out, [
                csv_safe($r['kode_usaha']), csv_safe($r['nama_usaha']), csv_safe($r['sektor']),
                csv_safe($r['wil']), csv_safe($r['pemilik']),
                (int)$r['tahun_mulai'], (int)$r['jumlah_pekerja'],
                number_format((float)$r['omzet_tahunan'], 2, '.', ''),
                csv_safe($r['status_usaha']),
            ]);
        }
        audit_log('EXPORT_CSV', 'laporan', null, 'Ekspor daftar seluruh usaha');
    } else {
        abort(404, 'Ekspor Tidak Dikenal', 'Jenis ekspor yang diminta tidak tersedia.');
    }
    exit;
}

// ---------- Halaman laporan ----------
$perSektor = db()->query(
    'SELECT s.kode, s.nama, COUNT(u.id) jml, COALESCE(SUM(u.jumlah_pekerja),0) pkj,
            COALESCE(SUM(u.omzet_tahunan),0) omzet
     FROM sektor s LEFT JOIN usaha u ON u.sektor_id = s.id
     GROUP BY s.id, s.kode, s.nama ORDER BY jml DESC, s.kode'
)->fetchAll();

$perWilayah = db()->query(
    "SELECT w.nama,
            (SELECT COUNT(*) FROM responden r WHERE r.wilayah_id IN
                (SELECT w2.id FROM wilayah w2 WHERE w2.id = w.id OR w2.parent_id = w.id)) jml_r,
            (SELECT COUNT(*) FROM usaha u WHERE u.wilayah_id IN
                (SELECT w2.id FROM wilayah w2 WHERE w2.id = w.id OR w2.parent_id = w.id)) jml_u,
            (SELECT COALESCE(SUM(u2.omzet_tahunan),0) FROM usaha u2 WHERE u2.wilayah_id IN
                (SELECT w2.id FROM wilayah w2 WHERE w2.id = w.id OR w2.parent_id = w.id)) omzet
     FROM wilayah w WHERE w.tingkat = 'kecamatan' ORDER BY w.nama"
)->fetchAll();

$topUsaha = db()->query(
    'SELECT u.id, u.kode_usaha, u.nama_usaha, u.jumlah_pekerja, u.omzet_tahunan, u.status_usaha,
            s.kode sektor_kode, w.nama wilayah_nama
     FROM usaha u
     JOIN sektor s ON s.id = u.sektor_id
     JOIN wilayah w ON w.id = u.wilayah_id
     ORDER BY u.omzet_tahunan DESC LIMIT 10'
)->fetchAll();

$active = 'laporan';
$page_title = 'Laporan Rekapitulasi';
$breadcrumb = ['Laporan' => null];
require __DIR__ . '/templates/header_app.php';
?>

<p class="page-desc">Rekapitulasi data pendataan per <strong><?= e(date('d-m-Y H:i')) ?> WIB</strong>. Gunakan tombol ekspor untuk mengunduh berkas CSV.</p>

<section class="panel">
  <div class="panel-head">
    <h2 class="panel-title">Rekap per Sektor Lapangan Usaha</h2>
    <a class="panel-link" href="<?= e(u('laporan.php?export=sektor')) ?>">Unduh CSV ↓</a>
  </div>
  <table class="table">
    <thead><tr><th>Kode</th><th>Sektor</th><th class="td-right">Unit Usaha</th><th class="td-right">Tenaga Kerja</th><th class="td-right">Total Omzet</th></tr></thead>
    <tbody>
    <?php foreach ($perSektor as $row): ?>
      <tr>
        <td><span class="sektor-chip"><?= e($row['kode']) ?></span></td>
        <td><?= e($row['nama']) ?></td>
        <td class="td-right td-num"><?= number_format((int)$row['jml'], 0, ',', '.') ?></td>
        <td class="td-right td-num"><?= number_format((int)$row['pkj'], 0, ',', '.') ?></td>
        <td class="td-right td-num"><?= e(format_rupiah($row['omzet'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2"><strong>Total</strong></td>
        <td class="td-right td-num"><strong><?= number_format(array_sum(array_map(fn($r) => (int)$r['jml'], $perSektor)), 0, ',', '.') ?></strong></td>
        <td class="td-right td-num"><strong><?= number_format(array_sum(array_map(fn($r) => (int)$r['pkj'], $perSektor)), 0, ',', '.') ?></strong></td>
        <td class="td-right td-num"><strong><?= e(format_rupiah(array_sum(array_map(fn($r) => (float)$r['omzet'], $perSektor)))) ?></strong></td>
      </tr>
    </tfoot>
  </table>
</section>

<section class="panel">
  <div class="panel-head">
    <h2 class="panel-title">Rekap per Kecamatan</h2>
    <a class="panel-link" href="<?= e(u('laporan.php?export=wilayah')) ?>">Unduh CSV ↓</a>
  </div>
  <table class="table">
    <thead><tr><th>Kecamatan</th><th class="td-right">Responden</th><th class="td-right">Unit Usaha</th><th class="td-right">Total Omzet</th></tr></thead>
    <tbody>
    <?php foreach ($perWilayah as $row): ?>
      <tr>
        <td><?= e($row['nama']) ?></td>
        <td class="td-right td-num"><?= number_format((int)$row['jml_r'], 0, ',', '.') ?></td>
        <td class="td-right td-num"><?= number_format((int)$row['jml_u'], 0, ',', '.') ?></td>
        <td class="td-right td-num"><?= e(format_rupiah($row['omzet'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<section class="panel">
  <div class="panel-head">
    <h2 class="panel-title">10 Unit Usaha dengan Omzet Terbesar</h2>
    <a class="panel-link" href="<?= e(u('laporan.php?export=usaha')) ?>">Unduh CSV ↓</a>
  </div>
  <table class="table">
    <thead><tr><th>#</th><th>Kode</th><th>Nama Usaha</th><th>Sektor</th><th>Kecamatan</th><th class="td-right">Omzet</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($topUsaha as $i => $row): ?>
      <tr>
        <td class="td-num"><?= $i + 1 ?></td>
        <td><code class="kode"><?= e($row['kode_usaha']) ?></code></td>
        <td><a class="row-link" href="<?= e(u('usaha/detail.php?id=' . (int)$row['id'])) ?>"><?= e($row['nama_usaha']) ?></a></td>
        <td><span class="sektor-chip"><?= e($row['sektor_kode']) ?></span></td>
        <td><?= e($row['wilayah_nama']) ?></td>
        <td class="td-right td-num"><?= e(format_rupiah($row['omzet_tahunan'])) ?></td>
        <td><span class="badge badge-<?= e($row['status_usaha']) ?>"><?= e($row['status_usaha']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__ . '/templates/footer.php'; ?>
