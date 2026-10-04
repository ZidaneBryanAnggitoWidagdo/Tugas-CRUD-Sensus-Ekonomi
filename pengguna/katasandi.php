<?php
/**
 * SISENSUS 2026 — Reset kata sandi pengguna (khusus admin)
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_role(['admin']);

$id = int_input('id', 0);
$stmt = db()->prepare('SELECT id, username, full_name FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    abort(404, 'Data Tidak Ditemukan', 'Pengguna dengan nomor tersebut tidak ada dalam basis data.');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if ($err = v_password($password))   $errors['password'] = $err;
    elseif ($password !== $confirm)     $errors['password_confirm'] = 'Konfirmasi kata sandi tidak sama.';

    if (!$errors) {
        $upd = db()->prepare(
            'UPDATE users SET password_hash = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?'
        );
        $upd->execute([password_hash($password, PASSWORD_DEFAULT), $id]);

        audit_log('RESET_PASSWORD', 'users', $id, 'Reset kata sandi pengguna: ' . $row['username']);
        flash_set('sukses', 'Kata sandi "' . $row['username'] . '" telah direset.');
        redirect('pengguna/index.php');
    }
}

$active = 'pengguna';
$page_title = 'Reset Kata Sandi';
$breadcrumb = ['Pengguna' => 'pengguna/index.php', 'Reset Sandi' => null];
require __DIR__ . '/../templates/header_app.php';

if ($errors):
?>
<div class="error-summary" role="alert">
  <h2>Ada masalah pada isian formulir</h2>
  <ul><?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form class="form-panel form-narrow" method="post" action="<?= e(u('pengguna/katasandi.php?id=' . $id)) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="form-intro">
    <p>Mereset kata sandi akun <strong><?= e($row['username']) ?></strong> (<?= e($row['full_name']) ?>).
       Reset juga akan membuka kunci akun bila sedang terkunci.</p>
  </div>

  <div class="form-grid">
    <div class="form-row form-row-full<?= isset($errors['password']) ? ' has-error' : '' ?>">
      <label for="password">Kata Sandi Baru <span class="req">*</span></label>
      <input type="password" id="password" name="password" autocomplete="new-password">
      <p class="field-hint">Minimal <?= PASSWORD_MIN_LEN ?> karakter, memuat huruf dan angka.</p>
      <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
    </div>

    <div class="form-row form-row-full<?= isset($errors['password_confirm']) ? ' has-error' : '' ?>">
      <label for="password_confirm">Konfirmasi Kata Sandi Baru <span class="req">*</span></label>
      <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password">
      <?php if (isset($errors['password_confirm'])): ?><p class="field-error"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Reset Kata Sandi</button>
    <a class="btn btn-ghost" href="<?= e(u('pengguna/index.php')) ?>">Batal</a>
  </div>
</form>

<?php require __DIR__ . '/../templates/footer.php'; ?>
