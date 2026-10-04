<?php
/**
 * SISENSUS 2026 — Tambah pengguna (khusus admin)
 * Tidak ada registrasi publik — sistem pemerintah internal.
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_role(['admin']);

$errors = [];
$old = ['username'=>'','email'=>'','full_name'=>'','role'=>'operator','is_active'=>'1'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($old as $k => $_) { $old[$k] = str_input($k); }
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if ($err = v_username($old['username']))            $errors['username'] = $err;
    if ($err = v_email($old['email']))                  $errors['email'] = $err;
    if ($err = v_required($old['full_name'], 'Nama lengkap', 100)) $errors['full_name'] = $err;
    if ($err = v_enum($old['role'], VALID_ROLES, 'Peran')) $errors['role'] = $err;
    if ($err = v_password($password))                   $errors['password'] = $err;
    elseif ($password !== $confirm)                     $errors['password_confirm'] = 'Konfirmasi kata sandi tidak sama.';

    if (!isset($errors['username'])) {
        $dupe = db()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $dupe->execute([$old['username']]);
        if ($dupe->fetch()) $errors['username'] = 'Username sudah digunakan.';
    }
    if (!isset($errors['email'])) {
        $dupe = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $dupe->execute([$old['email']]);
        if ($dupe->fetch()) $errors['email'] = 'Email sudah digunakan.';
    }

    if (!$errors) {
        $ins = db()->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $old['username'], $old['email'],
            password_hash($password, PASSWORD_DEFAULT),
            $old['full_name'], $old['role'],
            isset($_POST['is_active']) ? 1 : 0,
        ]);
        $newId = (int)db()->lastInsertId();

        audit_log('CREATE_USER', 'users', $newId, 'Menambah pengguna: ' . $old['username'] . ' (' . $old['role'] . ')');
        flash_set('sukses', 'Pengguna "' . $old['username'] . '" berhasil dibuat.');
        redirect('pengguna/index.php');
    }
}

$active = 'pengguna';
$page_title = 'Tambah Pengguna';
$breadcrumb = ['Pengguna' => 'pengguna/index.php', 'Tambah' => null];
require __DIR__ . '/../templates/header_app.php';

if ($errors):
?>
<div class="error-summary" role="alert">
  <h2>Ada masalah pada isian formulir</h2>
  <ul><?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form class="form-panel" method="post" action="<?= e(u('pengguna/tambah.php')) ?>" novalidate>
  <?= csrf_field() ?>

  <div class="form-grid">
    <div class="form-row<?= isset($errors['username']) ? ' has-error' : '' ?>">
      <label for="username">Username <span class="req">*</span></label>
      <input type="text" id="username" name="username" maxlength="50" value="<?= e($old['username']) ?>" autocomplete="off">
      <?php if (isset($errors['username'])): ?><p class="field-error"><?= e($errors['username']) ?></p><?php endif; ?>
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
      <select id="role" name="role">
        <?php foreach (VALID_ROLES as $r): ?>
          <option value="<?= e($r) ?>"<?= $old['role'] === $r ? ' selected' : '' ?>><?= e(role_label($r)) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['role'])): ?><p class="field-error"><?= e($errors['role']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['password']) ? ' has-error' : '' ?>">
      <label for="password">Kata Sandi <span class="req">*</span></label>
      <input type="password" id="password" name="password" autocomplete="new-password">
      <p class="field-hint">Minimal <?= PASSWORD_MIN_LEN ?> karakter, memuat huruf dan angka.</p>
      <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
    </div>

    <div class="form-row<?= isset($errors['password_confirm']) ? ' has-error' : '' ?>">
      <label for="password_confirm">Konfirmasi Kata Sandi <span class="req">*</span></label>
      <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password">
      <?php if (isset($errors['password_confirm'])): ?><p class="field-error"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
    </div>

    <div class="form-row form-row-full">
      <label class="check-label">
        <input type="checkbox" name="is_active" value="1"<?= $old['is_active'] === '1' ? ' checked' : '' ?>>
        Akun aktif (dapat digunakan untuk masuk)
      </label>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Buat Pengguna</button>
    <a class="btn btn-ghost" href="<?= e(u('pengguna/index.php')) ?>">Batal</a>
  </div>
</form>

<?php require __DIR__ . '/../templates/footer.php'; ?>
