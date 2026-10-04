<?php
/**
 * SISENSUS 2026 — Autentikasi & Kontrol Akses Berbasis Peran (RBAC)
 *
 * Peran:
 *   admin    — akses penuh termasuk manajemen pengguna & audit log
 *   operator — CRUD responden & usaha
 *   pimpinan — hanya membaca (dashboard, daftar, laporan)
 */

if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

/** Pengguna yang sedang masuk (null bila tamu). */
function current_user(): ?array
{
    static $cached = false;
    static $user = null;

    if ($cached) return $user;
    $cached = true;

    if (empty($_SESSION['user_id'])) return null;

    $stmt = db()->prepare(
        'SELECT id, username, email, full_name, role, is_active
         FROM users WHERE id = ? AND is_active = 1 LIMIT 1'
    );
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    // Akun dinonaktifkan di tengah sesi -> paksa keluar.
    if ($user === null) {
        session_destroy();
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

/** Wajib sudah masuk; jika tidak, arahkan ke halaman login. */
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        flash_set('peringatan', 'Silakan masuk terlebih dahulu untuk mengakses sistem.');
        redirect('login.php');
    }
    return $user;
}

/** Wajib memiliki salah satu peran; jika tidak, tolak 403 (tercatat di audit). */
function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        audit_log(
            'ACCESS_DENIED',
            null,
            null,
            'Peran "' . $user['role'] . '" mencoba mengakses: ' . ($_SERVER['REQUEST_URI'] ?? '-')
        );
        abort(403, 'Akses Ditolak', 'Anda tidak memiliki kewenangan untuk membuka halaman ini.');
    }
    return $user;
}

/** Boleh mengubah data (tambah/ubah/hapus) responden & usaha? */
function can_edit_data(): bool
{
    $user = current_user();
    return $user !== null && in_array($user['role'], ['admin', 'operator'], true);
}

function role_label(string $role): string
{
    return [
        'admin'    => 'Administrator',
        'operator' => 'Petugas/Penginput',
        'pimpinan' => 'Pimpinan',
    ][$role] ?? $role;
}
