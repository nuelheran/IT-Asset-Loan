<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$conn = getConnection();
$userId = $_SESSION['user_id'];
$id = $_GET['id'] ?? null;

$stmt = $conn->prepare("
    SELECT l.*, a.name as asset_name, a.asset_code
    FROM loans l JOIN assets a ON a.id = l.asset_id
    WHERE l.id = ? AND l.user_id = ?
");
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();

if (!$loan || !in_array($loan['status'], ['active', 'overdue'])) {
    flash('error', 'Data peminjaman tidak ditemukan atau tidak dapat diproses.');
    header('Location: ' . BASE_URL . 'my_loans.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $condition = $_POST['condition_on_return'] ?? 'good';
    $notes = trim($_POST['return_notes'] ?? '');

    $stmt = $conn->prepare("UPDATE loans SET return_date = CURDATE(), condition_on_return = ?, return_notes = ?, status = 'returned' WHERE id = ?");
    $stmt->bind_param('ssi', $condition, $notes, $id);
    $stmt->execute();

    // Update status aset kembali tersedia, dan sinkronkan kondisi aset
    $stmt2 = $conn->prepare("UPDATE assets SET status = 'available', condition_status = ? WHERE id = ?");
    $stmt2->bind_param('si', $condition, $loan['asset_id']);
    $stmt2->execute();

    logLoanAction($conn, $id, 'returned', 'Aset dikembalikan oleh peminjam: ' . $notes, $userId);

    flash('success', 'Pengembalian aset berhasil dicatat. Terima kasih.');
    header('Location: ' . BASE_URL . 'my_loans.php');
    exit;
}

$pageTitle = 'Pengembalian Aset';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:600px;">
    <div class="card-header"><h3>Kembalikan Aset</h3></div>

    <div class="alert alert-info">
        <strong><?php echo esc($loan['asset_name']); ?></strong> (<?php echo esc($loan['asset_code']); ?>)<br>
        Kode Pinjam: <span class="mono"><?php echo esc($loan['loan_code']); ?></span><br>
        Jatuh Tempo: <?php echo formatDate($loan['due_date']); ?>
    </div>

    <form method="POST" action="">
        <div class="form-group">
            <label>Kondisi Aset Saat Dikembalikan *</label>
            <select name="condition_on_return" class="form-control" required>
                <option value="good">Baik</option>
                <option value="minor_damage">Rusak Ringan</option>
                <option value="major_damage">Rusak Berat</option>
            </select>
        </div>
        <div class="form-group">
            <label>Catatan Pengembalian</label>
            <textarea name="return_notes" class="form-control" rows="3" placeholder="Contoh: layar sedikit baret, semua kelengkapan lengkap, dll."></textarea>
        </div>
        <div class="flex-gap">
            <button type="submit" class="btn btn-success">Konfirmasi Pengembalian</button>
            <a href="<?php echo BASE_URL; ?>my_loans.php" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
