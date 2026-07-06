<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hitung total item unik di keranjang
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<header style="background-color: var(--canvas); border-bottom: 1px solid var(--hairline); position: sticky; top: 0; z-index: 1000;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <!-- Logo -->
        <a href="index.php" style="display: flex; align-items: center; gap: 8px; font-size: 24px; font-weight: 700; color: var(--primary); text-decoration: none;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="var(--primary)" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
            </svg>
            <span>Fleurist</span>
        </a>

        <!-- Navigation Links -->
        <div style="display: flex; gap: var(--spacing-xl); align-items: center;">
            <a href="index.php" style="color: <?= $current_page === 'index.php' || $current_page === 'detail.php' ? 'var(--primary)' : 'var(--ink)' ?>; font-weight: 600; font-size: 15px; text-decoration: none;">Katalog Bunga</a>
            <a href="custom_bouquet.php" style="color: <?= $current_page === 'custom_bouquet.php' ? 'var(--primary)' : 'var(--ink)' ?>; font-weight: 600; font-size: 15px; text-decoration: none;">Buket Custom</a>
            <a href="riwayat.php" style="color: <?= $current_page === 'riwayat.php' ? 'var(--primary)' : 'var(--ink)' ?>; font-weight: 600; font-size: 15px; text-decoration: none;">Riwayat Belanja</a>
            
            <!-- Cart Icon with Badge -->
            <a href="cart.php" style="position: relative; display: flex; align-items: center; text-decoration: none; color: var(--ink); margin-left: 8px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shopping-cart">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <?php if ($cart_count > 0): ?>
                    <span style="position: absolute; top: -8px; right: -12px; background-color: var(--primary); color: #fff; border-radius: 50%; font-size: 10px; font-weight: 700; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Account Section -->
        <div style="display: flex; gap: var(--spacing-md); align-items: center;">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Logged In -->
                <div class="user-profile-badge">
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="btn btn-secondary-pill" style="padding: 8px 18px; font-size: 13px; margin-right: 8px;">Panel Admin</a>
                    <?php endif; ?>
                    <div class="avatar">
                        <?= strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span style="font-size: 14px; font-weight: 600; color: var(--ink);"><?= htmlspecialchars(explode(' ', $_SESSION['nama'])[0]) ?></span>
                    <a href="logout.php" style="font-size: 13px; font-weight: 600; color: var(--ink-mute); margin-left: 8px;">Keluar</a>
                </div>
            <?php else: ?>
                <!-- Guest -->
                <a href="login.php" class="btn btn-secondary-pill" style="padding: 8px 20px; font-size: 14px;">Masuk</a>
                <a href="register.php" class="btn btn-primary-pill" style="padding: 8px 20px; font-size: 14px;">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</header>
