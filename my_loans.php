<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$conn = getConnection();
$userId = $_SESSION['user_id'];
refreshOverdueLoans($conn);

$statusFilter = $_GET['status'] ?? '';
$where = "WHERE l.user_id = ?";
$params = [$userId];
$types = 'i';

if ($statusFilter !== '') {
    $where .= " AND l.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$sql = "SELECT l.*, a.name as asset_name, a.asset_code, a.brand
        FROM loans l JOIN assets a ON a.id = l.asset_id
        $where ORDER BY l.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$loans = $stmt->get_result();

$pageTitle = 'Peminjaman Saya';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="filter-bar">
        <a href="?status=" class="btn btn-sm <?php echo $statusFilter===''?'btn-primary':'btn-outline'; ?>">Semua</a>
        <a href="?status=pending" class="btn btn-sm <?php echo $statusFilter=='pending'?'btn-primary':'btn-outline'; ?>">Menunggu</a>
        <a href="?status=active" class="btn btn-sm <?php echo $statusFilter=='active'?'btn-primary':'btn-outline'; ?>">Sedang Dipinjam</a>
        <a href="?status=overdue" class="btn btn-sm <?php echo $statusFilter=='overdue'?'btn-primary':'btn-outline'; ?>">Terlambat</a>
        <a href="?status=returned" class="btn btn-sm <?php echo $statusFilter=='returned'?'btn-primary':'btn-outline'; ?>">Dikembalikan</a>
        <a href="<?php echo BASE_URL; ?>loan_request.php" class="btn btn-sm btn-accent" style="margin-left:auto;">+ Ajukan Peminjaman</a>
    </div>

    <div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Kode Pinjam</th>
                <th>Aset</th>
                <th>Tgl Pinjam</th>
                <th>Jatuh Tempo</th>
                <th>Tgl Kembali</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($loans->num_rows === 0): ?>
                <tr><td colspan="7" class="table-empty">Tidak ada data peminjaman.</td></tr>
            <?php endif; ?>
            <?php while ($row = $loans->fetch_assoc()): ?>
            <tr>
                <td class="mono"><?php echo esc($row['loan_code']); ?></td>
                <td><?php echo esc($row['asset_name']); ?> <span class="text-muted">(<?php echo esc($row['asset_code']); ?>)</span></td>
                <td><?php echo formatDate($row['loan_date']); ?></td>
                <td><?php echo formatDate($row['due_date']); ?></td>
                <td><?php echo formatDate($row['return_date']); ?></td>
                <td><?php echo statusBadge($row['status']); ?></td>
                <td>
                    <div class="flex-gap">
                        <a href="<?php echo BASE_URL; ?>loan_print.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">Cetak</a>
                        <?php if (in_array($row['status'], ['active','overdue'])): ?>
                            <a href="<?php echo BASE_URL; ?>loan_return_request.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">Ajukan Kembali</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
