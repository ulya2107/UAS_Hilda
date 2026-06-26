<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Dapatkan nama file saat ini untuk menentukan link mana yang aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="admin-navbar">
    <div class="admin-logo">
        <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
        </svg>
        <span>Fleurist Admin</span>
    </div>
    
    <div class="admin-nav-links">
        <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="produk.php" class="<?= $current_page === 'produk.php' ? 'active' : '' ?>">Kelola Produk</a>
        <a href="user.php" class="<?= $current_page === 'user.php' ? 'active' : '' ?>">Kelola User</a>
        <a href="pesanan.php" class="<?= $current_page === 'pesanan.php' ? 'active' : '' ?>">Kelola Pesanan</a>
        <a href="laporan.php" class="<?= $current_page === 'laporan.php' ? 'active' : '' ?>">Laporan Penjualan</a>
    </div>
    
    <div class="user-profile-badge">
        <div class="avatar">
            <?= strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)) ?>
        </div>
        <span style="font-size: 14px; font-weight: 600; margin-right: 12px;"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></span>
        <a href="../logout.php" style="color: var(--on-aubergine-mute); font-size: 13px; font-weight: 600;">Keluar</a>
    </div>
</nav>
