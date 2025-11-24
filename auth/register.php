<?php
require_once '../config/database.php';
require_once '../config/notification.php';
require_once 'session.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['driver_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama_driver'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';

    // Validasi input
    if (empty($nama) || empty($username) || empty($password) || empty($password_confirm)) {
        $error = "Semua field harus diisi!";
    } elseif (strlen($username) < 3) {
        $error = "Username minimal 3 karakter!";
    } elseif (strlen($password) < 5) {
        $error = "Password minimal 5 karakter!";
    } elseif ($password !== $password_confirm) {
        $error = "Password tidak cocok!";
    } elseif (empty($jenis_kelamin)) {
        $error = "Jenis kelamin harus dipilih!";
    } else {
        // Cek username sudah ada
        $stmt = $pdo->prepare("SELECT id_user FROM DRIVER WHERE username = :username");
        $stmt->execute([':username' => $username]);
        
        if ($stmt->fetch()) {
            $error = "Username sudah terdaftar! Gunakan username lain.";
        } else {
            // Insert driver baru
            try {
                $sql = "INSERT INTO DRIVER (nama_driver, username, password, jenis_kelamin, status) 
                        VALUES (:nama, :username, :password, :jk, 'aktif')";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nama' => $nama,
                    ':username' => $username,
                    ':password' => $password,
                    ':jk' => $jenis_kelamin
                ]);

                $success = "Registrasi berhasil! Silakan login dengan akun Anda.";
                // Clear form
                $nama = $username = $password = $password_confirm = '';
                $jenis_kelamin = '';
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
    <title>Registrasi Driver - Taksi App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            width: 100%;
            max-width: 500px;
        }
        .register-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            background: white;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .register-header h2 {
            margin: 0;
            font-weight: bold;
            font-size: 28px;
        }
        .register-header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .register-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-select {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 14px;
        }
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: bold;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }
        .alert-custom {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .password-strength {
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h2>🚕 Taksi App</h2>
                <p>Registrasi Driver Baru</p>
            </div>
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                        <strong>Gagal!</strong> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                        <strong>Sukses!</strong> <?= htmlspecialchars($success) ?>
                        <a href="login.php" class="btn btn-sm btn-success mt-2">Ke Halaman Login</a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="nama_driver">Nama Lengkap</label>
                        <input type="text" 
                               id="nama_driver"
                               name="nama_driver" 
                               class="form-control" 
                               placeholder="Contoh: Budi Santoso"
                               value="<?= htmlspecialchars($nama ?? '') ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" 
                               id="username"
                               name="username" 
                               class="form-control" 
                               placeholder="Minimal 3 karakter"
                               value="<?= htmlspecialchars($username ?? '') ?>"
                               required>
                        <small class="text-muted">Username harus unik dan minimal 3 karakter</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="jenis_kelamin">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="jenis_kelamin" class="form-select" required>
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="Pria" <?= ($jenis_kelamin ?? '') == 'Pria' ? 'selected' : '' ?>>Pria</option>
                            <option value="Wanita" <?= ($jenis_kelamin ?? '') == 'Wanita' ? 'selected' : '' ?>>Wanita</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" 
                               id="password"
                               name="password" 
                               class="form-control" 
                               placeholder="Minimal 5 karakter"
                               required>
                        <small class="text-muted">Minimal 5 karakter untuk keamanan</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirm">Konfirmasi Password</label>
                        <input type="password" 
                               id="password_confirm"
                               name="password_confirm" 
                               class="form-control" 
                               placeholder="Ulangi password"
                               required>
                    </div>

                    <button type="submit" class="btn btn-register btn-primary">
                        <i class="fas fa-user-plus"></i> Daftar Sekarang
                    </button>
                </form>

                <div class="login-link">
                    Sudah punya akun? <a href="login.php">Login di sini</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>
