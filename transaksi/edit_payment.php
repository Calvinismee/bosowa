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

// Cek metode pembayaran yang dipilih
$metode_saat_ini = null;
$stmt = $pdo->prepare("SELECT 'tunai' as tipe FROM TRANSAKSI_TUNAI WHERE id_transaksi = :id");
$stmt->execute([':id' => $id]);
if ($stmt->fetch()) {
    $metode_saat_ini = 'tunai';
} else {
    $stmt = $pdo->prepare("SELECT 'qris' as tipe FROM TRANSAKSI_QRIS WHERE id_transaksi = :id");
    $stmt->execute([':id' => $id]);
    if ($stmt->fetch()) {
        $metode_saat_ini = 'qris';
    }
}

// Ambil data pembayaran
$transaksi_tunai = null;
$transaksi_qris = null;

if ($metode_saat_ini == 'tunai') {
    $stmt = $pdo->prepare("SELECT * FROM TRANSAKSI_TUNAI WHERE id_transaksi = :id");
    $stmt->execute([':id' => $id]);
    $transaksi_tunai = $stmt->fetch();
} elseif ($metode_saat_ini == 'qris') {
    $stmt = $pdo->prepare("SELECT * FROM TRANSAKSI_QRIS WHERE id_transaksi = :id");
    $stmt->execute([':id' => $id]);
    $transaksi_qris = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'update_tunai') {
        $status_setoran = $_POST['status_setoran'] ?? '';
        $tanggal_setoran = $_POST['tanggal_setoran'] ?? '';

        if (empty($status_setoran)) {
            $error = "Status setoran harus dipilih!";
        } else {
            try {
                // Jika metode sebelumnya QRIS, hapus dulu
                if ($metode_saat_ini == 'qris') {
                    $stmt = $pdo->prepare("DELETE FROM TRANSAKSI_QRIS WHERE id_transaksi = :id");
                    $stmt->execute([':id' => $id]);
                }

                // Cek apakah sudah ada TUNAI
                $stmt = $pdo->prepare("SELECT * FROM TRANSAKSI_TUNAI WHERE id_transaksi = :id");
                $stmt->execute([':id' => $id]);
                $exists = $stmt->fetch();

                if ($exists) {
                    // Update
                    $sql = "UPDATE TRANSAKSI_TUNAI SET status_setoran = :status, tanggal_setoran = :tanggal 
                            WHERE id_transaksi = :id";
                } else {
                    // Insert
                    $sql = "INSERT INTO TRANSAKSI_TUNAI (id_transaksi, status_setoran, tanggal_setoran) 
                            VALUES (:id, :status, :tanggal)";
                }

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $id,
                    ':status' => $status_setoran,
                    ':tanggal' => !empty($tanggal_setoran) ? $tanggal_setoran : null
                ]);

                setFlashMessage('success', 'Metode pembayaran TUNAI berhasil diperbarui!');
                header("Location: edit_payment.php?id=" . $id);
                exit;
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    } elseif ($action == 'update_qris') {
        try {
            // Jika metode sebelumnya TUNAI, hapus dulu
            if ($metode_saat_ini == 'tunai') {
                $stmt = $pdo->prepare("DELETE FROM TRANSAKSI_TUNAI WHERE id_transaksi = :id");
                $stmt->execute([':id' => $id]);
            }

            // Cek apakah sudah ada QRIS
            $stmt = $pdo->prepare("SELECT * FROM TRANSAKSI_QRIS WHERE id_transaksi = :id");
            $stmt->execute([':id' => $id]);
            $exists = $stmt->fetch();

            if (!$exists) {
                // Insert jika belum ada
                $sql = "INSERT INTO TRANSAKSI_QRIS (id_transaksi, bukti_pembayaran) 
                        VALUES (:id, NULL)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
            }

            setFlashMessage('success', 'Metode pembayaran QRIS berhasil diperbarui!');
            header("Location: edit_payment.php?id=" . $id);
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
    <title>Edit Metode Pembayaran - Taksi App</title>
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
        .payment-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .payment-card.active {
            border-color: #667eea;
            background-color: #f3f5ff;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom mb-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h5">
                <i class="fas fa-taxi"></i> Taksi App - Edit Metode Pembayaran
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
                <h3>Edit Metode Pembayaran</h3>
                <p class="text-muted">Transaksi #<?= $id ?> - Total: Rp <?= number_format($transaksi['total'], 0, ',', '.') ?></p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <!-- TUNAI -->
                <div class="payment-card <?= $metode_saat_ini == 'tunai' ? 'active' : '' ?>">
                    <h5 class="mb-3">💵 Pembayaran TUNAI</h5>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_tunai">
                        
                        <div class="mb-3">
                            <label class="form-label" for="status_setoran">Status Setoran</label>
                            <select id="status_setoran" name="status_setoran" class="form-select">
                                <option value="">-- Pilih Status --</option>
                                <option value="Disetor" <?= $transaksi_tunai && $transaksi_tunai['status_setoran'] == 'Disetor' ? 'selected' : '' ?>>Disetor</option>
                                <option value="Belum Disetor" <?= $transaksi_tunai && $transaksi_tunai['status_setoran'] == 'Belum Disetor' ? 'selected' : '' ?>>Belum Disetor</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="tanggal_setoran">Tanggal Setoran (Opsional)</label>
                            <input type="date" 
                                   id="tanggal_setoran"
                                   name="tanggal_setoran" 
                                   class="form-control"
                                   value="<?= $transaksi_tunai && $transaksi_tunai['tanggal_setoran'] ? date('Y-m-d', strtotime($transaksi_tunai['tanggal_setoran'])) : '' ?>">
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-save"></i> Simpan TUNAI
                        </button>
                    </form>
                </div>

                <!-- QRIS -->
                <div class="payment-card <?= $metode_saat_ini == 'qris' ? 'active' : '' ?>">
                    <h5 class="mb-3">📱 Pembayaran QRIS</h5>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_qris">
                        
                        <p class="text-muted mb-3">
                            Pembayaran menggunakan kode QR (e-wallet, transfer bank, dll)
                        </p>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Gunakan QRIS
                        </button>
                    </form>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <a href="read.php" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-check-circle"></i> Selesai - Simpan Transaksi
                    </a>
                    <a href="biaya.php?id=<?= $id ?>" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
