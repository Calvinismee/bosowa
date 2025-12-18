<?php
require_once '../config/database.php';
require_once '../config/notification.php';
require_once 'session.php';

requireDriverLogin();

$driver_id = getLoggedInDriverId();
$driver_name = getLoggedInDriverName();

// Ambil data driver lengkap
$stmt = $pdo->prepare("SELECT * FROM DRIVER WHERE id_user = :id");
$stmt->execute([':id' => $driver_id]);
$driver = $stmt->fetch();

// Ambil data kendaraan driver
$stmt = $pdo->prepare("SELECT * FROM KENDARAAN WHERE id_user = :id ORDER BY status DESC, id_kendaraan");
$stmt->execute([':id' => $driver_id]);
$kendaraan_list = $stmt->fetchAll();

$error = '';
$edit_mode = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'edit_profil') {
        $nama_driver = $_POST['nama_driver'] ?? '';
        $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
        
        if (empty($nama_driver)) {
            $error = "Nama driver tidak boleh kosong!";
        } else {
            try {
                $sql = "UPDATE DRIVER SET nama_driver = :nama, jenis_kelamin = :jenis WHERE id_user = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nama' => $nama_driver,
                    ':jenis' => $jenis_kelamin,
                    ':id' => $driver_id
                ]);
                
                setFlashMessage('success', 'Profil berhasil diperbarui!');
                header("Location: profile.php");
                exit;
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Driver - Taksi App</title>
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
        .profile-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 15px;
        }
        .profile-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .profile-status {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        .profile-body {
            padding: 30px;
        }
        .info-group {
            margin-bottom: 20px;
        }
        .info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .info-value {
            font-size: 16px;
            color: #333;
            margin-top: 5px;
            font-weight: 500;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .vehicle-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
        }
        .vehicle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .vehicle-plat {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .vehicle-status {
            font-size: 12px;
            padding: 5px 12px;
            border-radius: 20px;
        }
        .status-aktif {
            background-color: #d4edda;
            color: #155724;
        }
        .status-nonaktif {
            background-color: #f8d7da;
            color: #721c24;
        }
        .vehicle-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            font-size: 14px;
        }
        .vehicle-info-item label {
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
        }
        .vehicle-info-item value {
            display: block;
            color: #333;
            margin-top: 3px;
            font-weight: 500;
        }
        .empty-message {
            text-align: center;
            padding: 30px;
            color: #999;
        }
        .btn-back {
            border-radius: 8px;
        }
        .edit-btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .form-group-edit {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom mb-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h5">
                <i class="fas fa-taxi"></i> Bosowa App - Profil Driver
            </span>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($driver_name) ?>
                </span>
                <a href="logout.php" class="btn btn-sm btn-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Tombol Kembali -->
        <div class="mb-3">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

        <!-- Profile Card -->
        <div class="profile-card mb-4">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h1 class="profile-name"><?= htmlspecialchars($driver['nama_driver']) ?></h1>
                <div class="profile-status">
                    <i class="fas fa-info-circle"></i> 
                    <?php if ($driver['status'] === 'aktif'): ?>
                        <span style="color: #90ee90;">Status: Aktif</span>
                    <?php else: ?>
                        <span style="color: #ffcccc;">Status: Tidak Aktif</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-body">
                <!-- Flash Message -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Data Pribadi -->
                <div class="section-title">DATA PRIBADI</div>
                
                <form method="POST" id="editForm" style="display: none;">
                    <input type="hidden" name="action" value="edit_profil">
                    
                    <div class="form-group-edit">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($driver['username']) ?>" disabled>
                        <small class="text-muted">Username tidak dapat diubah</small>
                    </div>

                    <div class="form-group-edit">
                        <label class="form-label" for="nama_driver">Nama Driver</label>
                        <input type="text" class="form-control" id="nama_driver" name="nama_driver" value="<?= htmlspecialchars($driver['nama_driver']) ?>" required>
                    </div>

                    <div class="form-group-edit">
                        <label class="form-label" for="jenis_kelamin">Jenis Kelamin</label>
                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="Pria" <?= $driver['jenis_kelamin'] === 'Pria' ? 'selected' : '' ?>> Pria</option>
                            <option value="Wanita" <?= $driver['jenis_kelamin'] === 'Wanita' ? 'selected' : '' ?>> Wanita</option>
                        </select>
                    </div>

                    <div class="edit-btn-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="cancelEdit()">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>

                <div id="viewMode">
                    <div class="info-group">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?= htmlspecialchars($driver['username']) ?></div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Nama Driver</div>
                        <div class="info-value"><?= htmlspecialchars($driver['nama_driver']) ?></div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Jenis Kelamin</div>
                        <div class="info-value">
                            <?php 
                            $jk = $driver['jenis_kelamin'];
                            if ($jk === 'Pria') echo 'Pria';
                            elseif ($jk === 'Wanita') echo 'Wanita';
                            else echo 'Tidak Diisi';
                            ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Status Akun</div>
                        <div class="info-value">
                            <?php if ($driver['status'] === 'aktif'): ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Tidak Aktif</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="button" class="btn btn-primary" onclick="editMode()">
                            <i class="fas fa-edit"></i> Edit Profil
                        </button>
                    </div>
                </div>

                <!-- Data Kendaraan -->
                <div class="section-title">DATA KENDARAAN</div>

                <?php if ($kendaraan_list): ?>
                    <?php foreach ($kendaraan_list as $kendaraan): ?>
                    <div class="vehicle-card">
                        <div class="vehicle-header">
                            <div class="vehicle-plat">
                                <i class="fas fa-car"></i> <?= htmlspecialchars($kendaraan['plat_kendaraan']) ?>
                            </div>
                            <div class="vehicle-status <?= $kendaraan['status'] === 'aktif' ? 'status-aktif' : 'status-nonaktif' ?>">
                                <?= ucfirst(htmlspecialchars($kendaraan['status'])) ?>
                            </div>
                        </div>
                        
                        <div class="vehicle-info">
                            <div class="vehicle-info-item">
                                <label>Merk Kendaraan</label>
                                <value><?= $kendaraan['merk'] ? htmlspecialchars($kendaraan['merk']) : '-' ?></value>
                            </div>
                            <div class="vehicle-info-item">
                                <label>ID Kendaraan</label>
                                <value>#<?= $kendaraan['id_kendaraan'] ?></value>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-message">
                        <i class="fas fa-car" style="font-size: 40px; margin-bottom: 10px;"></i>
                        <p>Belum ada data kendaraan</p>
                    </div>
                <?php endif; ?>

                <!-- Catatan -->
                <div style="background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin-top: 20px; border-radius: 4px;">
                    <i class="fas fa-info-circle" style="color: #2196F3;"></i>
                    <strong style="margin-left: 10px; color: #1565c0;">Informasi</strong>
                    <p style="margin-top: 10px; margin-bottom: 0; color: #0d47a1;">
                        Anda dapat mengubah nama driver dan jenis kelamin. Data lainnya tidak dapat diubah. Untuk perubahan data lain, silakan hubungi administrator.
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer Space -->
        <div class="mb-5"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editMode() {
            document.getElementById('viewMode').style.display = 'none';
            document.getElementById('editForm').style.display = 'block';
            document.getElementById('nama_driver').focus();
        }

        function cancelEdit() {
            document.getElementById('editForm').style.display = 'none';
            document.getElementById('viewMode').style.display = 'block';
        }
    </script>
</body>
</html>
