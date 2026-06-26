<?php
require_once '../koneksi.php';
include 'navbar.php';

$error = '';
$success = '';

$action = $_GET['action'] ?? 'list';
$id_user = $_GET['id'] ?? null;

// Proses Hapus User
if ($action === 'delete' && $id_user) {
    if ($id_user == $_SESSION['user_id']) {
        $error = "Anda tidak dapat menghapus akun Anda sendiri.";
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id_user]);
            header("Location: user.php?success=Pengguna berhasil dihapus");
            exit;
        } catch (PDOException $e) {
            $error = "Gagal menghapus pengguna: " . $e->getMessage();
        }
    }
}

// Proses Ubah Role User
if ($action === 'change_role' && $id_user && isset($_GET['role'])) {
    $new_role = $_GET['role'];
    if ($id_user == $_SESSION['user_id']) {
        $error = "Anda tidak dapat mengubah role akun Anda sendiri.";
    } elseif (!in_array($new_role, ['admin', 'user'])) {
        $error = "Role tidak valid.";
    } else {
        try {
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $id_user]);
            header("Location: user.php?success=Role pengguna berhasil diperbarui");
            exit;
        } catch (PDOException $e) {
            $error = "Gagal memperbarui role: " . $e->getMessage();
        }
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Ambil data semua pengguna
$users = [];
try {
    $stmt = $db->query("SELECT id, nama, email, role FROM users ORDER BY role ASC, nama ASC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Gagal memuat daftar pengguna: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna | Fleurist</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h1 class="display-md">Kelola User</h1>
                <p class="caption">Daftar pengguna dan admin toko yang terdaftar di sistem.</p>
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
                        <th>ID</th>
                        <th>Nama Lengkap</th>
                        <th>Alamat Email</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($user['id']) ?></td>
                            <td><strong><?= htmlspecialchars($user['nama']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span class="badge badge-role" style="background-color: <?= $user['role'] === 'admin' ? 'var(--primary)' : 'var(--canvas-lavender)' ?>; color: <?= $user['role'] === 'admin' ? 'var(--on-primary)' : 'var(--primary)' ?>;">
                                    <?= htmlspecialchars($user['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <div style="display: flex; gap: var(--spacing-sm);">
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <a href="user.php?action=change_role&id=<?= $user['id'] ?>&role=user" class="btn btn-secondary-pill" style="padding: 6px 14px; font-size: 12px;">Ubah ke User</a>
                                        <?php else: ?>
                                            <a href="user.php?action=change_role&id=<?= $user['id'] ?>&role=admin" class="btn btn-secondary-pill" style="padding: 6px 14px; font-size: 12px; background-color: var(--primary); color: #fff;">Ubah ke Admin</a>
                                        <?php endif; ?>
                                        <a href="user.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-outline-aubergine" style="padding: 4px 12px; font-size: 12px; border: 1px solid var(--primary);" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">Hapus</a>
                                    </div>
                                <?php else: ?>
                                    <span class="caption" style="font-style: italic;">Akun Anda saat ini</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
