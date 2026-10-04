<?php
/**
 * SISENSUS 2026 — Profil saya
 * Ubah identitas akun & ganti kata sandi (wajib sandi lama benar).
 */

require_once __DIR__ . '/app/bootstrap.php';
$user = require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $tindakan = str_input('tindakan');

    if ($tindakan === 'profil') {
        $full_name = str_input('full_name');
        $email     = str_input('email');

        if ($err = v_required($full_name, 'Nama lengkap', 100)) $errors['full_name'] = $err;
        if ($err = v_email($email)) $errors['email'] = $err;

        if (!isset($errors['email'])) {
            $dupe = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $dupe->execute([$email, (int)$user['id']]);
            if ($dupe->fetch()) $errors['email'] = 'Email sudah digunakan akun lain.';
        }

        if (!$errors) {
            $upd = db()->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ?');
            $upd->execute([$full_name, $email, (int)$user['id']]);
            audit_log('UPDATE_PROFILE', 'users', (int)$user['id'], 'Mengubah profil sendiri');
            flash_set('sukses', 'Profil berhasil diperbarui.');
            redirect('profil.php');
        }
    } elseif ($tindakan === 'sandi') {
        $lama    = $_POST['password_lama'] ?? '';
        $baru    = $_POST['password_baru'] ?? '';
        $konfirm = $_POST['password_confirm'] ?? '';

        $cur = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $cur->execute([(int)$user['id']]);
        $hash = $cur->fetch()['password_hash'];

        if (!password_verify($lama, $hash)) {
            $errors['password_lama'] = 'Kata sandi lama tidak sesuai.';
        }
        if ($err = v_password($baru)) $errors['password_baru'] = $err;
        elseif ($baru !== $konfirm)   $errors['password_confirm'] = 'Konfirmasi kata sandi tidak sama.';
        elseif (password_verify($baru, $hash)) $errors['password_baru'] = 'Kata sandi baru harus berbeda dari yang lama.';

        if (!$errors) {
            $upd = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->execute([password_hash($baru, PASSWORD_DEFAULT), (int)$user['id']]);
            session_regenerate_id(true); // rotasi ID sesi setelah perubahan kredensial
            audit_log('UPDATE_PASSWORD', 'users', (int)$user['id'], 'Mengganti kata sandi sendiri');
            flash_set('sukses', 'Kata sandi berhasil diganti.');
            redirect('profil.php');
        }
    }
}

// Segarkan data setelah perubahan.
$stmt = db()->prepare('SELECT id, username, email, full_name, role, last_login_at, created_at FROM users WHERE id = ?');
$stmt->execute([(int)$user['id']]);
$me = $stmt->fetch();

$recent = db()->prepare(
    'SELECT aksi, created_at, ip_address FROM audit_log WHERE user_id = ? ORDER BY id DESC LIMIT 5'
);
$recent->execute([(int)$user['id']]);
$recent = $recent->fetchAll();

$active = 'profil';
$page_title = 'Profil Saya';
$breadcrumb = ['Profil Saya' => null];
require __DIR__ . '/templates/header_app.php';

if ($errors):
?>
<div class="error-summary" role="alert">
  <h2>Ada masalah pada isian formulir</h2>
  <ul><?php foreach ($errors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="grid-2">
  <section class="panel">
    <h2 class="panel-title">Identitas Akun</h2>
    <form method="post" action="<?= e(u('profil.php')) ?>" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="tindakan" value="profil">
      <div class="form-grid">
        <div class="form-row">
          <label>Username</label>
          <input type="text" value="<?= e($me['username']) ?>" disabled>
          <p class="field-hint">Username tidak dapat diubah.</p>
        </div>
        <div class="form-row">
          <label>Peran</label>
          <input type="text" value="<?= e(role_label($me['role'])) ?>" disabled>
        </div>
        <div class="form-row<?= isset($errors['full_name']) ? ' has-error' : '' ?>">
          <label for="full_name">Nama Lengkap <span class="req">*</span></label>
          <input type="text" id="full_name" name="full_name" maxlength="100" value="<?= e($me['full_name']) ?>">
          <?php if (isset($errors['full_name'])): ?><p class="field-error"><?= e($errors['full_name']) ?></p><?php endif; ?>
        </div>
        <div class="form-row<?= isset($errors['email']) ? ' has-error' : '' ?>">
          <label for="email">Email <span class="req">*</span></label>
          <input type="email" id="email" name="email" maxlength="120" value="<?= e($me['email']) ?>">
          <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Simpan Profil</button>
      </div>
    </form>
  </section>

  <div>
    <section class="panel">
      <h2 class="panel-title">Ganti Kata Sandi</h2>
      <form method="post" action="<?= e(u('profil.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="tindakan" value="sandi">
        <div class="form-grid">
          <div class="form-row form-row-full<?= isset($errors['password_lama']) ? ' has-error' : '' ?>">
            <label for="password_lama">Kata Sandi Lama <span class="req">*</span></label>
            <input type="password" id="password_lama" name="password_lama" autocomplete="current-password">
            <?php if (isset($errors['password_lama'])): ?><p class="field-error"><?= e($errors['password_lama']) ?></p><?php endif; ?>
          </div>
          <div class="form-row form-row-full<?= isset($errors['password_baru']) ? ' has-error' : '' ?>">
            <label for="password_baru">Kata Sandi Baru <span class="req">*</span></label>
            <input type="password" id="password_baru" name="password_baru" autocomplete="new-password">
            <p class="field-hint">Minimal <?= PASSWORD_MIN_LEN ?> karakter, memuat huruf dan angka.</p>
            <?php if (isset($errors['password_baru'])): ?><p class="field-error"><?= e($errors['password_baru']) ?></p><?php endif; ?>
          </div>
          <div class="form-row form-row-full<?= isset($errors['password_confirm']) ? ' has-error' : '' ?>">
            <label for="password_confirm">Konfirmasi Kata Sandi Baru <span class="req">*</span></label>
            <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password">
            <?php if (isset($errors['password_confirm'])): ?><p class="field-error"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="form-actions">
          <button class="btn btn-primary" type="submit">Ganti Kata Sandi</button>
        </div>
      </form>
    </section>

    <section class="panel">
      <h2 class="panel-title">Aktivitas Terakhir Anda</h2>
      <table class="table">
        <thead><tr><th>Waktu</th><th>Aksi</th><th>IP</th></tr></thead>
        <tbody>
        <?php if (!$recent): ?>
          <tr><td colspan="3" class="empty">Belum ada aktivitas tercatat.</td></tr>
        <?php else: foreach ($recent as $r): ?>
          <tr>
            <td class="td-num td-nowrap"><?= e(format_dttm($r['created_at'])) ?></td>
            <td><code class="kode"><?= e($r['aksi']) ?></code></td>
            <td class="td-num"><?= e($r['ip_address'] ?: '—') ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </section>
  </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
