<?php
require_once '../config/database.php';
require_once '../config/notification.php';
require_once 'session.php';

// Pastikan driver sudah login
requireDriverLogin();

$driver_id = getLoggedInDriverId();
$driver_name = getLoggedInDriverName();

// Ambil data driver detail
$stmt = $pdo->prepare("SELECT * FROM DRIVER WHERE id_user = :id");
$stmt->execute([':id' => $driver_id]);
$driver = $stmt->fetch();

// Ambil statistik transaksi
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM TRANSAKSI WHERE id_user = :driver_id");
$stmt->execute([':driver_id' => $driver_id]);
$stat_transaksi = $stmt->fetch()['total'];

// Ambil total pendapatan
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as total_pendapatan FROM TRANSAKSI WHERE id_user = :driver_id");
$stmt->execute([':driver_id' => $driver_id]);
$total_pendapatan = $stmt->fetch()['total_pendapatan'];

// Ambil transaksi TUNAI yang sudah disetor
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM TRANSAKSI_TUNAI WHERE id_transaksi IN (SELECT id_transaksi FROM TRANSAKSI WHERE id_user = :driver_id) AND status_setoran = 'Disetor'");
$stmt->execute([':driver_id' => $driver_id]);
$stat_disetor = $stmt->fetch()['total'];

// Ambil transaksi TUNAI yang belum disetor
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM TRANSAKSI_TUNAI WHERE id_transaksi IN (SELECT id_transaksi FROM TRANSAKSI WHERE id_user = :driver_id) AND status_setoran = 'Belum Disetor'");
$stmt->execute([':driver_id' => $driver_id]);
$stat_belum_disetor = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Driver - Taksi App</title>
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
        .card-stat {
            border: none;
            border-radius: 12px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .btn-action {
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom mb-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h5">
                <i class="fas fa-taxi"></i> Taksi App - Dashboard Driver
            </span>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($driver_name) ?>
                </span>
                <a href="logout.php" class="btn btn-sm btn-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <!-- Flash Message -->
        <?php
        require_once '../config/notification.php';
        displayFlashMessage();
        ?>

        <!-- Greeting Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h3 class="mb-2">Selamat Datang, <?= htmlspecialchars($driver_name) ?>! 👋</h3>
                <p class="text-muted">Kelola transaksi dan pantau pendapatan Anda</p>
            </div>
        </div>

        <!-- Statistik Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stat">
                    <div class="card-body">
                        <div class="stat-icon" style="background-color: #e3f2fd; color: #1976d2;">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-number"><?= $stat_transaksi ?></div>
                        <div class="stat-label">Total Transaksi</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat">
                    <div class="card-body">
                        <div class="stat-icon" style="background-color: #fff3e0; color: #f57c00;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?= $stat_belum_disetor ?></div>
                        <div class="stat-label">Belum Disetor</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat">
                    <div class="card-body">
                        <div class="stat-icon" style="background-color: #e8f5e9; color: #388e3c;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?= $stat_disetor ?></div>
                        <div class="stat-label">Sudah Disetor</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat">
                    <div class="card-body">
                        <div class="stat-icon" style="background-color: #fff3e0; color: #f57c00;">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-number">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
                        <div class="stat-label">Total Pendapatan</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row mb-4">
            <div class="col-md-12">
                <a href="../transaksi/read.php" class="btn btn-primary btn-action">
                    <i class="fas fa-list"></i> History Transaksi
                </a>
                <a href="../transaksi/create.php" class="btn btn-success btn-action">
                    <i class="fas fa-plus"></i> Tambah Transaksi Baru
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
