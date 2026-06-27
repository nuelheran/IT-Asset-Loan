<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$conn = getConnection();
refreshOverdueLoans($conn);

$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];
$types = '';

if ($statusFilter !== '') {
    $where[] = "l.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}
if ($search !== '') {
    $where[] = "(l.loan_code LIKE ? OR a.name LIKE ? OR u.name LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT l.*, a.name as asset_name, a.asset_code, u.name as user_name, u.department
        FROM loans l
        JOIN assets a ON a.id = l.asset_id
        JOIN users u ON u.id = l.user_id
        $whereSql
        ORDER BY
            CASE l.status WHEN 'pending' THEN 0 WHEN 'overdue' THEN 1 WHEN 'active' THEN 2 ELSE 3 END,
            l.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$loans = $stmt->get_result();

$pageTitle = 'Kelola Peminjaman';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="form-control" placeholder="Cari kode, aset, atau nama peminjam..." value="<?php echo esc($search); ?>">
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="pending" <?php echo $statusFilter=='pending'?'selected':''; ?>>Menunggu Persetujuan</option>
            <option value="approved" <?php echo $statusFilter=='approved'?'selected':''; ?>>Disetujui</option>
            <option value="active" <?php echo $statusFilter=='active'?'selected':''; ?>>Sedang Dipinjam</option>
            <option value="overdue" <?php echo $statusFilter=='overdue'?'selected':''; ?>>Terlambat</option>
            <option value="returned" <?php echo $statusFilter=='returned'?'selected':''; ?>>Dikembalikan</option>
            <option value="rejected" <?php echo $statusFilter=='rejected'?'selected':''; ?>>Ditolak</option>
        </select>
        <button type="submit" class="btn btn-outline">Cari</button>
    </form>

    <div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Kode Pinjam</th>
                <th>Aset</th>
                <th>Peminjam</th>
                <th>Tgl Pinjam</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($loans->num_rows === 0): ?>
                <tr><td colspan="7" class="table-empty">Tidak ada data peminjaman.</td></tr>
            <?php endif; ?>
            <?php while ($row = $loans->fetch_assoc()): ?>
            <tr data-href="<?php echo BASE_URL; ?>loan_detail.php?id=<?php echo $row['id']; ?>">
                <td class="mono"><?php echo esc($row['loan_code']); ?></td>
                <td><?php echo esc($row['asset_name']); ?> <span class="text-muted">(<?php echo esc($row['asset_code']); ?>)</span></td>
                <td><?php echo esc($row['user_name']); ?><br><span class="text-muted" style="font-size:11.5px;"><?php echo esc($row['department']); ?></span></td>
                <td><?php echo formatDate($row['loan_date']); ?></td>
                <td><?php echo formatDate($row['due_date']); ?></td>
                <td><?php echo statusBadge($row['status']); ?></td>
                <td>
                    <div class="flex-gap">
                        <a href="<?php echo BASE_URL; ?>loan_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">Detail</a>
                        <?php if ($row['status'] === 'pending'): ?>
                            <a href="<?php echo BASE_URL; ?>loan_approve.php?id=<?php echo $row['id']; ?>&action=approve" class="btn btn-sm btn-success" data-confirm="Setujui peminjaman ini?">Setujui</a>
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
