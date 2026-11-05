
-- =====================================================
-- DATABASE PROJECT SISASENSE
-- Sistem Manajemen Inventaris dan Stok Produk
-- DBMS: MySQL
-- =====================================================

CREATE DATABASE IF NOT EXISTS sisasense
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE sisasense;

-- =====================================================
-- 1. TABLE USERS
-- =====================================================

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'owner') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 2. TABLE CATEGORIES
-- =====================================================

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 3. TABLE SUPPLIERS
-- =====================================================

CREATE TABLE IF NOT EXISTS suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 4. TABLE PRODUCTS
-- =====================================================

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NULL,
    product_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
    purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    selling_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    minimum_stock INT UNSIGNED NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_products_categories
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_products_suppliers
        FOREIGN KEY (supplier_id)
        REFERENCES suppliers(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- 5. TABLE STOCK_BATCHES
-- =====================================================

CREATE TABLE IF NOT EXISTS stock_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    batch_code VARCHAR(100) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    remaining_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    purchase_date DATE NULL,
    expiry_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_batches_products
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    UNIQUE KEY unique_product_batch (product_id, batch_code),
    INDEX idx_expiry_date (expiry_date)
) ENGINE=InnoDB;

-- =====================================================
-- 6. TABLE INVENTORY_MOVEMENTS
-- =====================================================

CREATE TABLE IF NOT EXISTS inventory_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_movements_products
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_movements_batches
        FOREIGN KEY (batch_id)
        REFERENCES stock_batches(id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_movements_users
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    INDEX idx_movement_date (movement_date),
    INDEX idx_movement_type (movement_type)
) ENGINE=InnoDB;

-- =====================================================
-- 7. TABLE SALES
-- =====================================================

CREATE TABLE IF NOT EXISTS sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    discount DECIMAL(15,2) NOT NULL DEFAULT 0,
    final_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    payment_method ENUM('cash', 'transfer', 'qris', 'other')
        NOT NULL DEFAULT 'cash',
    status ENUM('completed', 'cancelled')
        NOT NULL DEFAULT 'completed',
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_sales_users
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    INDEX idx_sale_date (sale_date)
) ENGINE=InnoDB;

-- =====================================================
-- 8. TABLE SALE_ITEMS
-- =====================================================

CREATE TABLE IF NOT EXISTS sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_sale_items_sales
        FOREIGN KEY (sale_id)
        REFERENCES sales(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_sale_items_products
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_sale_items_batches
        FOREIGN KEY (batch_id)
        REFERENCES stock_batches(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- 9. INDEX TAMBAHAN
-- =====================================================

CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_batches_product ON stock_batches(product_id);
CREATE INDEX idx_inventory_product ON inventory_movements(product_id);
CREATE INDEX idx_sale_items_sale ON sale_items(sale_id);
CREATE INDEX idx_sale_items_product ON sale_items(product_id);

-- =====================================================
-- 10. CONTOH DATA AWAL CATEGORIES
-- =====================================================

INSERT INTO categories (name, description) VALUES
('Makanan', 'Kategori produk makanan'),
('Minuman', 'Kategori produk minuman'),
('Kebutuhan Pokok', 'Kategori kebutuhan pokok'),
('Perawatan', 'Kategori produk perawatan');

-- =====================================================
-- 11. CONTOH DATA AWAL SUPPLIERS
-- =====================================================

INSERT INTO suppliers (name, phone, email, address) VALUES
('Supplier Utama', '081234567890', 'supplier@example.com',
 'Bandar Lampung'),
('Supplier Kedua', '082345678901', 'supplier2@example.com',
 'Lampung Selatan');

-- =====================================================
-- 12. CEK DATABASE DAN TABEL
-- =====================================================

SHOW TABLES;

-- Melihat struktur masing-masing tabel
DESCRIBE users;
DESCRIBE categories;
DESCRIBE suppliers;
DESCRIBE products;
DESCRIBE stock_batches;
DESCRIBE inventory_movements;
DESCRIBE sales;
DESCRIBE sale_items;

-- =====================================================
-- SELESAI MEMBUAT DATABASE SISASENSE
-- =====================================================
