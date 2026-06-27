<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$conn = getConnection();
$id = $_GET['id'] ?? null;

if ($id && $id != $_SESSION['user_id']) {
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();

    if ($current) {
        $newStatus = $current['status'] === 'active' ? 'inactive' : 'active';
        $update = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $update->bind_param('si', $newStatus, $id);
        $update->execute();
        flash('success', 'Status pengguna berhasil diperbarui.');
    }
}

header('Location: ' . BASE_URL . 'users_list.php');
exit;
