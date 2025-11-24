<?php
require_once '../config/database.php';
require_once '../config/notification.php';
require_once '../layout/header.php';

// Ambil data driver dari database
$sql = "SELECT * FROM DRIVER ORDER BY id_user ASC";
$stmt = $pdo->query($sql);
?>

<!-- Tampilkan Flash Message -->
<?php displayFlashMessage(); ?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2>Daftar Driver</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="create.php" class="btn btn-success">+ Tambah Driver</a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nama Driver</th>
                <th>Username</th>
                <th>Jenis Kelamin</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $stmt->fetch()): ?>
            <tr>
                <td><?= $row['id_user'] ?></td>
                <td><?= htmlspecialchars($row['nama_driver']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= $row['jenis_kelamin'] ?></td>
                <td>
                    <?php if($row['status'] == 'aktif'): ?>
                        <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="edit.php?id=<?= $row['id_user'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="delete.php?id=<?= $row['id_user'] ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Yakin ingin menghapus data <?= $row['nama_driver'] ?>?')">Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once '../layout/footer.php'; ?>