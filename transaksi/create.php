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
    $metode = $_POST['metode'] ?? '';
    
    // Data Biaya Tambahan (Array)
    $biaya_jenis = $_POST['biaya_jenis'] ?? [];
    $biaya_jumlah = isset($_POST['biaya_jumlah']) ? array_map(function($val) {
        return str_replace('.', '', $val);
    }, $_POST['biaya_jumlah']) : [];

    // Validasi
    if (empty($id_rute_tarif)) {
        $error = "Rute tarif harus dipilih!";
    } elseif (empty($metode)) {
        $error = "Metode pembayaran harus dipilih!";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Insert Transaksi
            $sql = "INSERT INTO TRANSAKSI (id_user, id_rute_tarif, tanggal_dibuat, total) 
                    VALUES (:id_user, :id_rute_tarif, NOW(), 
                            (SELECT harga FROM RUTE_TARIF WHERE id_rute_tarif = :id_rute_tarif))";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_user' => $driver_id,
                ':id_rute_tarif' => $id_rute_tarif
            ]);

            $id_transaksi = $pdo->lastInsertId();

            // 2. Insert Biaya Tambahan (Jika ada)
            if (!empty($biaya_jenis)) {
                $sql_biaya = "INSERT INTO DETAIL_BIAYA (id_transaksi, jenis_biaya, jumlah) VALUES (:id, :jenis, :jumlah)";
                $stmt_biaya = $pdo->prepare($sql_biaya);

                for ($i = 0; $i < count($biaya_jenis); $i++) {
                    if (!empty($biaya_jenis[$i]) && !empty($biaya_jumlah[$i])) {
                        $stmt_biaya->execute([
                            ':id' => $id_transaksi,
                            ':jenis' => $biaya_jenis[$i],
                            ':jumlah' => $biaya_jumlah[$i]
                        ]);
                    }
                }

                // Update Total Transaksi setelah tambah biaya
                $sql_update = "UPDATE TRANSAKSI SET 
                               total = (SELECT harga FROM RUTE_TARIF WHERE id_rute_tarif = TRANSAKSI.id_rute_tarif) + 
                                       (SELECT COALESCE(SUM(jumlah), 0) FROM DETAIL_BIAYA WHERE id_transaksi = :id)
                               WHERE id_transaksi = :id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([':id' => $id_transaksi]);
            }

            // 3. Insert Metode Pembayaran
            if ($metode == 'tunai') {
                $status_setoran = $_POST['status_setoran'] ?? 'Belum Disetor';
                $tanggal_setoran = $_POST['tanggal_setoran'] ?? null;
                
                $stmt = $pdo->prepare("INSERT INTO TRANSAKSI_TUNAI (id_transaksi, status_setoran, tanggal_setoran) VALUES (:id, :status, :tanggal)");
                $stmt->execute([':id' => $id_transaksi, ':status' => $status_setoran, ':tanggal' => $tanggal_setoran]);
            } elseif ($metode == 'qris') {
                $stmt = $pdo->prepare("INSERT INTO TRANSAKSI_QRIS (id_transaksi, bukti_pembayaran) VALUES (:id, NULL)");
                $stmt->execute([':id' => $id_transaksi]);
            }

            $pdo->commit();

            setFlashMessage('success', 'Transaksi berhasil dibuat!');
            header("Location: read.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
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
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: 600;
            color: #444;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
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
                <p class="text-muted">Lengkapi data transaksi di bawah ini</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <form method="POST" action="">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- 1. Rute Tarif -->
                    <div class="card form-card">
                        <div class="card-body p-4">
                            <h5 class="section-title"><i class="fas fa-road"></i> Rute & Tarif</h5>
                            <div class="mb-3">
                                <label class="form-label" for="id_rute_tarif">Pilih Rute Tarif</label>
                                <select id="id_rute_tarif"
                                        name="id_rute_tarif" 
                                        class="form-select" 
                                        required>
                                    <option value="">-- Pilih Rute Tarif --</option>
                                    <?php
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
                        </div>
                    </div>

                    <!-- 2. Biaya Tambahan -->
                    <div class="card form-card">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="section-title mb-0"><i class="fas fa-money-bill-alt"></i> Biaya Tambahan (Opsional)</h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addBiayaRow()">
                                    <i class="fas fa-plus"></i> Tambah Baris
                                </button>
                            </div>
                            
                            <div id="biaya-container">
                                <!-- Rows will be added here -->
                            </div>
                            <small class="text-muted">Klik "Tambah Baris" jika ada biaya tambahan seperti Tol, Parkir, dll.</small>
                        </div>
                    </div>

                    <!-- 3. Metode Pembayaran -->
                    <div class="card form-card">
                        <div class="card-body p-4">
                            <h5 class="section-title"><i class="fas fa-credit-card"></i> Metode Pembayaran</h5>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metode" id="metode_tunai" value="tunai" onchange="toggleMetode('tunai')" required>
                                    <label class="form-check-label" for="metode_tunai">
                                        <i class="fas fa-money-bill-wave"></i> Tunai
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metode" id="metode_qris" value="qris" onchange="toggleMetode('qris')">
                                    <label class="form-check-label" for="metode_qris">
                                        <i class="fas fa-qrcode"></i> QRIS
                                    </label>
                                </div>
                            </div>

                            <!-- Tunai Details -->
                            <div id="tunai_section" style="display: none; border: 1px solid #dee2e6; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                <h6 class="mb-3">Detail Pembayaran Tunai</h6>
                                <div class="mb-3">
                                    <label class="form-label">Status Setoran</label>
                                    <select name="status_setoran" class="form-select">
                                        <option value="Belum Disetor">Belum Disetor</option>
                                        <option value="Disetor">Disetor</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Setoran</label>
                                    <input type="date" name="tanggal_setoran" class="form-control">
                                </div>
                            </div>

                            <!-- QRIS Details -->
                            <div id="qris_section" style="display: none;">
                                <p class="text-muted">
                                    <i class="fas fa-info-circle"></i> Metode pembayaran QRIS dipilih.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mb-5">
                        <a href="read.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Simpan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleMetode(metode) {
            const tunaiSection = document.getElementById('tunai_section');
            const qrisSection = document.getElementById('qris_section');
            
            if (metode === 'tunai') {
                tunaiSection.style.display = 'block';
                qrisSection.style.display = 'none';
            } else {
                tunaiSection.style.display = 'none';
                qrisSection.style.display = 'block';
            }
        }

        function addBiayaRow() {
            const container = document.getElementById('biaya-container');
            const row = document.createElement('div');
            row.className = 'row mb-2 align-items-end';
            row.innerHTML = `
                <div class="col-md-6">
                    <label class="form-label small">Jenis Biaya</label>
                    <input type="text" name="biaya_jenis[]" class="form-control" placeholder="Contoh: Tol" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Jumlah (Rp)</label>
                    <input type="text" name="biaya_jumlah[]" class="form-control" placeholder="0" onkeyup="formatRupiah(this)" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger w-100" onclick="this.closest('.row').remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
        }

        function formatRupiah(input) {
            let value = input.value.replace(/[^,\d]/g, '').toString();
            let split = value.split(',');
            let sisa = split[0].length % 3;
            let rupiah = split[0].substr(0, sisa);
            let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                let separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            input.value = rupiah;
        }
    </script>
</body>
</html>
