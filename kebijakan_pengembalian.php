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
    <title>Kebijakan Pengembalian | Fleurist</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .policy-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            background-color: var(--canvas-lavender);
            color: var(--primary);
            border-radius: var(--rounded-md);
            font-size: 24px;
            margin-bottom: var(--spacing-md);
        }
        .policy-card {
            transition: transform 0.2s ease;
        }
        .policy-card:hover {
            transform: translateY(-2px);
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
                <span class="micro-cap" style="color: var(--primary); display: block; margin-bottom: var(--spacing-xs);">Garansi Layanan</span>
                <h1 class="display-md" style="color: var(--primary);">Kebijakan Pengembalian</h1>
                <p class="caption" style="max-width: 600px; margin: 0 auto;">Kami berkomitmen menjaga kesegaran bunga Anda. Pelajari garansi dan kebijakan pengembalian produk kami.</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--spacing-xl); margin-bottom: 48px;">
            <!-- Policy Item 1 -->
            <div class="admin-card policy-card" style="padding: 32px; margin-bottom: 0;">
                <div class="policy-icon">❀</div>
                <h3 class="heading-sm" style="color: var(--primary); margin-bottom: var(--spacing-sm);">Jaminan Kesegaran 24 Jam</h3>
                <p class="caption" style="line-height: 1.6; text-align: justify;">
                    Semua bunga dirangkai langsung sesaat sebelum pengiriman menggunakan bunga segar kualitas premium pilihan. Kami menjamin kesegaran bunga tetap prima setidaknya hingga 24 jam pertama setelah pesanan tiba di alamat tujuan.
                </p>
            </div>

            <!-- Policy Item 2 -->
            <div class="admin-card policy-card" style="padding: 32px; margin-bottom: 0;">
                <div class="policy-icon">⟲</div>
                <h3 class="heading-sm" style="color: var(--primary); margin-bottom: var(--spacing-sm);">Kondisi Klaim Valid</h3>
                <p class="caption" style="line-height: 1.6; text-align: justify;">
                    Anda dapat mengajukan klaim pengembalian atau penggantian buket bunga baru apabila:
                </p>
                <ul class="caption" style="line-height: 1.6; padding-left: 20px; margin-top: var(--spacing-sm); text-align: left;">
                    <li>Bunga layu/rusak parah akibat kesalahan penanganan saat kurir mengantar.</li>
                    <li>Rangkaian bunga atau jenis bunga yang diterima salah/tidak sesuai pesanan.</li>
                </ul>
            </div>

            <!-- Policy Item 3 -->
            <div class="admin-card policy-card" style="padding: 32px; margin-bottom: 0; grid-column: span 2;">
                <div class="policy-icon">✓</div>
                <h3 class="heading-sm" style="color: var(--primary); margin-bottom: var(--spacing-sm);">Prosedur Pengajuan Klaim</h3>
                <p class="caption" style="line-height: 1.6; text-align: justify; margin-bottom: var(--spacing-md);">
                    Untuk mengajukan komplain atau klaim garansi, silakan ikuti petunjuk berikut:
                </p>
                <ol class="caption" style="line-height: 1.8; padding-left: 20px; text-align: left;">
                    <li>Foto/videokan buket bunga yang bermasalah secara jelas segera setelah paket diterima dari kurir.</li>
                    <li>Kirimkan bukti visual beserta nomor ID Pesanan Anda kepada tim Customer Service kami melalui WhatsApp atau email.</li>
                    <li>Pengaduan harus diajukan selambat-lambatnya <strong>24 jam</strong> sejak bunga diterima.</li>
                    <li>Jika klaim disetujui, kami akan segera merangkai dan mengirimkan bunga pengganti yang baru atau memproses pengembalian dana (refund) penuh dalam waktu 1x24 jam kerja.</li>
                </ol>
            </div>
        </div>

        <!-- Additional info -->
        <div class="admin-card" style="padding: 32px; text-align: center; border: 1px dashed var(--primary); background-color: var(--canvas-lavender);">
            <p style="color: var(--primary); font-weight: 600; font-size: 15px;">
                Ada pertanyaan lain seputar pesanan Anda? Tim dukungan kami siap melayani Anda 24/7.
            </p>
            <div style="margin-top: var(--spacing-md);">
                <a href="hubungi_kami.php" class="btn btn-primary-pill" style="padding: 10px 24px; font-size: 14px; box-shadow: none;">Hubungi Customer Service</a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>
