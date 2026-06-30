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

$id_order = $_GET['id'] ?? null;
if (!$id_order) {
    header("Location: index.php");
    exit;
}

$error_msg = '';
$order_data = null;
$order_items = [];

try {
    // Ambil info order dan pastikan milik user saat ini (kecuali admin)
    $query = "SELECT orders.*, users.nama, users.email FROM orders 
              JOIN users ON orders.id_user = users.id 
              WHERE orders.id_order = ?";
    $params = [$id_order];
    
    if ($_SESSION['role'] !== 'admin') {
        $query .= " AND orders.id_user = ?";
        $params[] = $_SESSION['user_id'];
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $order_data = $stmt->fetch();
    
    if (!$order_data) {
        $error_msg = "Akses ditolak atau pesanan tidak ditemukan.";
    } else {
        // Ambil item produk dalam pesanan
        $stmt = $db->prepare("SELECT order_detail.*, produk.nama_produk FROM order_detail 
                            JOIN produk ON order_detail.id_produk = produk.id_produk 
                            WHERE order_detail.id_order = ?");
        $stmt->execute([$id_order]);
        $order_items = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $error_msg = "Kesalahan database: " . $e->getMessage();
}

// Persiapkan format Request JSON untuk Midtrans sesuai spek toko_bunga.txt
$midtrans_json = [];
if ($order_data) {
    $phone = $_SESSION['customer_phone'] ?? '08123456789';
    $midtrans_json = [
        "transaction_details" => [
            "order_id" => "ORDER-" . $order_data['id_order'],
            "gross_amount" => intval($order_data['total_harga'])
        ],
        "customer_details" => [
            "first_name" => $order_data['nama'],
            "email" => $order_data['email'],
            "phone" => $phone
        ]
    ];
    
    if (!empty($order_data['alamat_pengiriman'])) {
        $midtrans_json['customer_details']['billing_address'] = [
            "first_name" => $order_data['nama'],
            "email" => $order_data['email'],
            "phone" => $phone,
            "address" => $order_data['alamat_pengiriman']
        ];
        $midtrans_json['customer_details']['shipping_address'] = [
            "first_name" => $order_data['nama'],
            "email" => $order_data['email'],
            "phone" => $phone,
            "address" => $order_data['alamat_pengiriman']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Pesanan | Fleurist</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .json-box {
            background-color: #1e1e1e;
            color: #d4d4d4;
            padding: 16px;
            border-radius: var(--rounded-md);
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            overflow-x: auto;
            text-align: left;
            margin-top: 12px;
            border: 1px solid #3c3c3c;
        }
    </style>
</head>
<body style="background-color: var(--canvas-cream);">

    <!-- Header / Navbar -->
    <?php include 'header.php'; ?>

    <main class="admin-container" style="padding-top: 40px; min-height: 80vh;">
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><?= $error_msg ?></div>
            <a href="index.php" class="btn btn-primary-pill">Kembali ke Beranda</a>
        <?php else: ?>
            
            <div class="admin-header">
                <div>
                    <h1 class="display-md" style="color: var(--primary);">Gateway Pembayaran</h1>
                    <p class="caption">Integrasi Midtrans API JSON Simulator.</p>
                </div>
                <div>
                    <span class="badge badge-<?= htmlspecialchars($order_data['status']) ?>" style="font-size: 14px; padding: 6px 12px;">
                        Status: <?= htmlspecialchars($order_data['status']) ?>
                    </span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: var(--spacing-xl); align-items: start;">
                <!-- Kiri: Invoice & JSON payload simulator -->
                <div>
                    <!-- Detail Pesanan -->
                    <div class="admin-card">
                        <h3 class="heading-sm" style="color: var(--primary); margin-bottom: var(--spacing-lg);">Ringkasan Invoice #<?= htmlspecialchars($order_data['id_order']) ?></h3>
                        
                        <div style="margin-bottom: var(--spacing-lg);">
                            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                                <tr style="border-bottom: 1px solid var(--hairline);">
                                    <td style="padding: 8px 0; color: var(--ink-mute);">Penerima:</td>
                                    <td style="padding: 8px 0; text-align: right; font-weight: 700;"><?= htmlspecialchars($order_data['nama']) ?></td>
                                </tr>
                                <tr style="border-bottom: 1px solid var(--hairline);">
                                    <td style="padding: 8px 0; color: var(--ink-mute);">Tanggal Pemesanan:</td>
                                    <td style="padding: 8px 0; text-align: right;"><?= date('d M Y, H:i', strtotime($order_data['tanggal'])) ?></td>
                                </tr>
                                <?php if (!empty($order_data['alamat_pengiriman'])): ?>
                                <tr style="border-bottom: 1px solid var(--hairline);">
                                    <td style="padding: 8px 0; color: var(--ink-mute); vertical-align: top;">Alamat Pengiriman:</td>
                                    <td style="padding: 8px 0; text-align: right; font-weight: 500; max-width: 250px; word-wrap: break-word;"><?= htmlspecialchars($order_data['alamat_pengiriman']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: 700;">Total Harus Dibayar:</td>
                                    <td style="padding: 8px 0; text-align: right; font-weight: 700; color: var(--primary); font-size: 16px;">Rp <?= number_format($order_data['total_harga'], 0, ',', '.') ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- List Item -->
                        <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 8px;">Daftar Item:</h4>
                        <ul style="padding-left: 20px; font-size: 14px; color: var(--ink-mute); line-height: 1.6; margin-bottom: 16px;">
                            <?php foreach ($order_items as $item): ?>
                                <li><?= htmlspecialchars($item['nama_produk']) ?> (x<?= $item['qty'] ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Midtrans Request JSON Payload Display -->
                    <div class="admin-card">
                        <h3 class="heading-sm" style="color: var(--primary);">Midtrans JSON Payload</h3>
                        <p class="caption">Format payload JSON terkirim ke server API Midtrans:</p>
                        <div class="json-box">
                            <?= json_encode($midtrans_json, JSON_PRETTY_PRINT) ?>
                        </div>
                    </div>
                </div>

                <!-- Kanan: Simulative Payment Gateway Screen -->
                <div>
                    <div class="admin-card" style="border: 2px solid var(--primary); position: relative;">
                        <!-- Badge Simulator -->
                        <span style="position: absolute; top: -12px; right: 24px; background-color: var(--primary); color: #fff; font-size: 10px; font-weight: 700; padding: 2px 10px; border-radius: var(--rounded-pill); text-transform: uppercase; letter-spacing: 1px;">Simulator</span>

                        <h3 class="heading-sm" style="margin-bottom: var(--spacing-md); color: var(--primary); display: flex; align-items: center; gap: 8px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="var(--primary)" xmlns="http://www.w3.org/2000/svg">
                                <rect x="2" y="5" width="20" height="14" rx="2" />
                                <line x1="2" y1="10" x2="22" y2="10" stroke="#fff" stroke-width="2" />
                            </svg>
                            Metode Pembayaran
                        </h3>
                        <p class="caption" style="margin-bottom: var(--spacing-lg);">Silakan pilih metode pembayaran simulasi:</p>

                        <?php if ($order_data['status'] === 'pending'): ?>
                            <form action="callback.php" method="POST" id="payment-form">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_data['id_order']) ?>">
                                <input type="hidden" name="gross_amount" value="<?= htmlspecialchars($order_data['total_harga']) ?>">
                                
                                <!-- Payment Options -->
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 600;">Metode Pembayaran</label>
                                    <select name="payment_type" class="text-input" style="appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22><path fill=%22%231d1d1d%22 d=%22M7 10l5 5 5-5z%22/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;" required>
                                        <option value="bank_transfer_bca">BCA Virtual Account (Simulasi)</option>
                                        <option value="bank_transfer_mandiri">Mandiri Bill Payment (Simulasi)</option>
                                        <option value="credit_card">Kartu Kredit / Visa / Mastercard (Simulasi)</option>
                                        <option value="gopay">GoPay / E-Wallet (Simulasi)</option>
                                    </select>
                                </div>

                                <div class="form-group" style="margin-top: 24px;">
                                    <button type="submit" class="btn btn-primary-pill" style="width: 100%; padding: 14px 0; font-size: 16px;">
                                        Konfirmasi Bayar Rp <?= number_format($order_data['total_harga'], 0, ',', '.') ?>
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- Jika order sudah lunas / sukses -->
                            <div style="text-align: center; padding: 24px 0;">
                                <div style="color: var(--semantic-success); font-size: 48px; margin-bottom: var(--spacing-sm);">&check;</div>
                                <h4 class="heading-sm" style="color: var(--semantic-success); margin-bottom: 8px;">Pesanan Sudah Terbayar</h4>
                                <p class="caption" style="margin-bottom: var(--spacing-lg);">Terima kasih atas pembayaran Anda! Pesanan ini sedang diproses.</p>
                                <a href="riwayat.php" class="btn btn-primary-pill" style="width: 100%;">Lihat Riwayat Belanja</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>
