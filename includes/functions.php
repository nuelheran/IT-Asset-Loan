<?php
/**
 * Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------------------
// Auth helpers
// ----------------------------------------------------------------
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}

function currentUser() {
    static $user = null;
    if ($user === null && isLoggedIn()) {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    }
    return $user;
}

// ----------------------------------------------------------------
// General helpers
// ----------------------------------------------------------------
function esc($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function formatDate($date, $format = 'd M Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

function formatDateTime($date, $format = 'd M Y H:i') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function generateLoanCode() {
    return 'LOAN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function generateAssetCode($conn) {
    $result = $conn->query("SELECT MAX(CAST(SUBSTRING(asset_code, 5) AS UNSIGNED)) as max_num FROM assets WHERE asset_code LIKE 'AST-%'");
    $row = $result->fetch_assoc();
    $next = ($row['max_num'] ?? 0) + 1;
    return 'AST-' . str_pad($next, 4, '0', STR_PAD_LEFT);
}

function statusBadge($status) {
    $map = [
        'available'      => ['label' => 'Tersedia', 'class' => 'badge-success'],
        'on_loan'        => ['label' => 'Dipinjam', 'class' => 'badge-warning'],
        'maintenance'    => ['label' => 'Maintenance', 'class' => 'badge-secondary'],
        'retired'        => ['label' => 'Pensiun', 'class' => 'badge-dark'],
        'pending'        => ['label' => 'Menunggu Persetujuan', 'class' => 'badge-warning'],
        'approved'       => ['label' => 'Disetujui', 'class' => 'badge-info'],
        'rejected'       => ['label' => 'Ditolak', 'class' => 'badge-danger'],
        'active'         => ['label' => 'Sedang Dipinjam', 'class' => 'badge-primary'],
        'returned'       => ['label' => 'Dikembalikan', 'class' => 'badge-success'],
        'overdue'        => ['label' => 'Terlambat', 'class' => 'badge-danger'],
        'good'           => ['label' => 'Baik', 'class' => 'badge-success'],
        'minor_damage'   => ['label' => 'Rusak Ringan', 'class' => 'badge-warning'],
        'major_damage'   => ['label' => 'Rusak Berat', 'class' => 'badge-danger'],
        'active_user'    => ['label' => 'Aktif', 'class' => 'badge-success'],
        'inactive'       => ['label' => 'Nonaktif', 'class' => 'badge-secondary'],
    ];
    $info = $map[$status] ?? ['label' => $status, 'class' => 'badge-secondary'];
    return '<span class="badge ' . $info['class'] . '">' . esc($info['label']) . '</span>';
}

function logLoanAction($conn, $loanId, $status, $note, $actionBy) {
    $stmt = $conn->prepare("INSERT INTO loan_logs (loan_id, status, note, action_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('issi', $loanId, $status, $note, $actionBy);
    $stmt->execute();
}

// Auto-update overdue loans (dipanggil di dashboard/list)
function refreshOverdueLoans($conn) {
    $conn->query("UPDATE loans SET status = 'overdue' WHERE status = 'active' AND due_date < CURDATE()");
}
