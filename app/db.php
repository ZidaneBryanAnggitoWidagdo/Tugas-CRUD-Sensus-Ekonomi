<?php
/**
 * SISENSUS 2026 — Koneksi database (PDO, singleton)
 *
 * Semua query di seluruh aplikasi menggunakan prepared statement PDO
 * dengan emulasi dimatikan — mencegah SQL Injection.
 */

if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            error_log('[DB] ' . $e->getMessage());
            http_response_code(500);
            echo '<!doctype html><html lang="id"><meta charset="utf-8">'
               . '<title>Kesalahan Server</title>'
               . '<body style="font-family:sans-serif;text-align:center;padding-top:10vh">'
               . '<h1>503 — Layanan basis data tidak tersedia</h1>'
               . '<p>Silakan hubungi administrator sistem.</p></body></html>';
            exit;
        }
    }

    return $pdo;
}
