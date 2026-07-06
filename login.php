<?php
session_start();
require_once 'koneksi.php';

// Jika sudah login, redirect sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email dan password harus diisi.";
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                $success = "Login berhasil! Mengalihkan...";
                
                // Redirect berdasarkan role
                if ($user['role'] === 'admin') {
                    header("refresh:1.5;url=admin/dashboard.php");
                } else {
                    if (isset($_SESSION['redirect_url'])) {
                        $redirect = $_SESSION['redirect_url'];
                        unset($_SESSION['redirect_url']);
                        header("refresh:1.5;url=" . $redirect);
                    } else {
                        header("refresh:1.5;url=index.php");
                    }
                }
            } else {
                $error = "Email atau password salah.";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Akun | Toko Bunga Segar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <!-- Slacc-style Aubergine Flower Logo -->
            <div class="auth-logo">
                <svg width="32" height="32" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                <span>Fleurist</span>
            </div>
            
            <h2 class="auth-title display-md">Masuk ke Akun Anda</h2>
            <p class="auth-subtitle body-md">Silakan masukkan email dan password untuk melanjutkan.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="email" class="form-label">Alamat Email</label>
                    <input type="email" name="email" id="email" class="text-input" placeholder="name@email.com" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="text-input" placeholder="Masukkan password Anda" required>
                </div>

                <button type="submit" class="btn btn-primary-pill" style="width: 100%; margin-top: var(--spacing-md);">Masuk</button>
            </form>

            <div style="margin-top: var(--spacing-xl); font-size: 14px;">
                <span style="color: var(--ink-mute);">Belum punya akun? </span>
                <a href="register.php">Daftar sekarang</a>
            </div>
        </div>
    </div>
</body>
</html>
