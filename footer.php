<footer class="footer-aubergine">
    <div class="footer-content">
        <!-- Brand Column -->
        <div class="footer-column" style="grid-column: span 2;">
            <a href="index.php" style="display: flex; align-items: center; gap: 8px; font-size: 24px; font-weight: 700; color: var(--on-primary); text-decoration: none; margin-bottom: var(--spacing-md);">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="var(--on-primary)" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                <span>Fleurist</span>
            </a>
            <p style="color: var(--on-aubergine-mute); font-size: 14px; line-height: 1.6; max-width: 320px;">
                Kami menghadirkan rangkaian bunga segar kualitas premium untuk mewarnai hari-hari istimewa Anda. Layanan profesional dengan pengiriman cepat dan aman.
            </p>
        </div>

        <!-- Links Column 1 -->
        <div class="footer-column">
            <h3>Koleksi Bunga</h3>
            <ul>
                <li><a href="index.php?kategori=Mawar">Mawar Klasik</a></li>
                <li><a href="index.php?kategori=Lily">Lily Elegan</a></li>
                <li><a href="index.php?kategori=Tulip">Tulip Mewah</a></li>
                <li><a href="index.php?kategori=Buket">Buket Spesial</a></li>
            </ul>
        </div>

        <!-- Links Column 2 -->
        <div class="footer-column">
            <h3>Bantuan</h3>
            <ul>
                <li><a href="#">Cara Pemesanan</a></li>
                <li><a href="#">Kebijakan Pengembalian</a></li>
                <li><a href="#">Lacak Pengiriman</a></li>
                <li><a href="#">Hubungi Kami</a></li>
            </ul>
        </div>

        <!-- Contact Column -->
        <div class="footer-column">
            <h3>Hubungi Kami</h3>
            <p style="color: var(--on-aubergine-mute); font-size: 14px; margin-bottom: 8px;">Email: hello@fleurist.com</p>
            <p style="color: var(--on-aubergine-mute); font-size: 14px; margin-bottom: 8px;">Telepon: (021) 8888-9999</p>
            <p style="color: var(--on-aubergine-mute); font-size: 14px;">Alamat: Jl. Bunga Indah No. 45, Jakarta</p>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div>
            &copy; <?= date('Y') ?> Fleurist Toko Bunga Segar. Hak Cipta Dilindungi.
        </div>
        <div style="display: flex; gap: var(--spacing-lg);">
            <a href="#" style="color: var(--on-aubergine-mute); font-size: 13px;">Kebijakan Privasi</a>
            <a href="#" style="color: var(--on-aubergine-mute); font-size: 13px;">Syarat & Ketentuan</a>
        </div>
    </div>
</footer>
