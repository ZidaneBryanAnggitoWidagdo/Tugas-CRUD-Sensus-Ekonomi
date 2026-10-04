<?php
/**
 * SISENSUS 2026 — Ubah pengguna (khusus admin)
 * Proteksi logika bisnis: admin tidak dapat menurunkan/menonaktifkan akunnya sendiri.
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_role(['admin']);

$id = int_input('id', 0);
$stmt = db()->prepare('SELECT id, username, email, full_name, role, is_active FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    abort(404, 'Data Tidak Ditemukan', 'Pengguna dengan nomor tersebut tidak ada dalam basis data.');
}

$isSelf = ((int)$row['id'] === (int)$user['id']);
$errors = [];
$old = [
    'email'     => $row['email'],
    'full_name' => $row['full_name'],
    'role'      => $row['role'],
    'is_active' => (string)$row['is_active'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($old as $k => $_) { $old[$k] = str_input($k); }

    if ($err = v_email($old['email']))                  $errors['email'] = $err;
    if ($err = v_required($old['full_name'], 'Nama lengkap', 100)) $errors['full_name'] = $err;
    if ($err = v_enum($old['role'], VALID_ROLES, 'Peran')) $errors['role'] = $err;

    // Logika bisnis: jangan biarkan admin mengunci dirinya sendiri.
    if ($isSelf) {
        $old['role'] = $row['role'];            // peran sendiri tak boleh diubah
        if (!isset($_POST['is_active'])) {
            $errors['is_active'] = 'Anda tidak dapat menonaktifkan akun Anda sendiri.';
        }
    }

    if (!isset($errors['email'])) {
        $dupe = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $dupe->execute([$old['email'], $id]);
        if ($dupe->fetch()) $errors['email'] = 'Email sudah digunakan akun lain.';
    }

    if (!$errors) {
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        // Cegah sistem tanpa admin aktif.
        if ($row['role'] === 'admin' && ($isActive === 0 || $old['role'] !== 'admin')) {
            $cnt = db()->query(
                "SELECT COUNT(*) c FROM users WHERE role = 'admin' AND is_active = 1 AND id <> " . (int)$id
            )->fetch()['c'];
            if ((int)$cnt === 0) {
                $errors['is_active'] = 'Minimal harus ada satu administrator aktif selain akun ini.';
            }
        }

        if (!$errors) {
            $upd = db()->prepare('UPDATE users SET email = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?');
            $upd->execute([$old['email'], $old['full_name'], $old['role'], $isActive, $id]);

            audit_log('UPDATE_USER', 'users', $id, 'Mengubah pengguna: ' . $row['username']);
            flash_set('sukses', 'Data pengguna "' . $row['username'] . '" tersimpan.');
            redirect('pengguna/index.php');
        }
    }
}

$active = 'pengguna';
$page_title = 'Ubah Pengguna';
$breadcrumb = ['Pengguna' => 'pengguna/index.php', 'Ubah' => null];
require __DIR__ . '/../templates/header_app.php';

if ($errors):
?>
<div class="error-summary" role="alert">
  <h2>Ada masalah pada isian formulir</h2>
  <ul><?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form class="form-panel" method="post" action="<?= e(u('pengguna/ubah.php?id=' . $id)) ?>" novalidate>
  <?= csrf_field() ?>

  <div class="form-grid">
    <div class="form-row">
      <label>Username</label>
      <input type="text" value="<?= e($row['username']) ?>" disabled>
      <p class="field-hint">Username tidak dapat diubah.</p>
    </div>

    <div class="form-row<?= isset($errors['full_name']) ? ' has-error' : '' ?>">
      <label for="full_name">Nama Lengkap <span class="req">*</span></label>
      <input type="text" id="full_name" name="full_name" maxlength="100" value="<?= e($old['full_name']) ?>">
      <?php if (isset($errors['full_name'])): ?><p class="field-error"><?= e($errors['full_name']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['email']) ? ' has-error' : '' ?>">
      <label for="email">Email <span class="req">*</span></label>
      <input type="email" id="email" name="email" maxlength="120" value="<?= e($old['email']) ?>">
      <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['role']) ? ' has-error' : '' ?>">
      <label for="role">Peran <span class="req">*</span></label>
      <select id="role" name="role"<?= $isSelf ? ' disabled' : '' ?>>
        <?php foreach (VALID_ROLES as $r): ?>
          <option value="<?= e($r) ?>"<?= $old['role'] === $r ? ' selected' : '' ?>><?= e(role_label($r)) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($isSelf): ?><input type="hidden" name="role" value="<?= e($old['role']) ?>">
        <p class="field-hint">Anda tidak dapat mengubah peran akun sendiri.</p><?php endif; ?>
      <?php if (isset($errors['role'])): ?><p class="field-error"><?= e($errors['role']) ?></p><?php endif; ?>
    </div>

    <div class="form-row form-row-full<?= isset($errors['is_active']) ? ' has-error' : '' ?>">
      <label class="check-label">
        <input type="checkbox" name="is_active" value="1"<?= (int)$old['is_active'] === 1 ? ' checked' : '' ?>>
        Akun aktif (dapat digunakan untuk masuk)
      </label>
      <?php if (isset($errors['is_active'])): ?><p class="field-error"><?= e($errors['is_active']) ?></p><?php endif; ?>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
    <a class="btn btn-ghost" href="<?= e(u('pengguna/index.php')) ?>">Batal</a>
  </div>
</form>

<?php require __DIR__ . '/../templates/footer.php'; ?>
