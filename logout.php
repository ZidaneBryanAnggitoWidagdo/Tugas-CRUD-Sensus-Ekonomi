<?php
/**
 * SISENSUS 2026 — Keluar (POST + CSRF saja; GET tidak mengakhiri sesi).
 */

require_once __DIR__ . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (is_logged_in()) {
        audit_log('LOGOUT', null, null, 'Pengguna keluar secara manual');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

flash_set('sukses', 'Anda telah keluar dari sistem.');
redirect('index.php');
