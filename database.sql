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
('Baby\'s Breath / Ikat', 'Baby\'s Breath', 30000, 25, 'Baby\'s Breath terdiri dari ratusan bunga kecil yang memberikan tampilan lembut dan elegan. Biasanya digunakan sebagai filler flower dalam buket, namun kini juga populer dijadikan buket tunggal karena tampilannya yang minimalis.', 'flower_6a4b21419f2124.59626749.jpg'),
('Anyelir / Tangkai', 'Anyelir', 20000, 19, 'Anyelir mempunyai kelopak bergelombang yang indah serta daya tahan yang cukup lama. Bunga ini sering digunakan sebagai simbol penghargaan, rasa hormat, dan kasih sayang sehingga cocok dijadikan hadiah untuk orang tua maupun guru.', 'flower_6a4b20d6ac0660.16231254.jpg'),
('Peony / Tangkai', 'Peony', 120000, 15, 'Peony termasuk bunga premium yang memiliki kelopak sangat lebat dengan ukuran besar. Bunganya memberikan kesan romantis dan elegan sehingga banyak digunakan dalam dekorasi pernikahan kelas premium.', 'flower_6a4b202a99f1f2.57257856.jpg'),
('Garbera / Tangkai', 'Garbera', 22000, 20, 'Gerbera memiliki warna-warna cerah dengan bentuk menyerupai matahari. Bunga ini memberikan kesan ceria, optimis, dan penuh semangat. Sangat cocok untuk dekorasi meja, buket ulang tahun, maupun hadiah persahabatan.', 'flower_6a4b22c3e0a2a9.09332865.jpg'),
('Freesia / Tangkai', 'Freesia', 40000, 9, 'Freesia memiliki bunga berbentuk corong kecil dengan aroma yang harum. Sering digunakan sebagai bunga potong untuk hadiah maupun dekorasi meja karena tampilannya yang anggun.', 'flower_6a4b2245335137.50611939.jpg'),
('Lily Kuning / Tangkai', 'Lily', 50000, 17, 'Lily Kuning melambangkan kebahagiaan, persahabatan, dan semangat positif. Memiliki bunga berukuran besar dengan aroma harum yang lembut, sehingga cocok dijadikan hadiah, buket ucapan selamat, maupun dekorasi berbagai acara. Dengan perawatan yang baik, Lily Kuning dapat tetap segar selama 7–10 hari.', 'flower_6a4b229c5f9af7.33109115.jpg'),
('Lavender / Ikat', 'Lavender', 35000, 24, 'Lavender terkenal dengan aroma khas yang memberikan efek relaksasi. Selain digunakan sebagai bunga hias, lavender juga sering dijadikan pengharum ruangan alami maupun hadiah untuk orang yang menyukai tanaman aromatik.', 'flower_6a4b231ee00bb0.13959692.jpg'),
('Hydrangea / Tangkai', 'Hydrangea', 80000, 12, 'Hydrangea memiliki kumpulan bunga kecil yang membentuk bola besar. Bunganya terlihat sangat mewah sehingga banyak digunakan sebagai bunga utama dalam dekorasi pesta, hotel, maupun buket premium.', 'flower_6a4b237818ba55.71489858.jpg'),
('Krisan / Tangkai', 'Krisan', 18000, 27, 'Krisan merupakan bunga yang terkenal tahan lama setelah dipotong. Bunga ini banyak digunakan untuk dekorasi rumah, kantor, hotel, hingga acara pernikahan karena bentuknya yang rapi dan mudah dipadukan dengan bunga lainnya.', 'flower_6a4b23a44e0205.67086094.jpg'),
('Mawar Merah Premium / Tangkai', 'Mawar', 25000, 45, 'Mawar merah premium pilihan dengan kelopak tebal dan aroma harum romantis.', 'flower_mawar_merah_premium.jpg'),
('Mawar Putih Klasik / Tangkai', 'Mawar', 25000, 30, 'Mawar putih bersih melambangkan ketulusan dan cinta sejati.', 'flower_mawar_putih_klasik.jpg'),
('Mawar Merah Muda / Tangkai', 'Mawar', 25000, 35, 'Mawar pink manis yang melambangkan kekaguman dan kebahagiaan.', 'flower_mawar_merah_muda.jpg'),
('Mawar Peach Anggun / Tangkai', 'Mawar', 28000, 25, 'Mawar peach eksotis yang melambangkan rasa syukur dan apresiasi.', 'flower_mawar_peach.jpg'),
('Lily Putih Premium / Tangkai', 'Lily', 60000, 15, 'Lily putih premium impor dengan kuntum besar yang elegan dan harum.', 'flower_lily_putih_premium.jpg'),
('Lily Pink Cantik / Tangkai', 'Lily', 65000, 12, 'Lily pink yang cantik memberikan nuansa ceria dan feminin.', 'flower_lily_pink.jpg'),
('Tulip Merah Romantis / Tangkai', 'Tulip', 35000, 20, 'Tulip merah impor melambangkan cinta yang sempurna dan abadi.', 'flower_tulip_merah.jpg'),
('Tulip Putih Suci / Tangkai', 'Tulip', 35000, 18, 'Tulip putih bersih melambangkan permohonan maaf dan ketenangan.', 'flower_tulip_putih.jpg'),
('Tulip Pink Lembut / Tangkai', 'Tulip', 35000, 15, 'Tulip pink yang lembut melambangkan perhatian dan harapan baik.', 'flower_tulip_pink.jpg'),
('Bunga Matahari Ceria / Tangkai', 'Matahari', 30000, 25, 'Bunga matahari cerah yang selalu menghadap matahari melambangkan loyalitas.', 'flower_matahari.png'),
('Anggrek Bulan Ungu / Pot', 'Anggrek', 150000, 10, 'Anggrek bulan premium berwarna ungu keemasan, sangat mewah untuk hiasan.', 'flower_anggrek_ungu.png'),
('Anggrek Bulan Putih / Pot', 'Anggrek', 145000, 8, 'Anggrek bulan putih yang melambangkan keindahan dan kemewahan alami.', 'flower_anggrek_putih.png'),
('Baby\'s Breath Pink / Ikat', 'Baby\'s Breath', 35000, 15, 'Rangkaian baby\'s breath berwarna merah muda yang lembut dan estetik.', 'flower_babys_breath_pink.jpg'),
('Anyelir Merah / Tangkai', 'Anyelir', 20000, 20, 'Anyelir merah cerah melambangkan rasa kekaguman dan kasih sayang.', 'flower_anyelir_merah.jpg'),
('Anyelir Putih / Tangkai', 'Anyelir', 20000, 25, 'Anyelir putih melambangkan cinta murni, keberuntungan, dan kesucian.', 'flower_anyelir_putih.jpg'),
('Gerbera Kuning / Tangkai', 'Garbera', 22000, 30, 'Gerbera kuning yang cerah memancarkan energi kebahagiaan dan kehangatan.', 'flower_gerbera_kuning.jpg'),
('Gerbera Merah / Tangkai', 'Garbera', 22000, 28, 'Gerbera merah membara melambangkan kegembiraan dan semangat yang menyala.', 'flower_gerbera_merah.jpg'),
('Hydrangea Biru / Tangkai', 'Hydrangea', 85000, 14, 'Hydrangea biru yang besar melambangkan rasa terima kasih dan pengertian mendalam.', 'flower_hydrangea_biru.jpg'),
('Krisan Putih / Tangkai', 'Krisan', 18000, 40, 'Bunga krisan putih yang melambangkan kejujuran dan ketulusan hati.', 'flower_krisan_putih.jpg'),
('Krisan Kuning / Tangkai', 'Krisan', 18000, 35, 'Bunga krisan kuning melambangkan cinta yang penuh kegembiraan dan keceriaan.', 'flower_krisan_kuning.jpg');
