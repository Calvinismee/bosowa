<?php
require_once '../config/database.php';
require_once '../config/notification.php';
require_once '../auth/session.php';

requireDriverLogin();

$driver_id = getLoggedInDriverId();
$driver_name = getLoggedInDriverName();

// Ambil data transaksi driver yang login
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';

$sql = "SELECT t.*, 
        CASE 
            WHEN tt.id_transaksi IS NOT NULL THEN 'Tunai'
            WHEN tq.id_transaksi IS NOT NULL THEN 'QRIS'
            ELSE 'Belum Ditentukan'
        END as metode_pembayaran,
        tt.status_setoran,
        r.jenis as nama_rute
        FROM TRANSAKSI t
        LEFT JOIN TRANSAKSI_TUNAI tt ON t.id_transaksi = tt.id_transaksi
        LEFT JOIN TRANSAKSI_QRIS tq ON t.id_transaksi = tq.id_transaksi
        LEFT JOIN RUTE_TARIF rt ON t.id_rute_tarif = rt.id_rute_tarif
        LEFT JOIN RUTE r ON rt.id_rute = r.id_rute
        WHERE t.id_user = :driver_id";
$params = [':driver_id' => $driver_id];

if (!empty($tanggal_mulai) && !empty($tanggal_akhir)) {
    $sql .= " AND DATE(t.tanggal_dibuat) >= :tanggal_mulai AND DATE(t.tanggal_dibuat) <= :tanggal_akhir";
    $params[':tanggal_mulai'] = $tanggal_mulai;
    $params[':tanggal_akhir'] = $tanggal_akhir;
}

$sql .= " ORDER BY t.tanggal_dibuat DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transaksi_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Transaksi - Taksi App</title>
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
                <i class="fas fa-taxi"></i> Taksi App - Kelola Transaksi
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
        <!-- Flash Message -->
        <?php displayFlashMessage(); ?>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h3>Daftar Transaksi Anda</h3>
                <p class="text-muted">Total: <?= count($transaksi_list) ?> transaksi</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="create.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah Transaksi
                </a>
                <a href="../auth/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Filter & Search -->
        <div class="row mb-3">
            <div class="col-md-12">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" value="<?= htmlspecialchars($tanggal_mulai) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="<?= htmlspecialchars($tanggal_akhir) ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="read.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transaksi List -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal Dibuat</th>
                                    <th>Rute</th>
                                    <th>Total</th>
                                    <th>Metode Pembayaran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($transaksi_list): ?>
                                    <?php foreach ($transaksi_list as $row): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($row['tanggal_dibuat'])) ?></td>
                                        <td><?= htmlspecialchars($row['nama_rute']) ?></td>
                                        <td><strong>Rp <?= number_format($row['total'], 0, ',', '.') ?></strong></td>
                                        <td>
                                            <?php if ($row['metode_pembayaran'] == 'Tunai'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-money-bill-wave"></i> Tunai
                                                </span>
                                                <?php if ($row['status_setoran']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($row['status_setoran']) ?></small>
                                                <?php endif; ?>
                                            <?php elseif ($row['metode_pembayaran'] == 'QRIS'): ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-qrcode"></i> QRIS
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Belum Ditentukan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit.php?id=<?= $row['id_transaksi'] ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="delete.php?id=<?= $row['id_transaksi'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus transaksi ini?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-inbox" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                                            Tidak ada transaksi
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
