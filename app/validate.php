<?php
/**
 * SISENSUS 2026 — Validasi masukan (sisi server)
 * Pemercepatan klien tidak pernah dipercaya; semua aturan diterapkan ulang di sini.
 */

if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

const VALID_ROLES          = ['admin', 'operator', 'pimpinan'];
const VALID_JENIS_KELAMIN  = ['L', 'P'];
const VALID_PENDIDIKAN     = ['Tidak Sekolah', 'SD/Sederajat', 'SMP/Sederajat', 'SMA/Sederajat', 'Diploma', 'Sarjana', 'Pascasarjana'];
const VALID_STATUS_RESPONDEN = ['aktif', 'nonaktif'];
const VALID_STATUS_USAHA   = ['aktif', 'tutup', 'baru'];

/** String wajib dengan batas panjang. */
function v_required(string $value, string $label, int $max = 150): ?string
{
    if ($value === '') {
        return "{$label} wajib diisi.";
    }
    if (mb_strlen($value) > $max) {
        return "{$label} maksimal {$max} karakter.";
    }
    return null;
}

/** NIK: tepat 16 digit angka. */
function v_nik(string $value, string $label = 'NIK'): ?string
{
    if (!preg_match('/^\d{16}$/', $value)) {
        return "{$label} harus tepat 16 digit angka.";
    }
    return null;
}

/** Telepon: 5-20 karakter dari digit dan tanda umum. */
function v_phone(?string $value, string $label = 'Telepon'): ?string
{
    if ($value === '' || $value === null) return null; // boleh kosong
    if (!preg_match('/^[0-9+()\-\s]{5,20}$/', $value)) {
        return "{$label} tidak valid (5-20 karakter, hanya angka dan + ( ) -).";
    }
    return null;
}

/** Integer dalam rentang. */
function v_int($value, string $label, int $min, int $max): ?string
{
    if (!preg_match('/^\d+$/', (string)$value)) {
        return "{$label} harus berupa angka.";
    }
    $n = (int)$value;
    if ($n < $min || $n > $max) {
        return "{$label} harus antara {$min} sampai {$max}.";
    }
    return null;
}

/** Angka uang (desimal dengan titik, tanpa pemisah ribuan) >= 0. */
function v_money($value, string $label, float $max = 999999999999999): ?string
{
    $clean = str_replace([' ', ','], ['', '.'], (string)$value);
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $clean)) {
        return "{$label} harus berupa angka (contoh: 150000000).";
    }
    if ((float)$clean > $max) {
        return "{$label} melebihi batas maksimum.";
    }
    return null;
}

/** Tanggal YYYY-MM-DD valid dan tidak di masa depan. */
function v_date(string $value, string $label, bool $allow_future = false): ?string
{
    $d = DateTime::createFromFormat('Y-m-d', $value);
    if (!$d || $d->format('Y-m-d') !== $value) {
        return "{$label} tidak valid (gunakan pemilih tanggal).";
    }
    if (!$allow_future && $d > new DateTime('today')) {
        return "{$label} tidak boleh di masa depan.";
    }
    if ($d < new DateTime('1900-01-01')) {
        return "{$label} tidak masuk akal (sebelum tahun 1900).";
    }
    return null;
}

/** Nilai harus termasuk daftar yang diizinkan. */
function v_enum($value, array $allowed, string $label): ?string
{
    if (!in_array((string)$value, $allowed, true)) {
        return "{$label} tidak valid.";
    }
    return null;
}

/** Kebijakan kata sandi: minimal panjang, ada huruf dan angka. */
function v_password(string $value, string $label = 'Kata sandi'): ?string
{
    if (strlen($value) < PASSWORD_MIN_LEN) {
        return "{$label} minimal " . PASSWORD_MIN_LEN . " karakter.";
    }
    if (!preg_match('/[A-Za-z]/', $value) || !preg_match('/\d/', $value)) {
        return "{$label} harus memuat huruf dan angka.";
    }
    return null;
}

/** Username: 4-50 karakter alfanumerik/underscore/titik. */
function v_username(string $value, string $label = 'Username'): ?string
{
    if (!preg_match('/^[A-Za-z0-9._]{4,50}$/', $value)) {
        return "{$label} harus 4-50 karakter (huruf, angka, titik, underscore).";
    }
    return null;
}

/** Email dasar. */
function v_email(string $value, string $label = 'Email'): ?string
{
    if ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL) || strlen($value) > 120) {
        return "{$label} tidak valid.";
    }
    return null;
}

/** Cegah injeksi rumus pada CSV (= + - @ di awal sel). */
function csv_safe(?string $value): string
{
    if ($value !== null && $value !== '' && preg_match('/^[=+\-@\t\r]/', $value)) {
        return "'" . $value;
    }
    return (string)$value;
}
