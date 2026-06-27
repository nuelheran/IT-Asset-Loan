<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$conn = getConnection();

// Bisa difilter via query string ?ids=1,2,3 atau ?category=2, default semua aset non-pensiun
$idsParam = $_GET['ids'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

$where = ["a.status != 'retired'"];
$params = [];
$types = '';

if ($idsParam !== '') {
    $ids = array_filter(array_map('intval', explode(',', $idsParam)));
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $where = ["a.id IN ($placeholders)"];
        $params = $ids;
        $types = str_repeat('i', count($ids));
    }
} elseif ($categoryFilter !== '') {
    $where[] = "a.category_id = ?";
    $params[] = $categoryFilter;
    $types .= 'i';
}

$sql = "SELECT a.*, c.name as category_name FROM assets a
        JOIN categories c ON c.id = a.category_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY a.asset_code";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$assets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Label QR Massal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="<?php echo BASE_URL; ?>assets/js/vendor/qrcode.min.js"></script>
<style>
    :root { --ink:#0a0e1a; --accent:#818cf8; --accent2:#5eead4; }
    * { box-sizing: border-box; }
    html, body {
        font-family:'Inter',sans-serif; color:#e2e8f0; margin:0; min-height:100vh;
        background: #0a0e1a;
        background-image:
            radial-gradient(circle at 10% 10%, rgba(129,140,248,.3), transparent 40%),
            radial-gradient(circle at 90% 20%, rgba(94,234,212,.18), transparent 42%),
            linear-gradient(160deg, #1b2a52 0%, #0a0e1a 50%, #0f2438 100%);
    }
    .toolbar {
        position: sticky; top: 0; z-index: 10;
        display:flex; align-items:center; gap: 12px; flex-wrap: wrap;
        padding: 16px 24px;
        background: rgba(10,14,26,0.85);
        backdrop-filter: blur(16px) saturate(150%);
        border-bottom: 1px solid rgba(255,255,255,0.12);
    }
    .toolbar select {
        background: rgba(255,255,255,0.06); color:#e2e8f0; border: 1px solid rgba(255,255,255,0.16);
        padding: 8px 12px; border-radius: 8px; font-size: 13px; font-family:'Inter',sans-serif; cursor:pointer;
    }
    .toolbar select option { background-color:#161b2e; color:#f1f5f9; }
    .btn-print {
        background:linear-gradient(135deg, var(--accent), #6366f1); color:#fff; border:none;
        padding:9px 20px; border-radius:9px; font-size:13px; cursor:pointer;
        font-family:'Inter',sans-serif; font-weight:600; box-shadow:0 4px 14px rgba(129,140,248,.4);
    }
    .btn-print.secondary { background: rgba(255,255,255,0.08); box-shadow:none; border: 1px solid rgba(255,255,255,0.18); text-decoration:none; }
    .count-info { font-size: 12.5px; color:#8b97b3; margin-left: auto; }

    .label-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        padding: 24px;
        max-width: 1100px;
        margin: 0 auto;
    }
    .label-card {
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.14);
        border-radius: 16px;
        padding: 18px 14px;
        text-align: center;
        flex: 0 0 calc(33.333% - 11px);
        width: calc(33.333% - 11px);
    }
    .label-qr { display:inline-block; padding: 8px; background:#fff; border-radius: 10px; }
    .label-qr canvas, .label-qr img { width: 110px !important; height: 110px !important; }
    .label-code { font-family:'JetBrains Mono',monospace; font-size: 13px; color: var(--accent2); margin-top: 10px; font-weight:600; }
    .label-name { font-family:'Space Grotesk',sans-serif; font-size: 12.5px; font-weight:600; color:#fff; margin-top: 4px; line-height:1.35; }

    .empty-state { text-align:center; padding: 60px 20px; color:#8b97b3; }

    @media print {
        html, body { background:#fff !important; }
        .no-print { display:none !important; }
        .label-grid { padding: 10px; gap: 10px; max-width: 100%; }
        .label-card {
            background:#fff !important; border: 1px dashed #999; backdrop-filter:none !important;
            page-break-inside: avoid;
            flex: 0 0 calc(33.333% - 7px);
            width: calc(33.333% - 7px);
        }
        .label-code, .label-name { color:#16233e !important; }
    }
    @media (max-width: 700px) {
        .label-card { flex: 0 0 calc(50% - 8px); width: calc(50% - 8px); }
    }
    @media (max-width: 420px) {
        .label-card { flex: 0 0 100%; width: 100%; }
    }
</style>
</head>
<body>

<div class="toolbar no-print">
    <form method="GET" style="display:flex; gap:10px; align-items:center;">
        <select name="category" onchange="this.form.submit()">
            <option value="">Semua Kategori</option>
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>><?php echo esc($cat['name']); ?></option>
            <?php endwhile; ?>
        </select>
    </form>
    <button class="btn-print" onclick="window.print()">Cetak Semua Label</button>
    <a href="<?php echo BASE_URL; ?>assets_list.php" class="btn-print secondary">Kembali</a>
    <span class="count-info"><?php echo count($assets); ?> label siap dicetak</span>
</div>

<?php if (empty($assets)): ?>
    <div class="empty-state">Tidak ada aset yang cocok untuk dicetak labelnya.</div>
<?php else: ?>
<div class="label-grid">
    <?php foreach ($assets as $i => $asset): ?>
    <div class="label-card">
        <div class="label-qr" id="qr-<?php echo $i; ?>"></div>
        <div class="label-code"><?php echo esc($asset['asset_code']); ?></div>
        <div class="label-name"><?php echo esc($asset['name']); ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
var assetCodes = <?php echo json_encode(array_column($assets, 'asset_code')); ?>;
assetCodes.forEach(function(code, i) {
    new QRCode(document.getElementById('qr-' + i), {
        text: JSON.stringify({ type: 'IT_ASSET', code: code }),
        width: 110,
        height: 110,
        colorDark: "#0a0e1a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });
});
</script>

</body>
</html>
