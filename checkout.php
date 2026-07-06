<?php
require_once 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi: Harus login untuk checkout
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = 'checkout.php';
    header("Location: login.php");
    exit;
}

$error_msg = '';

// Proteksi: Keranjang tidak boleh kosong
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit;
}

// Ambil data detail produk di keranjang untuk review
$cart_items = [];
$grand_total = 0;
$ids = array_keys($_SESSION['cart']);
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
            'harga' => $prod['harga'],
            'qty' => $qty,
            'subtotal' => $subtotal,
            'kategori' => $prod['kategori'],
            'deskripsi' => $prod['deskripsi']
        ];
    }
} catch (PDOException $e) {
    $error_msg = "Gagal memuat detail pesanan: " . $e->getMessage();
}

// Proses pembuatan pesanan ketika tombol ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cart_items)) {
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($phone)) {
        $error_msg = "Nomor telepon/kontak harus diisi.";
    } elseif (empty($address)) {
        $error_msg = "Alamat pengiriman harus diisi.";
    } else {
        try {
            // Mulai database transaction agar aman
            $db->beginTransaction();
            
            // 1. Simpan ke tabel orders
            $stmt = $db->prepare("INSERT INTO orders (id_user, total_harga, status, alamat_pengiriman) VALUES (?, ?, 'pending', ?)");
            $stmt->execute([$_SESSION['user_id'], $grand_total, $address]);
            $id_order = $db->lastInsertId();
            
            // 2. Loop & Simpan ke order_detail, serta kurangi stok produk
            $stmt_detail = $db->prepare("INSERT INTO order_detail (id_order, id_produk, qty, subtotal) VALUES (?, ?, ?, ?)");
            $stmt_update_stok = $db->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
            
            foreach ($cart_items as $item) {
                // Double check stok di database
                $stmt_check = $db->prepare("SELECT stok FROM produk WHERE id_produk = ? FOR UPDATE");
                $stmt_check->execute([$item['id_produk']]);
                $current_stok = $stmt_check->fetchColumn();
                
                if ($current_stok < $item['qty']) {
                    throw new Exception("Stok untuk produk '" . $item['nama_produk'] . "' tidak mencukupi.");
                }
                
                // Insert detail
                $stmt_detail->execute([$id_order, $item['id_produk'], $item['qty'], $item['subtotal']]);
                
                // Kurangi stok
                $stmt_update_stok->execute([$item['qty'], $item['id_produk']]);
            }
            
            // 3. Catat di tabel pembayaran awal (status pending)
            $stmt_pay = $db->prepare("INSERT INTO pembayaran (id_order, payment_status) VALUES (?, 'pending')");
            $stmt_pay->execute([$id_order]);
            
            // Simpan nomor telepon dan alamat sementara ke session untuk request Midtrans jika perlu
            $_SESSION['customer_phone'] = $phone;
            $_SESSION['customer_address'] = $address;
            
            // Commit transaction
            $db->commit();
            
            // Kosongkan keranjang belanja
            unset($_SESSION['cart']);
            
            // Redirect ke halaman pembayaran
            header("Location: bayar.php?id=" . $id_order);
            exit;
            
        } catch (Exception $e) {
            // Rollback jika terjadi kegagalan
            $db->rollBack();
            $error_msg = "Gagal memproses pesanan: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Pesanan | Fleurist</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color: var(--canvas);">

    <!-- Header / Navbar -->
    <?php include 'header.php'; ?>

    <main class="admin-container" style="padding-top: 40px; min-height: 80vh;">
        <div class="admin-header" style="margin-bottom: var(--spacing-xl);">
            <div>
                <h1 class="display-md" style="color: var(--primary);">Checkout Pesanan</h1>
                <p class="caption">Selesaikan detail informasi kontak untuk memproses pesanan bunga.</p>
            </div>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger" style="margin-bottom: var(--spacing-xl);"><?= $error_msg ?></div>
        <?php endif; ?>

        <!-- Checkout Form Layout -->
        <form action="" method="POST">
            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: var(--spacing-xl); align-items: start;">
                
                <!-- Kiri: Data Pengiriman / Pelanggan -->
                <div class="admin-card" style="padding: 24px;">
                    <h3 class="heading-sm" style="margin-bottom: var(--spacing-lg); color: var(--primary);">Informasi Pelanggan</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="text-input" value="<?= htmlspecialchars($_SESSION['nama']) ?>" readonly style="background-color: var(--canvas-cream); color: var(--ink-mute); cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Email</label>
                        <input type="email" class="text-input" value="<?= htmlspecialchars($_SESSION['email']) ?>" readonly style="background-color: var(--canvas-cream); color: var(--ink-mute); cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Nomor Telepon / Kontak Whatsapp</label>
                        <input type="text" name="phone" id="phone" class="text-input" placeholder="Contoh: 081234567890" required value="<?= isset($_SESSION['customer_phone']) ? htmlspecialchars($_SESSION['customer_phone']) : '' ?>">
                        <span class="caption">Diperlukan untuk konfirmasi pengiriman bunga oleh kurir kami.</span>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Alamat Pengiriman</label>
                        <textarea name="address" id="address" class="text-input" rows="4" placeholder="Masukkan alamat lengkap tujuan pengiriman (Nama Jalan, RT/RW, Kelurahan, Kecamatan, Kota, Kode Pos)" required style="resize: vertical; min-height: 100px;"><?= isset($_SESSION['customer_address']) ? htmlspecialchars($_SESSION['customer_address']) : '' ?></textarea>
                        <span class="caption">Alamat lengkap pengantaran buket bunga segar.</span>
                    </div>
                </div>

                <!-- Kanan: Review Item Pesanan -->
                <div class="admin-card" style="padding: 24px;">
                    <h3 class="heading-sm" style="margin-bottom: var(--spacing-lg); color: var(--primary);">Review Keranjang</h3>
                    
                    <!-- Item List -->
                    <div style="max-height: 250px; overflow-y: auto; margin-bottom: var(--spacing-lg); border-bottom: 1px solid var(--hairline); padding-bottom: 16px;">
                        <?php foreach ($cart_items as $item): ?>
                            <div style="margin-bottom: 12px; font-size: 14px;">
                                <div style="display: flex; justify-content: space-between; align-items: start; gap: 8px;">
                                    <div>
                                        <span style="font-weight: 700; color: var(--ink);"><?= htmlspecialchars($item['nama_produk']) ?></span>
                                        <span style="color: var(--ink-mute); font-size: 13px; display: block;"><?= $item['qty'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></span>
                                    </div>
                                    <span style="font-weight: 700; color: var(--ink); white-space: nowrap;">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></span>
                                </div>
                                <?php if ($item['kategori'] === 'Custom'): ?>
                                    <div style="font-size: 12px; color: var(--ink-mute); white-space: pre-wrap; margin-top: 4px; line-height: 1.4; text-align: left;"><?= htmlspecialchars($item['deskripsi']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 16px; margin-bottom: var(--spacing-huge);">
                        <span style="font-weight: 700;">Grand Total:</span>
                        <strong style="color: var(--primary); font-size: 20px;">Rp <?= number_format($grand_total, 0, ',', '.') ?></strong>
                    </div>

                    <!-- Action buttons -->
                    <button type="submit" class="btn btn-primary-pill" style="width: 100%; padding: 14px 0; font-size: 16px;">Buat Pesanan & Bayar</button>
                    <a href="cart.php" class="btn btn-secondary-pill" style="width: 100%; padding: 10px 0; font-size: 14px; text-align: center; margin-top: var(--spacing-md); background-color: var(--canvas-cream);">Kembali ke Keranjang</a>
                </div>

            </div>
        </form>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>
