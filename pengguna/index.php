<?php
/**
 * SISENSUS 2026 — Manajemen pengguna (khusus admin)
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_role(['admin']);

$rows = db()->query(
    'SELECT id, username, email, full_name, role, is_active, last_login_at, created_at
     FROM users ORDER BY id'
)->fetchAll();

$active = 'pengguna';
$page_title = 'Pengguna Sistem';
$breadcrumb = ['Pengguna' => null];
require __DIR__ . '/../templates/header_app.php';
?>

<div class="page-actions">
  <a class="btn btn-primary" href="<?= e(u('pengguna/tambah.php')) ?>">+ Tambah Pengguna</a>
</div>

<section class="panel">
  <p class="result-count">Total <strong><?= count($rows) ?></strong> akun pengguna terdaftar</p>
  <table class="table">
    <thead>
      <tr>
        <th>Username</th><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th>Masuk Terakhir</th><th class="td-right">Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><code class="kode"><?= e($row['username']) ?></code></td>
        <td><?= e($row['full_name']) ?></td>
        <td><?= e($row['email']) ?></td>
        <td><span class="role-badge role-<?= e($row['role']) ?>"><?= e(role_label($row['role'])) ?></span></td>
        <td><span class="badge badge-<?= (int)$row['is_active'] === 1 ? 'aktif' : 'nonaktif' ?>"><?= (int)$row['is_active'] === 1 ? 'aktif' : 'nonaktif' ?></span></td>
        <td class="td-num"><?= e(format_dttm($row['last_login_at'])) ?></td>
        <td class="td-right row-actions">
          <a href="<?= e(u('pengguna/ubah.php?id=' . (int)$row['id'])) ?>">Ubah</a>
          <a href="<?= e(u('pengguna/katasandi.php?id=' . (int)$row['id'])) ?>">Reset Sandi</a>
          <?php if ((int)$row['id'] !== (int)$user['id']): ?>
            <form method="post" action="<?= e(u('pengguna/hapus.php')) ?>" class="inline-form"
                  data-confirm="Hapus akun <?= e($row['username']) ?>? Tindakan ini tidak dapat dibatalkan.">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button class="link-danger" type="submit">Hapus</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
