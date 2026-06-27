<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name !== '') {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param('ss', $name, $desc);
            $stmt->execute();
            flash('success', 'Kategori berhasil ditambahkan.');
        } else {
            flash('error', 'Nama kategori wajib diisi.');
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        $check = $conn->prepare("SELECT COUNT(*) c FROM assets WHERE category_id = ?");
        $check->bind_param('i', $id);
        $check->execute();
        $count = $check->get_result()->fetch_assoc()['c'];
        if ($count > 0) {
            flash('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh aset.');
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            flash('success', 'Kategori berhasil dihapus.');
        }
    }
    header('Location: ' . BASE_URL . 'categories.php');
    exit;
}

$pageTitle = 'Kategori Aset';
require_once __DIR__ . '/includes/header.php';

$categories = $conn->query("
    SELECT c.*, COUNT(a.id) as asset_count
    FROM categories c
    LEFT JOIN assets a ON a.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name
");
?>

<div class="card" style="max-width:480px;">
    <div class="card-header"><h3>Tambah Kategori</h3></div>
    <form method="POST" action="">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>Nama Kategori *</label>
            <input type="text" name="name" class="form-control" placeholder="Contoh: Laptop" required>
        </div>
        <div class="form-group">
            <label>Deskripsi</label>
            <input type="text" name="description" class="form-control" placeholder="Opsional">
        </div>
        <button type="submit" class="btn btn-primary">Tambah Kategori</button>
    </form>
</div>

<div class="card">
    <div class="card-header"><h3>Daftar Kategori</h3></div>
    <div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr><th>Nama</th><th>Deskripsi</th><th>Jumlah Aset</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php if ($categories->num_rows === 0): ?>
                <tr><td colspan="4" class="table-empty">Belum ada kategori.</td></tr>
            <?php endif; ?>
            <?php while ($cat = $categories->fetch_assoc()): ?>
            <tr>
                <td><?php echo esc($cat['name']); ?></td>
                <td class="text-muted"><?php echo esc($cat['description'] ?: '-'); ?></td>
                <td><?php echo $cat['asset_count']; ?></td>
                <td>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger" data-confirm="Hapus kategori &quot;<?php echo esc($cat['name']); ?>&quot;?">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
