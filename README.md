# SISENSUS 2026 — Sistem Informasi Sensus Ekonomi

Aplikasi web **CRUD Sensus Ekonomi** bergaya sistem pemerintahan — dibangun murni dengan
**PHP Native (tanpa framework) + MySQL/MariaDB**, lengkap dengan login, sistem peran
(RBAC), landing page, dasbor, audit trail, laporan, dan pengujian keamanan.

> Seluruh data pada repositori ini adalah **simulasi/fiktif** untuk keperluan tugas.

---

## Fitur

| Modul | Keterangan |
|---|---|
| Landing page | Halaman muka publik dengan ringkasan statistik agregat & penjelasan pendataan |
| Login + sesi keras | CSRF, lockout brute force, anti-enumerasi, anti session-fixation, idle timeout |
| Sistem peran (RBAC) | `admin` (penuh), `operator` (CRUD data), `pimpinan` (baca saja) |
| Dasbor | Statistik utama, distribusi usaha per sektor & responden per kecamatan (chart CSS murni) |
| CRUD Responden | Cari, filter kecamatan, paginasi, validasi server menyeluruh, detail + daftar usaha |
| CRUD Usaha | Kode usaha otomatis (`USE-2026-0001`), filter sektor/status, relasi responden |
| Manajemen pengguna | Khusus admin: buat/ubah/reset sandi/hapus + proteksi logika bisnis |
| Laporan | Rekap per sektor & per kecamatan + ekspor CSV anti formula-injection |
| Audit log | Jejak semua aksi penting: login, CRUD, ekspor, penolakan akses, CSRF gagal |
| Profil | Ubah identitas & ganti kata sandi (wajib sandi lama) |
| Keamanan | PDO prepared statement, CSP ketat, header keamanan lengkap, RBAC server-side |

## Tumpukan Teknologi

- PHP 7.4+ (kompatibel hingga PHP 8.5) — tanpa framework, tanpa composer
- MySQL 5.7+ / MariaDB 10.4+ (utf8mb4, InnoDB, foreign key)
- CSS murni (sistem desain sendiri) + vanilla JS ~30 baris — **tanpa CDN pihak ketiga**
- Font self-hosted: Public Sans, Archivo, IBM Plex Mono (OFL)

## Struktur Proyek

```
├── index.php               # Landing page (publik)
├── login.php / logout.php  # Autentikasi
├── dashboard.php           # Dasbor (setelah login)
├── laporan.php             # Rekapitulasi + ekspor CSV
├── audit_log.php           # Jalur audit (admin)
├── profil.php              # Profil & ganti sandi
├── responden/  usaha/      # CRUD data sensus
├── pengguna/               # Manajemen akun (admin)
├── app/                    # Inti: bootstrap, db, auth, csrf, validate, audit, helpers
├── config/                 # Konfigurasi aplikasi (dukung env var)
├── templates/              # Layout header/footer/sidebar
├── assets/                 # CSS, JS, font, logo (self-hosted)
├── database/               # schema.sql + seed.sql
├── docs/                   # ERD, dokumen SDLC Spiral, laporan pentest & blackbox
└── tests/                  # Suite blackbox testing (51 uji)
```

## Cara Instalasi (XAMPP / LAMPP)

1. Salin folder ini ke `htdocs` (mis. `C:\xampp\htdocs\Tugas-CRUD-Sensus-Ekonomi`).
2. Nyalakan **Apache** dan **MySQL** dari XAMPP Control Panel.
3. Buat database dengan salah satu cara:
   - Buka `http://localhost/phpmyadmin` → tab *Import* → unggah `database/schema.sql`
     lalu `database/seed.sql`; **atau** lewat terminal:
     ```bash
     mysql -u root < database/schema.sql
     mysql -u root < database/seed.sql
     ```
4. Sesuaikan kredensial di `config/config.php` (bila perlu) — atau set environment variable
   `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
5. Bila aplikasi diakses via subfolder, set `BASE_URL` di `config/config.php`
   (contoh: `'/Tugas-CRUD-Sensus-Ekonomi'`). Bila di root domain, biarkan kosong.
6. Buka `http://localhost/Tugas-CRUD-Sensus-Ekonomi/` (atau sesuai BASE_URL).

## Akun Bawaan (Simulasi)

| Peran | Username | Kata sandi | Hak akses |
|---|---|---|---|
| Administrator | `admin` | `Admin@2026` | Semua modul + manajemen pengguna + audit log |
| Petugas/Penginput | `operator` | `Petugas@2026` | CRUD responden & usaha + laporan |
| Pimpinan | `pimpinan` | `Pimpinan@2026` | Hanya melihat data & laporan |

> ⚠️ **Wajib** mengganti kata sandi bawaan sebelum dipakai pada lingkungan sungguhan.

## Dokumentasi Lanjutan

- [`docs/ERD-Sensus-Ekonomi.png`](docs/ERD-Sensus-Ekonomi.png) — diagram ERD 6 entitas
- [`docs/SDLC-SPIRAL.md`](docs/SDLC-SPIRAL.md) — penerapan metodologi Spiral (4 putaran)
- [`docs/PENTEST-REPORT.md`](docs/PENTEST-REPORT.md) — 16 vektor serangan diuji, 3 celah diperbaiki
- [`docs/BLACKBOX-TESTING.md`](docs/BLACKBOX-TESTING.md) — 51/51 uji lulus + cara menjalankan

## Catatan Desain (Anti "AI-Slop")

Antarmuka sengaja dirancang **institusional** (referensi: gov.uk Design System, laman Badan
Pusat Statistik, tipografi Swiss) dan menghindari pola visual generatif generik:

- Tanpa gradien ungu/biru, tanpa glassmorphism, tanpa emoji, tanpa kartu rounded-2xl berbayang
- Palet kertas hangat + navy pekat + aksen oranye bata, garis tipis tegas, sudut hampir tajam
- Tipografi: Archivo (judul), Public Sans (teks — font milik layanan publik),
  IBM Plex Mono (angka/kode, `tabular-nums` untuk perataan kolom angka)
- Pola layanan publik: focus ring kuning khas, error summary ala GOV.UK, breadcrumb mono
- Grafik batang CSS murni tanpa pustaka; seluruh aset self-hosted (CSP `default-src 'self'`)
