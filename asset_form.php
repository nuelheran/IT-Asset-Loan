<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$conn = getConnection();
$id = $_GET['id'] ?? null;
$asset = ['asset_code'=>'','name'=>'','category_id'=>'','brand'=>'','serial_number'=>'','specification'=>'','purchase_date'=>'','condition_status'=>'good','status'=>'available','notes'=>''];
$isEdit = false;

if ($id) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT * FROM assets WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        flash('error', 'Aset tidak ditemukan.');
        header('Location: ' . BASE_URL . 'assets_list.php');
        exit;
    }
    $asset = $result->fetch_assoc();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset['name'] = trim($_POST['name'] ?? '');
    $asset['category_id'] = $_POST['category_id'] ?? '';
    $asset['brand'] = trim($_POST['brand'] ?? '');
    $asset['serial_number'] = trim($_POST['serial_number'] ?? '');
    $asset['specification'] = trim($_POST['specification'] ?? '');
    $asset['purchase_date'] = $_POST['purchase_date'] ?? '';
    $asset['condition_status'] = $_POST['condition_status'] ?? 'good';
    $asset['status'] = $_POST['status'] ?? 'available';
    $asset['notes'] = trim($_POST['notes'] ?? '');

    if ($asset['name'] === '') $errors[] = 'Nama aset wajib diisi.';
    if ($asset['category_id'] === '') $errors[] = 'Kategori wajib dipilih.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $purchaseDate = $asset['purchase_date'] ?: null;
    if ($isEdit) {
        $stmt = $conn->prepare("UPDATE assets SET name=?, category_id=?, brand=?, serial_number=?, specification=?, purchase_date=?, condition_status=?, status=?, notes=? WHERE id=?");
        $stmt->bind_param('sisssssssi',
            $asset['name'], $asset['category_id'], $asset['brand'], $asset['serial_number'],
            $asset['specification'], $purchaseDate, $asset['condition_status'], $asset['status'],
            $asset['notes'], $id
        );
        $stmt->execute();
        flash('success', 'Aset berhasil diperbarui.');
    } else {
        $assetCode = generateAssetCode($conn);
        $stmt = $conn->prepare("INSERT INTO assets (asset_code, name, category_id, brand, serial_number, specification, purchase_date, condition_status, status, notes) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('ssisssssss',
            $assetCode, $asset['name'], $asset['category_id'], $asset['brand'], $asset['serial_number'],
            $asset['specification'], $purchaseDate, $asset['condition_status'], $asset['status'], $asset['notes']
        );
        $stmt->execute();
        flash('success', 'Aset baru berhasil ditambahkan dengan kode ' . $assetCode . '.');
    }
    header('Location: ' . BASE_URL . 'assets_list.php');
    exit;
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$pageTitle = $isEdit ? 'Edit Aset' : 'Tambah Aset';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:720px;">
    <div class="card-header">
        <h3><?php echo $isEdit ? 'Edit Aset: ' . esc($asset['name']) : 'Tambah Aset Baru'; ?></h3>
        <?php if ($isEdit): ?><span class="mono text-muted"><?php echo esc($asset['asset_code']); ?></span><?php endif; ?>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e) echo esc($e) . '<br>'; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Nama Aset *</label>
                <input type="text" name="name" class="form-control" value="<?php echo esc($asset['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Kategori *</label>
                <select name="category_id" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $asset['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo esc($cat['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Merk</label>
                <input type="text" name="brand" class="form-control" value="<?php echo esc($asset['brand']); ?>">
            </div>
            <div class="form-group">
                <label>Nomor Seri</label>
                <input type="text" name="serial_number" class="form-control" value="<?php echo esc($asset['serial_number']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Spesifikasi</label>
            <textarea name="specification" class="form-control" rows="3"><?php echo esc($asset['specification']); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Tanggal Pembelian</label>
                <input type="date" name="purchase_date" class="form-control" value="<?php echo esc($asset['purchase_date']); ?>">
            </div>
            <div class="form-group">
                <label>Kondisi</label>
                <select name="condition_status" class="form-control">
                    <option value="good" <?php echo $asset['condition_status']=='good'?'selected':''; ?>>Baik</option>
                    <option value="minor_damage" <?php echo $asset['condition_status']=='minor_damage'?'selected':''; ?>>Rusak Ringan</option>
                    <option value="major_damage" <?php echo $asset['condition_status']=='major_damage'?'selected':''; ?>>Rusak Berat</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Status Ketersediaan</label>
            <select name="status" class="form-control">
                <option value="available" <?php echo $asset['status']=='available'?'selected':''; ?>>Tersedia</option>
                <option value="on_loan" <?php echo $asset['status']=='on_loan'?'selected':''; ?>>Dipinjam</option>
                <option value="maintenance" <?php echo $asset['status']=='maintenance'?'selected':''; ?>>Maintenance</option>
                <option value="retired" <?php echo $asset['status']=='retired'?'selected':''; ?>>Pensiun</option>
            </select>
            <div class="hint">Status akan otomatis berubah menjadi "Dipinjam" saat ada peminjaman disetujui.</div>
        </div>

        <div class="form-group">
            <label>Catatan</label>
            <textarea name="notes" class="form-control" rows="2"><?php echo esc($asset['notes']); ?></textarea>
        </div>

        <div class="flex-gap">
            <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Simpan Perubahan' : 'Tambah Aset'; ?></button>
            <a href="<?php echo BASE_URL; ?>assets_list.php" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
