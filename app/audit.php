<?php
/**
 * SISENSUS 2026 — Jalur Audit
 * Semua aksi penting (login, CRUD, ekspor, penolakan akses) dicatat
 * beserta identitas, IP, dan user agent.
 */

if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

function audit_log(
    string $aksi,
    ?string $entitas = null,
    ?int $entitas_id = null,
    ?string $detail = null,
    ?string $username_snapshot = null
): void {
    try {
        $user = (function_exists('is_logged_in') && is_logged_in()) ? current_user() : null;

        $stmt = db()->prepare(
            'INSERT INTO audit_log
                (user_id, username_snapshot, aksi, entitas, entitas_id, detail, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $user['id'] ?? null,
            $username_snapshot ?? ($user['username'] ?? '-'),
            substr($aksi, 0, 50),
            $entitas !== null ? substr($entitas, 0, 50) : null,
            $entitas_id,
            $detail,
            client_ip(),
            client_agent(),
        ]);
    } catch (Throwable $ex) {
        // Jalur audit tidak boleh memutus alur utama; cukup masuk log server.
        error_log('[AUDIT] gagal mencatat: ' . $ex->getMessage());
    }
}
