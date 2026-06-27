<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$pageTitle = isAdmin() ? 'Master Aset' : 'Katalog Aset';
require_once __DIR__ . '/includes/header.php';

$conn = getConnection();

$search = trim($_GET['search'] ?? '');
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(a.name LIKE ? OR a.asset_code LIKE ? OR a.brand LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
if ($categoryFilter !== '') {
    $where[] = "a.category_id = ?";
    $params[] = $categoryFilter;
    $types .= 'i';
}
if ($statusFilter !== '') {
    $where[] = "a.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT a.*, c.name as category_name FROM assets a
        JOIN categories c ON c.id = a.category_id
        $whereSql ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$assets = $stmt->get_result();

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<div class="card">
    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="form-control" placeholder="Cari nama, kode, atau merk..." value="<?php echo esc($search); ?>">
        <select name="category" class="form-control">
            <option value="">Semua Kategori</option>
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>><?php echo esc($cat['name']); ?></option>
            <?php endwhile; ?>
        </select>
        <select name="status" class="form-control">
            <option value="">Semua Status</option>
            <option value="available" <?php echo $statusFilter=='available'?'selected':''; ?>>Tersedia</option>
            <option value="on_loan" <?php echo $statusFilter=='on_loan'?'selected':''; ?>>Dipinjam</option>
            <option value="maintenance" <?php echo $statusFilter=='maintenance'?'selected':''; ?>>Maintenance</option>
            <option value="retired" <?php echo $statusFilter=='retired'?'selected':''; ?>>Pensiun</option>
        </select>
        <button type="submit" class="btn btn-outline">Filter</button>
        <?php if (isAdmin()): ?>
            <a href="<?php echo BASE_URL; ?>assets_qr_print_bulk.php" class="btn btn-outline" style="margin-left:auto;">Cetak Semua QR</a>
            <a href="<?php echo BASE_URL; ?>asset_form.php" class="btn btn-accent">+ Tambah Aset</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Aset</th>
                <th>Kategori</th>
                <th>Merk</th>
                <th>Kondisi</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($assets->num_rows === 0): ?>
                <tr><td colspan="7" class="table-empty">Tidak ada data aset yang cocok.</td></tr>
            <?php endif; ?>
            <?php while ($row = $assets->fetch_assoc()): ?>
            <tr>
                <td class="mono"><?php echo esc($row['asset_code']); ?></td>
                <td><?php echo esc($row['name']); ?></td>
                <td><?php echo esc($row['category_name']); ?></td>
                <td><?php echo esc($row['brand'] ?: '-'); ?></td>
                <td><?php echo statusBadge($row['condition_status']); ?></td>
                <td><?php echo statusBadge($row['status']); ?></td>
                <td>
                    <div class="flex-gap">
                        <?php if (!isAdmin() && $row['status'] === 'available'): ?>
                            <a href="<?php echo BASE_URL; ?>loan_request.php?asset_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-accent">Pinjam</a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>asset_qr_print.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">QR</a>
                        <?php if (isAdmin()): ?>
                            <a href="<?php echo BASE_URL; ?>asset_form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="<?php echo BASE_URL; ?>asset_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" data-confirm="Hapus aset &quot;<?php echo esc($row['name']); ?>&quot;? Tindakan ini tidak dapat dibatalkan.">Hapus</a>
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
