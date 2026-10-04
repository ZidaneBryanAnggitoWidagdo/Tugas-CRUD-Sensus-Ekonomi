<?php
/**
 * SISENSUS 2026 — Bootstrap bersama
 * Dimuat oleh SETIAP halaman sebelum kode apa pun dieksekusi:
 * konfigurasi -> header keamanan -> sesi keras -> modul inti.
 */

// 1. Cegah akses langsung terhadap file bootstrap (harus lewat halaman aplikasi).
if (($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__ || realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(403);
    exit('Forbidden');
}

// 2. Konfigurasi.
require_once __DIR__ . '/../config/config.php';

// 3. Penanganan galat: tidak pernah membocorkan jejak tumpukan ke pengguna.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_LOG);

// 4. Header keamanan HTTP (kirim sebelum output apa pun).
header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Permitted-Cross-Domain-Policies: none');
header('Cross-Origin-Opener-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self'; style-src 'self'; "
     . "script-src 'self'; font-src 'self'; connect-src 'self'; object-src 'none'; "
     . "frame-ancestors 'none'; base-uri 'none'; form-action 'self'");
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// 5. Sesi keras (session hardening).
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_lifetime', '0');
ini_set('session.gc_maxlifetime', (string)SESSION_ABS_TIMEOUT);
ini_set('session.use_only_cookies', '1');
if (($_SERVER['HTTPS'] ?? '') === 'on') {
    ini_set('session.cookie_secure', '1');
}
session_name(SESSION_NAME);
session_start();

// 6. Modul inti.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/validate.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

// 7. Kedaluwarsa sesi: idle 30 menit / absolut 8 jam.
if (is_logged_in()) {
    $now = time();

    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
        audit_log('LOGOUT_IDLE', null, null, 'Sesi berakhir karena idle');
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        flash_set('peringatan', 'Sesi Anda berakhir karena tidak ada aktivitas. Silakan masuk kembali.');
        redirect('login.php');
    }

    if (isset($_SESSION['session_start']) && ($now - $_SESSION['session_start']) > SESSION_ABS_TIMEOUT) {
        audit_log('LOGOUT_ABSOLUTE', null, null, 'Sesi mencapai batas absolut');
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        flash_set('peringatan', 'Masa sesi habis. Silakan masuk kembali.');
        redirect('login.php');
    }

    $_SESSION['last_activity'] = $now;
    if (!isset($_SESSION['session_start'])) {
        $_SESSION['session_start'] = $now;
    }
}
