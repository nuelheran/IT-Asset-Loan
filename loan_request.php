<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$conn = getConnection();
$userId = $_SESSION['user_id'];
$presetAssetId = $_GET['asset_id'] ?? '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assetId = $_POST['asset_id'] ?? '';
    $purpose = trim($_POST['purpose'] ?? '');
    $loanDate = $_POST['loan_date'] ?? '';
    $dueDate = $_POST['due_date'] ?? '';

    if ($assetId === '') $errors[] = 'Aset wajib dipilih.';
    if ($purpose === '') $errors[] = 'Tujuan peminjaman wajib diisi.';
    if ($loanDate === '') $errors[] = 'Tanggal pinjam wajib diisi.';
    if ($dueDate === '') $errors[] = 'Tanggal rencana kembali wajib diisi.';
    if ($loanDate && $dueDate && strtotime($dueDate) < strtotime($loanDate)) {
        $errors[] = 'Tanggal kembali tidak boleh sebelum tanggal pinjam.';
    }

    if (empty($errors)) {
        // Re-check asset availability (anti race-condition sederhana)
        $check = $conn->prepare("SELECT status, condition_status FROM assets WHERE id = ?");
        $check->bind_param('i', $assetId);
        $check->execute();
        $assetRow = $check->get_result()->fetch_assoc();

        if (!$assetRow || $assetRow['status'] !== 'available') {
            $errors[] = 'Aset ini sudah tidak tersedia. Silakan pilih aset lain.';
        } else {
            $loanCode = generateLoanCode();
            $stmt = $conn->prepare("INSERT INTO loans (loan_code, asset_id, user_id, purpose, loan_date, due_date, condition_on_loan, status) VALUES (?,?,?,?,?,?,?, 'pending')");
            $stmt->bind_param('siissss', $loanCode, $assetId, $userId, $purpose, $loanDate, $dueDate, $assetRow['condition_status']);
            $stmt->execute();
            $loanId = $stmt->insert_id;

            logLoanAction($conn, $loanId, 'pending', 'Pengajuan peminjaman dibuat oleh karyawan', $userId);

            flash('success', 'Pengajuan peminjaman berhasil dikirim dengan kode ' . $loanCode . '. Menunggu persetujuan admin.');
            header('Location: ' . BASE_URL . 'my_loans.php');
            exit;
        }
    }
}

$availableAssets = $conn->query("
    SELECT a.*, c.name as category_name FROM assets a
    JOIN categories c ON c.id = a.category_id
    WHERE a.status = 'available'
    ORDER BY c.name, a.name
");

$pageTitle = 'Ajukan Peminjaman';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:720px;">
    <div class="card-header"><h3>Form Pengajuan Peminjaman Aset</h3></div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e) echo esc($e) . '<br>'; ?>
        </div>
    <?php endif; ?>

    <?php if ($availableAssets->num_rows === 0): ?>
        <div class="alert alert-info">Saat ini tidak ada aset yang tersedia untuk dipinjam. Silakan coba lagi nanti.</div>
    <?php else: ?>
    <form method="POST" action="">
        <div class="form-group">
            <label>Pilih Aset *</label>
            <select name="asset_id" class="form-control" required>
                <option value="">-- Pilih Aset --</option>
                <?php while ($a = $availableAssets->fetch_assoc()): ?>
                    <option value="<?php echo $a['id']; ?>" <?php echo $presetAssetId == $a['id'] ? 'selected' : ''; ?>>
                        [<?php echo esc($a['category_name']); ?>] <?php echo esc($a['name']); ?> — <?php echo esc($a['asset_code']); ?> <?php echo $a['brand'] ? '('.esc($a['brand']).')' : ''; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Tujuan Peminjaman *</label>
            <textarea name="purpose" class="form-control" rows="3" placeholder="Jelaskan keperluan peminjaman aset ini..." required></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Tanggal Pinjam *</label>
                <input type="date" name="loan_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Rencana Tanggal Kembali *</label>
                <input type="date" name="due_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <div class="alert alert-info">Pengajuan akan diteruskan ke admin untuk disetujui sebelum aset dapat diambil.</div>

        <div class="flex-gap">
            <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-outline">Batal</a>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
