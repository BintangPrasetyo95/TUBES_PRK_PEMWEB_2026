CREATE DATABASE IF NOT EXISTS db_flashpos;
USE db_flashpos;

-- 1. Tabel Users (Untuk Anggota 2: Auth & Session)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Categories (Untuk Anggota 3: Data Master)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL,
    deskripsi TEXT
);

-- 3. Tabel Products (Untuk Anggota 3: Data Master)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    kode_produk VARCHAR(20) NOT NULL UNIQUE,
    nama_produk VARCHAR(100) NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    status ENUM('tersedia', 'habis') DEFAULT 'tersedia',
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- 4. Tabel Orders / Transaksi Utama (Untuk Anggota 4: Kasir)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_invoice VARCHAR(30) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    nama_pelanggan VARCHAR(100) DEFAULT 'Umum',
    nomor_meja VARCHAR(10) DEFAULT '-',
    total_tagihan DECIMAL(12,2) NOT NULL,
    tipe_pembayaran ENUM('full', 'split') DEFAULT 'full',
    status_order ENUM('pending', 'sebagian', 'lunas', 'batal') DEFAULT 'pending',
    tanggal_transaksi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 5. Tabel Order Items / Detail Keranjang (Untuk Anggota 4 & 5)
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL,
    harga_satuan DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    status_bayar_item ENUM('belum', 'lunas') DEFAULT 'belum', -- Penanda untuk Split Bill per item
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 6. Tabel Payments / Bukti Pembayaran & Split Bill (Untuk Anggota 4)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    urutan_split INT DEFAULT 1, -- 1 jika bayar full, 1,2,3 dst jika split bill
    metode_bayar ENUM('tunai', 'qris', 'debit') NOT NULL DEFAULT 'tunai',
    nominal_bayar DECIMAL(12,2) NOT NULL,
    uang_diterima DECIMAL(12,2) NOT NULL,
    kembalian DECIMAL(12,2) NOT NULL DEFAULT 0,
    waktu_bayar DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ==========================================
-- DATA DUMMY AWAL (Agar Dashboard Tidak Kosong)
-- ==========================================

-- Password default: admin123 (menggunakan MD5/Bcrypt nanti diatur Anggota 2, ini contoh teks)
INSERT INTO users (nama_lengkap, username, password, role) VALUES
('Administrator Utama', 'admin', 'admin123', 'admin'),
('Kasir Shift Pagi', 'kasir1', 'kasir123', 'kasir');

INSERT INTO categories (nama_kategori, deskripsi) VALUES
('Kopi & Espresso', 'Minuman berbasis espresso panas dan dingin'),
('Non-Kopi & Teh', 'Minuman segar tanpa kopi'),
('Makanan Utama', 'Hidangan berat pendamping'),
('Snack & Pastry', 'Camilan ringan');

INSERT INTO products (category_id, kode_produk, nama_produk, harga, stok) VALUES
(1, 'KOP-001', 'Iced Americano', 18000, 50),
(1, 'KOP-002', 'Kopi Susu Gula Aren', 22000, 45),
(2, 'NON-001', 'Matcha Latte Ice', 24000, 30),
(3, 'MKN-001', 'Nasi Goreng Spesial', 28000, 25),
(4, 'SNK-001', 'French Fries Platter', 15000, 40);