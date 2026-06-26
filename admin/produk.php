<?php
require_once '../koneksi.php';
include 'navbar.php';

$action = $_GET['action'] ?? 'list';
$id_produk = $_GET['id'] ?? null;

$error = '';
$success = '';

// Folder upload
$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Proses Hapus Produk
if ($action === 'delete' && $id_produk) {
    try {
        // Cari gambar lama untuk dihapus
        $stmt = $db->prepare("SELECT gambar FROM produk WHERE id_produk = ?");
        $stmt->execute([$id_produk]);
        $prod = $stmt->fetch();
        if ($prod && $prod['gambar'] && file_exists($upload_dir . $prod['gambar'])) {
            unlink($upload_dir . $prod['gambar']);
        }

        $stmt = $db->prepare("DELETE FROM produk WHERE id_produk = ?");
        $stmt->execute([$id_produk]);
        header("Location: produk.php?success=Produk berhasil dihapus");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menghapus produk: " . $e->getMessage();
    }
}

// Proses Simpan Produk (Tambah & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    $stok = intval($_POST['stok'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (empty($nama_produk) || empty($kategori) || $harga <= 0) {
        $error = "Nama, Kategori, dan Harga harus valid.";
    } else {
        // File upload handling
        $gambar_name = $_POST['existing_gambar'] ?? '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['gambar']['tmp_name'];
            $file_name = $_FILES['gambar']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed)) {
                // Generate nama file unik
                $new_filename = uniqid('flower_', true) . '.' . $ext;
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    // Hapus gambar lama jika ada
                    if (!empty($gambar_name) && file_exists($upload_dir . $gambar_name)) {
                        unlink($upload_dir . $gambar_name);
                    }
                    $gambar_name = $new_filename;
                } else {
                    $error = "Gagal mengupload gambar.";
                }
            } else {
                $error = "Ekstensi gambar tidak diperbolehkan (hanya JPG, JPEG, PNG, WEBP).";
            }
        }

        if (empty($error)) {
            if ($action === 'add') {
                try {
                    $stmt = $db->prepare("INSERT INTO produk (nama_produk, kategori, harga, stok, deskripsi, gambar) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nama_produk, $kategori, $harga, $stok, $deskripsi, $gambar_name]);
                    header("Location: produk.php?success=Produk berhasil ditambahkan");
                    exit;
                } catch (PDOException $e) {
                    $error = "Gagal menambahkan produk: " . $e->getMessage();
                }
            } elseif ($action === 'edit' && $id_produk) {
                try {
                    $stmt = $db->prepare("UPDATE produk SET nama_produk = ?, kategori = ?, harga = ?, stok = ?, deskripsi = ?, gambar = ? WHERE id_produk = ?");
                    $stmt->execute([$nama_produk, $kategori, $harga, $stok, $deskripsi, $gambar_name, $id_produk]);
                    header("Location: produk.php?success=Produk berhasil diperbarui");
                    exit;
                } catch (PDOException $e) {
                    $error = "Gagal memperbarui produk: " . $e->getMessage();
                }
            }
        }
    }
}

// Tangkap pesan sukses redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Ambil data produk untuk Form Edit
$product_data = null;
if ($action === 'edit' && $id_produk) {
    $stmt = $db->prepare("SELECT * FROM produk WHERE id_produk = ?");
    $stmt->execute([$id_produk]);
    $product_data = $stmt->fetch();
    if (!$product_data) {
        $error = "Produk tidak ditemukan.";
        $action = 'list';
    }
}

// Ambil semua produk untuk list
$products = [];
if ($action === 'list') {
    try {
        $stmt = $db->query("SELECT * FROM produk ORDER BY id_produk DESC");
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Gagal memuat produk: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk | Fleurist</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-container">
        
        <?php if ($action === 'list'): ?>
            <!-- LIST PRODUK -->
            <div class="admin-header">
                <div>
                    <h1 class="display-md">Kelola Produk</h1>
                    <p class="caption">Tambah, edit, dan hapus katalog bunga segar.</p>
                </div>
                <div>
                    <a href="produk.php?action=add" class="btn btn-primary-pill">+ Tambah Produk</a>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--ink-mute);">Belum ada produk. Silakan tambahkan produk baru.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $prod): ?>
                                <tr>
                                    <td>
                                        <?php if ($prod['gambar'] && file_exists($upload_dir . $prod['gambar'])): ?>
                                            <img src="<?= $upload_dir . htmlspecialchars($prod['gambar']) ?>" alt="<?= htmlspecialchars($prod['nama_produk']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--rounded-sm); border: 1px solid var(--hairline);">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background-color: var(--canvas-cream); display: flex; align-items: center; justify-content: center; font-size: 10px; color: var(--ink-mute); border-radius: var(--rounded-sm); border: 1px solid var(--hairline);">No Img</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($prod['nama_produk']) ?></strong></td>
                                    <td><span class="badge badge-role" style="font-weight: normal;"><?= htmlspecialchars($prod['kategori']) ?></span></td>
                                    <td>Rp <?= number_format($prod['harga'], 0, ',', '.') ?></td>
                                    <td><?= number_format($prod['stok']) ?> pcs</td>
                                    <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($prod['deskripsi']) ?>">
                                        <?= htmlspecialchars($prod['deskripsi']) ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: var(--spacing-sm);">
                                            <a href="produk.php?action=edit&id=<?= $prod['id_produk'] ?>" class="btn btn-secondary-pill" style="padding: 6px 14px; font-size: 12px;">Edit</a>
                                            <a href="produk.php?action=delete&id=<?= $prod['id_produk'] ?>" class="btn btn-outline-aubergine" style="padding: 4px 12px; font-size: 12px; border: 1px solid var(--primary);" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <!-- FORM TAMBAH / EDIT PRODUK -->
            <div class="admin-header">
                <div>
                    <h1 class="display-md"><?= $action === 'add' ? 'Tambah Produk Baru' : 'Edit Produk' ?></h1>
                    <p class="caption">Isi informasi bunga segar di bawah ini.</p>
                </div>
                <div>
                    <a href="produk.php" class="btn btn-secondary-pill">Kembali ke Daftar</a>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="admin-card" style="max-width: 600px; margin: 0 auto;">
                <form action="" method="POST" enctype="multipart/form-data">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="existing_gambar" value="<?= htmlspecialchars($product_data['gambar'] ?? '') ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="nama_produk" class="form-label">Nama Bunga / Produk</label>
                        <input type="text" name="nama_produk" id="nama_produk" class="text-input" placeholder="Contoh: Buket Mawar Merah" required value="<?= htmlspecialchars($product_data['nama_produk'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="kategori" class="form-label">Kategori</label>
                        <input type="text" name="kategori" id="kategori" class="text-input" placeholder="Contoh: Mawar, Tulip, Buket" required value="<?= htmlspecialchars($product_data['kategori'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                        <div>
                            <label for="harga" class="form-label">Harga (Rupiah)</label>
                            <input type="number" name="harga" id="harga" class="text-input" placeholder="Contoh: 150000" required value="<?= htmlspecialchars($product_data['harga'] ?? '') ?>">
                        </div>
                        <div>
                            <label for="stok" class="form-label">Stok (pcs)</label>
                            <input type="number" name="stok" id="stok" class="text-input" placeholder="Contoh: 20" required value="<?= htmlspecialchars($product_data['stok'] ?? '0') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi" class="form-label">Deskripsi Produk</label>
                        <textarea name="deskripsi" id="deskripsi" class="text-input" style="height: 120px; resize: vertical;" placeholder="Deskripsikan kesegaran bunga, warna, dan kelengkapan buket..."><?= htmlspecialchars($product_data['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="gambar" class="form-label">Gambar Produk</label>
                        <?php if ($action === 'edit' && !empty($product_data['gambar']) && file_exists($upload_dir . $product_data['gambar'])): ?>
                            <div style="margin-bottom: var(--spacing-sm);">
                                <p class="caption">Gambar saat ini:</p>
                                <img src="<?= $upload_dir . htmlspecialchars($product_data['gambar']) ?>" alt="Preview" style="width: 100px; height: 100px; object-fit: cover; border-radius: var(--rounded-sm); border: 1px solid var(--hairline); margin-top: 4px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="gambar" id="gambar" class="text-input" style="padding: 8px;">
                        <span class="caption">Format: JPG, PNG, WEBP. Maksimal 2MB.</span>
                    </div>

                    <button type="submit" class="btn btn-primary-pill" style="width: 100%; margin-top: var(--spacing-lg);">
                        <?= $action === 'add' ? 'Simpan Produk' : 'Perbarui Produk' ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
