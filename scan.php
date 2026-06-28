<?php
$pageTitle = 'Scan Aset';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Scan QR Code Aset</h3>
        <button type="button" class="btn btn-sm btn-outline" id="toggleManualBtn">Input Manual</button>
    </div>

    <div id="scanArea">
        <div id="qr-reader"></div>
        <div class="scan-hint">Arahkan kamera ke QR code yang tertempel pada aset.</div>
        <div class="scan-controls">
            <select id="cameraSelect" class="form-control" style="display:none;"></select>
        </div>
    </div>

    <div id="manualArea" style="display:none;">
        <form id="manualForm" class="filter-bar" style="margin-bottom:0;">
            <input type="text" id="manualCode" class="form-control" placeholder="Ketik kode aset, contoh: AST-0001" autocomplete="off">
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>
    </div>
</div>

<div id="resultArea"></div>

<style>
#qr-reader {
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    border-radius: var(--radius-lg);
    overflow: hidden;
    border: 1px solid var(--glass-border);
}
#qr-reader video { width: 100% !important; }
.scan-hint { text-align:center; color: var(--text-lo); font-size: 13px; margin-top: 14px; }
.scan-controls { max-width: 480px; margin: 12px auto 0; }

.scan-result-card {
    margin-top: 20px;
    animation: alertSlideIn .3s ease;
}
.scan-result-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--glass-border-soft);
}
.scan-result-header h3 { margin: 0; font-size: 16px; }
.scan-asset-photo-placeholder {
    width: 56px; height: 56px;
    border-radius: var(--radius-sm);
    background: var(--glass-fill-strong);
    border: 1px solid var(--glass-border);
    display: flex; align-items: center; justify-content: center;
    color: var(--accent-cyan);
    flex-shrink: 0;
}
.scan-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 16px;
}
.scan-info-grid .block-label {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--text-lo);
    margin-bottom: 6px;
    font-weight: 700;
}
.scan-info-grid p { margin: 3px 0; font-size: 13.5px; color: var(--text-mid); }
.scan-info-grid p strong { color: var(--text-hi); }
.scan-loan-box {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--glass-border-soft);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    margin-bottom: 16px;
}
.scan-history-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px 0;
    border-bottom: 1px solid var(--glass-border-soft);
    font-size: 12.5px;
}
.scan-history-item:last-child { border-bottom: none; }
.scan-history-item:hover { background: rgba(255,255,255,0.04); border-radius: var(--radius-sm); padding-left: 6px; padding-right: 6px; }
.scan-error {
    text-align: center;
    padding: 30px 20px;
    color: var(--danger);
}
.scan-loading {
    text-align: center;
    padding: 30px 20px;
    color: var(--text-lo);
}
@media (max-width: 640px) {
    .scan-info-grid { grid-template-columns: 1fr; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="<?php echo BASE_URL; ?>assets/js/vendor/html5-qrcode.min.js"></script>
<script>
(function () {
    'use strict';

    var resultArea = document.getElementById('resultArea');
    var scanArea = document.getElementById('scanArea');
    var manualArea = document.getElementById('manualArea');
    var toggleBtn = document.getElementById('toggleManualBtn');
    var manualForm = document.getElementById('manualForm');
    var manualInput = document.getElementById('manualCode');
    var html5QrCode = null;
    var isProcessing = false;

    toggleBtn.addEventListener('click', function () {
        var showingManual = manualArea.style.display !== 'none';
        if (showingManual) {
            manualArea.style.display = 'none';
            scanArea.style.display = 'block';
            toggleBtn.textContent = 'Input Manual';
            startScanner();
        } else {
            manualArea.style.display = 'block';
            scanArea.style.display = 'none';
            toggleBtn.textContent = 'Gunakan Kamera';
            stopScanner();
            manualInput.focus();
        }
    });

    manualForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var code = manualInput.value.trim();
        if (code) lookupAsset(code);
    });

    function startScanner() {
        if (typeof Html5Qrcode === 'undefined') {
            showError('Pustaka pemindai QR gagal dimuat. Periksa koneksi internet Anda, atau gunakan input manual.');
            return;
        }
        html5QrCode = new Html5Qrcode("qr-reader");
        var config = { fps: 10, qrbox: { width: 240, height: 240 } };

        Html5Qrcode.getCameras().then(function (cameras) {
            if (!cameras || cameras.length === 0) {
                showError('Tidak ada kamera yang terdeteksi pada perangkat ini. Gunakan input manual sebagai alternatif.');
                return;
            }
            // Prioritaskan kamera belakang (environment) untuk perangkat mobile
            var preferred = cameras.find(function (c) {
                return /back|rear|environment/i.test(c.label);
            }) || cameras[0];

            html5QrCode.start(
                preferred.id,
                config,
                function onScanSuccess(decodedText) {
                    if (isProcessing) return;
                    isProcessing = true;
                    lookupAsset(decodedText);
                },
                function onScanFailure() { /* diam saja, ini dipanggil tiap frame tanpa hasil */ }
            ).catch(function (err) {
                showError('Gagal mengakses kamera: ' + err + '. Pastikan Anda mengizinkan akses kamera pada browser, atau gunakan input manual.');
            });
        }).catch(function (err) {
            showError('Tidak dapat mengakses daftar kamera: ' + err + '. Pastikan halaman diakses melalui HTTPS dan izin kamera sudah diberikan.');
        });
    }

    function stopScanner() {
        if (html5QrCode) {
            html5QrCode.stop().catch(function () {});
            html5QrCode = null;
        }
    }

    function lookupAsset(rawCode) {
        resultArea.innerHTML = '<div class="card scan-loading">Mencari data aset...</div>';
        fetch('<?php echo BASE_URL; ?>asset_lookup.php?code=' + encodeURIComponent(rawCode))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                renderResult(data);
                isProcessing = false;
            })
            .catch(function () {
                showError('Terjadi kesalahan saat menghubungi server. Silakan coba lagi.');
                isProcessing = false;
            });
    }

    function showError(message) {
        resultArea.innerHTML = '<div class="card scan-error">' + escapeHtml(message) + '</div>';
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function statusBadgeHtml(label, status) {
        var map = {
            available: 'badge-success', on_loan: 'badge-warning', maintenance: 'badge-secondary', retired: 'badge-dark',
            pending: 'badge-warning', approved: 'badge-info', active: 'badge-primary', returned: 'badge-success',
            overdue: 'badge-danger', rejected: 'badge-danger', good: 'badge-success', minor_damage: 'badge-warning', major_damage: 'badge-danger'
        };
        var cls = map[status] || 'badge-secondary';
        return '<span class="badge ' + cls + '">' + escapeHtml(label) + '</span>';
    }

    function renderResult(data) {
        if (!data.success) {
            showError(data.message || 'Aset tidak ditemukan.');
            return;
        }
        var a = data.asset;
        var activeLoans = data.active_loans || [];

        var html = '<div class="card scan-result-card">';
        html += '<div class="scan-result-header">';
        html += '<div class="flex-gap">';
        html += '<div class="scan-asset-photo-placeholder"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="13" rx="2"/><path d="M9 21h6"/><path d="M12 16v5"/></svg></div>';
        html += '<div><h3>' + escapeHtml(a.name) + '</h3><span class="mono text-muted">' + escapeHtml(a.asset_code) + '</span></div>';
        html += '</div>';
        html += statusBadgeHtml(a.status_label, a.status);
        html += '</div>';

        html += '<div class="scan-info-grid">';
        html += '<div><div class="block-label">Kategori</div><p>' + escapeHtml(a.category_name) + '</p></div>';
        html += '<div><div class="block-label">Merk</div><p>' + escapeHtml(a.brand || '-') + '</p></div>';
        html += '<div><div class="block-label">No. Seri</div><p>' + escapeHtml(a.serial_number || '-') + '</p></div>';
        html += '<div><div class="block-label">Kondisi</div><p>' + statusBadgeHtml(a.condition_label, a.condition_status) + '</p></div>';
        html += '</div>';

        if (a.specification) {
            html += '<div style="margin-bottom:16px;"><div class="block-label">Spesifikasi</div><p>' + escapeHtml(a.specification) + '</p></div>';
        }

        if (activeLoans.length > 0) {
            var sectionLabel = activeLoans.length > 1
                ? 'Peminjaman Aktif (' + activeLoans.length + ' permintaan)'
                : 'Sedang Dipinjam';
            html += '<div class="block-label" style="margin-bottom:8px;">' + sectionLabel + '</div>';
            activeLoans.forEach(function (loan) {
                html += '<div class="scan-loan-box">';
                html += '<p><strong>' + escapeHtml(loan.user_name) + '</strong>' + (loan.department ? ' &middot; ' + escapeHtml(loan.department) : '') + '</p>';
                html += '<p>Kode Pinjam: <span class="mono">' + escapeHtml(loan.loan_code) + '</span></p>';
                html += '<p>Periode: ' + escapeHtml(loan.loan_date_fmt) + ' &rarr; ' + escapeHtml(loan.due_date_fmt) + '</p>';
                html += '<p>Status: ' + statusBadgeHtml(loan.status_label, loan.status) + '</p>';
                html += '<div class="flex-gap" style="margin-top:14px; flex-wrap:wrap;">';
                html += '<a href="<?php echo BASE_URL; ?>loan_detail.php?id=' + loan.id + '" class="btn btn-outline btn-sm">Lihat Detail Peminjaman</a>';
                if (data.is_admin) {
                    if (loan.status === 'pending') {
                        html += '<a href="<?php echo BASE_URL; ?>loan_approve.php?id=' + loan.id + '" class="btn btn-success btn-sm">Setujui Peminjaman</a>';
                    } else if (loan.status === 'approved') {
                        html += '<a href="<?php echo BASE_URL; ?>loan_detail.php?id=' + loan.id + '" class="btn btn-primary btn-sm">Konfirmasi Serah Aset</a>';
                    } else if (loan.status === 'active' || loan.status === 'overdue') {
                        html += '<a href="<?php echo BASE_URL; ?>loan_detail.php?id=' + loan.id + '&action=return" class="btn btn-success btn-sm">Konfirmasi Pengembalian</a>';
                    }
                }
                html += '</div>';
                html += '</div>';
            });
        } else {
            html += '<div class="alert alert-success" style="margin-bottom:16px;">Aset ini sedang tidak dipinjam siapapun.</div>';
        }

        if (data.history && data.history.length > 0) {
            html += '<div class="block-label" style="margin-bottom:8px;">Riwayat Peminjaman Terakhir</div>';
            data.history.forEach(function (h) {
                html += '<a href="<?php echo BASE_URL; ?>loan_detail.php?id=' + h.id + '" class="scan-history-item" style="text-decoration:none; color:inherit;">';
                html += '<span class="mono">' + escapeHtml(h.loan_code) + '</span>';
                html += '<span>' + escapeHtml(h.user_name) + '</span>';
                html += statusBadgeHtml(h.status_label, h.status);
                html += '</a>';
            });
        }

        html += '<div class="flex-gap" style="margin-top:18px;">';
        html += '<a href="<?php echo BASE_URL; ?>assets_list.php?search=' + encodeURIComponent(a.asset_code) + '" class="btn btn-outline btn-sm">Lihat di Daftar Aset</a>';
        html += '<button type="button" class="btn btn-primary btn-sm" onclick="location.reload()">Scan Lagi</button>';
        html += '</div>';

        html += '</div>';
        resultArea.innerHTML = html;
    }

    // Mulai kamera otomatis saat halaman dibuka
    startScanner();

    // Hentikan kamera dengan rapi saat pengguna pindah halaman
    window.addEventListener('beforeunload', stopScanner);
})();
</script>
