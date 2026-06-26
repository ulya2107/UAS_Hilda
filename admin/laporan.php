<?php
require_once '../koneksi.php';
include 'navbar.php';

$error = '';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Inisialisasi summary
$total_laporan_pendapatan = 0;
$total_laporan_orders = 0;
$average_order_value = 0;
$completed_orders = [];

try {
    // Query list pesanan berstatus sukses/selesai dalam rentang tanggal
    // Kita anggap sukses/selesai = status IN ('paid', 'processed', 'shipped', 'completed')
    $stmt = $db->prepare("SELECT orders.*, users.nama FROM orders 
                        JOIN users ON orders.id_user = users.id 
                        WHERE DATE(orders.tanggal) BETWEEN ? AND ? 
                        AND orders.status IN ('paid', 'processed', 'shipped', 'completed') 
                        ORDER BY orders.tanggal DESC");
    $stmt->execute([$start_date, $end_date]);
    $completed_orders = $stmt->fetchAll();

    // Hitung ringkasan
    $total_laporan_orders = count($completed_orders);
    foreach ($completed_orders as $o) {
        $total_laporan_pendapatan += $o['total_harga'];
    }

    if ($total_laporan_orders > 0) {
        $average_order_value = $total_laporan_pendapatan / $total_laporan_orders;
    }
} catch (PDOException $e) {
    $error = "Gagal memuat laporan penjualan: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan | Fleurist</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h1 class="display-md">Laporan Penjualan</h1>
                <p class="caption">Analisis omset dan volume pesanan yang berhasil dilakukan.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form Filter Tanggal -->
        <div class="admin-card">
            <form action="" method="GET" style="display: flex; gap: var(--spacing-lg); align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 180px;">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" class="text-input" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 180px;">
                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                    <input type="date" name="end_date" id="end_date" class="text-input" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <button type="submit" class="btn btn-primary-pill" style="height: 44px; padding: 0 28px;">Filter Laporan</button>
            </form>
        </div>

        <!-- Summary Grid -->
        <div class="admin-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <div class="card-stat">
                <div class="card-stat-title">Omset Bersih (Filter)</div>
                <div class="card-stat-number">Rp <?= number_format($total_laporan_pendapatan, 0, ',', '.') ?></div>
            </div>
            <div class="card-stat">
                <div class="card-stat-title">Volume Transaksi Sukses</div>
                <div class="card-stat-number"><?= number_format($total_laporan_orders) ?> Pesanan</div>
            </div>
            <div class="card-stat">
                <div class="card-stat-title">Rata-Rata Transaksi (AOV)</div>
                <div class="card-stat-number">Rp <?= number_format($average_order_value, 0, ',', '.') ?></div>
            </div>
        </div>

        <!-- Tabel Detail Laporan -->
        <div class="admin-card">
            <h3 class="heading-sm" style="margin-bottom: var(--spacing-lg);">Rincian Penjualan Terfilter</h3>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID Order</th>
                            <th>Pelanggan</th>
                            <th>Tanggal Transaksi</th>
                            <th>Total Penjualan</th>
                            <th>Status Pesanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($completed_orders)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--ink-mute);">Tidak ada penjualan terdeteksi pada rentang tanggal terpilih.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($completed_orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($order['id_order']) ?></strong></td>
                                    <td><?= htmlspecialchars($order['nama']) ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($order['tanggal'])) ?></td>
                                    <td>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge badge-<?= htmlspecialchars($order['status']) ?>">
                                            <?= htmlspecialchars($order['status']) ?>
                                        </span>
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
