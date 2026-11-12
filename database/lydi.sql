-- =====================================================
--  LYDI ACCESSORIES - Database lengkap
--  Website pemesanan aksesori custom (gelang, ganci, kalung, cincin)
--  Import lewat phpMyAdmin: tab "Import" (tanpa memilih database dulu)
-- =====================================================

CREATE DATABASE IF NOT EXISTS lydi_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE lydi_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS riwayat_status;
DROP TABLE IF EXISTS detail_pesanan;
DROP TABLE IF EXISTS pesanan;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS produk_gambar;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS opsi_warna;
DROP TABLE IF EXISTS opsi_hiasan;
DROP TABLE IF EXISTS pengaturan;
DROP TABLE IF EXISTS alamat;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------
-- 1. USERS (pelanggan + admin)
-- -----------------------------------------------------
CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(100) NOT NULL,
  email       VARCHAR(100) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,              -- hasil password_hash()
  role        ENUM('pelanggan','admin') NOT NULL DEFAULT 'pelanggan',
  telepon     VARCHAR(20),
  foto        VARCHAR(255),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 2. ALAMAT (satu user bisa punya banyak alamat)
--    dipakai di menu "Alamat" profil dan "Informasi Pengiriman" checkout
-- -----------------------------------------------------
CREATE TABLE alamat (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  label           VARCHAR(30) DEFAULT 'Rumah',
  nama_penerima   VARCHAR(100) NOT NULL,
  telepon         VARCHAR(20) NOT NULL,
  alamat_lengkap  TEXT NOT NULL,
  kota            VARCHAR(60),
  kode_pos        VARCHAR(10),
  utama           TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 3. KATEGORI
-- -----------------------------------------------------
CREATE TABLE kategori (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  nama    VARCHAR(50) NOT NULL,
  slug    VARCHAR(50) NOT NULL UNIQUE,
  gambar  VARCHAR(255)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 4. PRODUK
-- -----------------------------------------------------
CREATE TABLE produk (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  kategori_id  INT NOT NULL,
  nama         VARCHAR(100) NOT NULL,
  deskripsi    TEXT,
  harga        INT NOT NULL,                      -- harga dasar (sebelum kustomisasi)
  stok         INT NOT NULL DEFAULT 0,
  gambar       VARCHAR(255),                      -- gambar utama (kartu katalog)
  bisa_custom  TINYINT(1) NOT NULL DEFAULT 1,     -- 1 = tombol "Custom Sekarang" aktif
  aktif        TINYINT(1) NOT NULL DEFAULT 1,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kategori_id) REFERENCES kategori(id),
  INDEX idx_produk_kategori (kategori_id)
) ENGINE=InnoDB;

-- Galeri foto di halaman Detail Produk (thumbnail di sisi kiri)
CREATE TABLE produk_gambar (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  produk_id  INT NOT NULL,
  file       VARCHAR(255) NOT NULL,
  urutan     INT NOT NULL DEFAULT 0,
  FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 5. OPSI KUSTOMISASI
--    Warna, hiasan (charm), dan tulisan (huruf) diatur di sini,
--    jadi admin bisa menambah opsi tanpa mengubah kode.
-- -----------------------------------------------------
CREATE TABLE opsi_warna (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nama            VARCHAR(50) NOT NULL,
  kode_hex        VARCHAR(7) NOT NULL,            -- contoh: #F8A5C2 (untuk swatch & preview)
  harga_tambahan  INT NOT NULL DEFAULT 0,
  aktif           TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE opsi_hiasan (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nama            VARCHAR(50) NOT NULL,
  ikon            VARCHAR(255),                   -- file gambar charm di assets/img/hiasan/
  harga_tambahan  INT NOT NULL DEFAULT 0,
  aktif           TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Pengaturan umum (menu "Pengaturan" admin)
CREATE TABLE pengaturan (
  kunci       VARCHAR(50) PRIMARY KEY,
  nilai       VARCHAR(255) NOT NULL,
  keterangan  VARCHAR(150)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 6. WISHLIST (ikon hati di kartu produk)
-- -----------------------------------------------------
CREATE TABLE wishlist (
  user_id    INT NOT NULL,
  produk_id  INT NOT NULL,
  PRIMARY KEY (user_id, produk_id),
  FOREIGN KEY (user_id)   REFERENCES users(id)  ON DELETE CASCADE,
  FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 7. PESANAN
-- -----------------------------------------------------
CREATE TABLE pesanan (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  kode_pesanan   VARCHAR(20) NOT NULL UNIQUE,     -- contoh: LYD001
  user_id        INT NOT NULL,
  nama_penerima  VARCHAR(100) NOT NULL,
  telepon        VARCHAR(20) NOT NULL,
  alamat_kirim   TEXT NOT NULL,                   -- disalin saat checkout (alamat user bisa berubah)
  metode_bayar   ENUM('transfer','ewallet','qris') NOT NULL,
  total          INT NOT NULL,
  status         ENUM('diterima','dibayar','dibuat','dikemas','dikirim','selesai','dibatalkan')
                 NOT NULL DEFAULT 'diterima',
  catatan        TEXT,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_pesanan_user (user_id),
  INDEX idx_pesanan_status (status)
) ENGINE=InnoDB;

-- Satu baris = satu item di keranjang, lengkap dengan pilihan kustomisasinya
CREATE TABLE detail_pesanan (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  pesanan_id    INT NOT NULL,
  produk_id     INT NOT NULL,
  nama_produk   VARCHAR(100) NOT NULL,            -- salinan nama saat dipesan
  jumlah        INT NOT NULL DEFAULT 1,
  harga_satuan  INT NOT NULL,                     -- harga dasar + warna + hiasan + tulisan
  warna         VARCHAR(50),                      -- contoh: Pink
  hiasan        VARCHAR(50),                      -- contoh: Bintang
  tulisan       VARCHAR(10),                      -- maksimal 10 karakter, contoh: DIVYA
  subtotal      INT NOT NULL,                     -- harga_satuan x jumlah
  FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
  FOREIGN KEY (produk_id)  REFERENCES produk(id)
) ENGINE=InnoDB;

-- Timeline di halaman "Lacak Pesanan"
CREATE TABLE riwayat_status (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  pesanan_id  INT NOT NULL,
  status      VARCHAR(30) NOT NULL,
  keterangan  VARCHAR(150),
  waktu       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
  INDEX idx_riwayat_pesanan (pesanan_id)
) ENGINE=InnoDB;

-- =====================================================
--  DATA AWAL (disesuaikan dengan mockup UI)
-- =====================================================

-- Akun contoh. Password: admin123 / divya123 / nana123 / rara123
-- (ganti sebelum dikumpulkan ke dosen)
INSERT INTO users (id, nama, email, password, role, telepon) VALUES
(1, 'Admin Lydi',      'admin@lydi.com',  '$2y$10$xd4Ae/hx9Na4AeWVfursnevxgB5SV4Al3PhG67JsJtnSBB2vf3rpa', 'admin',     NULL),
(2, 'Divya Salsabila', 'divya@email.com', '$2y$10$Na0GYg6YwUR9hQ/QeboB9OO3sqY0wRoM/PA3c5R9mRvrCgMDD7qcq', 'pelanggan', '0812-3456-7890'),
(3, 'Nana',            'nana@email.com',  '$2y$10$bU.PUomgk/0Hy0PaItHkrecvfsfLFdlx5nU7bzYR4gL/6LIvHVbbS', 'pelanggan', '0812-0000-0002'),
(4, 'Rara',            'rara@email.com',  '$2y$10$eTfqce07HmIf3yzZKdXlF.ghk2ZZos2P8fwF3EW.Zr7/45bbDnrce', 'pelanggan', '0812-0000-0003');

INSERT INTO alamat (user_id, label, nama_penerima, telepon, alamat_lengkap, kota, utama) VALUES
(2, 'Rumah', 'Divya Salsabila', '0812-3456-7890', 'Jl. Melati No.12', 'Jakarta', 1);

INSERT INTO kategori (id, nama, slug) VALUES
(1, 'Gelang', 'gelang'),
(2, 'Gantungan Kunci', 'gantungan-kunci'),
(3, 'Kalung', 'kalung'),
(4, 'Cincin', 'cincin');

INSERT INTO produk (id, kategori_id, nama, deskripsi, harga, stok, gambar, bisa_custom) VALUES
(1, 1, 'Gelang Beads', 'Gelang manik-manik lucu dengan bahan berkualitas, cocok untuk segala suasana!', 15000, 50, 'gelang-beads.jpg', 1),
(2, 1, 'Gelang Charm', 'Gelang dengan charm yang bisa kamu pilih sendiri.',                              18000, 40, 'gelang-charm.jpg', 1),
(3, 2, 'Ganci Cute',   'Gantungan kunci imut untuk tas dan kunci.',                                      12000, 80, 'ganci-cute.jpg',   1),
(4, 2, 'Ganci Bear',   'Gantungan kunci beruang lucu.',                                                  14000, 80, 'ganci-bear.jpg',   1),
(5, 3, 'Kalung Star',  'Kalung dengan liontin bintang.',                                                 25000, 30, 'kalung-star.jpg',  1),
(6, 4, 'Cincin Bunga', 'Cincin manik dengan hiasan bunga.',                                              10000, 60, 'cincin-bunga.jpg', 1);

INSERT INTO produk_gambar (produk_id, file, urutan) VALUES
(1, 'gelang-beads-1.jpg', 1),
(1, 'gelang-beads-2.jpg', 2),
(1, 'gelang-beads-3.jpg', 3),
(1, 'gelang-beads-4.jpg', 4);

-- 5 warna (urutan sama dengan mockup: pink, hijau, biru, ungu, pink muda)
INSERT INTO opsi_warna (nama, kode_hex, harga_tambahan) VALUES
('Pink',      '#F8A5C2', 0),
('Hijau',     '#A5D6A7', 0),
('Biru',      '#90CAF9', 0),
('Ungu',      '#B39DDB', 0),
('Pink Muda', '#F8D7E8', 0);

-- 4 hiasan (bintang, hati, pita, bunga)
INSERT INTO opsi_hiasan (nama, ikon, harga_tambahan) VALUES
('Bintang', 'bintang.png', 0),
('Hati',    'hati.png',    0),
('Pita',    'pita.png',    2000),
('Bunga',   'bunga.png',   2000);

-- Harga satuan = harga produk + warna + hiasan + (jumlah huruf x harga_per_huruf)
-- Contoh mockup: Gelang Beads 15.000 + tulisan "DIVYA" (5 x 1.000) = Rp 20.000
INSERT INTO pengaturan (kunci, nilai, keterangan) VALUES
('nama_toko',       'Lydi',  'Nama toko'),
('harga_per_huruf', '1000',  'Biaya tambahan per huruf pada tulisan custom'),
('maks_huruf',      '10',    'Batas jumlah huruf pada tulisan custom');

-- -----------------------------------------------------
-- 3 pesanan contoh (sama dengan halaman Lacak, Riwayat, dan Kelola Pesanan)
-- -----------------------------------------------------
INSERT INTO pesanan (id, kode_pesanan, user_id, nama_penerima, telepon, alamat_kirim, metode_bayar, total, status, created_at) VALUES
(1, 'LYD999', 4, 'Rara',            '0812-0000-0003', 'Jl. Contoh No.3, Padang',  'qris',     30000, 'selesai', '2025-04-20 10:00:00'),
(2, 'LYD000', 3, 'Nana',            '0812-0000-0002', 'Jl. Contoh No.2, Padang',  'ewallet',  28000, 'dikirim', '2025-05-05 08:00:00'),
(3, 'LYD001', 2, 'Divya Salsabila', '0812-3456-7890', 'Jl. Melati No.12, Jakarta', 'transfer', 34000, 'dibuat',  '2025-05-12 09:00:00');

INSERT INTO detail_pesanan (pesanan_id, produk_id, nama_produk, jumlah, harga_satuan, warna, hiasan, tulisan, subtotal) VALUES
(1, 3, 'Ganci Cute',   1, 12000, 'Hijau', NULL,      NULL,    12000),
(1, 2, 'Gelang Charm', 1, 18000, 'Ungu',  'Bintang', NULL,    18000),
(2, 2, 'Gelang Charm', 1, 18000, 'Biru',  'Hati',    NULL,    18000),
(2, 6, 'Cincin Bunga', 1, 10000, 'Pink Muda', NULL,  NULL,    10000),
(3, 1, 'Gelang Beads', 1, 20000, 'Pink',  NULL,      'DIVYA', 20000),
(3, 4, 'Ganci Bear',   1, 14000, 'Biru',  'Bintang', NULL,    14000);

-- Timeline di halaman Lacak Pesanan
INSERT INTO riwayat_status (pesanan_id, status, keterangan, waktu) VALUES
(1, 'diterima', 'Pesanan diterima',        '2025-04-20 10:00:00'),
(1, 'dibayar',  'Pembayaran dikonfirmasi', '2025-04-20 10:20:00'),
(1, 'dibuat',   'Sedang dibuat',           '2025-04-20 11:00:00'),
(1, 'dikemas',  'Dikemas',                 '2025-04-21 09:00:00'),
(1, 'dikirim',  'Dikirim',                 '2025-04-21 14:00:00'),
(1, 'selesai',  'Selesai',                 '2025-04-24 12:00:00'),
(2, 'diterima', 'Pesanan diterima',        '2025-05-05 08:00:00'),
(2, 'dibayar',  'Pembayaran dikonfirmasi', '2025-05-05 08:30:00'),
(2, 'dibuat',   'Sedang dibuat',           '2025-05-05 09:00:00'),
(2, 'dikemas',  'Dikemas',                 '2025-05-05 13:00:00'),
(2, 'dikirim',  'Dikirim',                 '2025-05-05 16:00:00'),
(3, 'diterima', 'Pesanan diterima',        '2025-05-12 09:00:00'),
(3, 'dibayar',  'Pembayaran dikonfirmasi', '2025-05-12 09:30:00'),
(3, 'dibuat',   'Sedang dibuat',           '2025-05-12 10:00:00');