<?php
require_once 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_order = intval($_POST['order_id'] ?? 0);
    $payment_type = trim($_POST['payment_type'] ?? '');
    $gross_amount = intval($_POST['gross_amount'] ?? 0);

    if ($id_order <= 0 || empty($payment_type)) {
        $error = "Data pembayaran tidak valid.";
    } else {
        try {
            // Mulai transaksi database
            $db->beginTransaction();

            // 1. Update status di orders menjadi 'paid'
            $stmt = $db->prepare("UPDATE orders SET status = 'paid' WHERE id_order = ?");
            $stmt->execute([$id_order]);

            // 2. Generate random transaction ID dari Midtrans
            $transaction_id = "MID-TX-" . strtoupper(uniqid());

            // 3. Update atau Insert data di tabel pembayaran
            // Cek apakah data pembayaran sudah ada
            $stmt = $db->prepare("SELECT id_bayar FROM pembayaran WHERE id_order = ?");
            $stmt->execute([$id_order]);
            $pay_exists = $stmt->fetch();

            if ($pay_exists) {
                $stmt = $db->prepare("UPDATE pembayaran SET metode = ?, transaction_id = ?, payment_status = 'settlement' WHERE id_order = ?");
                $stmt->execute([$payment_type, $transaction_id, $id_order]);
            } else {
                $stmt = $db->prepare("INSERT INTO pembayaran (id_order, metode, transaction_id, payment_status) VALUES (?, ?, ?, 'settlement')");
                $stmt->execute([$id_order, $payment_type, $transaction_id]);
            }

            // Commit transaksi
            $db->commit();
            
            // Set alert pesan sukses di session untuk ditampilkan di riwayat.php
            $_SESSION['payment_success_msg'] = "Pembayaran untuk pesanan <strong>#$id_order</strong> berhasil diproses via " . strtoupper(str_replace('_', ' ', $payment_type)) . ".";
            
            header("Location: riwayat.php");
            exit;

        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Gagal memproses callback pembayaran: " . $e->getMessage();
        }
    }
} else {
    // Jika diakses manual tanpa POST, redirect
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Memproses Pembayaran...</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2 class="auth-title display-md" style="color: var(--primary);">Terjadi Kesalahan</h2>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <a href="index.php" class="btn btn-primary-pill">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>
