<?php
require_once '../config/database.php';
require_once '../config/notification.php';

// Cek apakah ada ID
if (!isset($_GET['id'])) {
    header("Location: read.php");
    exit;
}

$id = $_GET['id'];

// Ambil data lama
$stmt = $pdo->prepare("SELECT * FROM DRIVER WHERE id_user = :id");
$stmt->execute([':id' => $id]);
$driver = $stmt->fetch();

if (!$driver) {
    die("Data driver tidak ditemukan.");
}

// Proses Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama_driver'];
    $jk = $_POST['jenis_kelamin'];
    $status = $_POST['status'];

    // Update query (Username & Password tidak diupdate di form ini untuk kesederhanaan)
    $sql = "UPDATE DRIVER SET nama_driver = :nama, jenis_kelamin = :jk, status = :status WHERE id_user = :id";
    $stmt = $pdo->prepare($sql);
    
    try {
        if ($stmt->execute([':nama' => $nama, ':jk' => $jk, ':status' => $status, ':id' => $id])) {
            // Set flash message sukses
            setFlashMessage('success', 'Data driver "' . $nama . '" berhasil diperbarui!');
            header("Location: read.php");
            exit;
        }
    } catch (PDOException $e) {
        $error = "Gagal update: " . $e->getMessage();
    }
}

require_once '../layout/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-warning">
        <h4 class="mb-0">Edit Driver: <?= htmlspecialchars($driver['nama_driver']) ?></h4>
    </div>
    <div class="card-body">

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nama Driver</label>
                <input type="text" name="nama_driver" class="form-control" value="<?= htmlspecialchars($driver['nama_driver']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Username (Read Only)</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($driver['username']) ?>" readonly disabled>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-select">
                        <option value="Pria" <?= $driver['jenis_kelamin'] == 'Pria' ? 'selected' : '' ?>>Pria</option>
                        <option value="Wanita" <?= $driver['jenis_kelamin'] == 'Wanita' ? 'selected' : '' ?>>Wanita</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="aktif" <?= $driver['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= $driver['status'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Data</button>
            <a href="read.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<?php require_once '../layout/footer.php'; ?>