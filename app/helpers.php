<?php
/**
 * SISENSUS 2026 — Fungsi bantu umum
 * e(): pelindung XSS — SELALU dipakai untuk keluaran HTML.
 */

if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

/** Escaping keluaran HTML (anti-XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Bangun URL berdasar BASE_URL. */
function u(string $path): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Redirect lalu hentikan eksekusi. */
function redirect(string $path): never
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : u($path)));
    exit;
}

/** Simpan pesan sekali-tampil (flash). */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $message];
}

/** Ambil pesan flash (lalu hapus). */
function flash_get(): ?array
{
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/** Nilai lama formulir. */
function old(string $key, array $src, string $default = ''): string
{
    return isset($src[$key]) ? (string)$src[$key] : $default;
}

/** Format rupiah dengan pemisah ribuan. */
function format_rupiah($value): string
{
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

/** Format tanggal Indonesia (d-m-Y). */
function format_tgl(?string $date): string
{
    if (!$date) return '-';
    $ts = strtotime($date);
    return $ts ? date('d-m-Y', $ts) : '-';
}

/** Format tanggal-waktu log. */
function format_dttm(?string $dt): string
{
    if (!$dt) return '-';
    $ts = strtotime($dt);
    return $ts ? date('d-m-Y H:i', $ts) : '-';
}

/** Alamat IP klien (REMOTE_ADDR saja — tidak memercayai header X-Forwarded-For). */
function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** User agent ringkas untuk log. */
function client_agent(): string
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '-';
    return substr($ua, 0, 255);
}

/** Pastikan metode HTTP POST; selain itu tolak. */
function post_only(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        audit_log('ACCESS_DENIED', null, null, 'Metode non-POST pada endpoint mutasi: ' . ($_SERVER['REQUEST_URI'] ?? '-'));
        abort(405, 'Metode Tidak Diizinkan', 'Aksi ini hanya boleh dilakukan melalui permintaan POST.');
    }
}

/** Ambil & paksa integer dari GET/POST (anti tampering). */
function int_input(string $key, int $default = 0): int
{
    $v = $_GET[$key] ?? $_POST[$key] ?? $default;
    return (int)$v;
}

/** Ambil & rapikan string GET/POST. */
function str_input(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/** Lepaskan karakter khusus LIKE dari kata kunci pencarian. */
function like_escape(string $value): string
{
    return addcslashes($value, "%_\\");
}

/**
 * Halaman galat generik (tanpa membocorkan detail internal).
 */
function abort(int $code, string $title, string $message): never
{
    http_response_code($code);
    $app = defined('APP_NAME') ? APP_NAME : 'SISENSUS';
    $long = defined('APP_NAME_LONG') ? APP_NAME_LONG : '';
    $year = defined('APP_YEAR') ? APP_YEAR : date('Y');
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<meta name="robots" content="noindex">'
       . '<title>' . e("{$code} — {$title}") . '</title>'
       . '<link rel="stylesheet" href="' . e(u('assets/css/style.css')) . '">'
       . '</head><body class="errpage"><main class="errpage-box">'
       . '<p class="errpage-code">' . e((string)$code) . '</p>'
       . '<h1 class="errpage-title">' . e($title) . '</h1>'
       . '<p class="errpage-msg">' . e($message) . '</p>'
       . '<p><a class="btn" href="' . e(u('index.php')) . '">Kembali ke Halaman Muka</a></p>'
       . '<p class="errpage-foot">' . e("{$app} — {$long} · {$year}") . '</p>'
       . '</main></body></html>';
    exit;
}
