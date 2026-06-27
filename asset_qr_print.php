<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$conn = getConnection();
$id = $_GET['id'] ?? null;

$stmt = $conn->prepare("
    SELECT a.*, c.name as category_name
    FROM assets a
    JOIN categories c ON c.id = a.category_id
    WHERE a.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    flash('error', 'Aset tidak ditemukan.');
    header('Location: ' . BASE_URL . 'assets_list.php');
    exit;
}

// Payload QR: data terstruktur sederhana (pipe-separated) supaya mudah di-parse
// saat scan, namun tetap manusiawi jika dibaca langsung oleh aplikasi QR generik.
$qrPayload = json_encode([
    'type' => 'IT_ASSET',
    'code' => $asset['asset_code'],
]);

$conditionLabels = ['good' => 'Baik', 'minor_damage' => 'Rusak Ringan', 'major_damage' => 'Rusak Berat'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>QR Code Aset — <?php echo esc($asset['asset_code']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="<?php echo BASE_URL; ?>assets/js/vendor/qrcode.min.js"></script>
<style>
    :root { --ink:#0a0e1a; --accent:#818cf8; --accent2:#5eead4; --line:#dcd8cd; }
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
    .actions { text-align:center; margin:20px 0; }
    .btn-print {
        background:linear-gradient(135deg, var(--accent), #6366f1); color:#fff; border:none;
        padding:11px 24px; border-radius:10px; font-size:13.5px; cursor:pointer;
        font-family:'Inter',sans-serif; font-weight:600; box-shadow:0 4px 18px rgba(129,140,248,.4);
        margin: 0 6px;
    }
    .btn-print.secondary { background: rgba(255,255,255,0.08); box-shadow:none; border: 1px solid rgba(255,255,255,0.18); }

    .label-sheet {
        max-width: 380px; margin: 30px auto; padding: 28px 26px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.14);
        backdrop-filter: blur(22px) saturate(140%);
        -webkit-backdrop-filter: blur(22px) saturate(140%);
        border-radius: 22px;
        text-align: center;
    }
    .label-brand { font-family:'Space Grotesk',sans-serif; font-weight:600; font-size:13px; color:#8b97b3; text-transform:uppercase; letter-spacing:.08em; margin-bottom:18px; }
    #qrcode { display:inline-block; padding:14px; background:#fff; border-radius:14px; }
    .label-code { font-family:'JetBrains Mono',monospace; font-size:17px; color:var(--accent2); margin-top:16px; font-weight:600; letter-spacing:.02em; }
    .label-name { font-family:'Space Grotesk',sans-serif; font-size:16px; font-weight:600; color:#fff; margin-top:6px; }
    .label-meta { font-size:12px; color:#8b97b3; margin-top:10px; line-height:1.6; }
    .label-meta strong { color:#cbd5e1; }
    .label-footer { margin-top:18px; padding-top:14px; border-top:1px dashed rgba(255,255,255,0.14); font-size:10.5px; color:#5d6a89; }

    @media print {
        html, body { background:#fff !important; }
        .no-print { display:none !important; }
        .label-sheet {
            margin:0; border-radius:0; border: 1px dashed #999;
            background:#fff !important; backdrop-filter:none !important;
            color:#16233e !important; box-shadow:none;
            page-break-inside: avoid;
        }
        .label-brand { color:#888 !important; }
        .label-name { color:#16233e !important; }
        .label-meta { color:#555 !important; }
        .label-meta strong { color:#16233e !important; }
        .label-footer { color:#999 !important; border-color:#ccc; }
        #qrcode { border: 1px solid #eee; }
    }
</style>
</head>
<body>

<div class="actions no-print">
    <button class="btn-print" onclick="window.print()">Cetak Label QR</button>
    <a href="<?php echo BASE_URL; ?>assets_list.php" class="btn-print secondary" style="text-decoration:none; display:inline-block;">Kembali</a>
</div>

<div class="label-sheet">
    <div class="label-brand">IT Asset Loan — Label Aset</div>
    <div id="qrcode"></div>
    <div class="label-code"><?php echo esc($asset['asset_code']); ?></div>
    <div class="label-name"><?php echo esc($asset['name']); ?></div>
    <div class="label-meta">
        Kategori: <strong><?php echo esc($asset['category_name']); ?></strong><br>
        Merk: <strong><?php echo esc($asset['brand'] ?: '-'); ?></strong><br>
        No. Seri: <strong><?php echo esc($asset['serial_number'] ?: '-'); ?></strong>
    </div>
    <div class="label-footer">
        Scan QR ini untuk melihat status &amp; riwayat peminjaman terkini.
    </div>
</div>

<script>
var qrData = <?php echo $qrPayload; ?>;
new QRCode(document.getElementById("qrcode"), {
    text: JSON.stringify(qrData),
    width: 200,
    height: 200,
    colorDark: "#0a0e1a",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.M
});
</script>

</body>
</html>
