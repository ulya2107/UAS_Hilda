<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');

    if (empty($nama) || empty($email) || empty($pesan)) {
        $error_msg = "Semua field input harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Format alamat email tidak valid.";
    } else {
        // Simulasi berhasil mengirimkan pesan kontak
        $success_msg = "Pesan Anda berhasil terkirim! Tim Fleurist akan membalas via email dalam waktu 24 jam.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hubungi Kami | Fleurist</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color: var(--canvas-cream);">

    <!-- Header / Navbar -->
    <?php include 'header.php'; ?>

    <main class="admin-container" style="padding-top: 40px; min-height: 80vh;">
        <!-- Page Title -->
        <div class="admin-header" style="margin-bottom: var(--spacing-huge); text-align: center; flex-direction: column; gap: var(--spacing-sm);">
            <div>
                <span class="micro-cap" style="color: var(--primary); display: block; margin-bottom: var(--spacing-xs);">Dukungan Pelanggan</span>
                <h1 class="display-md" style="color: var(--primary);">Hubungi Tim Fleurist</h1>
                <p class="caption" style="max-width: 600px; margin: 0 auto;">Punya pertanyaan mengenai buket bunga kustom, pengiriman korporat, atau kemitraan? Hubungi kami langsung.</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: var(--spacing-xl); align-items: start;">
            <!-- Left Column: Contact Form -->
            <div class="admin-card" style="padding: 32px; margin-bottom: 0;">
                <h3 class="heading-sm" style="color: var(--primary); margin-bottom: var(--spacing-lg);">Kirim Pesan</h3>
                
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success" style="margin-bottom: var(--spacing-xl);"><?= $success_msg ?></div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger" style="margin-bottom: var(--spacing-xl);"><?= $error_msg ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label for="nama" class="form-label">Nama Anda</label>
                        <input type="text" name="nama" id="nama" class="text-input" placeholder="Masukkan nama lengkap Anda" required value="<?= isset($nama) && empty($success_msg) ? htmlspecialchars($nama) : '' ?>">
                    </div>

                    <div class="form-group" style="margin-top: 16px;">
                        <label for="email" class="form-label">Alamat Email</label>
                        <input type="email" name="email" id="email" class="text-input" placeholder="name@email.com" required value="<?= isset($email) && empty($success_msg) ? htmlspecialchars($email) : '' ?>">
                    </div>

                    <div class="form-group" style="margin-top: 16px;">
                        <label for="pesan" class="form-label">Isi Pesan / Pertanyaan</label>
                        <textarea name="pesan" id="pesan" class="text-input" rows="5" placeholder="Tuliskan pesan atau pertanyaan Anda di sini..." required style="resize: vertical; min-height: 120px;"><?= isset($pesan) && empty($success_msg) ? htmlspecialchars($pesan) : '' ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary-pill" style="width: 100%; margin-top: 24px; font-size: 15px;">Kirim Pesan Sekarang</button>
                </form>
            </div>

            <!-- Right Column: Information -->
            <div class="admin-card" style="padding: 32px; margin-bottom: 0; background-color: var(--canvas-lavender); border-color: var(--primary);">
                <h3 class="heading-sm" style="color: var(--primary); margin-bottom: var(--spacing-lg);">Informasi Kontak</h3>
                
                <div style="display: flex; flex-direction: column; gap: var(--spacing-xl);">
                    <!-- Address -->
                    <div>
                        <span class="micro-cap" style="color: var(--primary); display: block; margin-bottom: var(--spacing-xs);">📍 Kantor Utama</span>
                        <p class="caption" style="line-height: 1.6; color: var(--ink);">
                            Jl. Pelor Mas I No. 13,<br>
                            Kec. Mataram, Kota Mataram,<br>
                            Nusa Tenggara Barat 83115
                        </p>
                    </div>

                    <!-- Whatsapp -->
                    <div>
                        <span class="micro-cap" style="color: var(--primary); display: block; margin-bottom: var(--spacing-xs);">💬 Layanan Cepat WhatsApp</span>
                        <a href="https://wa.me/628123456789" target="_blank" style="font-size: 15px; color: var(--link-blue); font-weight: 700; text-decoration: none;">
                            +62 812-3456-789
                        </a>
                        <span class="caption" style="display: block; margin-top: 4px;">Aktif Senin - Minggu (08:00 - 21:00 WITA)</span>
                    </div>

                    <!-- Email -->
                    <div>
                        <span class="micro-cap" style="color: var(--primary); display: block; margin-bottom: var(--spacing-xs);">✉ Email Support</span>
                        <a href="mailto:hello@fleurist.com" style="font-size: 15px; color: var(--link-blue); font-weight: 700; text-decoration: none;">
                            hello@fleurist.com
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>
