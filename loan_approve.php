<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$conn = getConnection();
$id = $_GET['id'] ?? null;

$stmt = $conn->prepare("SELECT * FROM loans WHERE id = ? AND status = 'pending'");
$stmt->bind_param('i', $id);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();

if (!$loan) {
    flash('error', 'Peminjaman tidak ditemukan atau sudah diproses sebelumnya.');
    header('Location: ' . BASE_URL . 'loans_list.php');
    exit;
}

// Pastikan aset masih tersedia sebelum approve
$assetCheck = $conn->prepare("SELECT status FROM assets WHERE id = ?");
$assetCheck->bind_param('i', $loan['asset_id']);
$assetCheck->execute();
$assetStatus = $assetCheck->get_result()->fetch_assoc()['status'];

if ($assetStatus !== 'available') {
    flash('error', 'Aset ini sudah tidak tersedia (mungkin sedang dipinjam transaksi lain).');
    header('Location: ' . BASE_URL . 'loan_detail.php?id=' . $id);
    exit;
}

$stmt = $conn->prepare("UPDATE loans SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?");
$stmt->bind_param('ii', $_SESSION['user_id'], $id);
$stmt->execute();

logLoanAction($conn, $id, 'approved', 'Disetujui oleh admin', $_SESSION['user_id']);

flash('success', 'Peminjaman disetujui. Silakan konfirmasi serah aset setelah aset diberikan ke peminjam.');
header('Location: ' . BASE_URL . 'loan_detail.php?id=' . $id);
exit;
