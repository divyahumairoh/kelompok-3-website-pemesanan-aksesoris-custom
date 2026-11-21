-- database/lydi.sql
-- Skema Basis Data MySQL Relasional Murni untuk Website LYDI (Tanpa JSON)

CREATE DATABASE IF NOT EXISTS lydi_db_baru CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lydi_db_baru;

-- 1. Tabel Pengguna (Pelanggan & Admin)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  no_hp VARCHAR(30) DEFAULT NULL,
  role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Jenis Aksesoris
CREATE TABLE IF NOT EXISTS accessories (
  id VARCHAR(40) PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  deskripsi VARCHAR(255) NOT NULL,
  harga_dasar INT NOT NULL,
  maks_manik INT NOT NULL DEFAULT 20
);

-- 3. Tabel Bahan & Warna Tali / Rantai
CREATE TABLE IF NOT EXISTS materials (
  id VARCHAR(40) PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  jenis_bahan VARCHAR(120) NOT NULL,
  warna_hex VARCHAR(16) NOT NULL,
  harga_tambahan INT NOT NULL DEFAULT 0
);

-- 4. Tabel Manik-Manik (Harga Per Butir)
CREATE TABLE IF NOT EXISTS beads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  kategori ENUM('Batu Alam', 'Logam', 'Mutiara & Kaca') NOT NULL DEFAULT 'Batu Alam',
  warna_hex VARCHAR(16) NOT NULL,
  highlight_hex VARCHAR(16) NOT NULL DEFAULT '#FFFFFF',
  finish ENUM('matte', 'glossy', 'metallic', 'textured') NOT NULL DEFAULT 'matte',
  harga_per_butir INT NOT NULL,
  stok INT NOT NULL DEFAULT 100,
  ukuran_mm INT NOT NULL DEFAULT 8
);

-- 5. Tabel Charm Personal
CREATE TABLE IF NOT EXISTS charms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  simbol VARCHAR(40) NOT NULL,
  bahan VARCHAR(100) NOT NULL,
  harga INT NOT NULL,
  stok INT NOT NULL DEFAULT 50
);

-- 6. Tabel Pesanan & Pembayaran
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_invoice VARCHAR(40) NOT NULL UNIQUE,
  user_id INT NULL,
  nama_penerima VARCHAR(120) NOT NULL,
  email_penerima VARCHAR(150) NOT NULL,
  no_hp VARCHAR(30) NOT NULL,
  alamat_lengkap TEXT NOT NULL,
  metode_pembayaran ENUM('qris', 'bca_va', 'mandiri_va', 'gopay', 'cod') NOT NULL,
  status_pembayaran ENUM('Menunggu Pembayaran', 'Dibayar', 'COD Terkonfirmasi') NOT NULL DEFAULT 'Menunggu Pembayaran',
  status_pesanan ENUM('Pending', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Pending',
  subtotal INT NOT NULL,
  ongkir INT NOT NULL DEFAULT 12000,
  total_bayar INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 7. Tabel Detail Item Kustomisasi
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  accessory_id VARCHAR(40) NOT NULL,
  ukuran VARCHAR(40) NOT NULL,
  material_id VARCHAR(40) NOT NULL,
  jumlah_manik INT NOT NULL DEFAULT 0,
  ukiran_nama VARCHAR(30) DEFAULT '',
  inisial VARCHAR(10) DEFAULT '',
  charm_id INT NULL,
  harga_satuan INT NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (charm_id) REFERENCES charms(id) ON DELETE SET NULL
);

-- 8. Tabel Relasional Butir Manik per Pesanan (Tanpa JSON)
CREATE TABLE IF NOT EXISTS order_item_beads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_item_id INT NOT NULL,
  bead_id INT NOT NULL,
  urutan INT NOT NULL DEFAULT 1,
  FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
  FOREIGN KEY (bead_id) REFERENCES beads(id) ON DELETE CASCADE
);

-- Data Awal (Seed Koleksi Vintage Coquette Wanita)
INSERT INTO users (nama, email, password_hash, no_hp, role) VALUES
('Alya Admin LYDI', 'admin@lydi.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081288001122', 'admin'),
('Clarissa Putri', 'pelanggan@lydi.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081377219901', 'customer');
-- Password default akun demo di atas: "password"

INSERT INTO accessories (id, nama, deskripsi, harga_dasar, maks_manik) VALUES
('gelang', 'Gelang Mutiara & Pita', 'Untaian lembut dusty rose quartz, sage jade, & mutiara air tawar bergaya vintage coquette', 35000, 24),
('kalung', 'Kalung Choker Coquette', 'Rantai rose gold antik dan mutiara anggun bernuansa pastel lembut untuk wanita', 55000, 34),
('gantungan_kunci', 'Bag Charm & Keychain', 'Hiasan tas & kunci dengan pita satin pink lembut, mutiara renda, dan liontin personal', 28000, 14),
('cincin', 'Cincin Manik Dainty', 'Cincin manis wanita berhiaskan mutiara mikro pastel & ukiran nama halus', 40000, 12);

INSERT INTO materials (id, nama, jenis_bahan, warna_hex, harga_tambahan) VALUES
('rose_gold_chain', 'Rose Gold 18K', 'Rantai Stainless Rose Gold Antik', '#D1A098', 12000),
('blush_satin_ribbon', 'Pita Satin Pink Lembut', 'Tali Satin Sutra Blush', '#EABEC8', 0),
('ivory_silk_cord', 'Renda Krim Sutra', 'Silk Cord Antique Lace', '#EFE2D8', 3000),
('champagne_lace', 'Champagne Vintage', 'Rantai Champagne Gold Lembut', '#D8C0A2', 8000),
('sage_satin', 'Pita Sage Lembut', 'Satin Pastel Sage Organik', '#BDD0BF', 2000),
('lavender_velvet', 'Lavender Mist Velvet', 'Beludru Pastel Lavender', '#C6BBD3', 4000);

INSERT INTO beads (nama, kategori, warna_hex, highlight_hex, finish, harga_per_butir, stok, ukuran_mm) VALUES
('Ivory Freshwater Pearl', 'Mutiara & Kaca', '#F5EFE9', '#FFFFFF', 'glossy', 6000, 280, 8),
('Dusty Rose Quartz', 'Batu Alam', '#EBBAC5', '#FFF0F3', 'glossy', 5000, 310, 8),
('Pastel Peach Pearl', 'Mutiara & Kaca', '#F5D0C5', '#FFF7F4', 'glossy', 5500, 240, 8),
('Antique Morganite', 'Batu Alam', '#E5BFB8', '#FFF2EE', 'matte', 5500, 200, 8),
('Rose Gold Micro Spacer', 'Logam', '#D1A098', '#FDEAE6', 'metallic', 4500, 350, 6),
('Champagne Lace Bead', 'Logam', '#D8C0A2', '#FFF5E6', 'metallic', 5000, 220, 6),
('Powder Lavender Kunzite', 'Batu Alam', '#D4C2DF', '#FAF4FD', 'glossy', 4500, 210, 8),
('Soft Sage Jade', 'Batu Alam', '#BDD0BF', '#F2F7F2', 'matte', 4500, 230, 8),
('Milky Rose Glass', 'Mutiara & Kaca', '#F3D5DE', '#FFFFFF', 'glossy', 4000, 260, 8);

INSERT INTO charms (nama, simbol, bahan, harga, stok) VALUES
('Pita Satin Bow', 'bow', 'Antique Rose Gold Plated', 18000, 95),
('Cameo Hati Mutiara', 'minimal_heart', 'Rose Gold & Cream Enamel', 16000, 110),
('Kupu-Kupu Vintage', 'butterfly', 'Champagne Gold', 17000, 88),
('Bunga Renda Mawar', 'cherry', 'Rose Gold & Pastel Crystal', 16500, 76),
('Bulan Sabit Pastel', 'crescent', 'Brushed Silver 925', 15000, 90);
