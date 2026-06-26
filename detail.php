<?php
require_once 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_produk = $_GET['id'] ?? null;
$error_msg = '';
$success_msg = '';

if (!$id_produk) {
    header("Location: index.php");
    exit;
}

// Proses form tambah ke keranjang dengan custom qty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty'])) {
    $qty = intval($_POST['qty']);
    
    try {
        $stmt = $db->prepare("SELECT * FROM produk WHERE id_produk = ?");
        $stmt->execute([$id_produk]);
        $prod = $stmt->fetch();
        
        if ($prod) {
            if ($qty <= 0) {
                $error_msg = "Jumlah pesanan harus minimal 1 pcs.";
            } elseif ($prod['stok'] <= 0) {
                $error_msg = "Maaf, stok bunga ini sedang habis.";
            } elseif ($qty > $prod['stok']) {
                $error_msg = "Maaf, Anda memesan melebihi stok yang tersedia (Maks: " . $prod['stok'] . " pcs).";
            } else {
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                // Tambahkan atau perbarui kuantitas di session
                $current_qty = $_SESSION['cart'][$id_produk] ?? 0;
                if (($current_qty + $qty) <= $prod['stok']) {
                    $_SESSION['cart'][$id_produk] = $current_qty + $qty;
                    $success_msg = "Berhasil menambahkan <strong>" . $qty . " pcs " . htmlspecialchars($prod['nama_produk']) . "</strong> ke keranjang belanja.";
                } else {
                    $error_msg = "Keranjang Anda sudah berisi produk ini. Tambahan qty baru melebihi stok yang tersedia.";
                }
            }
        } else {
            $error_msg = "Produk tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $error_msg = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Ambil data detail produk
$product = null;
try {
    $stmt = $db->prepare("SELECT * FROM produk WHERE id_produk = ?");
    $stmt->execute([$id_produk]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    $error_msg = "Gagal memuat detail produk: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['nama_produk']) ?> | Detail Bunga | Fleurist</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color: var(--canvas);">

    <!-- Header / Navbar -->
    <?php include 'header.php'; ?>

    <main class="admin-container" style="padding-top: 40px; min-height: 80vh;">
        <!-- Back Button Link -->
        <div style="margin-bottom: var(--spacing-xl);">
            <a href="index.php" style="font-size: 14px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: var(--ink-mute);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Kembali ke Katalog
            </a>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success" style="margin-bottom: var(--spacing-xl);"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger" style="margin-bottom: var(--spacing-xl);"><?= $error_msg ?></div>
        <?php endif; ?>

        <!-- Product Container -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 48px; align-items: start;">
            
            <!-- Left Column: Product Image -->
            <div class="admin-card" style="padding: 16px; border-radius: var(--rounded-xl);">
                <?php if ($product['gambar'] && file_exists('uploads/' . $product['gambar'])): ?>
                    <img src="uploads/<?= htmlspecialchars($product['gambar']) ?>" alt="<?= htmlspecialchars($product['nama_produk']) ?>" style="width: 100%; height: auto; max-height: 480px; object-fit: cover; border-radius: var(--rounded-lg);">
                <?php else: ?>
                    <div style="width: 100%; height: 350px; background-color: var(--canvas-cream); display: flex; align-items: center; justify-content: center; color: var(--ink-mute); border-radius: var(--rounded-lg);">Gambar Produk Bunga</div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Product Info & Cart Form -->
            <div>
                <!-- Category Eyebrow Tag -->
                <span class="card-product-kategori" style="font-size: 12px; padding: 6px 14px; margin-bottom: var(--spacing-md);"><?= htmlspecialchars($product['kategori']) ?></span>
                
                <h1 class="display-md" style="color: var(--primary); margin-bottom: var(--spacing-sm);"><?= htmlspecialchars($product['nama_produk']) ?></h1>
                
                <p style="font-size: 24px; font-weight: 700; color: var(--primary); margin-bottom: var(--spacing-lg);">
                    Rp <?= number_format($product['harga'], 0, ',', '.') ?>
                </p>

                <!-- Divider -->
                <hr style="border: 0; border-top: 1px solid var(--hairline); margin-bottom: var(--spacing-lg);">

                <!-- Description -->
                <div style="margin-bottom: var(--spacing-huge);">
                    <h3 class="heading-sm" style="margin-bottom: var(--spacing-xs); color: var(--ink);">Deskripsi Produk:</h3>
                    <p class="body-md" style="color: var(--ink-mute); text-align: justify; white-space: pre-wrap;"><?= htmlspecialchars($product['deskripsi'] ?: 'Tidak ada deskripsi untuk bunga ini.') ?></p>
                </div>

                <!-- Form Add to Cart -->
                <div class="admin-card" style="padding: 24px;">
                    <form action="" method="POST">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md);">
                            <span class="caption">Stok Tersedia:</span>
                            <span style="font-weight: 700; font-size: 15px; color: var(--ink);"><?= htmlspecialchars($product['stok']) ?> pcs</span>
                        </div>

                        <?php if ($product['stok'] > 0): ?>
                            <div class="form-group" style="margin-bottom: var(--spacing-lg);">
                                <label for="qty" class="form-label">Tentukan Jumlah Pesanan:</label>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <button type="button" onclick="adjustQty(-1)" class="btn btn-secondary-pill" style="padding: 10px 18px; border-radius: var(--rounded-md); font-size: 18px;">-</button>
                                    <input type="number" name="qty" id="qty" class="text-input" value="1" min="1" max="<?= $product['stok'] ?>" style="text-align: center; font-weight: 700; width: 80px;" readonly>
                                    <button type="button" onclick="adjustQty(1)" class="btn btn-secondary-pill" style="padding: 10px 18px; border-radius: var(--rounded-md); font-size: 18px;">+</button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-pill" style="width: 100%; font-size: 16px; padding: 14px 0;">+ Tambahkan ke Keranjang</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary-pill" style="width: 100%; cursor: not-allowed; color: var(--ink-mute);" disabled>Stok Habis</button>
                        <?php endif; ?>
                    </form>
                </div>

            </div>

        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script>
        function adjustQty(amount) {
            const qtyInput = document.getElementById('qty');
            const maxVal = parseInt(qtyInput.getAttribute('max')) || 1;
            let currentVal = parseInt(qtyInput.value) || 1;
            
            currentVal += amount;
            if (currentVal < 1) currentVal = 1;
            if (currentVal > maxVal) currentVal = maxVal;
            
            qtyInput.value = currentVal;
        }
    </script>

</body>
</html>
