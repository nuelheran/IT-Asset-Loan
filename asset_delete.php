<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$conn = getConnection();
$id = $_GET['id'] ?? null;

if ($id) {
    // Cek apakah aset masih punya peminjaman aktif
    $check = $conn->prepare("SELECT COUNT(*) c FROM loans WHERE asset_id = ? AND status IN ('pending','approved','active','overdue')");
    $check->bind_param('i', $id);
    $check->execute();
    $count = $check->get_result()->fetch_assoc()['c'];

    if ($count > 0) {
        flash('error', 'Aset tidak dapat dihapus karena masih memiliki transaksi peminjaman aktif.');
    } else {
        $stmt = $conn->prepare("DELETE FROM assets WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            flash('success', 'Aset berhasil dihapus.');
        } else {
            flash('error', 'Gagal menghapus aset.');
        }
    }
}

header('Location: ' . BASE_URL . 'assets_list.php');
exit;
