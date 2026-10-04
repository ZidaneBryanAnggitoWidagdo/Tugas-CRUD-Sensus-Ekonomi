<?php
/**
 * SISENSUS 2026 — Halaman masuk (login)
 *
 * Pertahanan:
 *  - CSRF token
 *  - Pesan galat generik (anti enumerasi username/email)
 *  - Kunci akun otomatis setelah N gagal (per akun)
 *  - Pembatas laju per IP berbasis audit log
 *  - Penyamaran waktu verifikasi (dummy hash saat username tidak ada)
 *  - session_regenerate_id (anti session fixation)
 */

require_once __DIR__ . '/app/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$old_username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old_username = str_input('username');
    $password     = $_POST['password'] ?? '';

    // Validasi bentuk masukan.
    if ($err = v_username($old_username)) { $errors['username'] = $err; }
    if (!is_string($password) || $password === '') { $errors['password'] = 'Kata sandi wajib diisi.'; }

    // Pembatas laju per IP: terlalu banyak kegagalan dalam 15 menit -> berhenti sejenak + tolak.
    $st = db()->prepare(
        "SELECT COUNT(*) c FROM audit_log
         WHERE aksi = 'LOGIN_FAIL' AND ip_address = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)"
    );
    $st->execute([client_ip()]);
    if ((int)$st->fetch()['c'] >= IP_MAX_FAILS) {
        audit_log('LOGIN_THROTTLED', null, null, 'Melebihi batas percobaan per IP');
        sleep(2);
        abort(429, 'Terlalu Banyak Percobaan', 'Aktivitas masuk dari jaringan Anda dibatasi sementara. '
            . 'Coba lagi dalam beberapa menit.');
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$old_username]);
        $user = $stmt->fetch();

        if ($user === null) {
            // Samakan beban waktu dengan kasus sandi salah (anti time-based enumeration).
            password_verify($password, '$2y$12$C6UzMDM.H6dfI/f/IKcEe.9uZfGvUeF6XuXmXW0l0iZ2fG0uRh6dq');
        }

        if ($user !== null) {
            if ($user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
                audit_log('LOGIN_LOCKED', 'users', (int)$user['id'], 'Percobaan masuk pada akun terkunci', $user['username']);
                $errors['form'] = 'Akun terkunci sementara karena terlalu banyak percobaan gagal. Coba lagi nanti.';
            } elseif (!password_verify($password, $user['password_hash'])) {
                $attempts = (int)$user['failed_login_attempts'] + 1;
                $lock = $attempts >= LOGIN_MAX_ATTEMPTS
                    ? date('Y-m-d H:i:s', time() + LOGIN_LOCK_SECONDS)
                    : null;
                $upd = db()->prepare(
                    'UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?'
                );
                $upd->execute([$attempts, $lock, (int)$user['id']]);
                audit_log('LOGIN_FAIL', 'users', (int)$user['id'], "Percobaan gagal ke-{$attempts}", $user['username']);
                $errors['form'] = 'Username atau kata sandi salah.';
            } elseif ((int)$user['is_active'] !== 1) {
                audit_log('LOGIN_INACTIVE', 'users', (int)$user['id'], 'Akun nonaktif', $user['username']);
                $errors['form'] = 'Username atau kata sandi salah.';
            }
        } else {
            $errors['form'] = 'Username atau kata sandi salah.';
        }

        // Sukses masuk.
        if (!$errors) {
            $upd = db()->prepare(
                'UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?'
            );
            $upd->execute([(int)$user['id']]);

            session_regenerate_id(true); // hapus ID sesi lama (anti session fixation)
            $_SESSION['user_id']       = (int)$user['id'];
            $_SESSION['session_start'] = time();
            $_SESSION['last_activity'] = time();

            audit_log('LOGIN_SUCCESS', 'users', (int)$user['id'], 'Berhasil masuk');
            flash_set('sukses', 'Selamat bekerja, ' . $user['full_name'] . '.');
            redirect('dashboard.php');
        }
    }
}

$page_title = 'Masuk';
require __DIR__ . '/templates/header_public.php';
?>

<main class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <img src="<?= e(u('assets/img/logo.svg')) ?>" alt="Logo <?= e(APP_NAME) ?>" width="44" height="44">
      <div>
        <h1>Masuk ke <?= e(APP_NAME) ?></h1>
        <p class="auth-sub"><?= e(APP_NAME_LONG) ?></p>
      </div>
    </div>

    <?php if ($flash = flash_get()): ?>
      <div class="flash flash-<?= e($flash['type']) ?>" role="status"><p><?= e($flash['msg']) ?></p></div>
    <?php endif; ?>

    <?php if (isset($errors['form'])): ?>
      <div class="flash flash-galat" role="alert"><p><?= e($errors['form']) ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?= e(u('login.php')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="form-row<?= isset($errors['username']) ? ' has-error' : '' ?>">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= e($old_username) ?>"
               autocomplete="username" autofocus required maxlength="50">
        <?php if (isset($errors['username'])): ?><p class="field-error"><?= e($errors['username']) ?></p><?php endif; ?>
      </div>
      <div class="form-row<?= isset($errors['password']) ? ' has-error' : '' ?>">
        <label for="password">Kata Sandi</label>
        <input type="password" id="password" name="password"
               autocomplete="current-password" required>
        <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Masuk</button>
    </form>

    <div class="auth-note">
      <p class="auth-note-title">Akun simulasi untuk keperluan tugas:</p>
      <p><code>admin / Admin@2026</code> · <code>operator / Petugas@2026</code> · <code>pimpinan / Pimpinan@2026</code></p>
      <p class="auth-note-warn">Pada lingkungan produksi, wajib mengganti sandi bawaan.</p>
    </div>
  </div>
</main>

<script src="<?= e(u('assets/js/app.js')) ?>"></script>
</body>
</html>
