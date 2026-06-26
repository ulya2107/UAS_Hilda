<?php
require_once '../koneksi.php';

// Cek session di navbar.php
include 'navbar.php';

// Inisialisasi data statistik
$total_produk = 0;
$total_user = 0;
$total_pesanan = 0;
$total_pendapatan = 0;
$recent_orders = [];

try {
    // 1. Total Produk
    $stmt = $db->query("SELECT COUNT(*) as total FROM produk");
    $total_produk = $stmt->fetch()['total'];

    // 2. Total User (Pelanggan)
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    $total_user = $stmt->fetch()['total'];

    // 3. Total Pesanan
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
    $total_pesanan = $stmt->fetch()['total'];

    // 4. Total Pendapatan (Pesanan yang sudah dibayar/selesai)
    $stmt = $db->query("SELECT SUM(total_harga) as total FROM orders WHERE status IN ('paid', 'processed', 'shipped', 'completed')");
    $total_pendapatan = $stmt->fetch()['total'] ?? 0;

    // 5. Pesanan Terbaru
    $stmt = $db->query("SELECT orders.*, users.nama FROM orders 
                        JOIN users ON orders.id_user = users.id 
                        ORDER BY orders.tanggal DESC LIMIT 5");
    $recent_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Gagal memuat data dashboard: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Fleurist</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body style="background-color: var(--canvas-cream);">
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h1 class="display-md">Dashboard</h1>
                <p class="caption">Ringkasan aktivitas toko bunga Anda saat ini.</p>
            </div>
            <div>
                <span class="caption">Tanggal: <?= date('d M Y') ?></span>
            </div>
        </div>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <!-- Stat Grid -->
        <div class="admin-grid">
            <!-- Card 1: Produk -->
            <div class="card-stat">
                <div class="card-stat-title">Total Produk</div>
                <div class="card-stat-number"><?= number_format($total_produk) ?></div>
            </div>

            <!-- Card 2: User -->
            <div class="card-stat">
                <div class="card-stat-title">Pelanggan</div>
                <div class="card-stat-number"><?= number_format($total_user) ?></div>
            </div>

            <!-- Card 3: Pesanan -->
            <div class="card-stat">
                <div class="card-stat-title">Total Pesanan</div>
                <div class="card-stat-number"><?= number_format($total_pesanan) ?></div>
            </div>

            <!-- Card 4: Pendapatan -->
            <div class="card-stat">
                <div class="card-stat-title">Pendapatan</div>
                <div class="card-stat-number">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
            </div>
        </div>

        <!-- Recent Orders Table -->
        <div class="admin-card">
            <h2 class="heading-sm" style="margin-bottom: var(--spacing-lg);">Pesanan Terbaru</h2>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID Order</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--ink-mute);">Belum ada pesanan terbaru.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($order['id_order']) ?></strong></td>
                                    <td><?= htmlspecialchars($order['nama']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['tanggal'])) ?></td>
                                    <td>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge badge-<?= htmlspecialchars($order['status']) ?>">
                                            <?= htmlspecialchars($order['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="pesanan.php?action=detail&id=<?= $order['id_order'] ?>" class="btn btn-secondary-pill" style="padding: 6px 16px; font-size: 12px; border-radius: var(--rounded-pill);">Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
