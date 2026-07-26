<?php
require_once '../koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$action = $_GET['action'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Inisialisasi summary
$total_laporan_pendapatan = 0;
$total_laporan_orders = 0;
$average_order_value = 0;
$completed_orders = [];

$error = '';
try {
    // Query list pesanan berstatus sukses/selesai dalam rentang tanggal
    // Kita anggap sukses/selesai = status IN ('paid', 'processed', 'shipped', 'completed')
    $stmt = $db->prepare("SELECT orders.*, users.nama FROM orders 
                        JOIN users ON orders.id_user = users.id 
                        WHERE DATE(orders.tanggal) BETWEEN ? AND ? 
                        AND orders.status IN ('paid', 'processed', 'shipped', 'completed') 
                        ORDER BY orders.tanggal DESC");
    $stmt->execute([$start_date, $end_date]);
    $completed_orders = $stmt->fetchAll();

    // Hitung ringkasan
    $total_laporan_orders = count($completed_orders);
    foreach ($completed_orders as $o) {
        $total_laporan_pendapatan += $o['total_harga'];
    }

    if ($total_laporan_orders > 0) {
        $average_order_value = $total_laporan_pendapatan / $total_laporan_orders;
    }
} catch (PDOException $e) {
    $error = "Gagal memuat laporan penjualan: " . $e->getMessage();
}

// ----------------------------------------------------
// JIKA AKSES EKSPOR EXCEL
// ----------------------------------------------------
if ($action === 'excel') {
    $filename = "laporan_penjualan_" . $start_date . "_to_" . $end_date . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=" . $filename);
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Output UTF-8 BOM agar Excel bisa membaca karakter dengan benar
    echo "\xEF\xBB\xBF";
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            .title {
                font-size: 16pt;
                font-weight: bold;
                text-align: center;
            }
            .subtitle {
                font-size: 11pt;
                text-align: center;
                color: #555;
            }
            .stats-table {
                margin-bottom: 20px;
                border: 1px solid #ccc;
            }
            .stats-table th {
                background-color: #f2f2f2;
                font-weight: bold;
                text-align: left;
                padding: 5px;
            }
            .stats-table td {
                padding: 5px;
            }
            .data-table {
                border-collapse: collapse;
                width: 100%;
            }
            .data-table th {
                background-color: #4a154b;
                color: #ffffff;
                font-weight: bold;
                border: 1px solid #000000;
                padding: 8px;
            }
            .data-table td {
                border: 1px solid #000000;
                padding: 8px;
            }
            .text-center {
                text-align: center;
            }
            .text-right {
                text-align: right;
            }
        </style>
    </head>
    <body>
        <table>
            <tr>
                <td colspan="5" class="title">LAPORAN PENJUALAN FLEURIST TOKO BUNGA</td>
            </tr>
            <tr>
                <td colspan="5" class="subtitle">Periode: <?= date('d M Y', strtotime($start_date)) ?> s/d <?= date('d M Y', strtotime($end_date)) ?></td>
            </tr>
            <tr>
                <td colspan="5" class="subtitle">Dicetak oleh: <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?> pada <?= date('d M Y, H:i') ?></td>
            </tr>
            <tr>
                <td colspan="5"></td>
            </tr>
        </table>

        <!-- Ringkasan Statistik -->
        <table border="1" class="stats-table">
            <tr>
                <th colspan="2" style="background-color: #e6e6e6;">RINGKASAN LAPORAN</th>
            </tr>
            <tr>
                <td><strong>Omset Bersih (IDR)</strong></td>
                <td><?= $total_laporan_pendapatan ?></td>
            </tr>
            <tr>
                <td><strong>Volume Transaksi Sukses</strong></td>
                <td><?= $total_laporan_orders ?></td>
            </tr>
            <tr>
                <td><strong>Rata-Rata Transaksi (AOV) (IDR)</strong></td>
                <td><?= round($average_order_value) ?></td>
            </tr>
        </table>

        <br>

        <!-- Detail Laporan -->
        <table border="1" class="data-table">
            <thead>
                <tr>
                    <th style="background-color: #4a154b; color: #ffffff; border: 1px solid #000000;">ID Order</th>
                    <th style="background-color: #4a154b; color: #ffffff; border: 1px solid #000000;">Pelanggan</th>
                    <th style="background-color: #4a154b; color: #ffffff; border: 1px solid #000000;">Tanggal Transaksi</th>
                    <th style="background-color: #4a154b; color: #ffffff; border: 1px solid #000000;">Total Penjualan (IDR)</th>
                    <th style="background-color: #4a154b; color: #ffffff; border: 1px solid #000000;">Status Pesanan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($completed_orders)): ?>
                    <tr>
                        <td colspan="5" class="text-center" style="border: 1px solid #000000;">Tidak ada penjualan pada rentang tanggal terpilih.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($completed_orders as $order): ?>
                        <tr>
                            <td class="text-center" style="border: 1px solid #000000;">#<?= htmlspecialchars($order['id_order']) ?></td>
                            <td style="border: 1px solid #000000;"><?= htmlspecialchars($order['nama']) ?></td>
                            <td class="text-center" style="border: 1px solid #000000;"><?= date('d M Y, H:i', strtotime($order['tanggal'])) ?></td>
                            <td class="text-right" style="border: 1px solid #000000;"><?= $order['total_harga'] ?></td>
                            <td class="text-center" style="text-transform: capitalize; border: 1px solid #000000;"><?= htmlspecialchars($order['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// ----------------------------------------------------
// JIKA AKSES CETAK PDF (BROWSER PRINT)
// ----------------------------------------------------
if ($action === 'print') {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cetak Laporan Penjualan (<?= htmlspecialchars($start_date) ?> - <?= htmlspecialchars($end_date) ?>)</title>
        <link rel="stylesheet" href="../style.css">
        <style>
            body {
                background-color: #fff;
                color: #000;
                font-size: 14px;
                padding: 20px;
            }
            .print-container {
                max-width: 900px;
                margin: 0 auto;
                background: #fff;
                padding: 20px;
            }
            .print-header {
                text-align: center;
                border-bottom: 3px double #000;
                padding-bottom: 15px;
                margin-bottom: 30px;
            }
            .print-header h1 {
                font-size: 26px;
                margin-bottom: 5px;
                text-transform: uppercase;
                color: #4a154b;
            }
            .print-header p {
                font-size: 14px;
                color: #555;
                margin: 2px 0;
            }
            .print-metadata {
                margin-bottom: 25px;
                font-size: 13px;
            }
            .print-metadata table {
                width: 100%;
                border: none;
            }
            .print-metadata td {
                padding: 4px 0;
                border: none;
            }
            .stats-box-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
                margin-bottom: 30px;
            }
            .stat-box {
                border: 1px solid #ccc;
                border-radius: 6px;
                padding: 15px;
                text-align: center;
                background-color: #fafafa;
            }
            .stat-box-title {
                font-size: 12px;
                color: #666;
                text-transform: uppercase;
                margin-bottom: 5px;
                font-weight: 600;
            }
            .stat-box-value {
                font-size: 18px;
                font-weight: 700;
                color: #000;
            }
            .report-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
                margin-bottom: 40px;
            }
            .report-table th, .report-table td {
                border: 1px solid #ddd;
                padding: 10px 12px;
                text-align: left;
            }
            .report-table th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            .report-table tr:nth-child(even) {
                background-color: #fafafa;
            }
            .signature-section {
                margin-top: 50px;
                display: flex;
                justify-content: flex-end;
            }
            .signature-box {
                text-align: center;
                width: 200px;
            }
            .signature-space {
                height: 75px;
            }
            .print-actions {
                margin-bottom: 20px;
                text-align: right;
            }
            .btn-print {
                background-color: #4a154b;
                color: #fff;
                padding: 10px 20px;
                font-weight: bold;
                border-radius: 4px;
                text-decoration: none;
                display: inline-block;
                border: none;
                cursor: pointer;
                box-shadow: 0 2px 5px rgba(0,0,0,0.15);
            }
            .btn-print:hover {
                background-color: #310b32;
            }
            @media print {
                body {
                    padding: 0;
                }
                .print-actions {
                    display: none;
                }
                .print-container {
                    max-width: 100%;
                    padding: 0;
                }
                .stat-box {
                    background-color: #fff !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .report-table th {
                    background-color: #f2f2f2 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
    </head>
    <body>
        <div class="print-actions">
            <button onclick="window.print();" class="btn-print">Cetak Laporan</button>
            <button onclick="window.close();" class="btn-print" style="background-color: #666; margin-left: 8px;">Tutup Halaman</button>
        </div>

        <div class="print-container">
            <div class="print-header">
                <h1>FLEURIST TOKO BUNGA</h1>
                <p>Ruko Elegant Flower, Jl. Mawar Indah No. 45, Jakarta</p>
                <p>Telepon: (021) 555-1234 | Email: info@fleurist.com</p>
            </div>

            <div style="text-align: center; margin-bottom: 25px;">
                <h2>LAPORAN PENJUALAN</h2>
                <p style="margin-top: 5px; color: #555;">Periode: <strong><?= date('d M Y', strtotime($start_date)) ?></strong> s/d <strong><?= date('d M Y', strtotime($end_date)) ?></strong></p>
            </div>

            <div class="print-metadata">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 15%;">Dicetak Oleh</td>
                        <td style="width: 2%;">:</td>
                        <td style="width: 33%;"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></td>
                        <td style="width: 15%;">Tanggal Cetak</td>
                        <td style="width: 2%;">:</td>
                        <td style="width: 33%;"><?= date('d M Y, H:i') ?></td>
                    </tr>
                </table>
            </div>

            <div class="stats-box-grid">
                <div class="stat-box">
                    <div class="stat-box-title">Omset Bersih</div>
                    <div class="stat-box-value">Rp <?= number_format($total_laporan_pendapatan, 0, ',', '.') ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-box-title">Volume Penjualan</div>
                    <div class="stat-box-value"><?= number_format($total_laporan_orders) ?> Pesanan</div>
                </div>
                <div class="stat-box">
                    <div class="stat-box-title">Rata-Rata Transaksi (AOV)</div>
                    <div class="stat-box-value">Rp <?= number_format($average_order_value, 0, ',', '.') ?></div>
                </div>
            </div>

            <h3 style="margin-top: 20px; font-size: 16px; border-bottom: 1px solid #ddd; padding-bottom: 8px;">Rincian Penjualan</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>ID Order</th>
                        <th>Pelanggan</th>
                        <th>Tanggal Transaksi</th>
                        <th>Total Penjualan</th>
                        <th>Status Pesanan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($completed_orders)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">Tidak ada penjualan pada rentang tanggal terpilih.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($completed_orders as $order): ?>
                            <tr>
                                <td><strong>#<?= htmlspecialchars($order['id_order']) ?></strong></td>
                                <td><?= htmlspecialchars($order['nama']) ?></td>
                                <td><?= date('d M Y, H:i', strtotime($order['tanggal'])) ?></td>
                                <td>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                                <td style="text-transform: capitalize;"><?= htmlspecialchars($order['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="signature-section">
                <div class="signature-box">
                    <p>Jakarta, <?= date('d M Y') ?></p>
                    <p>Mengetahui,</p>
                    <div class="signature-space"></div>
                    <p style="text-decoration: underline; font-weight: bold;"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></p>
                    <p style="color: #666; font-size: 12px;">Administrator</p>
                </div>
            </div>
        </div>

        <script>
            // Otomatis memicu cetak setelah halaman selesai dimuat
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    window.print();
                }, 500);
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ----------------------------------------------------
// TAMPILAN STANDAR ADMIN PANEL (DENGAN NAVBAR)
// ----------------------------------------------------
include 'navbar.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan | Fleurist</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h1 class="display-md">Laporan Penjualan</h1>
                <p class="caption">Analisis omset dan volume pesanan yang berhasil dilakukan.</p>
            </div>
            <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                <a href="laporan.php?action=print&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" target="_blank" class="btn btn-outline-aubergine" style="padding: 10px 20px; font-size: 14px; display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Cetak PDF
                </a>
                <a href="laporan.php?action=excel&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-primary-pill" style="padding: 10px 20px; font-size: 14px; box-shadow: none; display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Ekspor Excel
                </a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form Filter Tanggal -->
        <div class="admin-card">
            <form action="" method="GET" style="display: flex; gap: var(--spacing-lg); align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 180px;">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" class="text-input" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 180px;">
                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                    <input type="date" name="end_date" id="end_date" class="text-input" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <button type="submit" class="btn btn-primary-pill" style="height: 44px; padding: 0 28px;">Filter Laporan</button>
            </form>
        </div>

        <!-- Summary Grid -->
        <div class="admin-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <div class="card-stat">
                <div class="card-stat-title">Omset Bersih (Filter)</div>
                <div class="card-stat-number">Rp <?= number_format($total_laporan_pendapatan, 0, ',', '.') ?></div>
            </div>
            <div class="card-stat">
                <div class="card-stat-title">Volume Transaksi Sukses</div>
                <div class="card-stat-number"><?= number_format($total_laporan_orders) ?> Pesanan</div>
            </div>
            <div class="card-stat">
                <div class="card-stat-title">Rata-Rata Transaksi (AOV)</div>
                <div class="card-stat-number">Rp <?= number_format($average_order_value, 0, ',', '.') ?></div>
            </div>
        </div>

        <!-- Tabel Detail Laporan -->
        <div class="admin-card">
            <h3 class="heading-sm" style="margin-bottom: var(--spacing-lg);">Rincian Penjualan Terfilter</h3>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID Order</th>
                            <th>Pelanggan</th>
                            <th>Tanggal Transaksi</th>
                            <th>Total Penjualan</th>
                            <th>Status Pesanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($completed_orders)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--ink-mute);">Tidak ada penjualan terdeteksi pada rentang tanggal terpilih.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($completed_orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($order['id_order']) ?></strong></td>
                                    <td><?= htmlspecialchars($order['nama']) ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($order['tanggal'])) ?></td>
                                    <td>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge badge-<?= htmlspecialchars($order['status']) ?>">
                                            <?= htmlspecialchars($order['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
