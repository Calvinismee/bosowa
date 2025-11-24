<?php
require_once '../config/database.php';
require_once '../config/notification.php';
require_once '../auth/session.php';

requireDriverLogin();

$driver_id = getLoggedInDriverId();
$driver_name = getLoggedInDriverName();

if (!isset($_GET['id'])) {
    setFlashMessage('error', 'ID transaksi tidak ditemukan!');
    header("Location: read.php");
    exit;
}

$id = $_GET['id'];

// Ambil data transaksi
$stmt = $pdo->prepare("SELECT * FROM TRANSAKSI WHERE id_transaksi = :id AND id_user = :driver_id");
$stmt->execute([':id' => $id, ':driver_id' => $driver_id]);
$transaksi = $stmt->fetch();

if (!$transaksi) {
    setFlashMessage('error', 'Transaksi tidak ditemukan!');
    header("Location: read.php");
    exit;
}

$error = '';
$success = '';

// Ambil detail biaya existing
$stmt = $pdo->prepare("SELECT * FROM DETAIL_BIAYA WHERE id_transaksi = :id");
$stmt->execute([':id' => $id]);
$detail_biaya = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'add_biaya') {
        $jenis_biaya = $_POST['jenis_biaya'] ?? '';
        $jumlah = $_POST['jumlah'] ?? '';

        if (empty($jenis_biaya) || empty($jumlah)) {
            $error = "Jenis biaya dan jumlah harus diisi!";
        } elseif ($jumlah <= 0) {
            $error = "Jumlah biaya harus lebih besar dari 0!";
        } else {
            try {
                $sql = "INSERT INTO DETAIL_BIAYA (id_transaksi, jenis_biaya, jumlah) 
                        VALUES (:id_transaksi, :jenis_biaya, :jumlah)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_transaksi' => $id,
                    ':jenis_biaya' => $jenis_biaya,
                    ':jumlah' => $jumlah
                ]);

                $success = "Biaya tambahan berhasil ditambahkan!";
                // Refresh detail biaya
                $stmt = $pdo->prepare("SELECT * FROM DETAIL_BIAYA WHERE id_transaksi = :id");
                $stmt->execute([':id' => $id]);
                $detail_biaya = $stmt->fetchAll();

                // Refresh transaksi untuk total terbaru
                $stmt = $pdo->prepare("SELECT * FROM TRANSAKSI WHERE id_transaksi = :id");
                $stmt->execute([':id' => $id]);
                $transaksi = $stmt->fetch();
                
                setFlashMessage('success', 'Biaya tambahan berhasil ditambahkan!');
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    } elseif ($action == 'edit_biaya') {
        $jenis_biaya_lama = $_POST['jenis_biaya_lama'] ?? '';
        $jenis_biaya_baru = $_POST['jenis_biaya_baru'] ?? '';
        $jumlah = $_POST['jumlah'] ?? '';

        if (empty($jenis_biaya_baru) || empty($jumlah)) {
            $error = "Jenis biaya dan jumlah harus diisi!";
        } elseif ($jumlah <= 0) {
            $error = "Jumlah biaya harus lebih besar dari 0!";
        } else {
            try {
                // Hapus yang lama
                $stmt = $pdo->prepare("DELETE FROM DETAIL_BIAYA WHERE id_transaksi = :id AND jenis_biaya = :jenis_biaya");
                $stmt->execute([':id' => $id, ':jenis_biaya' => $jenis_biaya_lama]);

                // Insert yang baru
                $stmt = $pdo->prepare("INSERT INTO DETAIL_BIAYA (id_transaksi, jenis_biaya, jumlah) 
                        VALUES (:id, :jenis_biaya, :jumlah)");
                $stmt->execute([':id' => $id, ':jenis_biaya' => $jenis_biaya_baru, ':jumlah' => $jumlah]);

                $success = "Biaya berhasil diperbarui!";
                // Refresh detail biaya
                $stmt = $pdo->prepare("SELECT * FROM DETAIL_BIAYA WHERE id_transaksi = :id");
                $stmt->execute([':id' => $id]);
                $detail_biaya = $stmt->fetchAll();

                // Refresh transaksi
                $stmt = $pdo->prepare("SELECT * FROM TRANSAKSI WHERE id_transaksi = :id");
                $stmt->execute([':id' => $id]);
                $transaksi = $stmt->fetch();
                
                setFlashMessage('success', 'Biaya berhasil diperbarui!');
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    } elseif ($action == 'delete_biaya') {
        $jenis_biaya = $_POST['jenis_biaya'] ?? '';

        try {
            $sql = "DELETE FROM DETAIL_BIAYA WHERE id_transaksi = :id_transaksi AND jenis_biaya = :jenis_biaya";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_transaksi' => $id,
                ':jenis_biaya' => $jenis_biaya
            ]);

            $success = "Biaya berhasil dihapus!";
            // Refresh detail biaya
            $stmt = $pdo->prepare("SELECT * FROM DETAIL_BIAYA WHERE id_transaksi = :id");
            $stmt->execute([':id' => $id]);
            $detail_biaya = $stmt->fetchAll();

            // Refresh transaksi
            $stmt = $pdo->prepare("SELECT * FROM TRANSAKSI WHERE id_transaksi = :id");
            $stmt->execute([':id' => $id]);
            $transaksi = $stmt->fetch();
            
            setFlashMessage('success', 'Biaya berhasil dihapus!');
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Biaya Tambahan - Taksi App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom mb-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h5">
                <i class="fas fa-taxi"></i> Taksi App - Kelola Biaya Tambahan
            </span>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($driver_name) ?>
                </span>
                <a href="../auth/logout.php" class="btn btn-sm btn-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h3>Transaksi #<?= $id ?></h3>
                <p class="text-muted">Kelola biaya tambahan untuk transaksi ini</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Info Transaksi</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Rute Tarif ID:</strong> #<?= $transaksi['id_rute_tarif'] ?></p>
                        <p><strong>Tanggal Dibuat:</strong> <?= date('d/m/Y H:i', strtotime($transaksi['tanggal_dibuat'])) ?></p>
                        <p><strong>Total (Inc. Biaya):</strong> <h4>Rp <?= number_format($transaksi['total'], 0, ',', '.') ?></h4></p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Tambah Biaya Tambahan</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_biaya">
                            
                            <div class="mb-3">
                                <label class="form-label" for="jenis_biaya">Jenis Biaya</label>
                                <input type="text" 
                                       id="jenis_biaya"
                                       name="jenis_biaya" 
                                       class="form-control" 
                                       placeholder="Contoh: Tol, Parkir, dll"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="jumlah">Jumlah (Rp)</label>
                                <input type="number" 
                                       id="jumlah"
                                       name="jumlah" 
                                       class="form-control" 
                                       placeholder="0"
                                       min="0"
                                       step="1000"
                                       required>
                            </div>

                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-plus"></i> Tambah Biaya
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Daftar Biaya Tambahan</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($detail_biaya): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jenis Biaya</th>
                                        <th>Jumlah</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_biaya_tambahan = 0;
                                    foreach ($detail_biaya as $biaya): 
                                        $total_biaya_tambahan += $biaya['jumlah'];
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($biaya['jenis_biaya']) ?></td>
                                        <td>Rp <?= number_format($biaya['jumlah'], 0, ',', '.') ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editBiayaModal" 
                                                    onclick="fillBiayaForm('<?= htmlspecialchars($biaya['jenis_biaya']) ?>', <?= $biaya['jumlah'] ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_biaya">
                                                <input type="hidden" name="jenis_biaya" value="<?= htmlspecialchars($biaya['jenis_biaya']) ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-info">
                                        <td><strong>Total Biaya Tambahan</strong></td>
                                        <td><strong>Rp <?= number_format($total_biaya_tambahan, 0, ',', '.') ?></strong></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted py-5">
                            Belum ada biaya tambahan
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-3 mb-4">
                    <a href="edit_payment.php?id=<?= $id ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-credit-card"></i> Lanjut ke Atur Metode Pembayaran
                    </a>
                    <a href="read.php" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Modal Edit Biaya -->
    <div class="modal fade" id="editBiayaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Biaya Tambahan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_biaya">
                    <input type="hidden" name="jenis_biaya_lama" id="jenis_biaya_lama">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="jenis_biaya_baru">Jenis Biaya</label>
                            <input type="text" 
                                   id="jenis_biaya_baru"
                                   name="jenis_biaya_baru" 
                                   class="form-control" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="jumlah_edit">Jumlah (Rp)</label>
                            <input type="number" 
                                   id="jumlah_edit"
                                   name="jumlah" 
                                   class="form-control" 
                                   min="0"
                                   step="1000"
                                   required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function fillBiayaForm(jenis, jumlah) {
            document.getElementById('jenis_biaya_lama').value = jenis;
            document.getElementById('jenis_biaya_baru').value = jenis;
            document.getElementById('jumlah_edit').value = jumlah;
        }
    </script>
</body>
</html>
