# Dokumen SDLC — Metodologi Spiral

**Proyek**: SISENSUS 2026 — Sistem Informasi Sensus Ekonomi (CRUD PHP Native + MySQL)
**Metodologi**: Spiral Model (Barry Boehm, 1986 — *"A Spiral Model of Software Development and Enhancement"*, IEEE Computer)
**Periode pengerjaan**: 4 Oktober 2026

---

## 1. Mengapa Spiral?

Riset dilakukan terhadap literatur model Spiral (Boehm 1986; tutorial SDLC modern). Kesimpulan
perbandingan cepat terhadap model lain:

| Model | Cocok bila | Kelemahan untuk proyek ini |
|---|---|---|
| Waterfall | Kebutuhan stabil & dipahami penuh | Sistem data pemerintahan penuh risiko keamanan; risiko harus dianalisis tiap putaran |
| Incremental | Kebutuhan berkembang | Analisis risiko tidak eksplisit sebagai tahapan wajib |
| Agile/Scrum | Tim besar, backlog produk berubah cepat | Terlalu berat untuk proyek tunggal berjangka pendek |
| **Spiral** | **Proyek berisiko tinggi, perlu iterasi + prototyping, penekanan mitigasi risiko** | — |

Model Spiral dipilih karena **berbasis risiko (risk-driven)**: setiap putaran wajib melewati
empat kuadran — (1) *Determining Objectives*, (2) *Identifying & Resolving Risks*,
(3) *Development & Test*, (4) *Plan Next Iteration* — persis pola yang dibutuhkan sistem
pendataan ekonomi yang menuntut integritas, keamanan, dan akuntabilitas data.

## 2. Empat Kuadran per Putaran

```
   [1. PERENCANAAN]                    [4. EVALUASI & RENCANA
    Objektif, alternatif,               ITERASI BERIKUTNYA]
    kendala, ERD                    ↗↘
                                 ↗      ↘
                            ↗            ↘
   [2. ANALISIS RISIKO]                [3. REKAYASA]
    Identifikasi, evaluasi,             Bangun + prototipe
    mitigasi risiko                     + pengujian
```

## 3. Putaran Spiral yang Dijalankan

### Putaran 1 — Fondasi & Desain Data
| Kuadran | Aktivitas | Hasil |
|---|---|---|
| 1. Perencanaan | Menetapkan objektif sistem (CRUD + login + role + landing + dasbor), meneliti tema sensus ekonomi (SE2016/SE2026, KBLI), menentukan stack (PHP native, PDO, MySQL/MariaDB utf8mb4, InnoDB) | Ruang lingkup, deskripsi sistem |
| 2. Analisis risiko | Risiko terbesar: **desain database tidak layak** (relasi responden-usaha-wilayah-sektor), SQL injection sejak hari pertama | ERD 6 entitas (USERS, WILAYAH hierarkis, SEKTOR, RESPONDEN, USAHA, AUDIT_LOG) dengan FK RESTRICT/SET NULL — divalidasi *sebelum* coding (lihat `docs/ERD-Sensus-Ekonomi.png`) |
| 3. Rekayasa | `database/schema.sql` + `seed.sql` (3 akun, 15 wilayah, 11 sektor, 18 responden, 24 usaha), import & verifikasi | DB hidup, data simulasi realistis |
| 4. Evaluasi | Skema diuji impor ulang; seluruh FK valid | Lanjut putaran 2 |

### Putaran 2 — Kerangka Aplikasi & Keamanan Inti
| Kuadran | Aktivitas | Hasil |
|---|---|---|
| 1. Perencanaan | Objektif: autentikasi keras, RBAC 3 peran, landing page, dasbor | Daftar halaman prioritas |
| 2. Analisis risiko | Risiko: session fixation, CSRF, enumerasi akun, brute force, kebocoran stack trace | Desain mitigasi: session hardening (httponly, samesite=Strict, strict mode, regenerasi ID), CSRF per-sesi + hash_equals, pesan galat generik + dummy hash (anti timing), lockout 5 percobaan/15 menit, throttle per IP, display_errors off |
| 3. Rekayasa | `app/bootstrap.php`, `app/auth.php`, `app/csrf.php`, `app/db.php`, `app/helpers.php`, `app/validate.php`, `app/audit.php`, `index.php`, `login.php`, `logout.php`, `dashboard.php`, templates | Kerangka berjalan end-to-end |
| 4. Evaluasi | Lint `php -l` 25 file → 0 galat; smoke test HTTP | Lanjut putaran 3 |

### Putaran 3 — Fitur CRUD & Pelaporan
| Kuadran | Aktivitas | Hasil |
|---|---|---|
| 1. Perencanaan | Objektif: CRUD responden & usaha (cari, filter, paginasi), manajemen pengguna, laporan rekap + ekspor CSV, audit log, profil | Daftar fitur |
| 2. Analisis risiko | Risiko: IDOR, mass-assignment, param tampering, CSV formula injection, integritas FK saat hapus | Mitigasi: validasi server menyeluruh (16 aturan), casting integer, whitelist enum, POST+CSRF untuk semua mutasi, proteksi logika bisnis (tidak bisa hapus diri sendiri/ admin terakhir), csv_safe(), blokir hapus responden yang masih punya usaha |
| 3. Rekayasa | 15 halaman CRUD + laporan + audit + profil; UI institusional (font self-hosted Public Sans/Archivo/IBM Plex Mono, palet navy-kertas-oranye, chart CSS murni) | Aplikasi lengkap 25 file PHP |
| 4. Evaluasi | Screenshot UI 7 halaman — sesuai target "bukan AI-slop" | Lanjut putaran 4 |

### Putaran 4 — Pengujian, Pentesting & Rilis
| Kuadran | Aktivitas | Hasil |
|---|---|---|
| 1. Perencanaan | Objektif: nol bug kritis, nol celah keamanan yang dapat dieksploitasi | Kriteria rilis |
| 2. Analisis risiko | Pentesting gaya red-team: SQLi, XSS tersimpan, CSRF, IDOR, akses file langsung, cookie flag, enumerasi, brute force, type juggling, header keamanan | Temuan & perbaikan (lihat `docs/PENTEST-REPORT.md`) |
| 3. Rekayasa | Suite blackbox 51 uji otomatis; perbaikan bug: (a) guard `bootstrap.php` salah urutan, (b) `csrf_verify()` belum terpasang di 3 endpoint hapus, (c) guard `config.php`, (d) PDO HY093 pada filter wilayah, (e) deprecation `fputcsv` PHP 8.4+ | **51/51 PASS** |
| 4. Evaluasi & rilis | Review akhir, dokumentasi, commit bertahap per putaran spiral, push GitHub | Rilis v1.0.0 |

## 4. Register Risiko (Ringkasan)

| # | Risiko | Dampak | Mitigasi yang diterapkan | Status |
|---|---|---|---|---|
| R1 | SQL Injection | Kritis | PDO prepared statement, emulasi off, casting integer | Tertutup |
| R2 | XSS tersimpan | Kritis | `e()` (htmlspecialchars) di semua keluaran; CSP tanpa inline script | Tertutup |
| R3 | CSRF | Tinggi | Token per sesi + hash_equals + POST-only mutasi | Tertutup (bug ditemukan & diperbaiki saat uji) |
| R4 | Brute force login | Tinggi | Lockout per akun + throttle per IP + audit | Tertutup |
| R5 | Session hijacking/fixation | Tinggi | Regenerasi ID, HttpOnly, SameSite=Strict, idle+absolute timeout | Tertutup |
| R6 | Privilege escalation | Tinggi | RBAC server-side di tiap halaman + audit ACCESS_DENIED | Tertutup |
| R7 | Kehilangan integritas data | Tinggi | FK RESTRICT, transaksi pembuatan kode usaha, validasi ganda | Tertutup |
| R8 | CSV formula injection | Sedang | csv_safe() prefix apostrof | Tertutup |
| R9 | Kebocoran informasi (stack trace, phpinfo, listing) | Sedang | display_errors off, tanpa phpinfo, Options -Indexes, guard file internal | Tertutup |
| R10 | Enumerasi akun | Sedang | Pesan generik + dummy hash (timing sama) | Tertutup |

## 5. Artefak per Putaran

| Putaran | Artefak utama |
|---|---|
| 1 | `docs/ERD-Sensus-Ekonomi.png`, `database/schema.sql`, `database/seed.sql` |
| 2 | `app/*`, `templates/*`, `index.php`, `login.php`, `logout.php`, `dashboard.php` |
| 3 | `responden/*`, `usaha/*`, `pengguna/*`, `laporan.php`, `audit_log.php`, `profil.php`, `assets/*` |
| 4 | `tests/blackbox_test.py`, `docs/PENTEST-REPORT.md`, `docs/BLACKBOX-TESTING.md`, `README.md` |
