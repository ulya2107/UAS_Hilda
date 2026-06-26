<?php
require_once 'koneksi.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (empty($nama) || empty($email) || empty($password)) {
        $error = "Semua field harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal terdiri dari 6 karakter.";
    } elseif (!in_array($role, ['user', 'admin'])) {
        $error = "Role tidak valid.";
    } else {
        try {
            // Cek apakah email sudah terdaftar
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email sudah terdaftar. Silakan gunakan email lain.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Insert user baru
                $stmt = $db->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$nama, $email, $hashedPassword, $role])) {
                    $success = "Registrasi berhasil! Silakan login.";
                    // Redirect setelah 2 detik
                    header("refresh:2;url=login.php");
                } else {
                    $error = "Gagal melakukan registrasi. Coba lagi.";
                }
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
    <title>Daftar Akun | Toko Bunga Segar</title>
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
            
            <h2 class="auth-title display-md">Daftar Akun Baru</h2>
            <p class="auth-subtitle body-md">Mulai belanja bunga segar favorit Anda hari ini.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="nama" class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" id="nama" class="text-input" placeholder="Masukkan nama lengkap Anda" required value="<?= isset($nama) ? htmlspecialchars($nama) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Alamat Email</label>
                    <input type="email" name="email" id="email" class="text-input" placeholder="name@email.com" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="text-input" placeholder="Minimal 6 karakter" required>
                </div>

                <div class="form-group">
                    <label for="role" class="form-label">Daftar Sebagai</label>
                    <select name="role" id="role" class="text-input" style="appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22><path fill=%22%231d1d1d%22 d=%22M7 10l5 5 5-5z%22/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                        <option value="user" <?= isset($role) && $role === 'user' ? 'selected' : '' ?>>Pelanggan / User</option>
                        <option value="admin" <?= isset($role) && $role === 'admin' ? 'selected' : '' ?>>Admin Toko</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary-pill" style="width: 100%; margin-top: var(--spacing-md);">Daftar</button>
            </form>

            <div style="margin-top: var(--spacing-xl); font-size: 14px;">
                <span style="color: var(--ink-mute);">Sudah punya akun? </span>
                <a href="login.php">Masuk di sini</a>
            </div>
        </div>
    </div>
</body>
</html>
