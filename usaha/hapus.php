<?php
/**
 * SISENSUS 2026 — Hapus unit usaha (admin & operator, POST + CSRF)
 */

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_role(['admin', 'operator']);
post_only();
csrf_verify();

$id = int_input('id', 0);

$stmt = db()->prepare('SELECT id, nama_usaha, kode_usaha FROM usaha WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    abort(404, 'Data Tidak Ditemukan', 'Unit usaha yang hendak dihapus tidak ada dalam basis data.');
}

$del = db()->prepare('DELETE FROM usaha WHERE id = ?');
$del->execute([$id]);

audit_log('DELETE_USAHA', 'usaha', $id, 'Menghapus usaha: ' . $row['nama_usaha'] . ' (' . $row['kode_usaha'] . ')');
flash_set('sukses', 'Unit usaha "' . $row['nama_usaha'] . '" telah dihapus.');
redirect('usaha/index.php');
