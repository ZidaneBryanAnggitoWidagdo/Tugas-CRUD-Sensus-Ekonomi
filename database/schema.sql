-- =====================================================================
-- SISENSUS 2026 — Sistem Informasi Sensus Ekonomi
-- Skema Database (MySQL 5.7+/8.x, MariaDB 10.4+) — utf8mb4
-- =====================================================================

CREATE DATABASE IF NOT EXISTS sensus_ekonomi
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sensus_ekonomi;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS usaha;
DROP TABLE IF EXISTS responden;
DROP TABLE IF EXISTS sektor;
DROP TABLE IF EXISTS wilayah;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- users : akun internal sistem (tidak ada registrasi publik)
-- role : admin (penuh) | operator (petugas pendataan) | pimpinan (lihat)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username              VARCHAR(50)  NOT NULL,
  email                 VARCHAR(120) NOT NULL,
  password_hash         VARCHAR(255) NOT NULL,
  full_name             VARCHAR(100) NOT NULL,
  role                  ENUM('admin','operator','pimpinan') NOT NULL DEFAULT 'operator',
  is_active             TINYINT(1)   NOT NULL DEFAULT 1,
  failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
  locked_until          DATETIME     NULL DEFAULT NULL,
  last_login_at         DATETIME     NULL DEFAULT NULL,
  created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- wilayah : hierarki kota -> kecamatan -> kelurahan (self reference)
-- ---------------------------------------------------------------------
CREATE TABLE wilayah (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode      VARCHAR(16)  NOT NULL,
  nama      VARCHAR(100) NOT NULL,
  tingkat   ENUM('kota','kecamatan','kelurahan') NOT NULL,
  parent_id INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_wilayah_kode (kode),
  KEY idx_wilayah_parent (parent_id),
  CONSTRAINT fk_wilayah_parent FOREIGN KEY (parent_id)
    REFERENCES wilayah (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- sektor : penggolongan lapangan usaha (mengikuti klasifikasi KBLI)
-- ---------------------------------------------------------------------
CREATE TABLE sektor (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode      VARCHAR(4)   NOT NULL,
  nama      VARCHAR(120) NOT NULL,
  deskripsi TEXT         NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sektor_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- responden : kepala rumah tangga / pemilik usaha objek pendataan
-- ---------------------------------------------------------------------
CREATE TABLE responden (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nik              VARCHAR(16)  NOT NULL,
  nama_lengkap     VARCHAR(100) NOT NULL,
  jenis_kelamin    ENUM('L','P') NOT NULL,
  tanggal_lahir    DATE         NOT NULL,
  alamat           VARCHAR(255) NOT NULL,
  wilayah_id       INT UNSIGNED NOT NULL,
  telepon          VARCHAR(20)  NULL DEFAULT NULL,
  pendidikan       ENUM('Tidak Sekolah','SD/Sederajat','SMP/Sederajat',
                        'SMA/Sederajat','Diploma','Sarjana','Pascasarjana')
                   NOT NULL DEFAULT 'SMA/Sederajat',
  pekerjaan_utama  VARCHAR(100) NULL DEFAULT NULL,
  status           ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_by       INT UNSIGNED NULL DEFAULT NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_responden_nik (nik),
  KEY idx_responden_nama (nama_lengkap),
  KEY idx_responden_wilayah (wilayah_id),
  KEY idx_responden_creator (created_by),
  CONSTRAINT fk_responden_wilayah FOREIGN KEY (wilayah_id)
    REFERENCES wilayah (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_responden_creator FOREIGN KEY (created_by)
    REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- usaha : unit usaha / perusahaan hasil pendataan sensus ekonomi
-- ---------------------------------------------------------------------
CREATE TABLE usaha (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode_usaha      VARCHAR(20)  NOT NULL,
  nama_usaha      VARCHAR(150) NOT NULL,
  responden_id    INT UNSIGNED NOT NULL,
  sektor_id       INT UNSIGNED NOT NULL,
  wilayah_id      INT UNSIGNED NOT NULL,
  alamat_usaha    VARCHAR(255) NOT NULL,
  tahun_mulai     SMALLINT UNSIGNED NOT NULL,
  jumlah_pekerja  INT UNSIGNED NOT NULL DEFAULT 0,
  omzet_tahunan   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  status_usaha    ENUM('aktif','tutup','baru') NOT NULL DEFAULT 'aktif',
  keterangan      TEXT         NULL,
  created_by      INT UNSIGNED NULL DEFAULT NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usaha_kode (kode_usaha),
  KEY idx_usaha_nama (nama_usaha),
  KEY idx_usaha_responden (responden_id),
  KEY idx_usaha_sektor (sektor_id),
  KEY idx_usaha_wilayah (wilayah_id),
  KEY idx_usaha_omzet (omzet_tahunan),
  KEY idx_usaha_creator (created_by),
  CONSTRAINT fk_usaha_responden FOREIGN KEY (responden_id)
    REFERENCES responden (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_usaha_sektor FOREIGN KEY (sektor_id)
    REFERENCES sektor (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_usaha_wilayah FOREIGN KEY (wilayah_id)
    REFERENCES wilayah (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_usaha_creator FOREIGN KEY (created_by)
    REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_usaha_tahun CHECK (tahun_mulai BETWEEN 1800 AND 2100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_log : jejak aktivitas (jalur audit keamanan & akuntabilitas)
-- ---------------------------------------------------------------------
CREATE TABLE audit_log (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id           INT UNSIGNED NULL DEFAULT NULL,
  username_snapshot VARCHAR(50)  NOT NULL DEFAULT '-',
  aksi              VARCHAR(50)  NOT NULL,
  entitas           VARCHAR(50)  NULL DEFAULT NULL,
  entitas_id        INT UNSIGNED NULL DEFAULT NULL,
  detail            TEXT         NULL,
  ip_address        VARCHAR(45)  NULL DEFAULT NULL,
  user_agent        VARCHAR(255) NULL DEFAULT NULL,
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_user (user_id),
  KEY idx_audit_aksi (aksi),
  KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
