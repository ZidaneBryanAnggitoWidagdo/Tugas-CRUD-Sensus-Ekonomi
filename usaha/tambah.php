<?php
/**
 * SISENSUS 2026 — Tambah unit usaha (admin & operator)
 * Kode usaha dibangkitkan otomatis: USE-<tahun>-<nomor urut>.
 */

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_role(['admin', 'operator']);

$respondenList = db()->query('SELECT id, nik, nama_lengkap FROM responden ORDER BY nama_lengkap')->fetchAll();
$sektorList    = db()->query('SELECT id, kode, nama FROM sektor ORDER BY kode')->fetchAll();
$wilayahList   = db()->query(
    "SELECT w.id, w.nama, w.tingkat, pw.nama parent_nama
     FROM wilayah w LEFT JOIN wilayah pw ON pw.id = w.parent_id
     WHERE w.tingkat IN ('kecamatan','kelurahan')
     ORDER BY w.tingkat DESC, w.nama"
)->fetchAll();

$errors = [];
$old = ['nama_usaha'=>'','responden_id'=>'','sektor_id'=>'','wilayah_id'=>'','alamat_usaha'=>'',
        'tahun_mulai'=>(string)date('Y'),'jumlah_pekerja'=>'','omzet_tahunan'=>'','status_usaha'=>'aktif','keterangan'=>''];
if (isset($_GET['responden'])) { $old['responden_id'] = (string)int_input('responden', 0); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($old as $k => $_) { $old[$k] = str_input($k); }

    if ($err = v_required($old['nama_usaha'], 'Nama usaha', 150)) $errors['nama_usaha'] = $err;

    $rid = (int)$old['responden_id'];
    $chk = db()->prepare('SELECT id FROM responden WHERE id = ?');
    $chk->execute([$rid]);
    if ($rid <= 0 || !$chk->fetch()) $errors['responden_id'] = 'Pemilik (responden) wajib dipilih.';

    $sid = (int)$old['sektor_id'];
    $chk = db()->prepare('SELECT id FROM sektor WHERE id = ?');
    $chk->execute([$sid]);
    if ($sid <= 0 || !$chk->fetch()) $errors['sektor_id'] = 'Sektor wajib dipilih.';

    $wid = (int)$old['wilayah_id'];
    $chk = db()->prepare("SELECT id FROM wilayah WHERE id = ? AND tingkat IN ('kecamatan','kelurahan')");
    $chk->execute([$wid]);
    if ($wid <= 0 || !$chk->fetch()) $errors['wilayah_id'] = 'Wilayah wajib dipilih.';

    if ($err = v_required($old['alamat_usaha'], 'Alamat usaha', 255)) $errors['alamat_usaha'] = $err;
    if ($err = v_int($old['tahun_mulai'], 'Tahun mulai beroperasi', 1800, (int)date('Y'))) $errors['tahun_mulai'] = $err;
    if ($err = v_int($old['jumlah_pekerja'], 'Jumlah pekerja', 0, 1000000)) $errors['jumlah_pekerja'] = $err;
    if ($err = v_money($old['omzet_tahunan'], 'Omzet tahunan')) $errors['omzet_tahunan'] = $err;
    if ($err = v_enum($old['status_usaha'], VALID_STATUS_USAHA, 'Status usaha')) $errors['status_usaha'] = $err;
    if (mb_strlen($old['keterangan']) > 1000) $errors['keterangan'] = 'Keterangan maksimal 1000 karakter.';

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare(
                'INSERT INTO usaha
                    (kode_usaha, nama_usaha, responden_id, sektor_id, wilayah_id, alamat_usaha,
                     tahun_mulai, jumlah_pekerja, omzet_tahunan, status_usaha, keterangan, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                'PENDING', $old['nama_usaha'], $rid, $sid, $wid, $old['alamat_usaha'],
                (int)$old['tahun_mulai'], (int)$old['jumlah_pekerja'],
                (float)str_replace(',', '.', $old['omzet_tahunan']),
                $old['status_usaha'], ($old['keterangan'] !== '' ? $old['keterangan'] : null),
                (int)$user['id'],
            ]);
            $newId = (int)$pdo->lastInsertId();
            $kode  = 'USE-' . date('Y') . '-' . str_pad((string)$newId, 4, '0', STR_PAD_LEFT);
            $pdo->prepare('UPDATE usaha SET kode_usaha = ? WHERE id = ?')->execute([$kode, $newId]);
            $pdo->commit();

            audit_log('CREATE_USAHA', 'usaha', $newId, 'Menambah usaha: ' . $old['nama_usaha'] . ' (' . $kode . ')');
            flash_set('sukses', 'Unit usaha berhasil ditambahkan dengan kode ' . $kode . '.');
            redirect('usaha/detail.php?id=' . $newId);
        } catch (Throwable $ex) {
            $pdo->rollBack();
            error_log('[USAHA] ' . $ex->getMessage());
            $errors['form'] = 'Gagal menyimpan data usaha. Coba lagi.';
        }
    }
}

$active = 'usaha';
$page_title = 'Tambah Unit Usaha';
$breadcrumb = ['Usaha' => 'usaha/index.php', 'Tambah' => null];
require __DIR__ . '/../templates/header_app.php';

if (isset($errors['form'])):
?>
<div class="error-summary" role="alert"><h2>Penyimpanan gagal</h2><ul><li><?= e($errors['form']) ?></li></ul></div>
<?php endif; ?>
<?php if ($errors && !isset($errors['form'])): ?>
<div class="error-summary" role="alert">
  <h2>Ada masalah pada isian formulir</h2>
  <ul><?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form class="form-panel" method="post" action="<?= e(u('usaha/tambah.php')) ?>" novalidate>
  <?= csrf_field() ?>

  <div class="form-grid">
    <div class="form-row form-row-full<?= isset($errors['nama_usaha']) ? ' has-error' : '' ?>">
      <label for="nama_usaha">Nama Unit Usaha <span class="req">*</span></label>
      <input type="text" id="nama_usaha" name="nama_usaha" maxlength="150" value="<?= e($old['nama_usaha']) ?>">
      <p class="field-hint">Kode usaha dibangkitkan otomatis setelah data tersimpan.</p>
      <?php if (isset($errors['nama_usaha'])): ?><p class="field-error"><?= e($errors['nama_usaha']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['responden_id']) ? ' has-error' : '' ?>">
      <label for="responden_id">Pemilik (Responden) <span class="req">*</span></label>
      <select id="responden_id" name="responden_id">
        <option value="0">— Pilih responden —</option>
        <?php foreach ($respondenList as $r): ?>
          <option value="<?= (int)$r['id'] ?>"<?= (int)$old['responden_id'] === (int)$r['id'] ? ' selected' : '' ?>>
            <?= e($r['nama_lengkap']) ?> (<?= e($r['nik']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['responden_id'])): ?><p class="field-error"><?= e($errors['responden_id']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['sektor_id']) ? ' has-error' : '' ?>">
      <label for="sektor_id">Sektor Lapangan Usaha <span class="req">*</span></label>
      <select id="sektor_id" name="sektor_id">
        <option value="0">— Pilih sektor —</option>
        <?php foreach ($sektorList as $s): ?>
          <option value="<?= (int)$s['id'] ?>"<?= (int)$old['sektor_id'] === (int)$s['id'] ? ' selected' : '' ?>>
            <?= e($s['kode']) ?> · <?= e($s['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['sektor_id'])): ?><p class="field-error"><?= e($errors['sektor_id']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['wilayah_id']) ? ' has-error' : '' ?>">
      <label for="wilayah_id">Wilayah Usaha <span class="req">*</span></label>
      <select id="wilayah_id" name="wilayah_id">
        <option value="0">— Pilih wilayah —</option>
        <?php foreach ($wilayahList as $w): ?>
          <option value="<?= (int)$w['id'] ?>"<?= (int)$old['wilayah_id'] === (int)$w['id'] ? ' selected' : '' ?>>
            <?= e($w['nama']) ?> (<?= e($w['tingkat']) ?><?= $w['parent_nama'] ? ' · ' . e($w['parent_nama']) : '' ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['wilayah_id'])): ?><p class="field-error"><?= e($errors['wilayah_id']) ?></p><?php endif; ?>
    </div>

    <div class="form-row form-row-full<?= isset($errors['alamat_usaha']) ? ' has-error' : '' ?>">
      <label for="alamat_usaha">Alamat Usaha <span class="req">*</span></label>
      <input type="text" id="alamat_usaha" name="alamat_usaha" maxlength="255" value="<?= e($old['alamat_usaha']) ?>">
      <?php if (isset($errors['alamat_usaha'])): ?><p class="field-error"><?= e($errors['alamat_usaha']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['tahun_mulai']) ? ' has-error' : '' ?>">
      <label for="tahun_mulai">Tahun Mulai Beroperasi <span class="req">*</span></label>
      <input type="number" id="tahun_mulai" name="tahun_mulai" min="1800" max="<?= date('Y') ?>" value="<?= e($old['tahun_mulai']) ?>">
      <?php if (isset($errors['tahun_mulai'])): ?><p class="field-error"><?= e($errors['tahun_mulai']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['jumlah_pekerja']) ? ' has-error' : '' ?>">
      <label for="jumlah_pekerja">Jumlah Pekerja <span class="req">*</span></label>
      <input type="number" id="jumlah_pekerja" name="jumlah_pekerja" min="0" value="<?= e($old['jumlah_pekerja']) ?>">
      <?php if (isset($errors['jumlah_pekerja'])): ?><p class="field-error"><?= e($errors['jumlah_pekerja']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['omzet_tahunan']) ? ' has-error' : '' ?>">
      <label for="omzet_tahunan">Omzet Tahunan (Rp) <span class="req">*</span></label>
      <input type="text" id="omzet_tahunan" name="omzet_tahunan" inputmode="numeric" value="<?= e($old['omzet_tahunan']) ?>" placeholder="contoh: 350000000">
      <?php if (isset($errors['omzet_tahunan'])): ?><p class="field-error"><?= e($errors['omzet_tahunan']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['status_usaha']) ? ' has-error' : '' ?>">
      <label for="status_usaha">Status Usaha <span class="req">*</span></label>
      <select id="status_usaha" name="status_usaha">
        <?php foreach (VALID_STATUS_USAHA as $st): ?>
          <option value="<?= e($st) ?>"<?= $old['status_usaha'] === $st ? ' selected' : '' ?>><?= e(ucfirst($st)) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['status_usaha'])): ?><p class="field-error"><?= e($errors['status_usaha']) ?></p><?php endif; ?>
    </div>

    <div class="form-row form-row-full<?= isset($errors['keterangan']) ? ' has-error' : '' ?>">
      <label for="keterangan">Keterangan</label>
      <textarea id="keterangan" name="keterangan" rows="3" maxlength="1000"><?= e($old['keterangan']) ?></textarea>
      <?php if (isset($errors['keterangan'])): ?><p class="field-error"><?= e($errors['keterangan']) ?></p><?php endif; ?>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Simpan Unit Usaha</button>
    <a class="btn btn-ghost" href="<?= e(u('usaha/index.php')) ?>">Batal</a>
  </div>
</form>

<?php require __DIR__ . '/../templates/footer.php'; ?>
