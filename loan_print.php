<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$conn = getConnection();
$id = $_GET['id'] ?? null;
$userId = $_SESSION['user_id'];

$sql = "SELECT l.*, a.name as asset_name, a.asset_code, a.brand, a.serial_number,
               u.name as user_name, u.department, u.nik, u.email,
               ap.name as approved_by_name
        FROM loans l
        JOIN assets a ON a.id = l.asset_id
        JOIN users u ON u.id = l.user_id
        LEFT JOIN users ap ON ap.id = l.approved_by
        WHERE l.id = ?";

if (!isAdmin()) {
    $sql .= " AND l.user_id = " . intval($userId);
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();

if (!$loan) {
    flash('error', 'Data peminjaman tidak ditemukan.');
    header('Location: ' . BASE_URL . (isAdmin() ? 'loans_list.php' : 'my_loans.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Bukti Peminjaman — <?php echo esc($loan['loan_code']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    :root {
        --ink:#0a0e1a; --accent:#818cf8; --accent2:#5eead4; --line:#dcd8cd;
        --glass-fill: rgba(255,255,255,0.06); --glass-border: rgba(255,255,255,0.14);
    }
    * { box-sizing: border-box; }
    html, body {
        font-family:'Inter',sans-serif; color:#e2e8f0; margin:0; min-height:100vh;
        background: #0a0e1a;
        background-image:
            radial-gradient(circle at 10% 10%, rgba(129,140,248,.3), transparent 40%),
            radial-gradient(circle at 90% 20%, rgba(94,234,212,.18), transparent 42%),
            radial-gradient(circle at 30% 90%, rgba(192,132,252,.18), transparent 45%),
            linear-gradient(160deg, #1b2a52 0%, #0a0e1a 50%, #0f2438 100%);
    }
    .sheet {
        max-width:760px; margin:30px auto; padding:46px 50px;
        background: var(--glass-fill);
        border: 1px solid var(--glass-border);
        backdrop-filter: blur(22px) saturate(140%);
        -webkit-backdrop-filter: blur(22px) saturate(140%);
        border-radius: 22px;
        color: #e2e8f0;
    }
    .doc-head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid var(--glass-border); padding-bottom:18px; margin-bottom:24px; }
    .doc-head .brand { font-family:'Space Grotesk',sans-serif; font-size:22px; font-weight:600; color:#fff; }
    .doc-head .brand .mark { display:inline-flex; align-items:center; justify-content:center; width:32px;height:32px; background:linear-gradient(135deg, var(--accent), var(--accent2)); color:#0a0e1a; border-radius:8px; margin-right:8px; font-size:15px; font-weight:700; box-shadow:0 0 20px rgba(129,140,248,.45); }
    .doc-head .meta { text-align:right; font-size:12px; color:#8b97b3; }
    .doc-title { font-family:'Space Grotesk',sans-serif; font-weight:600; font-size:18px; text-transform:uppercase; letter-spacing:.05em; text-align:center; margin-bottom:6px; color:#fff; }
    .doc-code { text-align:center; font-family:'JetBrains Mono',monospace; color:var(--accent2); font-size:14px; margin-bottom:28px; }
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:22px; }
    .block-label { font-size:10.5px; text-transform:uppercase; letter-spacing:.08em; color:#8b97b3; margin-bottom:6px; border-bottom:1px solid var(--glass-border); padding-bottom:4px; font-weight:600;}
    .block p { margin:3px 0; font-size:13.5px; color:#cbd5e1; }
    .block p strong { color:#fff; }
    .purpose-box { background:rgba(255,255,255,0.04); padding:14px 16px; border-radius:10px; font-size:13.5px; margin-bottom:24px; border:1px solid var(--glass-border); color:#cbd5e1; }
    table.spec { width:100%; border-collapse:collapse; margin-bottom:24px; font-size:13px; border-radius:10px; overflow:hidden; }
    table.spec td { padding:9px 12px; border:1px solid var(--glass-border); color:#cbd5e1; }
    table.spec td:first-child { width:170px; color:#8b97b3; background:rgba(255,255,255,0.03); }
    .status-pill { display:inline-flex; align-items:center; gap:6px; padding:5px 13px; border-radius:100px; font-size:11px; font-weight:700; text-transform:uppercase; background:rgba(129,140,248,.14); color:#818cf8; border:1px solid rgba(129,140,248,.32); }
    .status-pill::before { content:''; width:6px; height:6px; border-radius:50%; background:#818cf8; box-shadow:0 0 6px #818cf8; }
    .sign-grid { display:grid; grid-template-columns:1fr 1fr; gap:40px; margin-top:50px; text-align:center; }
    .sign-grid .line { margin-top:60px; border-top:1px solid var(--glass-border); padding-top:6px; font-size:12.5px; color:#cbd5e1; }
    .sign-grid .role { font-size:11px; color:#8b97b3; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;}
    .footer-note { margin-top:40px; font-size:11px; color:#5d6a89; text-align:center; border-top:1px solid var(--glass-border); padding-top:14px; }
    .actions { text-align:center; margin:20px 0; }
    .btn-print {
        background:linear-gradient(135deg, var(--accent), #6366f1); color:#fff; border:none;
        padding:11px 24px; border-radius:10px; font-size:13.5px; cursor:pointer;
        font-family:'Inter',sans-serif; font-weight:600; box-shadow:0 4px 18px rgba(129,140,248,.4);
    }
    @media print {
        html, body { background:#fff !important; }
        .sheet {
            margin:0; padding:20px; border-radius:0; border:none;
            background:#fff !important; backdrop-filter:none !important;
            color:#16233e !important;
        }
        .doc-head .brand, .doc-title { color:#16233e !important; }
        .block p, table.spec td, .sign-grid .line { color:#16233e !important; }
        table.spec td:first-child { background:#fafaf7 !important; color:#666 !important; }
        .purpose-box { background:#f7f5f0 !important; border:1px dashed var(--line) !important; color:#16233e !important; }
        .sheet { margin:0; border:none; padding:20px; }
        .no-print { display:none !important; }
    }
</style>
</head>
<body>

<div class="actions no-print">
    <button class="btn-print" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
</div>

<div class="sheet">
    <div class="doc-head">
        <div class="brand"><span class="mark">AL</span>Asset Loan</div>
        <div class="meta">
            Dicetak: <?php echo formatDateTime(date('Y-m-d H:i:s')); ?><br>
            Sistem Peminjaman Aset IT
        </div>
    </div>

    <div class="doc-title">Bukti Peminjaman Aset IT</div>
    <div class="doc-code"><?php echo esc($loan['loan_code']); ?></div>

    <div class="grid2">
        <div class="block">
            <div class="block-label">Data Peminjam</div>
            <p><strong><?php echo esc($loan['user_name']); ?></strong></p>
            <p>NIK: <?php echo esc($loan['nik']); ?></p>
            <p>Departemen: <?php echo esc($loan['department'] ?: '-'); ?></p>
            <p>Email: <?php echo esc($loan['email']); ?></p>
        </div>
        <div class="block">
            <div class="block-label">Status &amp; Jadwal</div>
            <p><span class="status-pill"><?php
                $labels = ['pending'=>'Menunggu Persetujuan','approved'=>'Disetujui','active'=>'Sedang Dipinjam','returned'=>'Dikembalikan','overdue'=>'Terlambat','rejected'=>'Ditolak'];
                echo esc($labels[$loan['status']] ?? $loan['status']);
            ?></span></p>
            <p>Tgl Pinjam: <?php echo formatDate($loan['loan_date']); ?></p>
            <p>Jatuh Tempo: <?php echo formatDate($loan['due_date']); ?></p>
            <p>Tgl Kembali: <?php echo formatDate($loan['return_date']); ?></p>
        </div>
    </div>

    <table class="spec">
        <tr><td>Nama Aset</td><td><?php echo esc($loan['asset_name']); ?></td></tr>
        <tr><td>Kode Aset</td><td class="mono"><?php echo esc($loan['asset_code']); ?></td></tr>
        <tr><td>Merk</td><td><?php echo esc($loan['brand'] ?: '-'); ?></td></tr>
        <tr><td>Nomor Seri</td><td><?php echo esc($loan['serial_number'] ?: '-'); ?></td></tr>
        <tr><td>Kondisi Saat Dipinjam</td><td><?php
            $condLabels = ['good'=>'Baik','minor_damage'=>'Rusak Ringan','major_damage'=>'Rusak Berat'];
            echo esc($condLabels[$loan['condition_on_loan']] ?? $loan['condition_on_loan']);
        ?></td></tr>
        <?php if ($loan['condition_on_return']): ?>
        <tr><td>Kondisi Saat Dikembalikan</td><td><?php
            echo esc($condLabels[$loan['condition_on_return']] ?? $loan['condition_on_return']);
        ?></td></tr>
        <?php endif; ?>
    </table>

    <div class="block-label">Tujuan Peminjaman</div>
    <div class="purpose-box"><?php echo nl2br(esc($loan['purpose'])); ?></div>

    <div class="sign-grid">
        <div>
            <div class="line">
                <div class="role">Peminjam</div>
                <?php echo esc($loan['user_name']); ?>
            </div>
        </div>
        <div>
            <div class="line">
                <div class="role">Disetujui Oleh</div>
                <?php echo esc($loan['approved_by_name'] ?: '............................'); ?>
            </div>
        </div>
    </div>

    <div class="footer-note">
        Dokumen ini dihasilkan otomatis oleh Sistem Peminjaman Aset IT dan sah tanpa cap basah selama kode peminjaman terverifikasi pada sistem.
    </div>
</div>

</body>
</html>
