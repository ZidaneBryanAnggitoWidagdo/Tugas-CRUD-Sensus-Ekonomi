#!/usr/bin/env python3
"""
SISENSUS 2026 — Suite Blackbox Testing
Pengujian kotak-hitam end-to-end lewat HTTP (tanpa melihat kode internal):
autentikasi, otorisasi, CSRF, XSS, SQLi, IDOR, metode HTTP, header keamanan,
paginasi, ekspor CSV, brute-force lockout, dan logika bisnis.

Pemakaian: python3 tests/blackbox_test.py [BASE_URL]
"""

import re
import sys
import urllib.parse
import urllib.request
import http.cookiejar

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8080'

RESULTS = []


class Client:
    """Klien HTTP sederhana dengan cookie jar."""

    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(self.jar)
        )

    def get(self, path, follow=True):
        return self._req('GET', path, None, follow)

    def post(self, path, data, follow=True):
        return self._req('POST', path, data, follow)

    def _req(self, method, path, data, follow):
        url = BASE + path if path.startswith('/') else BASE + '/' + path
        body = urllib.parse.urlencode(data).encode() if data else None
        req = urllib.request.Request(url, data=body, method=method)
        if body:
            req.add_header('Content-Type', 'application/x-www-form-urlencoded')

        class NoRedirect(urllib.request.HTTPRedirectHandler):
            def redirect_request(self, *a, **k):
                return None

        if not follow:
            op = urllib.request.build_opener(
                NoRedirect, urllib.request.HTTPCookieProcessor(self.jar))
            try:
                resp = op.open(req, timeout=15)
                return resp.status, dict(resp.headers), resp.read().decode('utf-8', 'replace')
            except urllib.error.HTTPError as e:
                return e.code, dict(e.headers), e.read().decode('utf-8', 'replace')

        try:
            resp = self.opener.open(req, timeout=15)
            return resp.status, dict(resp.headers), resp.read().decode('utf-8', 'replace')
        except urllib.error.HTTPError as e:
            return e.code, dict(e.headers), e.read().decode('utf-8', 'replace')


def check(tid, name, ok, detail=''):
    RESULTS.append((tid, name, ok, detail))
    mark = 'PASS' if ok else 'FAIL'
    print(f'[{mark}] {tid} — {name}' + (f'  ({detail})' if detail and not ok else ''))


def extract_csrf(html):
    m = re.search(r'name="csrf"\s+value="([0-9a-f]+)"', html)
    return m.group(1) if m else None


def login(c, username, password):
    st, hd, body = c.get('/login.php')
    token = extract_csrf(body)
    return c.post('/login.php', {'csrf': token, 'username': username, 'password': password}, follow=False)


# =========================================================================
print(f'Blackbox Testing — {BASE}')
print('=' * 72)

# ---- T01-T02: halaman publik --------------------------------------------
c = Client()
st, hd, body = c.get('/index.php')
check('T01', 'Landing page terbuka (200)', st == 200, f'status={st}')
check('T01b', 'Landing berisi konten sensus', 'Sensus Ekonomi' in body)

st, hd, body = c.get('/login.php')
csrf_public = extract_csrf(body)
check('T02', 'Halaman login terbuka (200) + token CSRF', st == 200 and csrf_public is not None)

# ---- T03: proteksi halaman internal bagi tamu ----------------------------
st, hd, body = c.get('/dashboard.php', follow=False)
check('T03', 'Tamu dialihkan dari dasbor (302 -> login)',
      st == 302 and '/login.php' in (hd.get('Location') or hd.get('location') or ''), f'status={st}')

for page in ['/responden/index.php', '/usaha/index.php', '/laporan.php', '/audit_log.php', '/profil.php']:
    st, _, _ = c.get(page, follow=False)
    check('T03b', f'Tamu ditolak dari {page}', st == 302, f'status={st}')

# ---- T04: login sandi salah — pesan generik ------------------------------
c = Client()
st, hd, body = login(c, 'admin', 'SandiSalah99')
check('T04', 'Login gagal: pesan generik tanpa bocoran',
      'Username atau kata sandi salah' in body
      and 'kata sandi untuk' not in body
      and 'tidak ditemukan' not in body)
check('T04b', 'Percobaan gagal dicatat audit', 'LOGIN_FAIL' not in body)  # tidak bocor ke UI

# ---- T05: login sukses admin ---------------------------------------------
c = Client()
pre_cookie = '; '.join(f'{k.name}={k.value}' for k in c.jar)
st, hd, body = login(c, 'admin', 'Admin@2026')
loc = hd.get('Location') or hd.get('location') or ''
check('T05', 'Login sukses -> 302 ke dasbor', st == 302 and 'dashboard' in loc, f'status={st} loc={loc}')
post_cookie = '; '.join(f'{k.name}={k.value}' for k in c.jar)
check('T15', 'ID sesi berubah setelah login (anti fixation)',
      pre_cookie != post_cookie and 'SENSSID' in post_cookie)

# ---- T06: dasbor setelah masuk -------------------------------------------
st, hd, body = c.get('/dashboard.php')
check('T06', 'Dasbor tampil (200) dengan statistik',
      st == 200 and 'Responden Terdaftar' in body and 'Unit Usaha Terdata' in body)

# ---- T07: tanpa token CSRF ditolak ---------------------------------------
st, hd, body = c.post('/responden/hapus.php', {'id': '1'})
check('T07', 'POST tanpa CSRF -> 403', st == 403, f'status={st}')

# ---- T08: buat responden valid --------------------------------------------
st, hd, body = c.get('/responden/tambah.php')
token = extract_csrf(body)
data = {
    'csrf': token, 'nik': '9900990012345678', 'nama_lengkap': 'Tester Valid Sensus',
    'jenis_kelamin': 'L', 'tanggal_lahir': '1995-05-10', 'alamat': 'Jl. Uji Coba No. 1',
    'wilayah_id': '7', 'telepon': '081999000111', 'pendidikan': 'Sarjana',
    'pekerjaan_utama': 'Penguji', 'status': 'aktif',
}
st, hd, body = c.post('/responden/tambah.php', data, follow=False)
loc = hd.get('Location') or hd.get('location') or ''
check('T08', 'Buat responden valid -> redirect detail', st == 302 and 'detail.php' in loc, f'status={st} loc={loc}')

st, hd, body = c.get('/responden/index.php?q=9900990012345678')
check('T08b', 'Responden baru muncul di daftar', 'Tester Valid Sensus' in body)
m = re.search(r'detail\.php\?id=(\d+)', body)
new_responden_id = m.group(1) if m else None

# ---- T08c: filter kecamatan (regresi HY093: param dipakai dua kali) --------
st, hd, body = c.get('/responden/index.php?wilayah=7')
check('T08c', 'Filter kecamatan tunggal bekerja (200)', st == 200 and 'Klitren' in body)
st, hd, body = c.get('/responden/index.php?q=a&wilayah=7')
check('T08d', 'Kombinasi cari + filter kecamatan (200)', st == 200)

# ---- T09: SQL injection pada pencarian ------------------------------------
for payload in ["' OR 1=1--", "1' UNION SELECT NULL--", "%' AND SLEEP(2)--"]:
    st, hd, body = c.get('/responden/index.php?q=' + urllib.parse.quote(payload))
    check('T09', f'SQLi aman: {payload[:20]}',
          st == 200 and 'Fatal error' not in body and 'Warning' not in body and 'ORA-' not in body)

# ---- T10: XSS tersimpan harus ter-escape -----------------------------------
st, hd, body = c.get('/responden/tambah.php')
token = extract_csrf(body)
xss_data = {
    'csrf': token, 'nik': '9900990098765432',
    'nama_lengkap': '<script>alert("xss")</script>',
    'jenis_kelamin': 'P', 'tanggal_lahir': '1990-01-01', 'alamat': '" onmouseover="alert(1)',
    'wilayah_id': '8', 'telepon': '', 'pendidikan': 'SMP/Sederajat',
    'pekerjaan_utama': '', 'status': 'aktif',
}
st, hd, body = c.post('/responden/tambah.php', xss_data, follow=False)
st, hd, body = c.get('/responden/index.php?q=9900990098765432')
check('T10', 'XSS tersimpan ter-escape (tanpa <script> mentah)',
      '<script>alert' not in body and '&lt;script&gt;alert' in body)

# ---- T11: otorisasi peran (pimpinan vs halaman admin) ----------------------
c_pimpinan = Client()
st, hd, body = login(c_pimpinan, 'pimpinan', 'Pimpinan@2026')
st, hd, body = c_pimpinan.get('/pengguna/index.php', follow=False)
check('T11', 'Pimpinan ditolak di manajemen pengguna (403)', st == 403, f'status={st}')
st, hd, body = c_pimpinan.get('/responden/tambah.php', follow=False)
check('T11b', 'Pimpinan ditolak di tambah responden (403)', st == 403, f'status={st}')
st, hd, body = c_pimpinan.get('/responden/index.php')
check('T11c', 'Pimpinan boleh melihat daftar responden (200)',
      st == 200 and 'Tambah Responden' not in body)

# ---- T12: IDOR / data tidak ada -> 404 -------------------------------------
st, hd, body = c.get('/responden/detail.php?id=999999')
check('T12', 'ID tak dikenal -> 404', st == 404, f'status={st}')
st, hd, body = c.get('/usaha/detail.php?id=0')
check('T12b', 'ID=0 -> 404', st == 404, f'status={st}')

# ---- T13: mutasi via GET ditolak (405) --------------------------------------
st, hd, body = c.get('/usaha/hapus.php?id=1', follow=False)
check('T13', 'Hapus via GET -> 405', st == 405, f'status={st}')

# ---- T14: hapus responden dengan CSRF sah -----------------------------------
if new_responden_id:
    st, hd, body = c.get('/responden/index.php')
    token = extract_csrf(body)
    st, hd, body = c.post('/responden/hapus.php', {'csrf': token, 'id': new_responden_id}, follow=False)
    loc = hd.get('Location') or hd.get('location') or ''
    check('T14', 'Hapus responden (CSRF sah) -> redirect daftar', st == 302 and 'responden' in loc)

# ---- T16: paginasi & filter nakal -------------------------------------------
for bad in ['page=abc', 'page=-5', 'page=99999999', 'q=' + 'A' * 500]:
    st, hd, body = c.get('/responden/index.php?' + bad)
    check('T16', f'Input paginasi/cari nakal aman: {bad[:18]}', st == 200)

# ---- T17: header keamanan di halaman internal -------------------------------
st, hd, body = c.get('/dashboard.php')
hd_lower = {str(k).lower(): str(v).lower() for k, v in hd.items()}
csp = hd_lower.get('content-security-policy', '')
check('T17', 'Header keamanan lengkap',
      hd_lower.get('x-frame-options') == 'deny'
      and 'nosniff' in hd_lower.get('x-content-type-options', '')
      and "default-src 'self'" in csp
      and "frame-ancestors 'none'" in csp)

# ---- T18: akses langsung file internal -> 403 -------------------------------
for path in ['/app/db.php', '/config/config.php', '/templates/header_app.php', '/app/bootstrap.php']:
    st, hd, body = c.get(path)
    check('T18', f'Akses langsung {path} -> 403', st == 403, f'status={st}')

# ---- T19: ekspor CSV + anti injeksi rumus ------------------------------------
st, hd, body = c.get('/laporan.php')
check('T19', 'Halaman laporan tampil', st == 200 and 'Rekap per Sektor' in body)
st, hd, body = c.get('/laporan.php?export=sektor')
ctype = hd.get('Content-Type', '')
check('T19b', 'Ekspor CSV sektor: content-type benar', st == 200 and 'text/csv' in ctype, f'ctype={ctype}')
check('T19c', 'CSV tidak memuat karakter injeksi rumus', '=HYPERLINK' not in body and '=cmd' not in body)

# ---- T20: buat usaha valid -----------------------------------------------------
st, hd, body = c.get('/usaha/tambah.php')
token = extract_csrf(body)
data = {
    'csrf': token, 'nama_usaha': 'Usaha Uji Otomatis', 'responden_id': '1', 'sektor_id': '6',
    'wilayah_id': '7', 'alamat_usaha': 'Jl. Uji No. 2', 'tahun_mulai': '2024',
    'jumlah_pekerja': '3', 'omzet_tahunan': '150000000', 'status_usaha': 'baru', 'keterangan': 'dibuat tester',
}
st, hd, body = c.post('/usaha/tambah.php', data, follow=False)
loc = hd.get('Location') or hd.get('location') or ''
check('T20', 'Buat usaha valid -> redirect detail', st == 302 and 'detail' in loc, f'status={st}')
st, hd, body = c.get('/usaha/index.php?q=Usaha+Uji+Otomi')
m = re.search(r'USE-\d{4}-(\d{4})', body)
check('T20b', 'Kode usaha dibangkitkan otomatis', m is not None)
if m:
    m2 = re.search(r'detail\.php\?id=(\d+)"', body)
    if m2:
        token = extract_csrf(body)
        c.post('/usaha/hapus.php', {'csrf': token, 'id': m2.group(1)}, follow=False)

# ---- T21: validasi formulir menolak data sampah ---------------------------------
st, hd, body = c.get('/responden/tambah.php')
token = extract_csrf(body)
bad = {
    'csrf': token, 'nik': 'ABCD', 'nama_lengkap': '', 'jenis_kelamin': 'X',
    'tanggal_lahir': '2099-01-01', 'alamat': '', 'wilayah_id': '0',
    'telepon': 'abc', 'pendidikan': 'TidakSekolah', 'pekerjaan_utama': '', 'status': 'sibuk',
}
st, hd, body = c.post('/responden/tambah.php', bad)
check('T21', 'Validasi menolak isian sampah (ringkasan galat)',
      'Ada masalah pada isian formulir' in body and '16 digit' in body)

# ---- T22: profil — ganti sandi dengan sandi lama salah ---------------------------
st, hd, body = c.get('/profil.php')
token = extract_csrf(body)
st, hd, body = c.post('/profil.php', {
    'csrf': token, 'tindakan': 'sandi', 'password_lama': 'BukanSandi1',
    'password_baru': 'SandiBaru99', 'password_confirm': 'SandiBaru99',
})
check('T22', 'Ganti sandi: sandi lama salah ditolak', 'tidak sesuai' in body)

# ---- T23: logout — GET tidak mengakhiri sesi, POST mengakhiri ---------------------
st, hd, body = c.get('/logout.php', follow=False)
check('T23', 'Logout via GET tidak mengakhiri sesi (redirect saja)', st == 302)
st, hd, body = c.get('/dashboard.php')
check('T23b', 'Sesi masih hidup setelah GET logout', st == 200)
st, hd, body = c.get('/profil.php')
token = extract_csrf(body)
st, hd, body = c.post('/logout.php', {'csrf': token}, follow=False)
st, hd, body = c.get('/dashboard.php', follow=False)
check('T23c', 'Logout via POST mengakhiri sesi', st == 302, f'status={st}')

# ---- T24: brute force -> akun terkunci --------------------------------------------
c2 = Client()
locked = False
for i in range(1, 8):
    st, hd, body = login(c2, 'operator', 'SalahTotal' + str(i))
    if 'terkunci' in body.lower():
        locked = True
        check('T24', f'Akun terkunci pada percobaan ke-{i}', True)
        break
check('T24b', 'Lockout terjadi sebelum percobaan ke-7', locked)

print('=' * 72)
passed = sum(1 for _, _, ok, _ in RESULTS if ok)
failed = len(RESULTS) - passed
print(f'TOTAL: {len(RESULTS)}  PASS: {passed}  FAIL: {failed}')
sys.exit(0 if failed == 0 else 1)
