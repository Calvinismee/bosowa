<?php
require_once '../config/database.php';
require_once '../config/notification.php';
require_once 'session.php';

// Jika sudah login, redirect ke dashboard
if (isDriverLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        // Query cek username dan password
        $sql = "SELECT id_user, nama_driver FROM DRIVER WHERE username = :username AND password = :password AND status = 'aktif'";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute([
                ':username' => $username,
                ':password' => $password
            ]);
            
            $driver = $stmt->fetch();
            
            if ($driver) {
                // Login berhasil
                loginDriver($driver['id_user'], $driver['nama_driver']);
                setFlashMessage('success', 'Selamat datang, ' . $driver['nama_driver'] . '!');
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Username atau password salah, atau akun tidak aktif!";
            }
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
    <title>Login Driver - Taksi App</title>
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
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            background: white;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .login-header h2 {
            margin: 0;
            font-weight: bold;
            font-size: 28px;
        }
        .login-header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .login-body {
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
        .btn-login {
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
        .btn-login:hover {
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
        .demo-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
            border-left: 4px solid #667eea;
        }
        .demo-info strong {
            display: block;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>🚕 Taksi App</h2>
                <p>Login Driver</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                        <strong>Gagal!</strong> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" 
                               id="username"
                               name="username" 
                               class="form-control" 
                               placeholder="Masukkan username"
                               required 
                               autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" 
                               id="password"
                               name="password" 
                               class="form-control" 
                               placeholder="Masukkan password"
                               required>
                    </div>

                    <button type="submit" class="btn btn-login btn-primary">Login</button>
                </form>
                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                    Belum punya akun? <a href="register.php" style="color: #667eea; font-weight: bold;">Daftar di sini</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
