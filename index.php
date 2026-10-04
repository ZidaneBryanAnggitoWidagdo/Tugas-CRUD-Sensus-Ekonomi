<?php
/**
 * SISENSUS 2026 — Halaman muka (landing) — sebelum login.
 * Statistik agregat publik (tanpa data individual).
 */

require_once __DIR__ . '/app/bootstrap.php';

$page_title = 'Beranda';

// Statistik agregat untuk band informasi (gagal DB tidak boleh mematikan landing).
$pubStats = null;
try {
    $pubStats = [
        'usaha'     => (int)db()->query('SELECT COUNT(*) c FROM usaha')->fetch()['c'],
        'responden' => (int)db()->query('SELECT COUNT(*) c FROM responden')->fetch()['c'],
        'pekerja'   => (int)db()->query('SELECT COALESCE(SUM(jumlah_pekerja),0) c FROM usaha')->fetch()['c'],
        'wilayah'   => (int)db()->query("SELECT COUNT(*) c FROM wilayah WHERE tingkat = 'kecamatan'")->fetch()['c'],
    ];
} catch (Throwable $ex) {
    error_log('[LANDING] ' . $ex->getMessage());
}

require __DIR__ . '/templates/header_public.php';
?>

<section class="hero">
  <div class="hero-inner">
    <p class="hero-eyebrow"><?= e(APP_ORG) ?> · Tahun Pendataan <?= e(APP_YEAR) ?></p>
    <h1 class="hero-title">Memetakan roda perekonomian daerah, dari warung terdepan sampai pabrik terbelakang.</h1>
    <p class="hero-lead">
      <?= e(APP_NAME) ?> adalah sistem pendataan unit usaha dan responden dalam lingkup Sensus Ekonomi:
      pendaftaran lapangan, penggolongan sektor, penghitungan tenaga kerja dan omzet, sampai pelaporan resmi —
      satu pintu, terkontrol, dan terjejak audit.
    </p>
    <div class="hero-actions">
      <a class="btn btn-accent" href="<?= e(u('login.php')) ?>">Masuk ke Sistem Pendataan</a>
      <a class="btn btn-ghost" href="#tentang">Pelajari Lingkup Pendataan</a>
    </div>
  </div>
</section>

<section class="statband" aria-label="Ringkasan data terkini">
  <div class="statband-inner">
    <?php
    $items = [
        ['Unit usaha terdata',      $pubStats['usaha']     ?? null, 'unit usaha terdaftar dalam pendataan'],
        ['Responden terdaftar',     $pubStats['responden'] ?? null, 'kepala usaha & rumah tangga'],
        ['Tenaga kerja terserap',   $pubStats['pekerja']   ?? null, 'dari seluruh unit usaha terdata'],
        ['Kecamatan terjangkau',    $pubStats['wilayah']   ?? null, 'lingkup Kota Yogyakarta (simulasi)'],
    ];
    foreach ($items as [$label, $value, $sub]): ?>
      <div class="statband-item">
        <p class="statband-value"><?= $value === null ? '—' : e(number_format($value, 0, ',', '.')) ?></p>
        <p class="statband-label"><?= e($label) ?></p>
        <p class="statband-sub"><?= e($sub) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="pub-section" id="tentang">
  <div class="pub-grid">
    <div>
      <p class="kicker">Tentang Pendataan</p>
      <h2 class="section-title">Apa itu Sensus Ekonomi?</h2>
    </div>
    <div class="pub-text">
      <p>
        Sensus Ekonomi adalah pendataan menyeluruh atas seluruh unit usaha/perusahaan non-agrikultur
        yang beroperasi di wilayah Indonesia, mencakup industri pengolahan, perdagangan, jasa,
        konstruksi, sampai pertambangan. Pendataan ini menjadi dasar pembentukan kerangka statistik
        nasional dan perencanaan pembangunan ekonomi daerah.
      </p>
      <p>
        Melalui <?= e(APP_NAME) ?>, petugas pendataan mencatat identitas responden, profil unit usaha,
        jumlah tenaga kerja, dan omzet tahunan secara terstruktur. Setiap perubahan data tercatat
        dalam jalur audit sehingga akuntabilitas terjaga dari lapangan sampai laporan.
      </p>
    </div>
  </div>
</section>

<section class="pub-section pub-section-alt">
  <div class="pub-grid">
    <div>
      <p class="kicker">Lingkup Sektor</p>
      <h2 class="section-title">Yang Didata dalam Sistem</h2>
    </div>
    <div class="pub-text">
      <ul class="check-list">
        <li><strong>Responden</strong> — identitas pemilik usaha: NIK, pendidikan, domisili hingga tingkat kelurahan.</li>
        <li><strong>Unit usaha</strong> — nama, sektor lapangan usaha (klasifikasi KBLI), tahun berdiri, alamat usaha.</li>
        <li><strong>Tenaga kerja</strong> — jumlah pekerja terserap setiap unit usaha.</li>
        <li><strong>Omzet tahunan</strong> — perkiraan perputaran ekonomi tiap unit usaha.</li>
        <li><strong>Status usaha</strong> — aktif, baru berdiri, atau tutup — untuk pemutakhiran kerangka.</li>
      </ul>
    </div>
  </div>
</section>

<section class="pub-section">
  <div class="pub-grid">
    <div>
      <p class="kicker">Alur Pendataan</p>
      <h2 class="section-title">Empat Tahap di Lapangan</h2>
    </div>
    <div class="pub-text">
      <ol class="steps">
        <li><span class="step-no">01</span><div><strong>Pendataan awal</strong><span>Petugas mencatat identitas responden dan unit usaha di wilayah tugasnya.</span></div></li>
        <li><span class="step-no">02</span><div><strong>Penggolongan sektor</strong><span>Setiap usaha digolongkan ke lapangan usaha sesuai klasifikasi KBLI.</span></div></li>
        <li><span class="step-no">03</span><div><strong>Pengumpulan angka</strong><span>Jumlah pekerja dan omzet tahunan direkam sebagai indikator ekonomi.</span></div></li>
        <li><span class="step-no">04</span><div><strong>Pelaporan</strong><span>Rekapitulasi per sektor dan wilayah dihimpun menjadi laporan resmi.</span></div></li>
      </ol>
    </div>
  </div>
</section>

<section class="pub-cta">
  <div class="pub-cta-inner">
    <h2>Petugas pendataan? Masuk dan lanjutkan pencatatan Anda.</h2>
    <p>Akses sistem dibatasi sesuai peran: petugas penginput, pimpinan pemantau, dan administrator.</p>
    <a class="btn btn-accent" href="<?= e(u('login.php')) ?>">Masuk ke Sistem</a>
  </div>
</section>

<footer class="pub-footer">
  <div class="pub-footer-inner">
    <div class="pub-footer-brand">
      <img src="<?= e(u('assets/img/logo.svg')) ?>" alt="" width="30" height="30">
      <div>
        <p><strong><?= e(APP_NAME) ?> <?= e(APP_YEAR) ?></strong></p>
        <p><?= e(APP_NAME_LONG) ?></p>
      </div>
    </div>
    <div class="pub-footer-links">
      <a href="<?= e(u('login.php')) ?>">Masuk</a>
      <a href="#tentang">Tentang</a>
    </div>
  </div>
  <div class="pub-footer-bottom">
    <p>© <?= e(APP_YEAR) ?> <?= e(APP_ORG) ?>. Proyek simulasi untuk keperluan tugas — seluruh data bersifat fiktif.</p>
  </div>
</footer>

<script src="<?= e(u('assets/js/app.js')) ?>"></script>
</body>
</html>
