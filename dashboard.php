<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$conn = getConnection();
refreshOverdueLoans($conn);

if (isAdmin()) {
    // -------- Stats untuk Admin --------
    $totalAssets = $conn->query("SELECT COUNT(*) c FROM assets")->fetch_assoc()['c'];
    $availableAssets = $conn->query("SELECT COUNT(*) c FROM assets WHERE status='available'")->fetch_assoc()['c'];
    $onLoanAssets = $conn->query("SELECT COUNT(*) c FROM assets WHERE status='on_loan'")->fetch_assoc()['c'];
    $overdueLoans = $conn->query("SELECT COUNT(*) c FROM loans WHERE status='overdue'")->fetch_assoc()['c'];
    $pendingLoans = $conn->query("SELECT COUNT(*) c FROM loans WHERE status='pending'")->fetch_assoc()['c'];

    $recentLoans = $conn->query("
        SELECT l.*, a.name as asset_name, a.asset_code, u.name as user_name
        FROM loans l
        JOIN assets a ON a.id = l.asset_id
        JOIN users u ON u.id = l.user_id
        ORDER BY l.created_at DESC LIMIT 8
    ");
?>
<div class="stat-grid">
    <div class="stat-card c-total">
        <div class="corner-glow"></div>
        <div class="tooltip-bubble">Jumlah seluruh aset IT yang tercatat dalam sistem, apapun statusnya</div>
        <div class="label">Total Aset</div>
        <div class="value"><?php echo $totalAssets; ?></div>
        <div class="sub">Seluruh aset terdaftar</div>
    </div>
    <div class="stat-card c-available">
        <div class="corner-glow"></div>
        <div class="tooltip-bubble">Aset berstatus "Tersedia" dan siap diajukan peminjaman oleh karyawan</div>
        <div class="label">Tersedia</div>
        <div class="value"><?php echo $availableAssets; ?></div>
        <div class="sub">Siap dipinjam</div>
    </div>
    <div class="stat-card c-loan">
        <div class="corner-glow"></div>
        <div class="tooltip-bubble">Aset yang sedang berada di tangan karyawan saat ini</div>
        <div class="label">Sedang Dipinjam</div>
        <div class="value"><?php echo $onLoanAssets; ?></div>
        <div class="sub">Aset di tangan karyawan</div>
    </div>
    <div class="stat-card c-overdue">
        <div class="corner-glow"></div>
        <div class="tooltip-bubble">Peminjaman yang sudah melewati tanggal jatuh tempo dan belum dikembalikan</div>
        <div class="label">Terlambat</div>
        <div class="value"><?php echo $overdueLoans; ?></div>
        <div class="sub">Melewati tanggal jatuh tempo</div>
    </div>
</div>

<?php if ($pendingLoans > 0): ?>
<div class="alert alert-info flex-between">
    <span>Ada <strong><?php echo $pendingLoans; ?></strong> pengajuan peminjaman menunggu persetujuan Anda.</span>
    <a href="<?php echo BASE_URL; ?>loans_list.php?status=pending" class="btn btn-sm btn-accent">Tinjau Sekarang</a>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Aktivitas Peminjaman Terbaru</h3>
        <a href="<?php echo BASE_URL; ?>loans_list.php" class="btn btn-sm btn-outline">Lihat Semua</a>
    </div>
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
            </tr>
        </thead>
        <tbody>
            <?php if ($recentLoans->num_rows === 0): ?>
                <tr><td colspan="6" class="table-empty">Belum ada data peminjaman.</td></tr>
            <?php endif; ?>
            <?php while ($row = $recentLoans->fetch_assoc()): ?>
            <tr>
                <td class="mono"><?php echo esc($row['loan_code']); ?></td>
                <td><?php echo esc($row['asset_name']); ?> <span class="text-muted">(<?php echo esc($row['asset_code']); ?>)</span></td>
                <td><?php echo esc($row['user_name']); ?></td>
                <td><?php echo formatDate($row['loan_date']); ?></td>
                <td><?php echo formatDate($row['due_date']); ?></td>
                <td><?php echo statusBadge($row['status']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<?php
} else {
    // -------- Stats untuk Karyawan --------
    $userId = $_SESSION['user_id'];
    $activeLoans = $conn->prepare("SELECT COUNT(*) c FROM loans WHERE user_id=? AND status IN ('active','overdue')");
    $activeLoans->bind_param('i', $userId);
    $activeLoans->execute();
    $activeLoansCount = $activeLoans->get_result()->fetch_assoc()['c'];

    $pendingCount = $conn->prepare("SELECT COUNT(*) c FROM loans WHERE user_id=? AND status='pending'");
    $pendingCount->bind_param('i', $userId);
    $pendingCount->execute();
    $pendingCountVal = $pendingCount->get_result()->fetch_assoc()['c'];

    $returnedCount = $conn->prepare("SELECT COUNT(*) c FROM loans WHERE user_id=? AND status='returned'");
    $returnedCount->bind_param('i', $userId);
    $returnedCount->execute();
    $returnedCountVal = $returnedCount->get_result()->fetch_assoc()['c'];

    $overdueCount = $conn->prepare("SELECT COUNT(*) c FROM loans WHERE user_id=? AND status='overdue'");
    $overdueCount->bind_param('i', $userId);
    $overdueCount->execute();
    $overdueCountVal = $overdueCount->get_result()->fetch_assoc()['c'];

    $availableAssetsCount = $conn->query("SELECT COUNT(*) c FROM assets WHERE status='available'")->fetch_assoc()['c'];

    $myLoans = $conn->prepare("
        SELECT l.*, a.name as asset_name, a.asset_code
        FROM loans l JOIN assets a ON a.id = l.asset_id
        WHERE l.user_id = ?
        ORDER BY l.created_at DESC LIMIT 8
    ");
    $myLoans->bind_param('i', $userId);
    $myLoans->execute();
    $myLoansResult = $myLoans->get_result();
?>
<div class="stat-grid">
    <div class="stat-card c-loan">
        <div class="corner-glow"></div>
        <div class="tooltip-bubble">Jumlah aset yang sedang Anda pinjam, termasuk yang sudah terlambat</div>
        <div class="label">Sedang Dipinjam</div>
        <div class="value"><?php echo $activeLoansCount; ?></div>
        <div class="sub">Aset di tangan Anda</div>
    </div>
    <div class="stat-card c-total">
        <div class="corner-glow"></div>
        <div class="tooltip-bubble">Pengajuan peminjaman Anda yang masih menunggu persetujuan admin</div>
        <div class="label">Menunggu Persetujuan</div>
        <div class="value"><?php echo $pendingCountVal; ?></div>
        <div class="sub">Pengajuan diproses admin</div>
    </div>
    <div class="stat-card c-available">
        <div class="corner-glow"></div>
        <div class="tooltip-bubble">Aset yang berstatus tersedia dan bisa langsung Anda ajukan</div>
        <div class="label">Aset Tersedia</div>
        <div class="value"><?php echo $availableAssetsCount; ?></div>
        <div class="sub">Bisa diajukan sekarang</div>
    </div>
    <div class="stat-card c-overdue">
        <div class="corner-glow"></div>
        <div class="tooltip-bubble">Peminjaman Anda yang sudah melewati tanggal jatuh tempo</div>
        <div class="label">Terlambat</div>
        <div class="value"><?php echo $overdueCountVal; ?></div>
        <div class="sub">Segera kembalikan</div>
    </div>
</div>

<div class="card flex-between" style="background: linear-gradient(135deg, rgba(67,56,202,0.92), rgba(13,148,136,0.85)); color:#fff; border:none;">
    <div>
        <h3 style="color:#fff; margin-bottom:4px;">Butuh aset IT untuk pekerjaan Anda?</h3>
        <div style="color:#e6e9fb; font-size:13.5px;">Ajukan peminjaman laptop, monitor, proyektor, dan lainnya dalam beberapa klik.</div>
    </div>
    <a href="<?php echo BASE_URL; ?>loan_request.php" class="btn btn-accent">+ Ajukan Peminjaman</a>
</div>

<div class="card">
    <div class="card-header">
        <h3>Riwayat Peminjaman Saya</h3>
        <a href="<?php echo BASE_URL; ?>my_loans.php" class="btn btn-sm btn-outline">Lihat Semua</a>
    </div>
    <div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Kode Pinjam</th>
                <th>Aset</th>
                <th>Tgl Pinjam</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($myLoansResult->num_rows === 0): ?>
                <tr><td colspan="6" class="table-empty">Anda belum pernah mengajukan peminjaman.</td></tr>
            <?php endif; ?>
            <?php while ($row = $myLoansResult->fetch_assoc()): ?>
            <tr>
                <td class="mono"><?php echo esc($row['loan_code']); ?></td>
                <td><?php echo esc($row['asset_name']); ?> <span class="text-muted">(<?php echo esc($row['asset_code']); ?>)</span></td>
                <td><?php echo formatDate($row['loan_date']); ?></td>
                <td><?php echo formatDate($row['due_date']); ?></td>
                <td><?php echo statusBadge($row['status']); ?></td>
                <td><a href="<?php echo BASE_URL; ?>loan_print.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">Cetak</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>
<?php
}
require_once __DIR__ . '/includes/footer.php';
?>
