<?php
/**
 * SISENSUS 2026 — Hapus responden (admin & operator, POST + CSRF)
 * Ditolak bila responden masih memiliki unit usaha (integritas FK).
 */

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_role(['admin', 'operator']);
post_only();
csrf_verify();

$id = int_input('id', 0);

$stmt = db()->prepare('SELECT id, nama_lengkap, nik FROM responden WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    abort(404, 'Data Tidak Ditemukan', 'Responden yang hendak dihapus tidak ada dalam basis data.');
}

// Cegah penghapusan bila masih menaungi usaha (FK RESTRICT — beri pesan ramah).
$cnt = db()->prepare('SELECT COUNT(*) c FROM usaha WHERE responden_id = ?');
$cnt->execute([$id]);
if ((int)$cnt->fetch()['c'] > 0) {
    audit_log('DELETE_RESPONDEN_FAIL', 'responden', $id, 'Gagal: masih memiliki unit usaha terdaftar');
    flash_set('galat', 'Responden tidak dapat dihapus karena masih memiliki unit usaha terdaftar. '
        . 'Hapus atau pindahkan unit usaha terlebih dahulu.');
    redirect('responden/detail.php?id=' . $id);
}

$del = db()->prepare('DELETE FROM responden WHERE id = ?');
$del->execute([$id]);

audit_log('DELETE_RESPONDEN', 'responden', $id, 'Menghapus responden: ' . $row['nama_lengkap'] . ' (NIK ' . $row['nik'] . ')');
flash_set('sukses', 'Responden "' . $row['nama_lengkap'] . '" telah dihapus.');
redirect('responden/index.php');
