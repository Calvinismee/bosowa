<?php
require_once '../config/database.php';
require_once '../config/notification.php';
require_once '../auth/session.php';

requireDriverLogin();

$driver_id = getLoggedInDriverId();
$driver_name = getLoggedInDriverName();

// Cek ID transaksi
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

// Ambil detail biaya
$stmt = $pdo->prepare("SELECT * FROM DETAIL_BIAYA WHERE id_transaksi = :id");
$stmt->execute([':id' => $id]);
$detail_biaya = $stmt->fetchAll();

// Ambil metode pembayaran
$stmt = $pdo->prepare("SELECT * FROM TRANSAKSI_TUNAI WHERE id_transaksi = :id");
$stmt->execute([':id' => $id]);
$metode_tunai = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM TRANSAKSI_QRIS WHERE id_transaksi = :id");
$stmt->execute([':id' => $id]);
$metode_qris = $stmt->fetch();

$metode_saat_ini = $metode_tunai ? 'tunai' : ($metode_qris ? 'qris' : null);

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'update_rute') {
        $id_rute_tarif = $_POST['id_rute_tarif'] ?? '';
        $tanggal_dibuat = $_POST['tanggal_dibuat'] ?? '';

        if (empty($id_rute_tarif)) {
            $error = "Rute tarif harus dipilih!";
        } else {
            try {
                $sql = "UPDATE TRANSAKSI SET id_rute_tarif = :id_rute_tarif, 
                        tanggal_dibuat = :tanggal_dibuat,
                        total = (SELECT harga FROM RUTE_TARIF WHERE id_rute_tarif = :id_rute_tarif) + 
                                (SELECT COALESCE(SUM(jumlah), 0) FROM DETAIL_BIAYA WHERE id_transaksi = :id),
                        tanggal_diupdate = NOW()
                        WHERE id_transaksi = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_rute_tarif' => $id_rute_tarif,
                    ':tanggal_dibuat' => $tanggal_dibuat,
                    ':id' => $id
                ]);

                setFlashMessage('success', 'Rute tarif dan tanggal berhasil diperbarui!');
                header("Location: edit.php?id=" . $id);
                exit;
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    } elseif ($action == 'add_biaya') {
        $jenis_biaya = $_POST['jenis_biaya'] ?? '';
        $jumlah = str_replace('.', '', $_POST['jumlah'] ?? '');

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

                // Update Total Transaksi
                $sql_update = "UPDATE TRANSAKSI SET 
                               total = (SELECT harga FROM RUTE_TARIF WHERE id_rute_tarif = TRANSAKSI.id_rute_tarif) + 
                                       (SELECT COALESCE(SUM(jumlah), 0) FROM DETAIL_BIAYA WHERE id_transaksi = :id)
                               WHERE id_transaksi = :id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([':id' => $id]);

                setFlashMessage('success', 'Biaya tambahan berhasil ditambahkan!');
                header("Location: edit.php?id=" . $id);
                exit;
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    } elseif ($action == 'edit_biaya') {
        $jenis_biaya_lama = $_POST['jenis_biaya_lama'] ?? '';
        $jenis_biaya_baru = $_POST['jenis_biaya_baru'] ?? '';
        $jumlah = str_replace('.', '', $_POST['jumlah'] ?? '');

        if (empty($jenis_biaya_baru) || empty($jumlah)) {
            $error = "Jenis biaya dan jumlah harus diisi!";
        } elseif ($jumlah <= 0) {
            $error = "Jumlah biaya harus lebih besar dari 0!";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM DETAIL_BIAYA WHERE id_transaksi = :id AND jenis_biaya = :jenis_biaya");
                $stmt->execute([':id' => $id, ':jenis_biaya' => $jenis_biaya_lama]);

                $stmt = $pdo->prepare("INSERT INTO DETAIL_BIAYA (id_transaksi, jenis_biaya, jumlah) 
                        VALUES (:id, :jenis_biaya, :jumlah)");
                $stmt->execute([':id' => $id, ':jenis_biaya' => $jenis_biaya_baru, ':jumlah' => $jumlah]);

                // Update Total Transaksi
                $sql_update = "UPDATE TRANSAKSI SET 
                               total = (SELECT harga FROM RUTE_TARIF WHERE id_rute_tarif = TRANSAKSI.id_rute_tarif) + 
                                       (SELECT COALESCE(SUM(jumlah), 0) FROM DETAIL_BIAYA WHERE id_transaksi = :id)
                               WHERE id_transaksi = :id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([':id' => $id]);

                setFlashMessage('success', 'Biaya berhasil diperbarui!');
                header("Location: edit.php?id=" . $id);
                exit;
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    } elseif ($action == 'update_metode') {
        $metode = $_POST['metode'] ?? '';

        if (empty($metode)) {
            $error = "Pilih metode pembayaran!";
        } else {
            try {
                if ($metode == 'tunai') {
                    // Hapus QRIS jika ada
                    if ($metode_qris) {
                        $stmt = $pdo->prepare("DELETE FROM TRANSAKSI_QRIS WHERE id_transaksi = :id");
                        $stmt->execute([':id' => $id]);
                    }
                    
                    // Insert/Update TUNAI
                    $status_setoran = $_POST['status_setoran'] ?? 'Belum Disetor';
                    $tanggal_setoran = (!empty($_POST['tanggal_setoran']) && $status_setoran === 'Disetor') ? $_POST['tanggal_setoran'] : null;
                    
                    if ($metode_tunai) {
                        $stmt = $pdo->prepare("UPDATE TRANSAKSI_TUNAI SET status_setoran = :status, tanggal_setoran = :tanggal WHERE id_transaksi = :id");
                        $stmt->execute([':status' => $status_setoran, ':tanggal' => $tanggal_setoran, ':id' => $id]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO TRANSAKSI_TUNAI (id_transaksi, status_setoran, tanggal_setoran) VALUES (:id, :status, :tanggal)");
                        $stmt->execute([':id' => $id, ':status' => $status_setoran, ':tanggal' => $tanggal_setoran]);
                    }
                } elseif ($metode == 'qris') {
                    // Hapus TUNAI jika ada
                    if ($metode_tunai) {
                        $stmt = $pdo->prepare("DELETE FROM TRANSAKSI_TUNAI WHERE id_transaksi = :id");
                        $stmt->execute([':id' => $id]);
                    }
                    
                    // Insert/Update QRIS
                    if ($metode_qris) {
                        $stmt = $pdo->prepare("UPDATE TRANSAKSI_QRIS SET bukti_pembayaran = :bukti WHERE id_transaksi = :id");
                        $stmt->execute([':bukti' => NULL, ':id' => $id]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO TRANSAKSI_QRIS (id_transaksi, bukti_pembayaran) VALUES (:id, :bukti)");
                        $stmt->execute([':id' => $id, ':bukti' => NULL]);
                    }
                }

                setFlashMessage('success', 'Metode pembayaran berhasil diperbarui!');
                header("Location: edit.php?id=" . $id);
                exit;
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    } elseif ($action == 'delete_biaya') {
        $jenis_biaya = $_POST['jenis_biaya'] ?? '';
        try {
            $stmt = $pdo->prepare("DELETE FROM DETAIL_BIAYA WHERE id_transaksi = :id AND jenis_biaya = :jenis_biaya");
            $stmt->execute([':id' => $id, ':jenis_biaya' => $jenis_biaya]);

            // Update Total Transaksi
            $sql_update = "UPDATE TRANSAKSI SET 
                           total = (SELECT harga FROM RUTE_TARIF WHERE id_rute_tarif = TRANSAKSI.id_rute_tarif) + 
                                   (SELECT COALESCE(SUM(jumlah), 0) FROM DETAIL_BIAYA WHERE id_transaksi = :id)
                           WHERE id_transaksi = :id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([':id' => $id]);

            setFlashMessage('success', 'Biaya berhasil dihapus!');
            header("Location: edit.php?id=" . $id);
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
    <title>Edit Transaksi - Taksi App</title>
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
                <i class="fas fa-taxi"></i> Taksi App - Edit Transaksi
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
                <h3>Edit Transaksi #<?= $id ?></h3>
                <p class="text-muted">Kelola rute tarif, biaya tambahan, dan metode pembayaran</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="rute-tab" data-bs-toggle="tab" data-bs-target="#rute" type="button" role="tab">
                            <i class="fas fa-road"></i> Rute Tarif
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="biaya-tab" data-bs-toggle="tab" data-bs-target="#biaya" type="button" role="tab">
                            <i class="fas fa-money-bill-alt"></i> Biaya Tambahan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pembayaran-tab" data-bs-toggle="tab" data-bs-target="#pembayaran" type="button" role="tab">
                            <i class="fas fa-credit-card"></i> Metode Pembayaran
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- TAB 1: RUTE TARIF -->
                    <div class="tab-pane fade show active" id="rute" role="tabpanel">
                        <div class="card form-card">
                            <div class="card-body p-4">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="update_rute">
                                    
                                    <div class="mb-3">
                                        <label class="form-label" for="tanggal_dibuat">Tanggal Transaksi</label>
                                        <input type="date" 
                                               id="tanggal_dibuat" 
                                               name="tanggal_dibuat" 
                                               class="form-control" 
                                               value="<?= date('Y-m-d', strtotime($transaksi['tanggal_dibuat'])) ?>" 
                                               required>
                                    </div>

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
                                            <option value="<?= $rt['id_rute_tarif'] ?>" <?= $transaksi['id_rute_tarif'] == $rt['id_rute_tarif'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($rt['jenis']) ?> - <?= htmlspecialchars($rt['golongan']) ?> (Rp <?= number_format($rt['harga'], 0, ',', '.') ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Total (Rp)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   value="<?= number_format($transaksi['total'], 0, ',', '.') ?>"
                                                   readonly>
                                        </div>
                                        <small class="text-muted">Total otomatis dihitung dari harga rute tarif + biaya tambahan</small>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Simpan Rute Tarif
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: BIAYA TAMBAHAN -->
                    <div class="tab-pane fade" id="biaya" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="card form-card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Tambah Biaya Tambahan</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="add_biaya">
                                            
                                            <div class="mb-3">
                                                <label class="form-label" for="jenis_biaya_add">Jenis Biaya</label>
                                                <input type="text" 
                                                       id="jenis_biaya_add"
                                                       name="jenis_biaya" 
                                                       class="form-control" 
                                                       placeholder="Contoh: Tol, Parkir, dll"
                                                       required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="jumlah_add">Jumlah (Rp)</label>
                                                <input type="text" 
                                                       id="jumlah_add"
                                                       name="jumlah" 
                                                       class="form-control" 
                                                       placeholder="0"
                                                       onkeyup="formatRupiah(this)"
                                                       required>
                                            </div>

                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="fas fa-plus"></i> Tambah Biaya
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card form-card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Info Biaya</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $total_biaya = 0;
                                        foreach ($detail_biaya as $biaya) {
                                            $total_biaya += $biaya['jumlah'];
                                        }
                                        $rute_harga = $transaksi['total'] - $total_biaya;
                                        ?>
                                        <p><strong>Rute Harga:</strong> Rp <?= number_format($rute_harga, 0, ',', '.') ?></p>
                                        <p><strong>Total Biaya Tambahan:</strong> Rp <?= number_format($total_biaya, 0, ',', '.') ?></p>
                                        <hr>
                                        <p><strong>TOTAL TRANSAKSI:</strong> <h4>Rp <?= number_format($transaksi['total'], 0, ',', '.') ?></h4></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card form-card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Daftar Biaya Tambahan - Klik Edit untuk Ubah</h5>
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
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus?');">
                                                        <input type="hidden" name="action" value="delete_biaya">
                                                        <input type="hidden" name="jenis_biaya" value="<?= htmlspecialchars($biaya['jenis_biaya']) ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
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
                                <p class="text-center text-muted py-5">Belum ada biaya tambahan</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: METODE PEMBAYARAN -->
                    <div class="tab-pane fade" id="pembayaran" role="tabpanel">
                        <div class="card form-card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Metode Pembayaran</h5>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="update_metode">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Pilih Metode Pembayaran</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="metode" id="metode_tunai" value="tunai" 
                                                   <?= $metode_saat_ini == 'tunai' ? 'checked' : '' ?> onchange="toggleMetode('tunai')">
                                            <label class="form-check-label" for="metode_tunai">
                                                <i class="fas fa-money-bill-wave"></i> Tunai
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="metode" id="metode_qris" value="qris" 
                                                   <?= $metode_saat_ini == 'qris' ? 'checked' : '' ?> onchange="toggleMetode('qris')">
                                            <label class="form-check-label" for="metode_qris">
                                                <i class="fas fa-qrcode"></i> QRIS
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Tunai Details -->
                                    <div id="tunai_section" style="display: <?= $metode_saat_ini == 'tunai' ? 'block' : 'none' ?>; border: 1px solid #dee2e6; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                        <h6 class="mb-3">Detail Pembayaran Tunai</h6>
                                        <div class="mb-3">
                                            <label class="form-label">Status Setoran</label>
                                            <select name="status_setoran" id="status_setoran" class="form-select" onchange="toggleTanggalSetoran()">
                                                <option value="Belum Disetor" <?= $metode_tunai && $metode_tunai['status_setoran'] == 'Belum Disetor' ? 'selected' : '' ?>>Belum Disetor</option>
                                                <option value="Disetor" <?= $metode_tunai && $metode_tunai['status_setoran'] == 'Disetor' ? 'selected' : '' ?>>Disetor</option>
                                            </select>
                                        </div>
                                        <div class="mb-3" id="tanggal_setoran_container" style="display: <?= ($metode_tunai && $metode_tunai['status_setoran'] == 'Disetor') ? 'block' : 'none' ?>;">
                                            <label class="form-label">Tanggal Setoran</label>
                                            <input type="date" name="tanggal_setoran" id="tanggal_setoran" class="form-control" value="<?= $metode_tunai && $metode_tunai['tanggal_setoran'] ? $metode_tunai['tanggal_setoran'] : '' ?>">
                                        </div>
                                    </div>

                                    <!-- QRIS Details -->
                                    <div id="qris_section" style="display: <?= $metode_saat_ini == 'qris' ? 'block' : 'none' ?>;">
                                        <h6 class="mb-3">Detail Pembayaran QRIS</h6>
                                        <p class="text-muted">
                                            <i class="fas fa-info-circle"></i> Metode pembayaran QRIS telah dipilih. Bukti pembayaran dapat diunggah kemudian.
                                        </p>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Simpan Metode Pembayaran
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2 justify-content-end">
                    <a href="read.php" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Selesai
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
                            <label class="form-label" for="jumlah">Jumlah (Rp)</label>
                            <input type="text" 
                                   id="jumlah"
                                   name="jumlah" 
                                   class="form-control" 
                                   onkeyup="formatRupiah(this)"
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
        // Save active tab to localStorage
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function() {
                const tabTarget = this.getAttribute('data-bs-target');
                localStorage.setItem('activeTab', tabTarget);
            });
        });

        // Restore active tab on page load
        window.addEventListener('load', function() {
            const activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                const tabButton = document.querySelector(`[data-bs-target="${activeTab}"]`);
                if (tabButton) {
                    const tab = new bootstrap.Tab(tabButton);
                    tab.show();
                }
            }
        });

        function fillBiayaForm(jenis, jumlah) {
            document.getElementById('jenis_biaya_lama').value = jenis;
            document.getElementById('jenis_biaya_baru').value = jenis;
            document.getElementById('jumlah').value = jumlah;
        }

        function toggleMetode(metode) {
            const tunaiSection = document.getElementById('tunai_section');
            const qrisSection = document.getElementById('qris_section');
            
            if (metode === 'tunai') {
                tunaiSection.style.display = 'block';
                qrisSection.style.display = 'none';
                toggleTanggalSetoran();
            } else {
                tunaiSection.style.display = 'none';
                qrisSection.style.display = 'block';
            }
        }

        function toggleTanggalSetoran() {
            const status = document.getElementById('status_setoran').value;
            const container = document.getElementById('tanggal_setoran_container');
            const input = document.getElementById('tanggal_setoran');
            
            if (status === 'Disetor') {
                container.style.display = 'block';
                input.required = true;
            } else {
                container.style.display = 'none';
                input.required = false;
                input.value = '';
            }
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
