<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

header('Content-Type: application/json');

$conn = getConnection();
refreshOverdueLoans($conn);

$rawCode = trim($_GET['code'] ?? '');

if ($rawCode === '') {
    echo json_encode(['success' => false, 'message' => 'Kode tidak boleh kosong.']);
    exit;
}

// Hasil scan QR berisi payload JSON { type: 'IT_ASSET', code: 'AST-0001' }.
// Tapi tetap dukung jika yang di-scan/diketik hanya kode polos (misal dari barcode 1D
// atau input manual), supaya endpoint ini fleksibel untuk kedua skenario.
$assetCode = $rawCode;
$decoded = json_decode($rawCode, true);
if (is_array($decoded) && !empty($decoded['code'])) {
    $assetCode = $decoded['code'];
}
$assetCode = trim($assetCode);

$stmt = $conn->prepare("
    SELECT a.*, c.name as category_name
    FROM assets a
    JOIN categories c ON c.id = a.category_id
    WHERE a.asset_code = ?
    LIMIT 1
");
$stmt->bind_param('s', $assetCode);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    echo json_encode(['success' => false, 'message' => 'Kode aset "' . $assetCode . '" tidak ditemukan dalam sistem.']);
    exit;
}

// Ambil transaksi peminjaman yang sedang berjalan untuk aset ini (jika ada)
$loanStmt = $conn->prepare("
    SELECT l.*, u.name as user_name, u.department, u.email, u.phone
    FROM loans l
    JOIN users u ON u.id = l.user_id
    WHERE l.asset_id = ? AND l.status IN ('pending','approved','active','overdue')
    ORDER BY l.created_at DESC
    LIMIT 1
");
$loanStmt->bind_param('i', $asset['id']);
$loanStmt->execute();
$activeLoan = $loanStmt->get_result()->fetch_assoc();

// Ambil 3 riwayat peminjaman terakhir (termasuk yang sudah selesai) untuk konteks tambahan
$historyStmt = $conn->prepare("
    SELECT l.loan_code, l.status, l.loan_date, l.due_date, l.return_date, u.name as user_name
    FROM loans l
    JOIN users u ON u.id = l.user_id
    WHERE l.asset_id = ?
    ORDER BY l.created_at DESC
    LIMIT 3
");
$historyStmt->bind_param('i', $asset['id']);
$historyStmt->execute();
$history = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conditionLabels = ['good' => 'Baik', 'minor_damage' => 'Rusak Ringan', 'major_damage' => 'Rusak Berat'];
$statusLabels = [
    'available' => 'Tersedia', 'on_loan' => 'Dipinjam', 'maintenance' => 'Maintenance', 'retired' => 'Pensiun',
    'pending' => 'Menunggu Persetujuan', 'approved' => 'Disetujui', 'active' => 'Sedang Dipinjam',
    'returned' => 'Dikembalikan', 'overdue' => 'Terlambat', 'rejected' => 'Ditolak',
];

echo json_encode([
    'success' => true,
    'asset' => [
        'id' => $asset['id'],
        'asset_code' => $asset['asset_code'],
        'name' => $asset['name'],
        'category_name' => $asset['category_name'],
        'brand' => $asset['brand'],
        'serial_number' => $asset['serial_number'],
        'specification' => $asset['specification'],
        'condition_status' => $asset['condition_status'],
        'condition_label' => $conditionLabels[$asset['condition_status']] ?? $asset['condition_status'],
        'status' => $asset['status'],
        'status_label' => $statusLabels[$asset['status']] ?? $asset['status'],
        'notes' => $asset['notes'],
    ],
    'active_loan' => $activeLoan ? [
        'loan_code' => $activeLoan['loan_code'],
        'status' => $activeLoan['status'],
        'status_label' => $statusLabels[$activeLoan['status']] ?? $activeLoan['status'],
        'user_name' => $activeLoan['user_name'],
        'department' => $activeLoan['department'],
        'loan_date' => $activeLoan['loan_date'],
        'due_date' => $activeLoan['due_date'],
        'loan_date_fmt' => formatDate($activeLoan['loan_date']),
        'due_date_fmt' => formatDate($activeLoan['due_date']),
    ] : null,
    'history' => array_map(function ($h) use ($statusLabels) {
        return [
            'loan_code' => $h['loan_code'],
            'status' => $h['status'],
            'status_label' => $statusLabels[$h['status']] ?? $h['status'],
            'user_name' => $h['user_name'],
            'loan_date_fmt' => formatDate($h['loan_date']),
            'return_date_fmt' => $h['return_date'] ? formatDate($h['return_date']) : null,
        ];
    }, $history),
]);
