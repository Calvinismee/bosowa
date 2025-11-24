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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $metode = $_POST['metode_pembayaran'] ?? '';

    if (empty($metode)) {
        $error = "Metode pembayaran harus dipilih!";
    } elseif ($metode == 'tunai') {
        // Insert ke TRANSAKSI_TUNAI
        try {
            $sql = "INSERT INTO TRANSAKSI_TUNAI (id_transaksi, status_setoran, tanggal_setoran) 
                    VALUES (:id, 'Belum Disetor', NULL)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            setFlashMessage('success', 'Transaksi berhasil dibuat dengan metode TUNAI!');
            header("Location: read.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($metode == 'qris') {
        // Insert ke TRANSAKSI_QRIS (bukti_pembayaran optional untuk sekarang)
        try {
            $sql = "INSERT INTO TRANSAKSI_QRIS (id_transaksi, bukti_pembayaran) 
                    VALUES (:id, NULL)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            setFlashMessage('success', 'Transaksi berhasil dibuat dengan metode QRIS!');
            header("Location: read.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Cek apakah sudah punya metode pembayaran
$stmt = $pdo->prepare("SELECT 'tunai' as tipe FROM TRANSAKSI_TUNAI WHERE id_transaksi = :id 
                       UNION ALL 
                       SELECT 'qris' as tipe FROM TRANSAKSI_QRIS WHERE id_transaksi = :id");
$stmt->execute([':id' => $id]);
$existing = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pembayaran - Taksi App</title>
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
        .payment-option {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-option:hover {
            border-color: #667eea;
            background-color: #f8f9fa;
            transform: translateY(-5px);
        }
        .payment-option input[type="radio"] {
            cursor: pointer;
        }
        .payment-option.selected {
            border-color: #667eea;
            background-color: #f3f5ff;
        }
        .payment-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom mb-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h5">
                <i class="fas fa-taxi"></i> Taksi App - Metode Pembayaran
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
                <h3>Pilih Metode Pembayaran</h3>
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
                <form method="POST" action="">
                    <!-- Opsi TUNAI -->
                    <div class="payment-option" onclick="document.getElementById('tunai_radio').click()">
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div>
                                <input type="radio" id="tunai_radio" name="metode_pembayaran" value="tunai" required>
                            </div>
                            <div class="payment-icon" style="color: #28a745; margin: 0;">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div style="flex: 1;">
                                <h5 class="mb-2">💵 Pembayaran TUNAI</h5>
                                <p class="text-muted mb-0">Pembayaran secara tunai langsung kepada penumpang atau kantor pusat</p>
                                <?php if ($existing): ?>
                                    <span class="badge bg-success mt-2">Sudah dipilih</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Opsi QRIS -->
                    <div class="payment-option" onclick="document.getElementById('qris_radio').click()">
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div>
                                <input type="radio" id="qris_radio" name="metode_pembayaran" value="qris" required>
                            </div>
                            <div class="payment-icon" style="color: #007bff; margin: 0;">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div style="flex: 1;">
                                <h5 class="mb-2">📱 Pembayaran QRIS</h5>
                                <p class="text-muted mb-0">Pembayaran menggunakan kode QR (e-wallet, transfer bank, dll)</p>
                                <?php if ($existing): ?>
                                    <span class="badge bg-success mt-2">Sudah dipilih</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-success btn-lg" style="flex: 1;">
                            <i class="fas fa-check"></i> Konfirmasi Metode
                        </button>
                        <a href="biaya.php?id=<?= $id ?>" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-8 offset-md-2">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-info-circle"></i> Info Transaksi</h6>
                        <ul class="mb-0">
                            <li><strong>ID Transaksi:</strong> #<?= $id ?></li>
                            <li><strong>Tanggal:</strong> <?= date('d/m/Y H:i', strtotime($transaksi['tanggal_dibuat'])) ?></li>
                            <li><strong>Total Pembayaran:</strong> Rp <?= number_format($transaksi['total'], 0, ',', '.') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update visual saat radio dipilih
        document.querySelectorAll('input[name="metode_pembayaran"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.closest('.payment-option').classList.add('selected');
            });
        });
    </script>
</body>
</html>
