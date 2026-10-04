<?php
/**
 * SISENSUS 2026 — Konfigurasi Aplikasi
 *
 * Nilai di bawah dapat ditimpa lewat environment variable (getenv)
 * sehingga aman dipakai di lingkungan pengembangan maupun produksi.
 *
 * PENTING: pada lingkungan produksi, ganti kredensial database dan
 * jalankan di atas HTTPS (lihat docs/PENTEST-REPORT.md).
 */

// Blokir akses langsung terhadap berkas konfigurasi ini.
if (($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__
    || realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(403);
    exit('Forbidden');
}

// --- Identitas aplikasi -------------------------------------------------
define('APP_NAME',        'SISENSUS');
define('APP_NAME_LONG',   'Sistem Informasi Sensus Ekonomi 2026');
define('APP_ORG',         'Unit Pendataan Ekonomi Daerah');
define('APP_YEAR',        '2026');

// --- Database (MySQL/MariaDB) -------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'sensus_ekonomi');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// --- URL dasar aplikasi ---------------------------------------------------
// Kosongkan bila aplikasi berada di root domain. Contoh subfolder XAMPP:
// '/Tugas-CRUD-Sensus-Ekonomi'
define('BASE_URL', rtrim(getenv('BASE_URL') ?: '', '/'));

// --- Lingkungan ------------------------------------------------------------
define('APP_ENV', getenv('APP_ENV') ?: 'production');   // production | development
define('APP_LOG', getenv('APP_LOG') ?: sys_get_temp_dir() . '/sisensus-error.log');

// --- Kebijakan keamanan ------------------------------------------------------
define('SESSION_NAME',            'SENSSID');
define('SESSION_IDLE_TIMEOUT',    1800);   // detik — logout otomatis saat idle (30 menit)
define('SESSION_ABS_TIMEOUT',     28800);  // detik — umur absolut sesi (8 jam)
define('LOGIN_MAX_ATTEMPTS',      5);      // percobaan gagal sebelum akun terkunci
define('LOGIN_LOCK_SECONDS',      900);    // durasi kunci akun (15 menit)
define('IP_MAX_FAILS',            30);     // gagal login per IP dalam 15 menit
define('PASSWORD_MIN_LEN',        8);

date_default_timezone_set('Asia/Jakarta');

// Tandai aplikasi berjalan (melindungi file include dari akses langsung).
define('APP_RUNNING', true);
