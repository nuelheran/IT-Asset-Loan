<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$conn = getConnection();
$id = $_GET['id'] ?? null;
$isEdit = false;
$user = ['nik'=>'','name'=>'','email'=>'','department'=>'','phone'=>'','role'=>'employee','status'=>'active'];

if ($id) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        flash('error', 'Pengguna tidak ditemukan.');
        header('Location: ' . BASE_URL . 'users_list.php');
        exit;
    }
    $user = $result->fetch_assoc();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user['nik'] = trim($_POST['nik'] ?? '');
    $user['name'] = trim($_POST['name'] ?? '');
    $user['email'] = trim($_POST['email'] ?? '');
    $user['department'] = trim($_POST['department'] ?? '');
    $user['phone'] = trim($_POST['phone'] ?? '');
    $user['role'] = $_POST['role'] ?? 'employee';
    $user['status'] = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';

    if ($user['nik'] === '') $errors[] = 'NIK wajib diisi.';
    if ($user['name'] === '') $errors[] = 'Nama wajib diisi.';
    if ($user['email'] === '') $errors[] = 'Email wajib diisi.';
    if (!$isEdit && $password === '') $errors[] = 'Password wajib diisi untuk pengguna baru.';

    if (empty($errors)) {
        // Cek duplikasi email/nik
        $dupCheck = $conn->prepare("SELECT id FROM users WHERE (email = ? OR nik = ?) AND id != ?");
        $checkId = $isEdit ? $id : 0;
        $dupCheck->bind_param('ssi', $user['email'], $user['nik'], $checkId);
        $dupCheck->execute();
        if ($dupCheck->get_result()->num_rows > 0) {
            $errors[] = 'NIK atau Email sudah digunakan oleh pengguna lain.';
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET nik=?, name=?, email=?, department=?, phone=?, role=?, status=?, password=? WHERE id=?");
                $stmt->bind_param('ssssssssi', $user['nik'], $user['name'], $user['email'], $user['department'], $user['phone'], $user['role'], $user['status'], $hash, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nik=?, name=?, email=?, department=?, phone=?, role=?, status=? WHERE id=?");
                $stmt->bind_param('sssssssi', $user['nik'], $user['name'], $user['email'], $user['department'], $user['phone'], $user['role'], $user['status'], $id);
            }
            $stmt->execute();
            flash('success', 'Data pengguna berhasil diperbarui.');
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (nik, name, email, password, department, phone, role, status) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param('ssssssss', $user['nik'], $user['name'], $user['email'], $hash, $user['department'], $user['phone'], $user['role'], $user['status']);
            $stmt->execute();
            flash('success', 'Pengguna baru berhasil ditambahkan.');
        }
        header('Location: ' . BASE_URL . 'users_list.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:640px;">
    <div class="card-header"><h3><?php echo $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna Baru'; ?></h3></div>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo esc($e) . '<br>'; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>NIK *</label>
                <input type="text" name="nik" class="form-control" value="<?php echo esc($user['nik']); ?>" required>
            </div>
            <div class="form-group">
                <label>Nama Lengkap *</label>
                <input type="text" name="name" class="form-control" value="<?php echo esc($user['name']); ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" value="<?php echo esc($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label>No. Telepon</label>
                <input type="text" name="phone" class="form-control" value="<?php echo esc($user['phone']); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Departemen</label>
                <input type="text" name="department" class="form-control" value="<?php echo esc($user['department']); ?>">
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role" class="form-control" required>
                    <option value="employee" <?php echo $user['role']=='employee'?'selected':''; ?>>Karyawan</option>
                    <option value="admin" <?php echo $user['role']=='admin'?'selected':''; ?>>Admin</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Password <?php echo $isEdit ? '(kosongkan jika tidak diubah)' : '*'; ?></label>
                <input type="password" name="password" class="form-control" placeholder="<?php echo $isEdit ? '••••••••' : 'Minimal 6 karakter'; ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?php echo $user['status']=='active'?'selected':''; ?>>Aktif</option>
                    <option value="inactive" <?php echo $user['status']=='inactive'?'selected':''; ?>>Nonaktif</option>
                </select>
            </div>
        </div>
        <div class="flex-gap">
            <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Simpan Perubahan' : 'Tambah Pengguna'; ?></button>
            <a href="<?php echo BASE_URL; ?>users_list.php" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
