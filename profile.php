<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$conn = getConnection();
$userId = $_SESSION['user_id'];
$errors = [];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if ($name === '') $errors[] = 'Nama wajib diisi.';

    if ($newPassword !== '') {
        if ($currentPassword === '' || !password_verify($currentPassword, $user['password'])) {
            $errors[] = 'Password saat ini salah.';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'Password baru minimal 6 karakter.';
        }
    }

    if (empty($errors)) {
        if ($newPassword !== '') {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, password=? WHERE id=?");
            $stmt->bind_param('sssi', $name, $phone, $hash, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, phone=? WHERE id=?");
            $stmt->bind_param('ssi', $name, $phone, $userId);
        }
        $stmt->execute();
        $_SESSION['name'] = $name;
        flash('success', 'Profil berhasil diperbarui.');
        header('Location: ' . BASE_URL . 'profile.php');
        exit;
    }
}

$pageTitle = 'Profil Saya';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:560px;">
    <div class="card-header"><h3>Profil Saya</h3></div>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo esc($e) . '<br>'; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>NIK</label>
                <input type="text" class="form-control" value="<?php echo esc($user['nik']); ?>" disabled>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="text" class="form-control" value="<?php echo esc($user['email']); ?>" disabled>
            </div>
        </div>
        <div class="form-group">
            <label>Nama Lengkap *</label>
            <input type="text" name="name" class="form-control" value="<?php echo esc($user['name']); ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Departemen</label>
                <input type="text" class="form-control" value="<?php echo esc($user['department']); ?>" disabled>
            </div>
            <div class="form-group">
                <label>No. Telepon</label>
                <input type="text" name="phone" class="form-control" value="<?php echo esc($user['phone']); ?>">
            </div>
        </div>

        <hr style="border:none; border-top:1px dashed var(--line); margin:20px 0;">
        <h4 style="font-size:14px;">Ubah Password</h4>
        <div class="form-row">
            <div class="form-group">
                <label>Password Saat Ini</label>
                <input type="password" name="current_password" class="form-control" placeholder="Isi jika ingin ganti password">
            </div>
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" class="form-control" placeholder="Minimal 6 karakter">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
