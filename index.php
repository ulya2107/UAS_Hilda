<?php
require_once 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success_msg = '';
$error_msg = '';

// Proses Tambah ke Keranjang Belanja
if (isset($_GET['action']) && $_GET['action'] === 'add_to_cart' && isset($_GET['id'])) {
    $id_prod = intval($_GET['id']);
    
    // Cek apakah produk ada di database dan stoknya tersedia
    try {
        $stmt = $db->prepare("SELECT stok, nama_produk FROM produk WHERE id_produk = ?");
        $stmt->execute([$id_prod]);
        $prod = $stmt->fetch();
        
        if ($prod) {
            if ($prod['stok'] > 0) {
                // Tambah ke session cart
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                // Cek kuantitas yang sudah ada di cart
                $current_qty = $_SESSION['cart'][$id_prod] ?? 0;
                if ($current_qty < $prod['stok']) {
                    $_SESSION['cart'][$id_prod] = $current_qty + 1;
                    $success_msg = "<strong>" . htmlspecialchars($prod['nama_produk']) . "</strong> berhasil ditambahkan ke keranjang belanja.";
                } else {
                    $error_msg = "Maaf, Anda tidak dapat memesan melebihi stok yang tersedia.";
                }
            } else {
                $error_msg = "Maaf, stok bunga ini sedang habis.";
            }
        } else {
            $error_msg = "Produk tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $error_msg = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Ambil Kategori untuk Filter Eyebrow
$categories = [];
try {
    $stmt = $db->query("SELECT DISTINCT kategori FROM produk WHERE stok > 0");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Abaikan error filter
}

// Inisialisasi Filter Pencarian & Kategori
$search = trim($_GET['search'] ?? '');
$kategori_filter = trim($_GET['kategori'] ?? '');

// Ambil Katalog Produk
$products = [];
try {
    $query = "SELECT * FROM produk WHERE stok > 0";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (nama_produk LIKE ? OR deskripsi LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($kategori_filter)) {
        $query .= " AND kategori = ?";
        $params[] = $kategori_filter;
    }
    
    $query .= " ORDER BY id_produk DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Gagal memuat katalog produk: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Bunga Segar Premium | Fleurist</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color: var(--canvas);">

    <!-- Header / Navbar -->
    <?php include 'header.php'; ?>

    <!-- Hero Section dengan Mesh Gradient Slacc -->
    <div class="hero-section">
        <div class="hero-content">
            <span class="micro-cap" style="color: var(--primary); display: block; margin-bottom: var(--spacing-md);">Koleksi Bunga Segar Pilihan</span>
            <h1 class="display-xxl" style="margin-bottom: var(--spacing-lg); color: var(--primary);">Bunga Segar untuk Momen Spesial Anda</h1>
            <p class="body-lg" style="color: var(--ink-mute); margin-bottom: var(--spacing-huge); max-width: 650px; margin-left: auto; margin-right: auto;">
                Koleksi buket bunga premium impor yang dipetik langsung dan dirangkai dengan kasih sayang oleh florist ahli untuk menyampaikan perasaan Anda secara sempurna.
            </p>
            <a href="#katalog" class="btn btn-primary-pill">Lihat Katalog Bunga</a>
        </div>
    </div>

    <!-- Main Content Container -->
    <main class="admin-container" id="katalog" style="padding-top: 60px;">
        
        <!-- Pesan Alert jika Ada -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success" style="margin-bottom: var(--spacing-huge);"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger" style="margin-bottom: var(--spacing-huge);"><?= $error_msg ?></div>
        <?php endif; ?>

        <!-- Filter & Search Bar -->
        <div class="admin-card" style="margin-bottom: 40px; padding: 24px;">
            <form action="index.php" method="GET" style="display: flex; gap: var(--spacing-md); align-items: center; justify-content: space-between; flex-wrap: wrap;">
                
                <!-- Kategori Buttons/Links -->
                <div style="display: flex; gap: var(--spacing-sm); flex-wrap: wrap;">
                    <a href="index.php#katalog" class="btn btn-secondary-pill" style="padding: 8px 18px; font-size: 13px; background-color: <?= empty($kategori_filter) ? 'var(--primary); color: #fff;' : 'var(--canvas-cream); color: var(--ink);' ?>">Semua Bunga</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="index.php?kategori=<?= urlencode($cat) ?>#katalog" class="btn btn-secondary-pill" style="padding: 8px 18px; font-size: 13px; background-color: <?= $kategori_filter === $cat ? 'var(--primary); color: #fff;' : 'var(--canvas-cream); color: var(--ink);' ?>"><?= htmlspecialchars($cat) ?></a>
                    <?php endforeach; ?>
                </div>

                <!-- Input Pencarian -->
                <div style="display: flex; gap: var(--spacing-sm); width: 100%; max-width: 380px;">
                    <input type="text" name="search" class="text-input" placeholder="Cari nama bunga..." value="<?= htmlspecialchars($search) ?>">
                    <?php if (!empty($kategori_filter)): ?>
                        <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_filter) ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary-pill" style="padding: 0 24px; height: 44px; font-size: 14px;">Cari</button>
                </div>
            </form>
        </div>

        <!-- Section Title -->
        <div style="margin-bottom: var(--spacing-lg);">
            <h2 class="heading-lg">
                <?php 
                if (!empty($kategori_filter)) {
                    echo "Kategori Bunga: " . htmlspecialchars($kategori_filter);
                } elseif (!empty($search)) {
                    echo "Hasil Pencarian: \"" . htmlspecialchars($search) . "\"";
                } else {
                    echo "Katalog Bunga Segar";
                }
                ?>
            </h2>
            <p class="caption"><?= count($products) ?> varian bunga siap dipesan</p>
        </div>

        <!-- Product Grid -->
        <div class="product-grid">
            <?php if (empty($products)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 48px; background-color: var(--canvas-cream); border-radius: var(--rounded-lg); color: var(--ink-mute);">
                    <p>Maaf, bunga yang Anda cari sedang tidak tersedia atau habis.</p>
                    <a href="index.php" style="margin-top: 16px; display: inline-block;">Kembali ke Katalog Utama</a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $prod): ?>
                    <div class="card-product">
                        <div>
                            <!-- Product Image -->
                            <?php if ($prod['gambar'] && file_exists('uploads/' . $prod['gambar'])): ?>
                                <img src="uploads/<?= htmlspecialchars($prod['gambar']) ?>" alt="<?= htmlspecialchars($prod['nama_produk']) ?>" class="card-product-img">
                            <?php else: ?>
                                <div class="card-product-img" style="display: flex; align-items: center; justify-content: center; font-size: 14px; color: var(--ink-mute);">Gambar Bunga</div>
                            <?php endif; ?>

                            <!-- Kategori Tag Eyebrow -->
                            <span class="card-product-kategori"><?= htmlspecialchars($prod['kategori']) ?></span>
                            
                            <!-- Title -->
                            <h3 class="card-product-title"><?= htmlspecialchars($prod['nama_produk']) ?></h3>
                            
                            <!-- Price & Stok -->
                            <p class="card-product-price">Rp <?= number_format($prod['harga'], 0, ',', '.') ?></p>
                            <p class="caption" style="margin-bottom: var(--spacing-lg);">Stok tersedia: <strong><?= htmlspecialchars($prod['stok']) ?> pcs</strong></p>
                        </div>
                        
                        <!-- CTA Buttons -->
                        <div style="display: flex; gap: var(--spacing-sm); margin-top: auto;">
                            <a href="detail.php?id=<?= $prod['id_produk'] ?>" class="btn btn-secondary-pill" style="flex: 1; padding: 10px 0; font-size: 13px; text-align: center;">Detail</a>
                            <a href="index.php?action=add_to_cart&id=<?= $prod['id_produk'] ?>#katalog" class="btn btn-primary-pill" style="flex: 1; padding: 10px 0; font-size: 13px; text-align: center; box-shadow: none;">+ Keranjang</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>
