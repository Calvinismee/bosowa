<?php
require_once '../config/database.php';
require_once '../config/notification.php';
require_once '../auth/session.php';

requireDriverLogin();

$driver_id = getLoggedInDriverId();
$driver_name = getLoggedInDriverName();
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_rute_tarif = $_POST['id_rute_tarif'] ?? '';

    // Validasi
    if (empty($id_rute_tarif)) {
        $error = "Rute tarif harus dipilih!";
    } else {
        try {
            $sql = "INSERT INTO TRANSAKSI (id_user, id_rute_tarif, tanggal_dibuat, total) 
                    VALUES (:id_user, :id_rute_tarif, NOW(), 
                            (SELECT harga FROM RUTE_TARIF WHERE id_rute_tarif = :id_rute_tarif))";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_user' => $driver_id,
                ':id_rute_tarif' => $id_rute_tarif
            ]);

            // Ambil ID transaksi yang baru dibuat
            $id_transaksi = $pdo->lastInsertId();

            setFlashMessage('success', 'Transaksi berhasil dibuat! Silakan lengkapi detailnya.');
            header("Location: edit.php?id=" . $id_transaksi);
            exit;
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
    <title>Tambah Transaksi - Taksi App</title>
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
        .form-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom mb-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h5">
                <i class="fas fa-taxi"></i> Taksi App - Tambah Transaksi
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
            <div class="col-md-8 offset-md-2">
                <h3>Tambah Transaksi Baru</h3>
                <p class="text-muted">Isi form di bawah untuk menambah transaksi</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card form-card">
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label" for="id_rute_tarif">Pilih Rute Tarif</label>
                                <select id="id_rute_tarif"
                                        name="id_rute_tarif" 
                                        class="form-select" 
                                        required>
                                    <option value="">-- Pilih Rute Tarif --</option>
                                    <?php
                                    // Ambil daftar rute tarif
                                    $sql = "SELECT rt.id_rute_tarif, r.jenis, kt.golongan, rt.harga 
                                            FROM RUTE_TARIF rt
                                            JOIN RUTE r ON rt.id_rute = r.id_rute
                                            JOIN KATEGORI_TARIF kt ON rt.id_tarif = kt.id_tarif
                                            ORDER BY r.jenis, kt.golongan";
                                    $stmt = $pdo->query($sql);
                                    $rute_tarifs = $stmt->fetchAll();
                                    
                                    foreach ($rute_tarifs as $rt):
                                    ?>
                                    <option value="<?= $rt['id_rute_tarif'] ?>">
                                        <?= htmlspecialchars($rt['jenis']) ?> - <?= htmlspecialchars($rt['golongan']) ?> (Rp <?= number_format($rt['harga'], 0, ',', '.') ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Simpan Transaksi
                                </button>
                                <a href="read.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
