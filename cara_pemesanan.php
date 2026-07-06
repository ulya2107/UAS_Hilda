<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cara Pemesanan | Fleurist</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: var(--primary);
            color: var(--on-primary);
            border-radius: 50%;
            font-weight: 700;
            font-size: 18px;
            margin-bottom: var(--spacing-md);
        }
        .step-card {
            border-top: 4px solid var(--primary);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .step-card:hover {
            transform: translateY(-4px);
            box-shadow: rgba(0, 0, 0, 0.08) 0 8px 24px 0;
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
                <span class="micro-cap" style="color: var(--primary); display: block; margin-bottom: var(--spacing-xs);">Panduan Belanja</span>
                <h1 class="display-md" style="color: var(--primary);">Cara Pemesanan Bunga</h1>
                <p class="caption" style="max-width: 600px; margin: 0 auto;">Ikuti langkah-langkah mudah berikut untuk memesan buket bunga segar terbaik Anda di Fleurist.</p>
            </div>
        </div>

        <!-- Grid of steps -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--spacing-xl); margin-bottom: 48px;">
            <!-- Step 1 -->
            <div class="admin-card step-card" style="padding: 28px; text-align: center; margin-bottom: 0;">
                <div class="step-number">1</div>
                <h3 class="heading-sm" style="color: var(--primary); margin-bottom: var(--spacing-sm);">Pilih Bunga Segar</h3>
                <p class="caption" style="line-height: 1.6;">Jelajahi katalog kami. Temukan berbagai jenis bunga premium mulai dari Mawar, Lily, Tulip, hingga Buket istimewa.</p>
            </div>

            <!-- Step 2 -->
            <div class="admin-card step-card" style="padding: 28px; text-align: center; margin-bottom: 0;">
                <div class="step-number">2</div>
                <h3 class="heading-sm" style="color: var(--primary); margin-bottom: var(--spacing-sm);">Tambahkan ke Keranjang</h3>
                <p class="caption" style="line-height: 1.6;">Pilih bunga kesukaan Anda, atur jumlah buket (pcs) pada detail produk, dan klik tombol <strong>+ Keranjang</strong>.</p>
            </div>

            <!-- Step 3 -->
            <div class="admin-card step-card" style="padding: 28px; text-align: center; margin-bottom: 0;">
                <div class="step-number">3</div>
                <h3 class="heading-sm" style="color: var(--primary); margin-bottom: var(--spacing-sm);">Isi Informasi Pengiriman</h3>
                <p class="caption" style="line-height: 1.6;">Masuk atau daftarkan akun baru Anda, lalu selesaikan data alamat pengantaran bunga serta kontak Whatsapp penerima secara lengkap.</p>
            </div>

            <!-- Step 4 -->
            <div class="admin-card step-card" style="padding: 28px; text-align: center; margin-bottom: 0;">
                <div class="step-number">4</div>
                <h3 class="heading-sm" style="color: var(--primary); margin-bottom: var(--spacing-sm);">Bayar via Simulator</h3>
                <p class="caption" style="line-height: 1.6;">Selesaikan transaksi Anda melalui Midtrans JSON API Simulator dengan memilih opsi Virtual Account atau metode pembayaran lainnya.</p>
            </div>
        </div>

        <!-- Call to Action Banner -->
        <div class="admin-card" style="background-color: var(--surface-aubergine); color: var(--on-primary); padding: 48px; text-align: center; border-radius: var(--rounded-xl); margin-top: 24px;">
            <h2 class="heading-lg" style="color: var(--on-primary); margin-bottom: var(--spacing-sm);">Siap Menghias Momen Istimewa Anda?</h2>
            <p style="color: var(--on-aubergine-mute); max-width: 600px; margin: 0 auto var(--spacing-xl) auto; font-size: 15px; line-height: 1.6;">
                Dapatkan bunga segar berkualitas premium dengan aroma menenangkan untuk orang terkasih Anda.
            </p>
            <a href="index.php" class="btn btn-primary-pill" style="background-color: var(--canvas); color: var(--primary); font-size: 15px; box-shadow: none;">Belanja Sekarang</a>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>
