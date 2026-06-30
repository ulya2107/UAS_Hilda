CREATE DATABASE IF NOT EXISTS toko_bunga;
USE toko_bunga;

-- Hapus tabel lama jika ada (agar bersih)
DROP TABLE IF EXISTS pembayaran;
DROP TABLE IF EXISTS order_detail;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS users;

-- Tabel Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Produk
CREATE TABLE produk (
    id_produk INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(150) NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    harga INT NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    deskripsi TEXT,
    gambar VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Orders
CREATE TABLE orders (
    id_order INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_harga INT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    alamat_pengiriman TEXT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Order Detail
CREATE TABLE order_detail (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_order INT NOT NULL,
    id_produk INT NOT NULL,
    qty INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE,
    FOREIGN KEY (id_produk) REFERENCES produk(id_produk) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Pembayaran
CREATE TABLE pembayaran (
    id_bayar INT AUTO_INCREMENT PRIMARY KEY,
    id_order INT NOT NULL,
    metode VARCHAR(50),
    transaction_id VARCHAR(100),
    payment_status VARCHAR(50) DEFAULT 'pending',
    FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tambahkan data awal untuk uji coba
INSERT INTO users (nama, email, password, role) VALUES 
('Administrator', 'admin@toko.com', '$2y$10$WwG1C61tJlyx8k1lXF0K.u2oO3b/Z7L3kly3WfM1.GZ42XbK9Uv2u', 'admin'), -- password: admin
('User Demo', 'user@toko.com', '$2y$10$6RzMpx0HlJgY8B/g.Jsz6OxB8eP3h19k4Qo2XnI74b6q/3h.9LhK.', 'user'); -- password: user

INSERT INTO produk (nama_produk, kategori, harga, stok, deskripsi, gambar) VALUES
('Mawar Merah Segar (Isi 10)', 'Mawar', 150000, 25, 'Bunga mawar merah segar pilihan, harum dan tahan lama.', 'mawar_merah.jpg'),
('Lily Putih Elegan', 'Lily', 200000, 15, 'Bunga lily putih bersih dengan aroma menenangkan.', 'lily_putih.jpg'),
('Tulip Kuning Cantik', 'Tulip', 250000, 10, 'Tulip kuning premium impor langsung dari perkebunan.', 'tulip_kuning.jpg');
