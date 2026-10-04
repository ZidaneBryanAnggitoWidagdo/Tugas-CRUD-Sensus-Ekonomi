<?php
/**
 * SISENSUS 2026 — Perlindungan CSRF (Cross-Site Request Forgery)
 *
 * Token acak per sesi, diverifikasi dengan hash_equals (ketahan timing).
 * Setiap formulir mutasi wajib menyertakan csrf_field().
 */

if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Verifikasi token CSRF; gagal = catat audit + tolak 403. */
function csrf_verify(): void
{
    $sent = $_POST['csrf'] ?? '';
    $known = $_SESSION['csrf'] ?? '';

    if (!is_string($sent) || $sent === '' || $known === '' || !hash_equals($known, $sent)) {
        audit_log('CSRF_FAIL', null, null, 'Token CSRF tidak valid pada: ' . ($_SERVER['REQUEST_URI'] ?? '-'));
        abort(403, 'Permintaan Ditolak', 'Token keamanan formulir tidak valid atau kedaluwarsa. '
            . 'Muat ulang halaman dan coba lagi.');
    }
}
