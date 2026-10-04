# Hasil Blackbox Testing — SISENSUS 2026

**Metode**: pengujian kotak-hitam otomatis melalui HTTP (`tests/blackbox_test.py`,
tanpa melihat kode internal — seperti pengguna jahat maupun pengguna biasa).
**Perangkat**: Python 3 (urllib + cookie jar), PHP dev server, MariaDB 11.8.
**Tanggal**: 4 Oktober 2026
**Hasil akhir**: **51 dari 51 uji LULUS** setelah 5 perbaikan bug.

---

## Ringkasan Perbaikan yang Dipicu Pengujian

| # | Bug yang ditemukan | Dampak | Perbaikan |
|---|--------------------|--------|-----------|
| B1 | Guard `bootstrap.php` memeriksa konstanta yang belum terdefinisi | Semua halaman 403 | Guard diubah ke deteksi akses langsung `SCRIPT_FILENAME` |
| B2 | `csrf_verify()` tidak dipanggil pada 3 endpoint hapus | Mutasi tanpa CSRF lolos | Token diverifikasi di `responden/hapus.php`, `usaha/hapus.php`, `pengguna/hapus.php` |
| B3 | `config/config.php` dapat diakses langsung (200) | Eksposur berkas konfigurasi | Guard akses langsung → 403 |
| B4 | PDO HY093: parameter `:wid` dipakai dua kali (emulasi preparasi OFF) | Filter kecamatan → 500 | Parameter dibedakan `:wid`/`:wid2` + uji regresi |
| B5 | `fputcsv` deprecated di PHP 8.4+ | Peringatan pada ekspor CSV | Parameter escape eksplisit via helper `csv_row()` |

## Matriks Uji

| ID | Skenario | Ekspektasi | Hasil |
|----|----------|------------|-------|
| T01 | Buka landing page | 200 + konten sensus | ✅ |
| T02 | Buka halaman login | 200 + token CSRF ada | ✅ |
| T03 | Tamu buka 6 halaman internal | 302 → login | ✅ |
| T04 | Login sandi salah | Pesan generik, tanpa bocoran | ✅ |
| T05 | Login admin benar | 302 → dasbor | ✅ |
| T06 | Dasbor setelah masuk | 200 + kartu statistik | ✅ |
| T07 | POST hapus tanpa CSRF | 403 | ✅ |
| T08 | Buat responden valid | 302 → detail, muncul di daftar | ✅ |
| T08c/d | Filter kecamatan & kombinasi filter | 200 (regresi B4) | ✅ |
| T09 | SQLi 3 payload pada pencarian | 200, tanpa galat DB | ✅ |
| T10 | XSS tersimpan pada nama responden | Ter-escape (`&lt;script&gt;`) | ✅ |
| T11 | Pimpinan akses halaman admin/operator | 403; menu tak tampil; boleh lihat daftar | ✅ |
| T12 | Detail ID tidak ada / 0 | 404 | ✅ |
| T13 | Hapus via GET | 405 | ✅ |
| T14 | Hapus responden dengan CSRF sah | 302 → daftar | ✅ |
| T15 | ID sesi sebelum vs sesudah login | Berubah (anti fixation) | ✅ |
| T16 | Paginasi/cari nakal (`page=abc`, `-5`, `99999999`, q 500 char) | 200 aman | ✅ |
| T17 | Header keamanan halaman internal | CSP, DENY, nosniff lengkap | ✅ |
| T18 | Akses langsung 4 berkas internal | 403 | ✅ |
| T19 | Laporan + ekspor CSV | 200, `text/csv`, bebas injeksi rumus | ✅ |
| T20 | Buat usaha valid | 302 → detail, kode otomatis `USE-2026-xxxx` | ✅ |
| T21 | Formulir diisi sampah (NIK abjad, tanggal 2099, enum nakal) | Ringkasan galat menolak | ✅ |
| T22 | Ganti sandi dengan sandi lama salah | Ditolak | ✅ |
| T23 | Logout GET vs POST | GET tidak mengakhiri sesi; POST mengakhiri | ✅ |
| T24 | Brute force operator 7× | Terkunci pada percobaan ke-6 | ✅ |

## Cara Menjalankan Ulang

```bash
# 1) Siapkan database (sekali):
mysql -u root < database/schema.sql
mysql -u root < database/seed.sql

# 2) Jalankan server aplikasi:
php -S 127.0.0.1:8080 -t .

# 3) Jalankan suite (tab lain):
python3 tests/blackbox_test.py http://127.0.0.1:8080
```

Keluaran diakhiri ringkasan `TOTAL / PASS / FAIL`; kode keluar non-nol bila ada kegagalan
sehingga mudah dipasang di CI.
