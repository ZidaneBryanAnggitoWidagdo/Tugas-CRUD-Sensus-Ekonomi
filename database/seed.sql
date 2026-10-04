-- =====================================================================
-- SISENSUS 2026 — Data awal (SEED) — seluruh data bersifat SIMULASI
-- Akun bawaan (WAJIB diganti passwordnya pada lingkungan produksi):
--   admin    / Admin@2026      (administrator sistem)
--   operator / Petugas@2026    (petugas pendataan)
--   pimpinan / Pimpinan@2026   (pimpinan — akses baca)
-- =====================================================================
USE sensus_ekonomi;

-- ------------------------------------------------ users
INSERT INTO users (id, username, email, password_hash, full_name, role, is_active) VALUES
(1, 'admin',    'admin@sensus.test',    '$2y$12$trivQAGCWF7leHXyaqR9VeHI6VdjUDLr3b0ae7gQYsR9xcDKoH7ie', 'Zidane Bryan Anggito Widagdo', 'admin',    1),
(2, 'operator', 'operator@sensus.test', '$2y$12$Rj6BkYPeMyPmMdKqDiotLe1of9dhQhiHrgxFfu.vEVyPCB2aBdlZy', 'Rina Kartika',                 'operator', 1),
(3, 'pimpinan', 'pimpinan@sensus.test', '$2y$12$mw4JhpzDK62KPLuWimOkkeNxpfvdP2JECBffPPTD1zIUBblOubbsO', 'Drs. Hendra Wijaya, M.Si.',    'pimpinan', 1);

-- ------------------------------------------------ wilayah (Kota Yogyakarta — simulasi)
INSERT INTO wilayah (id, kode, nama, tingkat, parent_id) VALUES
(1, '34.71', 'Kota Yogyakarta', 'kota', NULL),
(2, '34.71.01', 'Gondokusuman', 'kecamatan', 1),
(3, '34.71.02', 'Kotagede',     'kecamatan', 1),
(4, '34.71.03', 'Mergangsan',   'kecamatan', 1),
(5, '34.71.04', 'Umbulharjo',   'kecamatan', 1),
(6, '34.71.05', 'Wirogunan',    'kecamatan', 1),
( 7, '34.71.01.1001', 'Klitren',   'kelurahan', 2),
( 8, '34.71.01.1002', 'Demangan',  'kelurahan', 2),
( 9, '34.71.01.1003', 'Baciro',    'kelurahan', 2),
(10, '34.71.02.2001', 'Prenggan',  'kelurahan', 3),
(11, '34.71.02.2002', 'Purbayan',  'kelurahan', 3),
(12, '34.71.03.3001', 'Prawirodirjan', 'kelurahan', 4),
(13, '34.71.03.3002', 'Gedongkuning',  'kelurahan', 4),
(14, '34.71.04.4001', 'Warungboto',    'kelurahan', 5),
(15, '34.71.05.5001', 'Giwangan',      'kelurahan', 6);

-- ------------------------------------------------ sektor (mengacu kategori KBLI)
INSERT INTO sektor (id, kode, nama, deskripsi) VALUES
(1,  'A', 'Pertanian, Kehutanan, dan Perikanan', 'Budidaya tanaman, peternakan, perikanan tangkap dan budidaya, jasa pertanian.'),
(2,  'B', 'Pertambangan dan Penggalian',         'Ekstraksi mineral padat, cair, gas, termasuk kegiatan penunjang tambang.'),
(3,  'C', 'Industri Pengolahan',                 'Transformasi fisik/kimia bahan baku menjadi produk bernilai tambah.'),
(4,  'D', 'Pengadaan Listrik dan Gas',           'Pembangkitan, transmisi, dan distribusi listrik serta gas kota.'),
(5,  'E', 'Konstruksi',                          'Bangunan lengkap beserta perapiannya; pekerjaan sipil umum.'),
(6,  'F', 'Perdagangan Besar dan Eceran',        'Jual beli barang baik skala besar maupun eceran termasuk warung dan toko.'),
(7,  'G', 'Transportasi dan Pergudangan',        'Angkutan darat/laut/udara, jasa pos, pergudangan dan penunjang.'),
(8,  'H', 'Akomodasi dan Penyediaan Makan Minum','Hotel, penginapan, rumah makan, katering, dan jasa penyajian minuman.'),
(9,  'I', 'Informasi dan Komunikasi',            'Penerbitan, penyiaran, telekomunikasi, jasa komputer dan informasi.'),
(10, 'J', 'Jasa Keuangan dan Asuransi',          'Perbankan, pembiayaan, asuransi, dan pasar modal.'),
(11, 'K', 'Jasa Perusahaan',                     'Riset, konsultasi, periklanan, keamanan, jasa kantor dan tenaga kerja.');

-- ------------------------------------------------ responden (18 — data simulasi)
INSERT INTO responden (nik, nama_lengkap, jenis_kelamin, tanggal_lahir, alamat, wilayah_id, telepon, pendidikan, pekerjaan_utama, status, created_by) VALUES
('3401012501780001', 'Sutarno Wibowo',       'L', '1978-01-25', 'Jl. Kaliurang KM 4 No. 12',   7,  '081234500101', 'Sarjana',       'Pengusaha',       'aktif', 1),
('3401015008820002', 'Dwi Lestari Rahayu',    'P', '1982-08-05', 'Jl. Affandi No. 45',          7,  '081234500102', 'Diploma',       'Pemilik Toko',    'aktif', 1),
('3401012209850003', 'Agus Prasetyo Nugroho', 'L', '1985-09-22', 'Jl. Seturan Raya No. 8',      8,  '081234500103', 'SMA/Sederajat', 'Wiraniaga',       'aktif', 2),
('3401014511900004', 'Sri Wahyuni Astuti',    'P', '1990-11-04', 'Jl. Demangan Barat No. 21',   8,  '081234500104', 'Sarjana',       'Pemilik Warung',  'aktif', 2),
('3401011009790005', 'Bambang Setiawan',      'L', '1979-10-09', 'Jl. Baciro Raya No. 3',       9,  '081234500105', 'SMP/Sederajat', 'Montir',          'aktif', 1),
('3401016701940006', 'Ratna Puspita Dewi',    'P', '1994-06-17', 'Jl. Baciro Selatan No. 17',   9,  '081234500106', 'SMA/Sederajat', 'Penjahit',        'aktif', 2),
('3401021208800007', 'Joko Susilo Hadi',      'L', '1980-12-08', 'Jl. Gatot Subroto No. 33',    10, '081234500107', 'SD/Sederajat',  'Pandai Perak',    'aktif', 1),
('3401020503770008', 'Endang Kusumawati',     'P', '1977-05-03', 'Jl. Kemasan No. 9',           10, '081234500108', 'SMP/Sederajat', 'Pedagang Perak',  'aktif', 1),
('3401022909920009', 'Hendra Gunawan',        'L', '1992-09-29', 'Jl. Patehan No. 14',          11, '081234500109', 'Sarjana',       'Pengusaha Kreatif','aktif', 2),
('3401025504960010', 'Novita Sari Wulandari', 'P', '1996-04-05', 'Jl. Wosegaruran No. 27',      11, '081234500110', 'Diploma',       'Barista',         'aktif', 2),
('3401031801730011', 'Slamet Riyadi',         'L', '1973-08-18', 'Jl. Ngasar Suprapto No. 5',   12, '081234500111', 'SD/Sederajat',  'Tukang Bangunan', 'aktif', 1),
('3401038303890012', 'Yuliana Tri Hapsari',   'P', '1989-08-03', 'Jl. Blunyah Rento No. 19',    12, '081234500112', 'Sarjana',       'Pemilik Restoran','aktif', 1),
('3401034402950013', 'Fajar Nugraha Putra',   'L', '1995-02-04', 'Jl. Gedongkuning No. 11',     13, '081234500113', 'SMA/Sederajat', 'Kurir',           'aktif', 2),
('3401040907880014', 'Lilis Suryani',         'P', '1988-07-09', 'Jl. Warungboto Raya No. 2',   14, '081234500114', 'SMP/Sederajat', 'Penjual Sayur',   'aktif', 2),
('3401046601970015', 'Ari Wibowo Santoso',    'L', '1997-06-06', 'Jl. Ring Road Selatan No. 40',14, '081234500115', 'Sarjana',       'Operator Printing','aktif', 1),
('3401053301860016', 'Muhammad Fauzi Rahman', 'L', '1986-01-03', 'Jl. Giwangan No. 23',         15, '081234500116', 'Diploma',       'Pemilik Bengkel', 'aktif', 1),
('3401052101910017', 'Nur Aini Safitri',      'P', '1991-01-02', 'Jl. Giwangan Kidul No. 7',    15, '081234500117', 'Sarjana',       'Guru Les Privat', 'nonaktif', 2),
('3401027702680018', 'Ki Sutarman Hardjito',  'L', '1968-02-07', 'Jl. Prenggan Raya No. 16',    10, '081234500118', 'Tidak Sekolah', 'Perajin Gerabah', 'aktif', 1);

-- ------------------------------------------------ usaha (24 — data simulasi)
-- kode sektor -> id: A=1 C=3 E=5 F=6 G=7 H=8 I=9 K=11
INSERT INTO usaha (kode_usaha, nama_usaha, responden_id, sektor_id, wilayah_id, alamat_usaha, tahun_mulai, jumlah_pekerja, omzet_tahunan, status_usaha, keterangan, created_by) VALUES
('USE-2026-0001', 'Toko Elektronik Sinar Jaya',        1,  6,  7,  'Jl. Kaliurang KM 4 No. 12',   2010, 6,  1850000000.00, 'aktif', 'Toko ritel elektronik dan sparepart.', 1),
('USE-2026-0002', 'CV Karya Agung Mandiri',            1,  11, 7,  'Jl. Affandi No. 45',          2014, 22, 9400000000.00, 'aktif', 'Jasa konsultasi manajemen dan rekrutmen.', 1),
('USE-2026-0003', 'Warung Makan Bu Sri',               4,  8,  8,  'Jl. Demangan Barat No. 21',   2016, 4,  320000000.00,  'aktif', 'Rumah makan masakan Jawa.', 2),
('USE-2026-0004', 'Studio Fotografi Cahaya',           3,  9,  8,  'Jl. Seturan Raya No. 8',      2019, 5,  410000000.00,  'aktif', 'Jasa fotografi komersial dan event.', 2),
('USE-2026-0005', 'Bengkel Mesin Pratama',             5,  3,  9,  'Jl. Baciro Raya No. 3',       2008, 9,  780000000.00,  'aktif', 'Bengkel bubut dan las industri.', 1),
('USE-2026-0006', 'Butik Jahit Ratna',                 6,  3,  9,  'Jl. Baciro Selatan No. 17',   2020, 7,  260000000.00,  'aktif', 'Konveksi kecil dan jahit pemesanan.', 2),
('USE-2026-0007', 'Perajin Perak Kotagede Jaya',       7,  3,  10, 'Jl. Gatot Subroto No. 33',    1998, 18, 2150000000.00, 'aktif', 'Perajin dan pengecer perak khas Kotagede.', 1),
('USE-2026-0008', 'Galeri Silver Kemasan',             8,  6,  10, 'Jl. Kemasan No. 9',           2005, 5,  690000000.00,  'aktif', 'Toko perak dan suvenir.', 1),
('USE-2026-0009', 'Kriya Digital Nusantara',           9,  9,  11, 'Jl. Patehan No. 14',          2021, 12, 1350000000.00, 'aktif', 'Pengembangan aplikasi dan desain digital.', 2),
('USE-2026-0010', 'Kedai Kopi Purbayan',               10, 8,  11, 'Jl. Wosegaruran No. 27',      2022, 6,  295000000.00,  'baru',  'Kedai kopi dan ruang kerja bersama.', 2),
('USE-2026-0011', 'UD Bangunan Jaya Makmur',           11, 5,  12, 'Jl. Ngasar Suprapto No. 5',   2006, 15, 4200000000.00, 'aktif', 'Kontraktor bangunan kecil dan toko material.', 1),
('USE-2026-0012', 'Resto Gudeg Yu Yuli',               12, 8,  12, 'Jl. Blunyah Rento No. 19',    2012, 10, 540000000.00,  'aktif', 'Restoran gudeg dan katering.', 1),
('USE-2026-0013', 'Ekspedisi Cepat Nusantara',         13, 7,  13, 'Jl. Gedongkuning No. 11',     2023, 8,  480000000.00,  'baru',  'Jasa kurir dan pengiriman barang.', 2),
('USE-2026-0014', 'Sayur Segar Bu Lilis',              14, 6,  14, 'Jl. Warungboto Raya No. 2',   2015, 2,  145000000.00,  'aktif', 'Pedagang sayur keliling dan grosir.', 2),
('USE-2026-0015', 'Digital Printing Wibowo',           15, 3,  14, 'Jl. Ring Road Selatan No. 40',2018, 4,  230000000.00,  'aktif', 'Percetakan digital dan sablon.', 1),
('USE-2026-0016', 'Bengkel Mobil Fauzi',               16, 7,  15, 'Jl. Giwangan No. 23',         2011, 7,  890000000.00,  'aktif', 'Servis dan reparasi kendaraan ringan.', 1),
('USE-2026-0017', 'Bimbingan Belajar Aini Center',     17, 11, 15, 'Jl. Giwangan Kidul No. 7',    2021, 9,  350000000.00,  'tutup', 'Lembaga les privat SD-SMA (berhenti 2025).', 2),
('USE-2026-0018', 'Sanggar Gerabah Ki Sutarman',       18, 3,  10, 'Jl. Prenggan Raya No. 16',    1990, 3,  120000000.00,  'aktif', 'Perajin gerabah dan keramik tradisional.', 1),
('USE-2026-0019', 'Peternakan Ayam Berkah',            5,  1,  9,  'Jl. Baciro Raya No. 3B',      2017, 5,  460000000.00,  'aktif', 'Peternakan ayam petelur skala menengah.', 1),
('USE-2026-0020', 'Penginapan Homestay Demangan',      3,  8,  8,  'Jl. Seturan Kecil No. 4',     2019, 6,  380000000.00,  'aktif', 'Homestay 8 kamar dekat kampus.', 2),
('USE-2026-0021', 'Apotek Sehat Sentosa',              4,  6,  8,  'Jl. Demangan Barat No. 30',   2013, 5,  980000000.00,  'aktif', 'Apotek dan toko alat kesehatan.', 2),
('USE-2026-0022', 'Biro Perjalanan Wisata Yogya Tour', 9,  11, 11, 'Jl. Patehan No. 20',          2016, 11, 1250000000.00, 'aktif', 'Paket wisata dan sewa kendaraan.', 2),
('USE-2026-0023', 'Toko Roti Mekar Sari',              12, 3,  12, 'Jl. Blunyah Rento No. 25',    2009, 8,  415000000.00,  'aktif', 'Pabrik roti dan kue tradisional.', 1),
('USE-2026-0024', 'Layanan Internet RTNet',            16, 9,  15, 'Jl. Giwangan No. 25',         2020, 4,  310000000.00,  'aktif', 'Penyedia jasa internet wilayah Giwangan.', 1);
