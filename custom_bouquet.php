<?php
require_once 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_msg = '';
$success_msg = '';

// Ketersediaan & harga bahan buket custom
$sizes = [
    'small' => ['nama' => 'Small (1-10 tangkai)', 'harga' => 50000],
    'medium' => ['nama' => 'Medium (11-20 tangkai)', 'harga' => 80000],
    'large' => ['nama' => 'Large (21-35 tangkai)', 'harga' => 120000],
];

$flowers = [
    'mawar' => ['nama' => 'Mawar Merah', 'harga' => 15000],
    'lily' => ['nama' => 'Lily Putih', 'harga' => 25000],
    'tulip' => ['nama' => 'Tulip Kuning', 'harga' => 30000],
    'babys_breath' => ['nama' => 'Baby\'s Breath', 'harga' => 10000],
    'eucalyptus' => ['nama' => 'Daun Eucalyptus', 'harga' => 5000],
];

$wrapping_papers = ['Pink Pastel', 'Biru Muda', 'Putih Klasik', 'Hitam Elegan', 'Kraft Paper (Rustic)'];
$ribbons = ['Satin Pink', 'Satin Gold', 'Satin Purple', 'Satin White'];

// Proses tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Proteksi: Harus login
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_url'] = 'custom_bouquet.php';
        header("Location: login.php");
        exit;
    }

    $selected_size = trim($_POST['size'] ?? 'small');
    $selected_flowers = $_POST['flowers'] ?? [];
    $selected_wrap = trim($_POST['wrap'] ?? '');
    $selected_ribbon = trim($_POST['ribbon'] ?? '');
    $use_card = isset($_POST['use_card']) ? 1 : 0;
    $card_msg = trim($_POST['card_msg'] ?? '');

    // Validasi input
    if (!array_key_exists($selected_size, $sizes)) {
        $error_msg = "Ukuran buket tidak valid.";
    } elseif (!in_array($selected_wrap, $wrapping_papers)) {
        $error_msg = "Kertas pembungkus tidak valid.";
    } elseif (!in_array($selected_ribbon, $ribbons)) {
        $error_msg = "Pita tidak valid.";
    } else {
        // Hitung total harga
        $total_harga = $sizes[$selected_size]['harga'];
        $komposisi_bunga = [];
        $total_tangkai = 0;

        foreach ($flowers as $key => $flower) {
            $qty = isset($selected_flowers[$key]) ? intval($selected_flowers[$key]) : 0;
            if ($qty > 0) {
                $subtotal = $qty * $flower['harga'];
                $total_harga += $subtotal;
                $total_tangkai += $qty;
                $komposisi_bunga[] = "- {$flower['nama']}: {$qty} tangkai (Rp " . number_format($subtotal, 0, ',', '.') . ")";
            }
        }

        // Validasi batasan kuantitas berdasarkan ukuran buket
        if ($selected_size === 'small' && $total_tangkai > 10) {
            $error_msg = "Ukuran Small hanya memuat maksimal 10 tangkai bunga. Total Anda saat ini: $total_tangkai tangkai.";
        } elseif ($selected_size === 'medium' && $total_tangkai > 20) {
            $error_msg = "Ukuran Medium hanya memuat maksimal 20 tangkai bunga. Total Anda saat ini: $total_tangkai tangkai.";
        } elseif ($selected_size === 'large' && $total_tangkai > 35) {
            $error_msg = "Ukuran Large hanya memuat maksimal 35 tangkai bunga. Total Anda saat ini: $total_tangkai tangkai.";
        } elseif ($total_tangkai === 0) {
            $error_msg = "Harap masukkan minimal 1 tangkai bunga untuk membuat buket custom.";
        } else {
            if ($use_card) {
                $total_harga += 5000;
            }

            // Gabung deskripsi buket
            $desc_lines = [];
            $desc_lines[] = "Buket Custom Premium. Rincian Komposisi:";
            $desc_lines[] = "- Ukuran: " . $sizes[$selected_size]['nama'] . " (Rp " . number_format($sizes[$selected_size]['harga'], 0, ',', '.') . ")";
            $desc_lines[] = "Bunga Pilihan:";
            $desc_lines = array_merge($desc_lines, $komposisi_bunga);
            $desc_lines[] = "- Kertas Bungkus: $selected_wrap";
            $desc_lines[] = "- Pita: $selected_ribbon";
            $desc_lines[] = "- Kartu Ucapan: " . ($use_card ? "\"$card_msg\" (+Rp 5.000)" : "Tidak ada");

            $deskripsi_lengkap = implode("\n", $desc_lines);

            try {
                $db->beginTransaction();

                // Tambahkan buket custom sebagai produk baru temporer di database
                $stmt = $db->prepare("INSERT INTO produk (nama_produk, kategori, harga, stok, deskripsi, gambar) 
                                      VALUES (?, 'Custom', ?, 1, ?, 'custom_bouquet.jpg')");
                $nama_produk_kustom = "Buket Custom - " . $_SESSION['nama'];
                $stmt->execute([$nama_produk_kustom, $total_harga, $deskripsi_lengkap]);
                $new_id = $db->lastInsertId();

                $db->commit();

                // Tambahkan ID produk kustom tersebut ke session keranjang
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                $_SESSION['cart'][$new_id] = 1;

                $_SESSION['cart_success_msg'] = "Buket custom berhasil didesain dan ditambahkan ke keranjang belanja.";
                header("Location: cart.php");
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                $error_msg = "Gagal memproses pembuatan buket custom: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buket Bunga Custom | Fleurist</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .custom-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: var(--spacing-xl);
            align-items: start;
        }
        .form-section-title {
            color: var(--primary);
            font-size: 16px;
            font-weight: 700;
            border-bottom: 2px solid var(--canvas-lavender);
            padding-bottom: 8px;
            margin-bottom: var(--spacing-lg);
            margin-top: var(--spacing-xl);
        }
        .form-section-title:first-of-type {
            margin-top: 0;
        }
        .size-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: var(--spacing-lg);
        }
        .size-card {
            border: 1px solid var(--hairline);
            border-radius: var(--rounded-md);
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        .size-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        .size-card.selected {
            border-color: var(--primary);
            background-color: var(--canvas-lavender);
            box-shadow: 0 0 0 1px var(--primary);
        }
        .size-card-title {
            font-weight: 700;
            font-size: 14px;
            color: var(--ink);
        }
        .size-card-desc {
            font-size: 12px;
            color: var(--ink-mute);
            margin-top: 4px;
        }
        .size-card-price {
            font-weight: 700;
            color: var(--primary);
            font-size: 14px;
            margin-top: 8px;
        }
        .flower-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--hairline);
        }
        .flower-info {
            display: flex;
            flex-direction: column;
        }
        .flower-name {
            font-weight: 600;
            font-size: 14px;
        }
        .flower-price {
            font-size: 12px;
            color: var(--ink-mute);
        }
        .flower-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .qty-btn {
            background-color: var(--canvas-cream);
            border: none;
            border-radius: var(--rounded-sm);
            width: 32px;
            height: 32px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .qty-btn:hover {
            background-color: #e2d7cb;
        }
        .qty-input {
            width: 44px;
            text-align: center;
            font-weight: 700;
            border: 1px solid var(--hairline);
            border-radius: var(--rounded-sm);
            padding: 4px;
        }
        .summary-card {
            border: 2px solid var(--primary);
            position: sticky;
            top: 100px;
        }
        
        /* Preset Option Card Styling */
        .presets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .preset-card {
            background-color: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--rounded-lg);
            overflow: hidden;
            box-shadow: rgba(0, 0, 0, 0.03) 0 4px 12px 0;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            border-bottom: 4px solid var(--hairline);
        }
        .preset-card:hover {
            transform: translateY(-4px);
            box-shadow: rgba(74, 21, 75, 0.1) 0 10px 25px 0;
            border-bottom-color: var(--primary);
        }
        .preset-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-bottom: 1px solid var(--hairline);
        }
        .preset-content {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .preset-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 6px;
        }
        .preset-desc {
            font-size: 12px;
            color: var(--ink-mute);
            margin-bottom: 12px;
            line-height: 1.4;
            flex: 1;
        }
        .preset-details {
            font-size: 11px;
            background-color: var(--canvas-lavender);
            padding: 8px 12px;
            border-radius: var(--rounded-sm);
            margin-bottom: 14px;
            line-height: 1.5;
            color: var(--primary);
        }
        .btn-use-preset {
            width: 100%;
            background-color: var(--canvas-lavender);
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            padding: 8px 0;
            border-radius: var(--rounded-pill);
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-use-preset:hover {
            background-color: var(--primary);
            color: var(--on-primary);
        }
    </style>
</head>
<body style="background-color: var(--canvas-cream);">

    <!-- Header / Navbar -->
    <?php include 'header.php'; ?>

    <main class="admin-container" style="padding-top: 40px; min-height: 80vh;">
        <!-- Page Title -->
        <div class="admin-header" style="margin-bottom: var(--spacing-xl);">
            <div>
                <h1 class="display-md" style="color: var(--primary);">Desain Buket Kustom Anda</h1>
                <p class="caption">Rangkai buket bunga impian Anda sendiri dan sesuaikan dengan keinginan pribadi.</p>
            </div>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger" style="margin-bottom: var(--spacing-xl);"><?= $error_msg ?></div>
        <?php endif; ?>

        <!-- Section Inspirasi Buket -->
        <div class="admin-card" style="padding: 28px; margin-bottom: 40px; border-left: 4px solid var(--primary);">
            <h2 class="heading-sm" style="color: var(--primary); margin-bottom: 6px;">Bingung Rangkai Bunga? Gunakan Inspirasi Buket Populer Kami</h2>
            <p class="caption" style="margin-bottom: var(--spacing-xl);">Klik tombol "Gunakan Desain Ini" pada salah satu pilihan di bawah untuk mengisi form custom secara otomatis. Anda masih bisa mengedit komposisinya!</p>
            
            <div class="presets-grid">
                <!-- Preset 1: Romance Red -->
                <div class="preset-card">
                    <img src="uploads/preset_romance.png" alt="Romance Red Bouquet" class="preset-img">
                    <div class="preset-content">
                        <h3 class="preset-title">Buket Romance Red</h3>
                        <p class="preset-desc">Kombinasi klasik mawar merah romantis melambangkan cinta mendalam. Cocok untuk hadiah anniversary atau hari kasih sayang.</p>
                        <div class="preset-details">
                            <strong>Komposisi:</strong> Medium size, 12 Mawar Merah, 3 Baby's Breath, 2 Eucalyptus. Bungkus Pink, Pita Satin Pink.
                        </div>
                        <button type="button" class="btn-use-preset" onclick="loadPreset('romance')">Gunakan Desain Ini</button>
                    </div>
                </div>

                <!-- Preset 2: Elegant Lily -->
                <div class="preset-card">
                    <img src="uploads/preset_lily.png" alt="Elegant Lily Bouquet" class="preset-img">
                    <div class="preset-content">
                        <h3 class="preset-title">Buket Elegant Lily</h3>
                        <p class="preset-desc">Rangkaian lily putih elegan dengan aroma menenangkan, melambangkan keanggunan, ketulusan, dan harapan murni.</p>
                        <div class="preset-details">
                            <strong>Komposisi:</strong> Medium size, 6 Lily Putih, 4 Baby's Breath, 4 Eucalyptus. Bungkus Putih, Pita Satin Gold.
                        </div>
                        <button type="button" class="btn-use-preset" onclick="loadPreset('lily')">Gunakan Desain Ini</button>
                    </div>
                </div>

                <!-- Preset 3: Sunshine Tulip -->
                <div class="preset-card">
                    <img src="uploads/preset_tulip.png" alt="Sunshine Tulip Bouquet" class="preset-img">
                    <div class="preset-content">
                        <h3 class="preset-title">Buket Sunshine Tulip</h3>
                        <p class="preset-desc">Bunga tulip kuning cerah dipadukan pembungkus kertas kraft coklat rustic untuk keceriaan dan kehangatan.</p>
                        <div class="preset-details">
                            <strong>Komposisi:</strong> Small size, 8 Tulip Kuning, 2 Baby's Breath. Bungkus Kraft (Rustic), Pita Satin Gold.
                        </div>
                        <button type="button" class="btn-use-preset" onclick="loadPreset('tulip')">Gunakan Desain Ini</button>
                    </div>
                </div>

                <!-- Preset 4: Luxurious Mixed -->
                <div class="preset-card">
                    <img src="uploads/preset_mixed.png" alt="Luxurious Mixed Bouquet" class="preset-img">
                    <div class="preset-content">
                        <h3 class="preset-title">Buket Luxurious Mixed</h3>
                        <p class="preset-desc">Rangkaian kombinasi mawar merah dan lily putih ukuran besar yang megah untuk kesan mewah yang mendalam.</p>
                        <div class="preset-details">
                            <strong>Komposisi:</strong> Large size, 15 Mawar Merah, 8 Lily Putih, 8 Baby's Breath, 4 Eucalyptus. Bungkus Hitam, Pita Satin Gold.
                        </div>
                        <button type="button" class="btn-use-preset" onclick="loadPreset('mixed')">Gunakan Desain Ini</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Pembuatan Buket Custom -->
        <form action="" method="POST" id="custom-bouquet-form">
            <div class="custom-grid">
                
                <!-- Kiri: Pilihan Komponen Buket -->
                <div class="admin-card" style="padding: 28px;">
                    
                    <!-- 1. Pilih Ukuran Buket -->
                    <div class="form-section-title">1. Pilih Ukuran Rangkaian Buket</div>
                    <div class="size-options">
                        <?php foreach ($sizes as $key => $size): ?>
                            <label class="size-card <?= $key === 'small' ? 'selected' : '' ?>" id="label-size-<?= $key ?>">
                                <input type="radio" name="size" value="<?= $key ?>" <?= $key === 'small' ? 'checked' : '' ?> onchange="selectSize('<?= $key ?>')">
                                <div class="size-card-title"><?= ucfirst($key) ?></div>
                                <div class="size-card-desc"><?= $size['nama'] ?></div>
                                <div class="size-card-price">Rp <?= number_format($size['harga'], 0, ',', '.') ?></div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- 2. Tambah Bunga (Tangkai) -->
                    <div class="form-section-title">2. Pilih & Atur Bunga (Per Tangkai)</div>
                    <div style="margin-bottom: var(--spacing-lg);">
                        <?php foreach ($flowers as $key => $flower): ?>
                            <div class="flower-item">
                                <div class="flower-info">
                                    <span class="flower-name"><?= $flower['nama'] ?></span>
                                    <span class="flower-price">Rp <?= number_format($flower['harga'], 0, ',', '.') ?> / tangkai</span>
                                </div>
                                <div class="flower-control">
                                    <button type="button" class="qty-btn" onclick="adjustFlower('<?= $key ?>', -1)">-</button>
                                    <input type="number" name="flowers[<?= $key ?>]" id="qty-<?= $key ?>" class="qty-input" value="0" min="0" max="35" readonly onchange="updateSummary()">
                                    <button type="button" class="qty-btn" onclick="adjustFlower('<?= $key ?>', 1)">+</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- 3. Pilih Kertas & Pita -->
                    <div class="form-section-title">3. Warna Kertas Pembungkus & Pita</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
                        <div class="form-group">
                            <label for="wrap" class="form-label">Kertas Pembungkus (Wrapping Paper)</label>
                            <select name="wrap" id="wrap" class="text-input" style="appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22><path fill=%22%231d1d1d%22 d=%22M7 10l5 5 5-5z%22/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;" onchange="updateSummary()" required>
                                <?php foreach ($wrapping_papers as $paper): ?>
                                    <option value="<?= htmlspecialchars($paper) ?>"><?= htmlspecialchars($paper) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ribbon" class="form-label">Warna Pita (Ribbon)</label>
                            <select name="ribbon" id="ribbon" class="text-input" style="appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22><path fill=%22%231d1d1d%22 d=%22M7 10l5 5 5-5z%22/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;" onchange="updateSummary()" required>
                                <?php foreach ($ribbons as $ribbon): ?>
                                    <option value="<?= htmlspecialchars($ribbon) ?>"><?= htmlspecialchars($ribbon) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- 4. Kartu Ucapan -->
                    <div class="form-section-title">4. Tambahkan Kartu Ucapan</div>
                    <div style="margin-bottom: var(--spacing-sm);">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 14px; cursor: pointer; margin-bottom: 12px;">
                            <input type="checkbox" name="use_card" id="use_card" value="1" onchange="toggleCardMsg(); updateSummary();" style="width: 18px; height: 18px; accent-color: var(--primary);">
                            Tambah Kartu Ucapan (+ Rp 5.000)
                        </label>
                        <div id="card_msg_container" style="display: none;">
                            <textarea name="card_msg" id="card_msg" class="text-input" rows="3" placeholder="Tuliskan pesan ucapan Anda (Maksimal 150 karakter)..." maxlength="150" style="resize: vertical;" oninput="updateSummary()"></textarea>
                        </div>
                    </div>

                </div>

                <!-- Kanan: Ringkasan & Total -->
                <div class="admin-card summary-card" style="padding: 28px;">
                    <h3 class="heading-sm" style="color: var(--primary); margin-bottom: var(--spacing-md); text-align: center;">Ringkasan Komposisi</h3>
                    
                    <div style="max-height: 280px; overflow-y: auto; margin-bottom: var(--spacing-lg); border-bottom: 1px solid var(--hairline); padding-bottom: 16px; font-size: 14px;">
                        <!-- Ukuran -->
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span id="summary-size-name" style="font-weight: 600;">Small Bouquet</span>
                            <span id="summary-size-price">Rp 50.000</span>
                        </div>
                        
                        <!-- List Bunga Terpilih -->
                        <div id="summary-flowers-container" style="color: var(--ink-mute); font-size: 13px; line-height: 1.6; margin-bottom: 12px;">
                            <!-- Dinamis via JS -->
                        </div>

                        <!-- Aksesoris -->
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; color: var(--ink-mute);">
                            <span>Kertas Pembungkus:</span>
                            <strong id="summary-wrap-name" style="color: var(--ink);">Pink Pastel</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; color: var(--ink-mute);">
                            <span>Warna Pita:</span>
                            <strong id="summary-ribbon-name" style="color: var(--ink);">Satin Pink</strong>
                        </div>
                        <div id="summary-card-row" style="display: none; justify-content: space-between; margin-bottom: 6px; font-size: 13px; color: var(--ink-mute);">
                            <span>Kartu Ucapan:</span>
                            <strong style="color: var(--ink);">Rp 5.000</strong>
                        </div>
                    </div>

                    <!-- Total Harga -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
                        <span style="font-weight: 700; font-size: 15px;">Estimasi Total:</span>
                        <strong style="color: var(--primary); font-size: 22px;" id="grand-total-display">Rp 50.000</strong>
                    </div>

                    <!-- Tombol Keranjang -->
                    <button type="submit" class="btn btn-primary-pill" style="width: 100%; padding: 14px 0; font-size: 16px;">
                        Masukkan Rangkaian ke Keranjang
                    </button>
                    
                    <p class="caption" style="text-align: center; margin-top: 12px; font-size: 12px; line-height: 1.4;">
                        Buket custom memerlukan waktu perangkaian sekitar 1-2 jam sebelum siap dikirim oleh kurir.
                    </p>
                </div>

            </div>
        </form>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script>
        // Data data harga dari PHP ke JS
        const sizesData = <?= json_encode($sizes) ?>;
        const flowersData = <?= json_encode($flowers) ?>;
        
        let currentSize = 'small';

        function selectSize(sizeKey) {
            currentSize = sizeKey;
            
            // Hapus kelas selected dari semua kartu ukuran
            document.querySelectorAll('.size-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Tambahkan kelas ke kartu terpilih
            document.getElementById('label-size-' + sizeKey).classList.add('selected');
            
            // Update summary
            updateSummary();
        }

        function adjustFlower(flowerKey, amount) {
            const input = document.getElementById('qty-' + flowerKey);
            let currentVal = parseInt(input.value) || 0;
            currentVal += amount;
            
            if (currentVal < 0) currentVal = 0;
            
            // Batasan per-item max
            if (currentVal > 35) currentVal = 35;
            
            input.value = currentVal;
            
            updateSummary();
        }

        function toggleCardMsg() {
            const useCardCheckbox = document.getElementById('use_card');
            const msgContainer = document.getElementById('card_msg_container');
            
            if (useCardCheckbox.checked) {
                msgContainer.style.display = 'block';
            } else {
                msgContainer.style.display = 'none';
                document.getElementById('card_msg').value = '';
            }
        }

        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(number).replace("Rp", "Rp ");
        }

        function updateSummary() {
            // 1. Ukuran Rangkaian
            const sizeNameDisplay = document.getElementById('summary-size-name');
            const sizePriceDisplay = document.getElementById('summary-size-price');
            
            const sizeInfo = sizesData[currentSize];
            sizeNameDisplay.textContent = "Ukuran: " + currentSize.charAt(0).toUpperCase() + currentSize.slice(1);
            sizePriceDisplay.textContent = formatRupiah(sizeInfo.harga);
            
            let totalHarga = sizeInfo.harga;
            let totalTangkai = 0;
            
            // 2. Bunga-bunga
            const flowersContainer = document.getElementById('summary-flowers-container');
            flowersContainer.innerHTML = '';
            
            Object.keys(flowersData).forEach(key => {
                const qty = parseInt(document.getElementById('qty-' + key).value) || 0;
                if (qty > 0) {
                    const flowerInfo = flowersData[key];
                    const subtotal = qty * flowerInfo.harga;
                    totalHarga += subtotal;
                    totalTangkai += qty;
                    
                    const p = document.createElement('div');
                    p.style.display = 'flex';
                    p.style.justify = 'space-between';
                    p.style.marginBottom = '4px';
                    p.innerHTML = `<span>• ${flowerInfo.nama} (x${qty})</span><span>${formatRupiah(subtotal)}</span>`;
                    flowersContainer.appendChild(p);
                }
            });
            
            if (flowersContainer.children.length === 0) {
                flowersContainer.innerHTML = '<span style="font-style: italic; color: #a0a0a0;">Belum ada bunga yang dipilih</span>';
            }

            // 3. Wrapping & Ribbon
            const wrapSelect = document.getElementById('wrap');
            document.getElementById('summary-wrap-name').textContent = wrapSelect.value;

            const ribbonSelect = document.getElementById('ribbon');
            document.getElementById('summary-ribbon-name').textContent = ribbonSelect.value;

            // 4. Kartu Ucapan
            const useCard = document.getElementById('use_card').checked;
            const cardRow = document.getElementById('summary-card-row');
            
            if (useCard) {
                cardRow.style.display = 'flex';
                totalHarga += 5000;
            } else {
                cardRow.style.display = 'none';
            }

            // 5. Grand Total Display
            document.getElementById('grand-total-display').textContent = formatRupiah(totalHarga);
        }

        // Data Preset untuk pengisian form otomatis
        const presets = {
            romance: {
                size: 'medium',
                flowers: {
                    mawar: 12,
                    lily: 0,
                    tulip: 0,
                    babys_breath: 3,
                    eucalyptus: 2
                },
                wrap: 'Pink Pastel',
                ribbon: 'Satin Pink'
            },
            lily: {
                size: 'medium',
                flowers: {
                    mawar: 0,
                    lily: 6,
                    tulip: 0,
                    babys_breath: 4,
                    eucalyptus: 4
                },
                wrap: 'Putih Klasik',
                ribbon: 'Satin Gold'
            },
            tulip: {
                size: 'small',
                flowers: {
                    mawar: 0,
                    lily: 0,
                    tulip: 8,
                    babys_breath: 2,
                    eucalyptus: 0
                },
                wrap: 'Kraft Paper (Rustic)',
                ribbon: 'Satin Gold'
            },
            mixed: {
                size: 'large',
                flowers: {
                    mawar: 15,
                    lily: 8,
                    tulip: 0,
                    babys_breath: 8,
                    eucalyptus: 4
                },
                wrap: 'Hitam Elegan',
                ribbon: 'Satin Gold'
            }
        };

        function loadPreset(presetKey) {
            const data = presets[presetKey];
            if (!data) return;

            // 1. Set Size
            const sizeRadio = document.querySelector(`input[name="size"][value="${data.size}"]`);
            if (sizeRadio) {
                sizeRadio.checked = true;
                selectSize(data.size);
            }

            // 2. Set Flowers
            Object.keys(flowersData).forEach(key => {
                const qtyInput = document.getElementById('qty-' + key);
                if (qtyInput) {
                    qtyInput.value = data.flowers[key] !== undefined ? data.flowers[key] : 0;
                }
            });

            // 3. Set Wrapping Paper
            const wrapSelect = document.getElementById('wrap');
            if (wrapSelect) {
                wrapSelect.value = data.wrap;
            }

            // 4. Set Ribbon
            const ribbonSelect = document.getElementById('ribbon');
            if (ribbonSelect) {
                ribbonSelect.value = data.ribbon;
            }

            // Update the display summary
            updateSummary();

            // Smooth scroll to the form
            const formElement = document.getElementById('custom-bouquet-form');
            if (formElement) {
                window.scrollTo({
                    top: formElement.offsetTop - 100,
                    behavior: 'smooth'
                });
            }

            // Flash visual effect to highlight the form
            const formCard = document.querySelector('.custom-grid .admin-card');
            if (formCard) {
                formCard.style.transition = 'outline 0.3s ease';
                formCard.style.outline = '3px solid var(--primary)';
                setTimeout(() => {
                    formCard.style.outline = '3px solid transparent';
                }, 1000);
            }
        }

        // Jalankan kalkulator pertama kali saat halaman dimuat
        window.onload = function() {
            updateSummary();
        };
    </script>
</body>
</html>
