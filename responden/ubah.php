<?php
/**
 * SISENSUS 2026 — Ubah responden (admin & operator)
 */

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_role(['admin', 'operator']);

$id = int_input('id', 0);

$stmt = db()->prepare('SELECT * FROM responden WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    abort(404, 'Data Tidak Ditemukan', 'Responden dengan nomor tersebut tidak ada dalam basis data.');
}

$wilayahList = db()->query(
    "SELECT w.id, w.nama, w.tingkat, w.parent_id, pw.nama parent_nama
     FROM wilayah w LEFT JOIN wilayah pw ON pw.id = w.parent_id
     WHERE w.tingkat IN ('kecamatan','kelurahan')
     ORDER BY w.tingkat DESC, w.nama"
)->fetchAll();

$errors = [];
$old = [
    'nik'            => $row['nik'],
    'nama_lengkap'   => $row['nama_lengkap'],
    'jenis_kelamin'  => $row['jenis_kelamin'],
    'tanggal_lahir'  => $row['tanggal_lahir'],
    'alamat'         => $row['alamat'],
    'wilayah_id'     => (string)$row['wilayah_id'],
    'telepon'        => (string)$row['telepon'],
    'pendidikan'     => $row['pendidikan'],
    'pekerjaan_utama'=> (string)$row['pekerjaan_utama'],
    'status'         => $row['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($old as $k => $_) {
        $old[$k] = str_input($k);
    }

    if ($err = v_required($old['nik'], 'NIK', 16))            $errors['nik'] = $err;
    elseif ($err = v_nik($old['nik']))                        $errors['nik'] = $err;
    if ($err = v_required($old['nama_lengkap'], 'Nama lengkap', 100)) $errors['nama_lengkap'] = $err;
    if ($err = v_enum($old['jenis_kelamin'], VALID_JENIS_KELAMIN, 'Jenis kelamin')) $errors['jenis_kelamin'] = $err;
    if ($err = v_required($old['tanggal_lahir'], 'Tanggal lahir', 10)) $errors['tanggal_lahir'] = $err;
    elseif ($err = v_date($old['tanggal_lahir'], 'Tanggal lahir'))    $errors['tanggal_lahir'] = $err;
    if ($err = v_required($old['alamat'], 'Alamat', 255))     $errors['alamat'] = $err;
    if ($err = v_enum($old['pendidikan'], VALID_PENDIDIKAN, 'Pendidikan')) $errors['pendidikan'] = $err;
    if ($err = v_enum($old['status'], VALID_STATUS_RESPONDEN, 'Status'))   $errors['status'] = $err;
    if ($err = v_phone($old['telepon']))                      $errors['telepon'] = $err;
    if (mb_strlen($old['pekerjaan_utama']) > 100)             $errors['pekerjaan_utama'] = 'Pekerjaan utama maksimal 100 karakter.';

    $wid = (int)$old['wilayah_id'];
    $wStmt = db()->prepare("SELECT id FROM wilayah WHERE id = ? AND tingkat IN ('kecamatan','kelurahan')");
    $wStmt->execute([$wid]);
    if ($wid <= 0 || !$wStmt->fetch()) {
        $errors['wilayah_id'] = 'Wilayah wajib dipilih.';
    }

    // NIK unik (kecuali milik sendiri).
    if (!isset($errors['nik'])) {
        $dupe = db()->prepare('SELECT id FROM responden WHERE nik = ? AND id <> ? LIMIT 1');
        $dupe->execute([$old['nik'], $id]);
        if ($dupe->fetch()) {
            $errors['nik'] = 'NIK sudah terdaftar untuk responden lain.';
        }
    }

    if (!$errors) {
        $upd = db()->prepare(
            'UPDATE responden SET
                nik = ?, nama_lengkap = ?, jenis_kelamin = ?, tanggal_lahir = ?,
                alamat = ?, wilayah_id = ?, telepon = ?, pendidikan = ?,
                pekerjaan_utama = ?, status = ?
             WHERE id = ?'
        );
        $upd->execute([
            $old['nik'], $old['nama_lengkap'], $old['jenis_kelamin'], $old['tanggal_lahir'],
            $old['alamat'], $wid, ($old['telepon'] !== '' ? $old['telepon'] : null),
            $old['pendidikan'], ($old['pekerjaan_utama'] !== '' ? $old['pekerjaan_utama'] : null),
            $old['status'], $id,
        ]);

        audit_log('UPDATE_RESPONDEN', 'responden', $id, 'Mengubah responden: ' . $old['nama_lengkap'] . ' (NIK ' . $old['nik'] . ')');
        flash_set('sukses', 'Perubahan responden tersimpan.');
        redirect('responden/detail.php?id=' . $id);
    }
}

$active = 'responden';
$page_title = 'Ubah Responden';
$breadcrumb = ['Responden' => 'responden/index.php', 'Ubah' => null];
require __DIR__ . '/../templates/header_app.php';

if ($errors):
?>
<div class="error-summary" role="alert">
  <h2>Ada masalah pada isian formulir</h2>
  <ul>
    <?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form class="form-panel" method="post" action="<?= e(u('responden/ubah.php?id=' . $id)) ?>" novalidate>
  <?= csrf_field() ?>

  <div class="form-grid">
    <div class="form-row<?= isset($errors['nik']) ? ' has-error' : '' ?>">
      <label for="nik">NIK <span class="req">*</span></label>
      <input type="text" id="nik" name="nik" inputmode="numeric" maxlength="16" value="<?= e($old['nik']) ?>">
      <p class="field-hint">16 digit angka, tanpa spasi.</p>
      <?php if (isset($errors['nik'])): ?><p class="field-error"><?= e($errors['nik']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['nama_lengkap']) ? ' has-error' : '' ?>">
      <label for="nama_lengkap">Nama Lengkap <span class="req">*</span></label>
      <input type="text" id="nama_lengkap" name="nama_lengkap" maxlength="100" value="<?= e($old['nama_lengkap']) ?>">
      <?php if (isset($errors['nama_lengkap'])): ?><p class="field-error"><?= e($errors['nama_lengkap']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['jenis_kelamin']) ? ' has-error' : '' ?>">
      <label for="jenis_kelamin">Jenis Kelamin <span class="req">*</span></label>
      <select id="jenis_kelamin" name="jenis_kelamin">
        <option value="L"<?= $old['jenis_kelamin'] === 'L' ? ' selected' : '' ?>>Laki-laki</option>
        <option value="P"<?= $old['jenis_kelamin'] === 'P' ? ' selected' : '' ?>>Perempuan</option>
      </select>
      <?php if (isset($errors['jenis_kelamin'])): ?><p class="field-error"><?= e($errors['jenis_kelamin']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['tanggal_lahir']) ? ' has-error' : '' ?>">
      <label for="tanggal_lahir">Tanggal Lahir <span class="req">*</span></label>
      <input type="date" id="tanggal_lahir" name="tanggal_lahir" max="<?= date('Y-m-d') ?>" value="<?= e($old['tanggal_lahir']) ?>">
      <?php if (isset($errors['tanggal_lahir'])): ?><p class="field-error"><?= e($errors['tanggal_lahir']) ?></p><?php endif; ?>
    </div>

    <div class="form-row form-row-full<?= isset($errors['alamat']) ? ' has-error' : '' ?>">
      <label for="alamat">Alamat Tempat Tinggal <span class="req">*</span></label>
      <input type="text" id="alamat" name="alamat" maxlength="255" value="<?= e($old['alamat']) ?>">
      <?php if (isset($errors['alamat'])): ?><p class="field-error"><?= e($errors['alamat']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['wilayah_id']) ? ' has-error' : '' ?>">
      <label for="wilayah_id">Wilayah (Kecamatan/Kelurahan) <span class="req">*</span></label>
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

    <div class="form-row<?= isset($errors['telepon']) ? ' has-error' : '' ?>">
      <label for="telepon">Telepon</label>
      <input type="text" id="telepon" name="telepon" maxlength="20" value="<?= e($old['telepon']) ?>">
      <?php if (isset($errors['telepon'])): ?><p class="field-error"><?= e($errors['telepon']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['pendidikan']) ? ' has-error' : '' ?>">
      <label for="pendidikan">Pendidikan Terakhir <span class="req">*</span></label>
      <select id="pendidikan" name="pendidikan">
        <?php foreach (VALID_PENDIDIKAN as $p): ?>
          <option value="<?= e($p) ?>"<?= $old['pendidikan'] === $p ? ' selected' : '' ?>><?= e($p) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['pendidikan'])): ?><p class="field-error"><?= e($errors['pendidikan']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['pekerjaan_utama']) ? ' has-error' : '' ?>">
      <label for="pekerjaan_utama">Pekerjaan Utama</label>
      <input type="text" id="pekerjaan_utama" name="pekerjaan_utama" maxlength="100" value="<?= e($old['pekerjaan_utama']) ?>">
      <?php if (isset($errors['pekerjaan_utama'])): ?><p class="field-error"><?= e($errors['pekerjaan_utama']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['status']) ? ' has-error' : '' ?>">
      <label for="status">Status Responden <span class="req">*</span></label>
      <select id="status" name="status">
        <?php foreach (VALID_STATUS_RESPONDEN as $s): ?>
          <option value="<?= e($s) ?>"<?= $old['status'] === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['status'])): ?><p class="field-error"><?= e($errors['status']) ?></p><?php endif; ?>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
    <a class="btn btn-ghost" href="<?= e(u('responden/detail.php?id=' . $id)) ?>">Batal</a>
  </div>
</form>

<?php require __DIR__ . '/../templates/footer.php'; ?>
