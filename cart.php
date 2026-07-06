<?php
require_once 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_msg = '';
$success_msg = '';

// Proses Hapus Item dari Keranjang
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_del = intval($_GET['id']);
    if (isset($_SESSION['cart'][$id_del])) {
        unset($_SESSION['cart'][$id_del]);
        $success_msg = "Item berhasil dihapus dari keranjang belanja.";
    }
}

// Proses Perbarui Kuantitas Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    $qtys = $_POST['qty'] ?? [];
    
    foreach ($qtys as $id_prod => $qty) {
        $id_prod = intval($id_prod);
        $qty = intval($qty);
        
        if ($qty <= 0) {
            unset($_SESSION['cart'][$id_prod]);
        } else {
            // Cek ketersediaan stok produk
            try {
                $stmt = $db->prepare("SELECT stok, nama_produk FROM produk WHERE id_produk = ?");
                $stmt->execute([$id_prod]);
                $prod = $stmt->fetch();
                
                if ($prod) {
                    if ($qty > $prod['stok']) {
                        $_SESSION['cart'][$id_prod] = $prod['stok'];
                        $error_msg = "Kuantitas pesanan <strong>" . htmlspecialchars($prod['nama_produk']) . "</strong> disesuaikan ke stok maksimal (" . $prod['stok'] . " pcs).";
                    } else {
                        $_SESSION['cart'][$id_prod] = $qty;
                    }
                }
            } catch (PDOException $e) {
                $error_msg = "Gagal memproses perbaruan keranjang.";
            }
        }
    }
    
    if (empty($error_msg)) {
        $success_msg = "Keranjang belanja berhasil diperbarui.";
    }
}

// Ambil data detail untuk produk-produk di keranjang
$cart_items = [];
$grand_total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    // Buat string placeholder (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    try {
        $stmt = $db->prepare("SELECT * FROM produk WHERE id_produk IN ($placeholders)");
        $stmt->execute($ids);
        $products = $stmt->fetchAll();
        
        foreach ($products as $prod) {
            $id = $prod['id_produk'];
            $qty = $_SESSION['cart'][$id];
            $subtotal = $prod['harga'] * $qty;
            $grand_total += $subtotal;
            
            $cart_items[] = [
                'id_produk' => $id,
                'nama_produk' => $prod['nama_produk'],
                'gambar' => $prod['gambar'],
                'harga' => $prod['harga'],
                'stok' => $prod['stok'],
                'qty' => $qty,
                'subtotal' => $subtotal,
                'kategori' => $prod['kategori'],
                'deskripsi' => $prod['deskripsi']
            ];
        }
    } catch (PDOException $e) {
        $error_msg = "Gagal memuat item keranjang: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja | Fleurist</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color: var(--canvas);">

    <!-- Header / Navbar -->
    <?php include 'header.php'; ?>

    <main class="admin-container" style="padding-top: 40px; min-height: 80vh;">
        <div class="admin-header" style="margin-bottom: var(--spacing-xl);">
            <div>
                <h1 class="display-md" style="color: var(--primary);">Keranjang Belanja Anda</h1>
                <p class="caption">Kelola item pilihan Anda sebelum lanjut ke pembayaran.</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success" style="margin-bottom: var(--spacing-xl);"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger" style="margin-bottom: var(--spacing-xl);"><?= $error_msg ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <!-- Cart Empty State -->
            <div class="admin-card" style="text-align: center; padding: 64px 32px;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--ink-mute)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: var(--spacing-lg);">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <h2 class="heading-lg" style="margin-bottom: var(--spacing-sm);">Keranjang Belanja Kosong</h2>
                <p class="body-md" style="color: var(--ink-mute); margin-bottom: var(--spacing-huge); max-width: 450px; margin-left: auto; margin-right: auto;">
                    Anda belum memasukkan bunga segar ke keranjang. Cari dan temukan buket bunga kesayangan Anda sekarang.
                </p>
                <a href="index.php" class="btn btn-primary-pill">Belanja Sekarang</a>
            </div>
        <?php else: ?>
            <!-- Cart Layout -->
            <form action="cart.php" method="POST">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-xl); align-items: start;">
                    <!-- Left: Table of Items -->
                    <div class="admin-card" style="padding: 24px;">
                        <div class="admin-table-container" style="box-shadow: none; border-radius: 0; border: none;">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Gambar</th>
                                        <th>Bunga</th>
                                        <th>Harga Satuan</th>
                                        <th>Jumlah</th>
                                        <th>Total</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td>
                                                <?php if ($item['gambar'] && file_exists('uploads/' . $item['gambar'])): ?>
                                                    <img src="uploads/<?= htmlspecialchars($item['gambar']) ?>" alt="flower" style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--rounded-sm);">
                                                <?php else: ?>
                                                    <div style="width: 50px; height: 50px; background-color: var(--canvas-cream); display: flex; align-items: center; justify-content: center; font-size: 8px; color: var(--ink-mute);">No Img</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($item['nama_produk']) ?></strong>
                                                <?php if ($item['kategori'] === 'Custom'): ?>
                                                    <div style="font-size: 12px; color: var(--ink-mute); white-space: pre-wrap; margin-top: 4px; line-height: 1.4; text-align: left;"><?= htmlspecialchars($item['deskripsi']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                            <td>
                                                <input type="number" name="qty[<?= $item['id_produk'] ?>]" value="<?= $item['qty'] ?>" min="1" max="<?= $item['stok'] ?>" class="text-input" style="width: 70px; text-align: center; padding: 6px;">
                                            </td>
                                            <td><strong>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></strong></td>
                                            <td>
                                                <a href="cart.php?action=delete&id=<?= $item['id_produk'] ?>" style="color: var(--semantic-error); font-size: 13px; font-weight: 600;" onclick="return confirm('Hapus item ini dari keranjang?')">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Update Button -->
                        <div style="text-align: right; margin-top: var(--spacing-xl);">
                            <button type="submit" name="update_cart" class="btn btn-secondary-pill" style="padding: 10px 24px; font-size: 14px;">Perbarui Keranjang</button>
                        </div>
                    </div>

                    <!-- Right: Cart Summary / Checkout Card -->
                    <div class="admin-card" style="padding: 24px;">
                        <h3 class="heading-sm" style="margin-bottom: var(--spacing-lg); color: var(--primary);">Ringkasan Belanja</h3>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: var(--spacing-sm);">
                            <span style="color: var(--ink-mute);">Total Item:</span>
                            <strong>
                                <?php 
                                    $qty_total = 0;
                                    foreach ($cart_items as $item) $qty_total += $item['qty'];
                                    echo $qty_total . ' pcs';
                                ?>
                            </strong>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: var(--spacing-lg);">
                            <span style="color: var(--ink-mute);">Subtotal Belanja:</span>
                            <strong>Rp <?= number_format($grand_total, 0, ',', '.') ?></strong>
                        </div>

                        <hr style="border: 0; border-top: 1px solid var(--hairline); margin-bottom: var(--spacing-lg);">

                        <div style="display: flex; justify-content: space-between; font-size: 16px; margin-bottom: var(--spacing-huge);">
                            <span style="font-weight: 700;">Grand Total:</span>
                            <strong style="color: var(--primary); font-size: 18px;">Rp <?= number_format($grand_total, 0, ',', '.') ?></strong>
                        </div>

                        <!-- Action buttons -->
                        <a href="checkout.php" class="btn btn-primary-pill" style="width: 100%; padding: 14px 0; font-size: 16px; text-align: center; margin-bottom: var(--spacing-md);">Lanjut ke Checkout</a>
                        <a href="index.php" class="btn btn-secondary-pill" style="width: 100%; padding: 10px 0; font-size: 14px; text-align: center; background-color: var(--canvas-cream);">Lanjut Belanja</a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>
