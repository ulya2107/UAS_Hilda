<?php
require_once '../koneksi.php';
include 'navbar.php';

$action = $_GET['action'] ?? 'list';
$id_order = $_GET['id'] ?? null;

$error = '';
$success = '';

// Proses Pembaruan Status Pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status' && $id_order) {
    $new_status = $_POST['status'] ?? '';
    
    if (in_array($new_status, ['pending', 'paid', 'processed', 'shipped', 'completed', 'cancelled'])) {
        try {
            $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id_order = ?");
            $stmt->execute([$new_status, $id_order]);
            $success = "Status pesanan #$id_order berhasil diperbarui menjadi " . strtoupper($new_status);
            $action = 'detail'; // tetap di detail setelah update
        } catch (PDOException $e) {
            $error = "Gagal memperbarui status pesanan: " . $e->getMessage();
        }
    } else {
        $error = "Status tidak valid.";
    }
}

// Ambil detail pesanan khusus
$order_data = null;
$order_items = [];
$payment_data = null;

if (($action === 'detail' || $action === 'update_status') && $id_order) {
    try {
        // Ambil info pesanan utama & nama pelanggan
        $stmt = $db->prepare("SELECT orders.*, users.nama, users.email FROM orders 
                            JOIN users ON orders.id_user = users.id 
                            WHERE orders.id_order = ?");
        $stmt->execute([$id_order]);
        $order_data = $stmt->fetch();

        if ($order_data) {
            // Ambil item produk dalam pesanan
            $stmt = $db->prepare("SELECT order_detail.*, produk.nama_produk, produk.gambar, produk.harga FROM order_detail 
                                JOIN produk ON order_detail.id_produk = produk.id_produk 
                                WHERE order_detail.id_order = ?");
            $stmt->execute([$id_order]);
            $order_items = $stmt->fetchAll();

            // Ambil info pembayaran dari Midtrans (tabel pembayaran)
            $stmt = $db->prepare("SELECT * FROM pembayaran WHERE id_order = ?");
            $stmt->execute([$id_order]);
            $payment_data = $stmt->fetch();
        } else {
            $error = "Pesanan tidak ditemukan.";
            $action = 'list';
        }
    } catch (PDOException $e) {
        $error = "Gagal memuat detail pesanan: " . $e->getMessage();
    }
}

// Ambil semua pesanan untuk list
$orders = [];
if ($action === 'list') {
    try {
        $stmt = $db->query("SELECT orders.*, users.nama FROM orders 
                            JOIN users ON orders.id_user = users.id 
                            ORDER BY orders.tanggal DESC");
        $orders = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Gagal memuat daftar pesanan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan | Fleurist</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-container">
        
        <?php if ($action === 'list'): ?>
            <!-- LIST PESANAN -->
            <div class="admin-header">
                <div>
                    <h1 class="display-md">Kelola Pesanan</h1>
                    <p class="caption">Lihat detail pesanan dan perbarui status pengiriman.</p>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID Order</th>
                            <th>Pelanggan</th>
                            <th>Tanggal Masuk</th>
                            <th>Total Pembayaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--ink-mute);">Belum ada transaksi pesanan masuk.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
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
                                    <td>
                                        <a href="pesanan.php?action=detail&id=<?= $order['id_order'] ?>" class="btn btn-secondary-pill" style="padding: 6px 14px; font-size: 12px;">Detail & Kelola</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action === 'detail' || $action === 'update_status'): ?>
            <!-- DETAIL PESANAN -->
            <div class="admin-header">
                <div>
                    <h1 class="display-md">Detail Pesanan #<?= htmlspecialchars($order_data['id_order']) ?></h1>
                    <p class="caption">Informasi lengkap pemesanan pelanggan.</p>
                </div>
                <div>
                    <a href="pesanan.php" class="btn btn-secondary-pill">Kembali ke Daftar</a>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-xl); align-items: start;">
                <!-- Kolom Kiri: Detail Item Pesanan -->
                <div>
                    <div class="admin-card">
                        <h3 class="heading-sm" style="margin-bottom: var(--spacing-lg);">Item Bunga yang Dipesan</h3>
                        <div class="admin-table-container" style="box-shadow: none; border-radius: 0; border: none; border-bottom: 1px solid var(--hairline);">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Gambar</th>
                                        <th>Nama Produk</th>
                                        <th>Harga Satuan</th>
                                        <th>Jumlah</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <?php if ($item['gambar'] && file_exists('../uploads/' . $item['gambar'])): ?>
                                                    <img src="../uploads/<?= htmlspecialchars($item['gambar']) ?>" alt="img" style="width: 45px; height: 45px; object-fit: cover; border-radius: var(--rounded-sm);">
                                                <?php else: ?>
                                                    <div style="width: 45px; height: 45px; background-color: var(--canvas-cream); display: flex; align-items: center; justify-content: center; font-size: 8px; color: var(--ink-mute);">No Img</div>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?= htmlspecialchars($item['nama_produk']) ?></strong></td>
                                            <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                            <td><?= htmlspecialchars($item['qty']) ?> pcs</td>
                                            <td><strong>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr style="background-color: var(--canvas-cream); font-weight: 700;">
                                        <td colspan="4" style="text-align: right; padding: 12px var(--spacing-lg);">Grand Total:</td>
                                        <td style="color: var(--primary); padding: 12px var(--spacing-lg); font-size: 16px;">Rp <?= number_format($order_data['total_harga'], 0, ',', '.') ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Informasi Pembayaran -->
                    <div class="admin-card">
                        <h3 class="heading-sm" style="margin-bottom: var(--spacing-md);">Detail Pembayaran (Midtrans)</h3>
                        <?php if ($payment_data): ?>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); font-size: 14px;">
                                <div>
                                    <p style="color: var(--ink-mute);">Metode Pembayaran:</p>
                                    <p><strong><?= htmlspecialchars(strtoupper($payment_data['metode'] ?? '-')) ?></strong></p>
                                </div>
                                <div>
                                    <p style="color: var(--ink-mute);">ID Transaksi Midtrans:</p>
                                    <p><strong><?= htmlspecialchars($payment_data['transaction_id'] ?? '-') ?></strong></p>
                                </div>
                                <div style="margin-top: 10px;">
                                    <p style="color: var(--ink-mute);">Status Pembayaran:</p>
                                    <p><span class="badge badge-<?= htmlspecialchars($payment_data['payment_status']) ?>"><?= htmlspecialchars($payment_data['payment_status']) ?></span></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="caption" style="font-style: italic;">Informasi detail transaksi pembayaran belum masuk ke database.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Kolom Kanan: Info Pelanggan & Form Status -->
                <div>
                    <!-- Info Pelanggan -->
                    <div class="admin-card">
                        <h3 class="heading-sm" style="margin-bottom: var(--spacing-md);">Pelanggan</h3>
                        <div style="font-size: 14px;">
                            <p style="font-weight: 700; color: var(--primary);"><?= htmlspecialchars($order_data['nama']) ?></p>
                            <p style="color: var(--ink-mute);"><?= htmlspecialchars($order_data['email']) ?></p>
                            <p style="margin-top: 10px; color: var(--ink-mute);">Tanggal Order:</p>
                            <p><strong><?= date('d M Y, H:i', strtotime($order_data['tanggal'])) ?></strong></p>
                        </div>
                    </div>

                    <!-- Perbarui Status -->
                    <div class="admin-card">
                        <h3 class="heading-sm" style="margin-bottom: var(--spacing-md);">Status Pesanan</h3>
                        <div style="margin-bottom: var(--spacing-lg);">
                            <span class="badge badge-<?= htmlspecialchars($order_data['status']) ?>" style="font-size: 14px; padding: 6px 12px; border-radius: var(--rounded-sm);">
                                <?= htmlspecialchars($order_data['status']) ?>
                            </span>
                        </div>
                        
                        <form action="pesanan.php?action=update_status&id=<?= $order_data['id_order'] ?>" method="POST">
                            <div class="form-group">
                                <label for="status" class="form-label">Ubah Status Ke:</label>
                                <select name="status" id="status" class="text-input" style="appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22><path fill=%22%231d1d1d%22 d=%22M7 10l5 5 5-5z%22/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                                    <option value="pending" <?= $order_data['status'] === 'pending' ? 'selected' : '' ?>>pending (Belum Bayar)</option>
                                    <option value="paid" <?= $order_data['status'] === 'paid' ? 'selected' : '' ?>>paid (Sudah Bayar)</option>
                                    <option value="processed" <?= $order_data['status'] === 'processed' ? 'selected' : '' ?>>processed (Sedang Diproses)</option>
                                    <option value="shipped" <?= $order_data['status'] === 'shipped' ? 'selected' : '' ?>>shipped (Dalam Pengiriman)</option>
                                    <option value="completed" <?= $order_data['status'] === 'completed' ? 'selected' : '' ?>>completed (Selesai)</option>
                                    <option value="cancelled" <?= $order_data['status'] === 'cancelled' ? 'selected' : '' ?>>cancelled (Dibatalkan)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary-pill" style="width: 100%;">Perbarui Status</button>
                        </form>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</body>
</html>
