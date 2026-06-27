<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$conn = getConnection();
$id = $_GET['id'] ?? null;

$stmt = $conn->prepare("
    SELECT l.*, a.name as asset_name, a.asset_code, a.brand, a.serial_number,
           u.name as user_name, u.department, u.email, u.phone,
           ap.name as approved_by_name
    FROM loans l
    JOIN assets a ON a.id = l.asset_id
    JOIN users u ON u.id = l.user_id
    LEFT JOIN users ap ON ap.id = l.approved_by
    WHERE l.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();

if (!$loan) {
    flash('error', 'Data peminjaman tidak ditemukan.');
    header('Location: ' . BASE_URL . 'loans_list.php');
    exit;
}

// Handle reject submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reject') {
    $reason = trim($_POST['rejection_reason'] ?? '');
    $stmt = $conn->prepare("UPDATE loans SET status='rejected', rejection_reason=?, approved_by=?, approved_at=NOW() WHERE id=?");
    $stmt->bind_param('sii', $reason, $_SESSION['user_id'], $id);
    $stmt->execute();
    logLoanAction($conn, $id, 'rejected', 'Ditolak: ' . $reason, $_SESSION['user_id']);
    flash('success', 'Pengajuan peminjaman telah ditolak.');
    header('Location: ' . BASE_URL . 'loans_list.php');
    exit;
}

// Handle confirm asset handover (approved -> active)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'handover') {
    $stmt = $conn->prepare("UPDATE loans SET status='active' WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt2 = $conn->prepare("UPDATE assets SET status='on_loan' WHERE id=?");
    $stmt2->bind_param('i', $loan['asset_id']);
    $stmt2->execute();
    logLoanAction($conn, $id, 'active', 'Aset telah diserahkan ke peminjam', $_SESSION['user_id']);
    flash('success', 'Aset telah diserahkan ke peminjam.');
    header('Location: ' . BASE_URL . 'loan_detail.php?id=' . $id);
    exit;
}

// Handle confirm return (admin menerima fisik aset)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_return') {
    $condition = $_POST['condition_on_return'] ?? 'good';
    $notes = trim($_POST['return_notes'] ?? '');
    $stmt = $conn->prepare("UPDATE loans SET status='returned', return_date=CURDATE(), condition_on_return=?, return_notes=?, received_by=? WHERE id=?");
    $stmt->bind_param('ssii', $condition, $notes, $_SESSION['user_id'], $id);
    $stmt->execute();
    $stmt2 = $conn->prepare("UPDATE assets SET status='available', condition_status=? WHERE id=?");
    $stmt2->bind_param('si', $condition, $loan['asset_id']);
    $stmt2->execute();
    logLoanAction($conn, $id, 'returned', 'Pengembalian dikonfirmasi oleh admin: ' . $notes, $_SESSION['user_id']);
    flash('success', 'Pengembalian aset berhasil dikonfirmasi.');
    header('Location: ' . BASE_URL . 'loan_detail.php?id=' . $id);
    exit;
}

$logs = $conn->prepare("
    SELECT ll.*, u.name as action_by_name FROM loan_logs ll
    LEFT JOIN users u ON u.id = ll.action_by
    WHERE ll.loan_id = ? ORDER BY ll.created_at ASC
");
$logs->bind_param('i', $id);
$logs->execute();
$logsResult = $logs->get_result();

$pageTitle = 'Detail Peminjaman';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Detail Peminjaman — <span class="mono"><?php echo esc($loan['loan_code']); ?></span></h3>
        <?php echo statusBadge($loan['status']); ?>
    </div>

    <div class="form-row">
        <div>
            <h4 style="font-size:13px; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.05em;">Aset</h4>
            <p><strong><?php echo esc($loan['asset_name']); ?></strong><br>
            Kode: <?php echo esc($loan['asset_code']); ?><br>
            Merk: <?php echo esc($loan['brand'] ?: '-'); ?><br>
            No. Seri: <?php echo esc($loan['serial_number'] ?: '-'); ?></p>
        </div>
        <div>
            <h4 style="font-size:13px; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.05em;">Peminjam</h4>
            <p><strong><?php echo esc($loan['user_name']); ?></strong><br>
            Departemen: <?php echo esc($loan['department'] ?: '-'); ?><br>
            Email: <?php echo esc($loan['email']); ?><br>
            Telp: <?php echo esc($loan['phone'] ?: '-'); ?></p>
        </div>
    </div>

    <div class="form-row">
        <div>
            <h4 style="font-size:13px; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.05em;">Jadwal</h4>
            <p>Tgl Pinjam: <?php echo formatDate($loan['loan_date']); ?><br>
            Jatuh Tempo: <?php echo formatDate($loan['due_date']); ?><br>
            Tgl Kembali: <?php echo formatDate($loan['return_date']); ?></p>
        </div>
        <div>
            <h4 style="font-size:13px; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.05em;">Kondisi</h4>
            <p>Saat Dipinjam: <?php echo statusBadge($loan['condition_on_loan']); ?><br>
            Saat Dikembalikan: <?php echo $loan['condition_on_return'] ? statusBadge($loan['condition_on_return']) : '-'; ?></p>
        </div>
    </div>

    <h4 style="font-size:13px; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.05em;">Tujuan Peminjaman</h4>
    <p><?php echo nl2br(esc($loan['purpose'])); ?></p>

    <?php if ($loan['status'] === 'rejected'): ?>
        <div class="alert alert-danger">Alasan penolakan: <?php echo esc($loan['rejection_reason']); ?></div>
    <?php endif; ?>
    <?php if ($loan['return_notes']): ?>
        <div class="alert alert-info">Catatan pengembalian: <?php echo esc($loan['return_notes']); ?></div>
    <?php endif; ?>

    <div class="flex-gap no-print" style="margin-top:18px; flex-wrap:wrap;">
        <a href="<?php echo BASE_URL; ?>loan_print.php?id=<?php echo $loan['id']; ?>" class="btn btn-outline">Cetak Bukti</a>

        <?php if ($loan['status'] === 'pending'): ?>
            <a href="<?php echo BASE_URL; ?>loan_approve.php?id=<?php echo $loan['id']; ?>" class="btn btn-success" data-confirm="Setujui peminjaman ini? Aset akan ditandai sebagai disetujui dan menunggu penyerahan fisik.">Setujui Peminjaman</a>
            <button type="button" class="btn btn-danger" onclick="document.getElementById('rejectForm').style.display='block';">Tolak Peminjaman</button>
        <?php endif; ?>

        <?php if ($loan['status'] === 'approved'): ?>
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" name="action" value="handover">
                <button type="submit" class="btn btn-primary" data-confirm="Konfirmasi aset telah diserahkan ke peminjam?">Konfirmasi Serah Aset</button>
            </form>
        <?php endif; ?>

        <?php if (in_array($loan['status'], ['active','overdue'])): ?>
            <button type="button" class="btn btn-success" onclick="document.getElementById('returnForm').style.display='block';">Konfirmasi Pengembalian</button>
        <?php endif; ?>
    </div>

    <?php if ($loan['status'] === 'pending'): ?>
    <form id="rejectForm" method="POST" action="" style="display:none; margin-top:16px; padding:16px; background:#fdf5f4; border-radius:4px; border:1px solid var(--line);">
        <input type="hidden" name="action" value="reject">
        <div class="form-group">
            <label>Alasan Penolakan *</label>
            <textarea name="rejection_reason" class="form-control" rows="2" required placeholder="Jelaskan alasan menolak pengajuan ini..."></textarea>
        </div>
        <button type="submit" class="btn btn-danger">Konfirmasi Tolak</button>
    </form>
    <?php endif; ?>

    <?php if (in_array($loan['status'], ['active','overdue'])): ?>
    <form id="returnForm" method="POST" action="" style="display:none; margin-top:16px; padding:16px; background:#f0f8f2; border-radius:4px; border:1px solid var(--line);">
        <input type="hidden" name="action" value="confirm_return">
        <div class="form-row">
            <div class="form-group">
                <label>Kondisi Aset Saat Dikembalikan *</label>
                <select name="condition_on_return" class="form-control" required>
                    <option value="good">Baik</option>
                    <option value="minor_damage">Rusak Ringan</option>
                    <option value="major_damage">Rusak Berat</option>
                </select>
            </div>
            <div class="form-group">
                <label>Catatan</label>
                <input type="text" name="return_notes" class="form-control" placeholder="Opsional">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Konfirmasi Pengembalian</button>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header"><h3>Riwayat Status</h3></div>
    <div class="table-responsive">
    <table class="data-table">
        <thead><tr><th>Waktu</th><th>Status</th><th>Catatan</th><th>Oleh</th></tr></thead>
        <tbody>
            <?php while ($log = $logsResult->fetch_assoc()): ?>
            <tr>
                <td><?php echo formatDateTime($log['created_at']); ?></td>
                <td><?php echo statusBadge($log['status']); ?></td>
                <td><?php echo esc($log['note']); ?></td>
                <td><?php echo esc($log['action_by_name'] ?: 'Sistem'); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
