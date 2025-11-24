<?php
require_once '../config/database.php';
require_once '../config/notification.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama_driver'];
    $user = $_POST['username'];
    $pass = $_POST['password']; 
    $jk   = $_POST['jenis_kelamin'];
    $status = $_POST['status'];

    // Validasi sederhana
    if(!empty($nama) && !empty($user) && !empty($pass)) {
        $sql = "INSERT INTO DRIVER (nama_driver, username, password, jenis_kelamin, status) 
                VALUES (:nama, :user, :pass, :jk, :status)";
        
        $stmt = $pdo->prepare($sql);
        $data = [
            ':nama' => $nama,
            ':user' => $user,
            ':pass' => $pass, 
            ':jk'   => $jk,
            ':status' => $status
        ];

        try {
            if ($stmt->execute($data)) {
                // Set flash message sukses
                setFlashMessage('success', 'Driver "' . $nama . '" berhasil ditambahkan!');
                // Redirect ke read.php setelah sukses
                header("Location: read.php");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Semua field wajib diisi!";
    }
}

require_once '../layout/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-success text-white">
        <h4 class="mb-0">Tambah Driver Baru</h4>
    </div>
    <div class="card-body">
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama_driver" class="form-control" required placeholder="Contoh: Budi Santoso">
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-select">
                        <option value="Pria">Pria</option>
                        <option value="Wanita">Wanita</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Simpan Data</button>
            <a href="read.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<?php require_once '../layout/footer.php'; ?>