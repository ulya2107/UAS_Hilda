<?php
require_once 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

// 1. Cek apakah input berupa JSON payload (Webhook Notification asli Midtrans)
$json_raw = file_get_contents('php://input');
$json_data = json_decode($json_raw, true);

if ($json_data) {
    // Dipanggil sebagai Webhook oleh Midtrans
    header('Content-Type: application/json');
    
    $order_id_raw = $json_data['order_id'] ?? '';
    $transaction_status = $json_data['transaction_status'] ?? '';
    $payment_type = $json_data['payment_type'] ?? '';
    $gross_amount_raw = $json_data['gross_amount'] ?? '0';
    $transaction_id = $json_data['transaction_id'] ?? '';
    $status_code = $json_data['status_code'] ?? '';
    $signature_key = $json_data['signature_key'] ?? '';
    
    // Verifikasi Signature Key untuk keamanan
    $computed_signature = hash("sha512", $order_id_raw . $status_code . $gross_amount_raw . MIDTRANS_SERVER_KEY);
    
    if ($computed_signature !== $signature_key) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Invalid signature key"]);
        exit;
    }
    
    // Parse order_id untuk mengambil ID database asli
    // Format: "ORDER-{id_order}-{timestamp}"
    $id_order = 0;
    $parts = explode('-', $order_id_raw);
    if (count($parts) >= 2 && $parts[0] === 'ORDER') {
        $id_order = intval($parts[1]);
    } else {
        $id_order = intval($order_id_raw);
    }
    
    if ($id_order <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid Order ID format"]);
        exit;
    }
    
    // Peta status transaksi Midtrans ke status lokal database
    $order_status = 'pending';
    $payment_status = 'pending';
    
    if ($transaction_status == 'capture') {
        $fraud = $json_data['fraud_status'] ?? '';
        if ($fraud == 'challenge') {
            $order_status = 'pending';
            $payment_status = 'challenge';
        } else if ($fraud == 'accept') {
            $order_status = 'paid';
            $payment_status = 'settlement';
        }
    } else if ($transaction_status == 'settlement') {
        $order_status = 'paid';
        $payment_status = 'settlement';
    } else if ($transaction_status == 'pending') {
        $order_status = 'pending';
        $payment_status = 'pending';
    } else if (in_array($transaction_status, ['deny', 'expire', 'cancel'])) {
        $order_status = 'cancelled';
        $payment_status = $transaction_status;
    }
    
    try {
        $db->beginTransaction();
        
        // 1. Update status di tabel orders
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id_order = ?");
        $stmt->execute([$order_status, $id_order]);
        
        // 2. Update/Insert pembayaran
        $stmt = $db->prepare("SELECT id_bayar FROM pembayaran WHERE id_order = ?");
        $stmt->execute([$id_order]);
        $pay_exists = $stmt->fetch();
        
        if ($pay_exists) {
            $stmt = $db->prepare("UPDATE pembayaran SET metode = ?, transaction_id = ?, payment_status = ? WHERE id_order = ?");
            $stmt->execute([$payment_type, $transaction_id, $payment_status, $id_order]);
        } else {
            $stmt = $db->prepare("INSERT INTO pembayaran (id_order, metode, transaction_id, payment_status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_order, $payment_type, $transaction_id, $payment_status]);
        }
        
        $db->commit();
        echo json_encode(["status" => "success", "message" => "Order #$id_order status updated to $order_status"]);
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        exit;
    }
}

// 2. Jika bukan JSON, cek apakah merupakan POST form standard (Redirect Klien / Simulator Fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_order = intval($_POST['order_id'] ?? 0);
    $payment_type = trim($_POST['payment_type'] ?? '');
    $gross_amount = intval($_POST['gross_amount'] ?? 0);
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    
    if ($id_order <= 0 || empty($payment_type)) {
        $error = "Data pembayaran tidak valid.";
    } else {
        $is_valid_payment = false;
        $payment_status = 'settlement';
        $order_status = 'paid';
        
        // Jika transaction_id ada dan bukan simulasi offline lokal (bukan diawali MID-TX-)
        if (!empty($transaction_id) && strpos($transaction_id, 'MID-TX-') === false) {
            // Verifikasi status transaksi asli via server-ke-server Midtrans API
            $midtrans_url = MIDTRANS_IS_PRODUCTION 
                ? "https://api.midtrans.com/v2/" . urlencode($transaction_id) . "/status"
                : "https://api.sandbox.midtrans.com/v2/" . urlencode($transaction_id) . "/status";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $midtrans_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Untuk kompatibilitas XAMPP lokal
            
            $auth_header = base64_encode(MIDTRANS_SERVER_KEY . ':');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Accept: application/json",
                "Authorization: Basic " . $auth_header
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response) {
                $result = json_decode($response, true);
                $transaction_status = $result['transaction_status'] ?? '';
                
                if ($transaction_status === 'settlement') {
                    $is_valid_payment = true;
                    $payment_status = 'settlement';
                    $order_status = 'paid';
                } elseif ($transaction_status === 'capture') {
                    $fraud = $result['fraud_status'] ?? '';
                    if ($fraud === 'accept') {
                        $is_valid_payment = true;
                        $payment_status = 'settlement';
                        $order_status = 'paid';
                    } elseif ($fraud === 'challenge') {
                        $is_valid_payment = true; 
                        $payment_status = 'challenge';
                        $order_status = 'pending';
                    }
                } elseif ($transaction_status === 'pending') {
                    $is_valid_payment = true;
                    $payment_status = 'pending';
                    $order_status = 'pending';
                } elseif (in_array($transaction_status, ['deny', 'expire', 'cancel'])) {
                    $is_valid_payment = true;
                    $payment_status = $transaction_status;
                    $order_status = 'cancelled';
                } else {
                    $error = "Status transaksi dari Midtrans tidak valid atau belum terbayar (Status: $transaction_status).";
                }
            } else {
                $error = "Gagal memverifikasi status pembayaran ke server Midtrans.";
            }
        } else {
            // Simulasi Pembayaran Luring / Fallback Simulator
            if (empty($transaction_id)) {
                $transaction_id = "MID-TX-" . strtoupper(uniqid());
            }
            $is_valid_payment = true;
        }

        if ($is_valid_payment && empty($error)) {
            try {
                $db->beginTransaction();

                // 1. Update status pesanan di orders
                $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id_order = ?");
                $stmt->execute([$order_status, $id_order]);

                // 2. Update/Insert ke tabel pembayaran
                $stmt = $db->prepare("SELECT id_bayar FROM pembayaran WHERE id_order = ?");
                $stmt->execute([$id_order]);
                $pay_exists = $stmt->fetch();

                if ($pay_exists) {
                    $stmt = $db->prepare("UPDATE pembayaran SET metode = ?, transaction_id = ?, payment_status = ? WHERE id_order = ?");
                    $stmt->execute([$payment_type, $transaction_id, $payment_status, $id_order]);
                } else {
                    $stmt = $db->prepare("INSERT INTO pembayaran (id_order, metode, transaction_id, payment_status) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id_order, $payment_type, $transaction_id, $payment_status]);
                }

                $db->commit();
                
                if ($order_status === 'paid') {
                    $_SESSION['payment_success_msg'] = "Pembayaran untuk pesanan <strong>#$id_order</strong> berhasil diproses via " . strtoupper(str_replace('_', ' ', $payment_type)) . ".";
                } else {
                    $_SESSION['payment_success_msg'] = "Status transaksi untuk pesanan <strong>#$id_order</strong> adalah: " . strtoupper($payment_status) . ".";
                }
                
                header("Location: riwayat.php");
                exit;

            } catch (PDOException $e) {
                $db->rollBack();
                $error = "Gagal memproses pembayaran ke database: " . $e->getMessage();
            }
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
