<?php
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$conn = getConnection();
$users = $conn->query("SELECT * FROM users ORDER BY role DESC, name ASC");

$pageTitle = 'Pengguna';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Daftar Pengguna</h3>
        <a href="<?php echo BASE_URL; ?>user_form.php" class="btn btn-accent">+ Tambah Pengguna</a>
    </div>
    <div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr><th>NIK</th><th>Nama</th><th>Email</th><th>Departemen</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php if ($users->num_rows === 0): ?>
                <tr><td colspan="7" class="table-empty">Belum ada data pengguna.</td></tr>
            <?php endif; ?>
            <?php while ($u = $users->fetch_assoc()): ?>
            <tr>
                <td class="mono"><?php echo esc($u['nik']); ?></td>
                <td><?php echo esc($u['name']); ?></td>
                <td><?php echo esc($u['email']); ?></td>
                <td><?php echo esc($u['department'] ?: '-'); ?></td>
                <td><span class="badge <?php echo $u['role']=='admin'?'badge-dark':'badge-info'; ?>"><?php echo $u['role']=='admin'?'Admin':'Karyawan'; ?></span></td>
                <td><span class="badge <?php echo $u['status']=='active'?'badge-success':'badge-secondary'; ?>"><?php echo $u['status']=='active'?'Aktif':'Nonaktif'; ?></span></td>
                <td>
                    <div class="flex-gap">
                        <a href="<?php echo BASE_URL; ?>user_form.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <a href="<?php echo BASE_URL; ?>user_toggle.php?id=<?php echo $u['id']; ?>" class="btn btn-sm <?php echo $u['status']=='active'?'btn-danger':'btn-success'; ?>" data-confirm="<?php echo $u['status']=='active'?'Nonaktifkan':'Aktifkan'; ?> pengguna &quot;<?php echo esc($u['name']); ?>&quot;?">
                                <?php echo $u['status']=='active'?'Nonaktifkan':'Aktifkan'; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
