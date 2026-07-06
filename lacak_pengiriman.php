<?php
require_once 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$order_data = null;
$error_msg = '';

if ($order_id !== null) {
    if ($order_id <= 0) {
        $error_msg = "Format ID Pesanan tidak valid.";
    } elseif (empty($email)) {
        $error_msg = "Silakan masukkan alamat email yang digunakan saat pemesanan.";
    } else {
        try {
            // Ambil data order dan verifikasi kepemilikan via email user
            $stmt = $db->prepare("SELECT orders.*, users.nama, users.email 
                                  FROM orders 
                                  JOIN users ON orders.id_user = users.id 
                                  WHERE orders.id_order = ? AND users.email = ?");
            $stmt->execute([$order_id, $email]);
            $order_data = $stmt->fetch();

            if (!$order_data) {
                $error_msg = "Pesanan dengan ID #$order_id dan email tersebut tidak ditemukan.";
            } else {
                // Ambil daftar barang pesanan untuk konfirmasi visual
                $stmt = $db->prepare("SELECT order_detail.*, produk.nama_produk 
                                      FROM order_detail 
                                      JOIN produk ON order_detail.id_produk = produk.id_produk 
                                      WHERE order_detail.id_order = ?");
                $stmt->execute([$order_id]);
                $order_items = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            $error_msg = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pengiriman | Fleurist</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Timeline Styles */
        .timeline {
            position: relative;
            max-width: 600px;
            margin: 40px auto;
            padding: 0 10px;
        }
        .timeline::after {
            content: '';
            position: absolute;
            width: 4px;
            background-color: var(--hairline);
            top: 0;
            bottom: 0;
            left: 28px;
            margin-left: -2px;
            z-index: 1;
        }
        .timeline-item {
            padding: 10px 30px 30px 70px;
            position: relative;
            background-color: inherit;
            width: 100%;
            box-sizing: border-box;
            text-align: left;
        }
        .timeline-badge {
            width: 24px;
            height: 24px;
            position: absolute;
            background-color: var(--canvas);
            border: 4px solid var(--hairline);
            border-radius: 50%;
            left: 16px;
            top: 12px;
            z-index: 2;
            transition: all 0.3s ease;
        }
        /* Active States */
        .timeline-item.active .timeline-badge {
            background-color: var(--primary);
            border-color: var(--canvas-lavender);
            box-shadow: 0 0 0 3px var(--primary);
        }
        .timeline-item.active h4 {
            color: var(--primary);
            font-weight: 700;
        }
        .timeline-item.completed::after {
            background-color: var(--primary);
        }
        .timeline-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--ink-mute);
            margin-bottom: 4px;
        }
        .timeline-desc {
            font-size: 13px;
            color: var(--ink-mute);
        }
        /* Line progress styling between badges */
        .timeline-item.active ~ .timeline-item::after {
            background-color: var(--hairline);
        }
        .timeline-item.active-line::after {
            content: '';
            position: absolute;
            width: 4px;
            background-color: var(--primary);
            top: 24px;
            bottom: -24px;
            left: 28px;
            margin-left: -2px;
            z-index: 1;
        }
    </style>
</head>
<body style="background-color: var(--canvas-cream);">

    <!-- Header / Navbar -->
    <?php include 'header.php'; ?>

    <main class="admin-container" style="padding-top: 40px; min-height: 80vh;">
        <!-- Page Title -->
        <div class="admin-header" style="margin-bottom: var(--spacing-huge); text-align: center; flex-direction: column; gap: var(--spacing-sm);">
            <div>
                <span class="micro-cap" style="color: var(--primary); display: block; margin-bottom: var(--spacing-xs);">Status Transaksi</span>
                <h1 class="display-md" style="color: var(--primary);">Lacak Pengiriman Bunga</h1>
                <p class="caption" style="max-width: 600px; margin: 0 auto;">Masukkan ID Pesanan dan alamat email Anda untuk memeriksa status perangkaian dan pengiriman buket bunga.</p>
            </div>
        </div>

        <!-- Tracking Search Card -->
        <div class="admin-card" style="max-width: 600px; margin: 0 auto 40px auto; padding: 28px;">
            <form action="" method="GET">
                <div class="form-group">
                    <label for="order_id" class="form-label">ID Pesanan (Order ID)</label>
                    <input type="number" name="order_id" id="order_id" class="text-input" placeholder="Contoh: 1" required value="<?= $order_id ? htmlspecialchars($order_id) : '' ?>">
                    <span class="caption">ID Pesanan tertera pada struk belanja atau riwayat transaksi Anda.</span>
                </div>
                <div class="form-group" style="margin-top: 16px;">
                    <label for="email" class="form-label">Email Pelanggan</label>
                    <input type="email" name="email" id="email" class="text-input" placeholder="name@email.com" required value="<?= htmlspecialchars($email) ?>">
                </div>
                <button type="submit" class="btn btn-primary-pill" style="width: 100%; margin-top: 24px; font-size: 15px;">Lacak Status Pesanan</button>
            </form>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger" style="max-width: 600px; margin: 0 auto;"><?= $error_msg ?></div>
        <?php endif; ?>

        <!-- Visual Timeline Results -->
        <?php if ($order_data): 
            $status = strtolower($order_data['status']);
            
            // Map status code to sequence levels (1 to 5)
            $status_level = 1;
            if ($status === 'paid') {
                $status_level = 2;
            } elseif ($status === 'processed') {
                $status_level = 3;
            } elseif ($status === 'shipped') {
                $status_level = 4;
            } elseif ($status === 'completed') {
                $status_level = 5;
            }
        ?>
            <div class="admin-card" style="max-width: 600px; margin: 0 auto; padding: 32px; text-align: center;">
                <h3 class="heading-sm" style="color: var(--primary); margin-bottom: 8px;">Pesanan #<?= htmlspecialchars($order_data['id_order']) ?></h3>
                <p class="caption" style="margin-bottom: 24px;">Penerima: <strong><?= htmlspecialchars($order_data['nama']) ?></strong> | Total: <strong>Rp <?= number_format($order_data['total_harga'], 0, ',', '.') ?></strong></p>
                
                <!-- Timeline Component -->
                <div class="timeline">
                    
                    <!-- Step 1: Pending -->
                    <div class="timeline-item <?= $status_level >= 1 ? 'active' : '' ?>">
                        <div class="timeline-badge"></div>
                        <div class="timeline-title">Pesanan Dibuat</div>
                        <div class="timeline-desc">Pesanan bunga telah dibuat dan menunggu pembayaran dikonfirmasi.</div>
                    </div>
                    
                    <!-- Step 2: Paid -->
                    <div class="timeline-item <?= $status_level >= 2 ? 'active' : '' ?>">
                        <div class="timeline-badge"></div>
                        <div class="timeline-title">Lunas & Terverifikasi</div>
                        <div class="timeline-desc">Pembayaran pesanan lunas dan berhasil diverifikasi oleh sistem.</div>
                    </div>
                    
                    <!-- Step 3: Processed -->
                    <div class="timeline-item <?= $status_level >= 3 ? 'active' : '' ?>">
                        <div class="timeline-badge"></div>
                        <div class="timeline-title">Bunga Sedang Dirangkai</div>
                        <div class="timeline-desc">Florist kami sedang memilih kelopak segar terbaik dan merangkai buket Anda.</div>
                    </div>
                    
                    <!-- Step 4: Shipped -->
                    <div class="timeline-item <?= $status_level >= 4 ? 'active' : '' ?>">
                        <div class="timeline-badge"></div>
                        <div class="timeline-title">Buket Dikirim</div>
                        <div class="timeline-desc">Kurir khusus kami sedang membawa buket bunga segar menuju alamat Anda.</div>
                    </div>
                    
                    <!-- Step 5: Completed -->
                    <div class="timeline-item <?= $status_level >= 5 ? 'active' : '' ?>">
                        <div class="timeline-badge"></div>
                        <div class="timeline-title">Bunga Diterima</div>
                        <div class="timeline-desc">Buket bunga telah tiba dan diterima dengan baik oleh penerima di alamat tujuan.</div>
                    </div>
                </div>

                <!-- Info detail alamat pengiriman -->
                <?php if (!empty($order_data['alamat_pengiriman'])): ?>
                    <div style="margin-top: 24px; padding: 16px; background-color: var(--canvas-cream); border-radius: var(--rounded-md); font-size: 14px; text-align: left;">
                        <span style="color: var(--ink-mute); font-weight: 700; display: block; font-size: 11px; text-transform: uppercase; margin-bottom: 4px;">Tujuan Pengantaran Bunga:</span>
                        <span style="color: var(--ink); line-height: 1.5;"><?= htmlspecialchars($order_data['alamat_pengiriman']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>
