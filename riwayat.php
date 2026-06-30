<?php
require_once 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi: Harus login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error_msg = '';
$success_msg = '';

// Tangkap alert sukses pembayaran dari callback
if (isset($_SESSION['payment_success_msg'])) {
    $success_msg = $_SESSION['payment_success_msg'];
    unset($_SESSION['payment_success_msg']);
}

$orders_history = [];

try {
    // Ambil semua pesanan milik user saat ini
    $stmt = $db->prepare("SELECT * FROM orders WHERE id_user = ? ORDER BY tanggal DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $user_orders = $stmt->fetchAll();

    // Loop untuk mengambil item detail di setiap order
    foreach ($user_orders as $order) {
        $stmt_items = $db->prepare("SELECT order_detail.*, produk.nama_produk, produk.gambar 
                                    FROM order_detail 
                                    JOIN produk ON order_detail.id_produk = produk.id_produk 
                                    WHERE order_detail.id_order = ?");
        $stmt_items->execute([$order['id_order']]);
        $items = $stmt_items->fetchAll();

        // Ambil info pembayaran jika ada
        $stmt_pay = $db->prepare("SELECT * FROM pembayaran WHERE id_order = ?");
        $stmt_pay->execute([$order['id_order']]);
        $pay_data = $stmt_pay->fetch();

        $orders_history[] = [
            'order' => $order,
            'items' => $items,
            'payment' => $pay_data
        ];
    }
} catch (PDOException $e) {
    $error_msg = "Gagal memuat riwayat belanja: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Belanja | Fleurist</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color: var(--canvas-cream);">

    <!-- Header / Navbar -->
    <?php include 'header.php'; ?>

    <main class="admin-container" style="padding-top: 40px; min-height: 80vh;">
        <div class="admin-header" style="margin-bottom: var(--spacing-xl);">
            <div>
                <h1 class="display-md" style="color: var(--primary);">Riwayat Belanja Anda</h1>
                <p class="caption">Daftar transaksi dan pengiriman buket bunga Anda.</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <?php if (empty($orders_history)): ?>
            <!-- Empty History State -->
            <div class="admin-card" style="text-align: center; padding: 64px 32px;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--ink-mute)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: var(--spacing-lg);">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <h2 class="heading-lg" style="margin-bottom: var(--spacing-sm);">Belum Ada Riwayat Belanja</h2>
                <p class="body-md" style="color: var(--ink-mute); margin-bottom: var(--spacing-huge); max-width: 450px; margin-left: auto; margin-right: auto;">
                    Anda belum pernah melakukan checkout bunga segar di toko kami. Ayo ke katalog bunga kami.
                </p>
                <a href="index.php" class="btn btn-primary-pill">Belanja Sekarang</a>
            </div>
        <?php else: ?>
            <!-- Order History Cards List -->
            <div style="display: flex; flex-direction: column; gap: var(--spacing-xl);">
                <?php foreach ($orders_history as $oh): 
                    $order = $oh['order'];
                    $items = $oh['items'];
                    $payment = $oh['payment'];
                ?>
                    <div class="admin-card" style="margin-bottom: 0; padding: 24px;">
                        <!-- Top Order Summary Bar -->
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spacing-md); border-bottom: 1px solid var(--hairline); padding-bottom: 16px; margin-bottom: 16px;">
                            <div>
                                <span class="caption" style="display: block;">ID Transaksi:</span>
                                <strong style="color: var(--primary); font-size: 16px;">#<?= htmlspecialchars($order['id_order']) ?></strong>
                            </div>
                            <div>
                                <span class="caption" style="display: block;">Tanggal Pembelian:</span>
                                <span style="font-weight: 600; font-size: 14px;"><?= date('d M Y, H:i', strtotime($order['tanggal'])) ?></span>
                            </div>
                            <div>
                                <span class="caption" style="display: block;">Total Tagihan:</span>
                                <span style="font-weight: 700; color: var(--primary); font-size: 16px;">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></span>
                            </div>
                            <div>
                                <span class="caption" style="display: block; margin-bottom: 4px;">Status Pesanan:</span>
                                <span class="badge badge-<?= htmlspecialchars($order['status']) ?>">
                                    <?= htmlspecialchars($order['status']) ?>
                                </span>
                            </div>
                            <div>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <a href="bayar.php?id=<?= $order['id_order'] ?>" class="btn btn-primary-pill" style="padding: 8px 20px; font-size: 13px; box-shadow: none;">Bayar Sekarang</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary-pill" style="padding: 8px 20px; font-size: 13px; cursor: default;" disabled>&check; Lunas</button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Product List in this Order -->
                        <div>
                            <h4 style="font-size: 14px; font-weight: 700; color: var(--ink); margin-bottom: var(--spacing-sm);">Rincian Item Bunga:</h4>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <?php foreach ($items as $item): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; background-color: var(--canvas-cream); padding: 8px 16px; border-radius: var(--rounded-sm); font-size: 14px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php if ($item['gambar'] && file_exists('uploads/' . $item['gambar'])): ?>
                                                <img src="uploads/<?= htmlspecialchars($item['gambar']) ?>" alt="flower" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px;">
                                            <?php endif; ?>
                                            <span><strong><?= htmlspecialchars($item['nama_produk']) ?></strong></span>
                                        </div>
                                        <span style="color: var(--ink-mute);"><?= $item['qty'] ?> pcs x Rp <?= number_format($item['subtotal'] / $item['qty'], 0, ',', '.') ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <?php if (!empty($order['alamat_pengiriman'])): ?>
                            <div style="margin-top: 16px; padding: 12px 16px; background-color: var(--canvas-lavender); border-radius: var(--rounded-sm); font-size: 14px; text-align: left; border-left: 3px solid var(--primary);">
                                <span style="color: var(--primary); font-weight: 700; display: block; margin-bottom: 4px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Alamat Pengiriman:</span>
                                <span style="color: var(--ink);"><?= htmlspecialchars($order['alamat_pengiriman']) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Payment Method Info if paid -->
                        <?php if ($payment && $payment['payment_status'] === 'settlement'): ?>
                            <div style="margin-top: 16px; padding-top: 12px; border-top: 1px dotted var(--hairline); font-size: 13px; color: var(--ink-mute); display: flex; gap: var(--spacing-xl);">
                                <span>Metode: <strong><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $payment['metode']))) ?></strong></span>
                                <span>ID Transaksi Midtrans: <strong><?= htmlspecialchars($payment['transaction_id']) ?></strong></span>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>
