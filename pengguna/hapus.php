<?php
/**
 * SISENSUS 2026 — Hapus pengguna (khusus admin, POST + CSRF)
 * Proteksi: tidak dapat menghapus akun sendiri; minimal satu admin aktif harus tersisa.
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_role(['admin']);
post_only();
csrf_verify();

$id = int_input('id', 0);

$stmt = db()->prepare('SELECT id, username, role FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    abort(404, 'Data Tidak Ditemukan', 'Pengguna yang hendak dihapus tidak ada dalam basis data.');
}

if ((int)$row['id'] === (int)$user['id']) {
    audit_log('DELETE_USER_FAIL', 'users', $id, 'Ditolak: tidak dapat menghapus akun sendiri');
    flash_set('galat', 'Anda tidak dapat menghapus akun yang sedang Anda gunakan.');
    redirect('pengguna/index.php');
}

if ($row['role'] === 'admin') {
    $cnt = db()->query(
        "SELECT COUNT(*) c FROM users WHERE role = 'admin' AND is_active = 1 AND id <> " . (int)$id
    )->fetch()['c'];
    if ((int)$cnt === 0) {
        audit_log('DELETE_USER_FAIL', 'users', $id, 'Ditolak: admin aktif terakhir');
        flash_set('galat', 'Minimal harus ada satu administrator aktif. Akun ini tidak dapat dihapus.');
        redirect('pengguna/index.php');
    }
}

$del = db()->prepare('DELETE FROM users WHERE id = ?');
$del->execute([$id]);

audit_log('DELETE_USER', 'users', $id, 'Menghapus pengguna: ' . $row['username']);
flash_set('sukses', 'Pengguna "' . $row['username'] . '" telah dihapus.');
redirect('pengguna/index.php');
