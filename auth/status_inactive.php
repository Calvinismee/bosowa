<?php
session_start();

// Hapus session
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Tidak Aktif - Taksi App</title>
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
        .status-container {
            width: 100%;
            max-width: 500px;
        }
        .status-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            background: white;
            overflow: hidden;
        }
        .status-header {
            background: linear-gradient(135deg, #ef5350 0%, #e53935 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .status-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .status-header h2 {
            margin: 0 0 10px 0;
            font-weight: bold;
            font-size: 28px;
        }
        .status-body {
            padding: 40px 30px;
            text-align: center;
        }
        .status-message {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .info-box {
            background: #f5f5f5;
            border-left: 4px solid #ef5350;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: left;
        }
        .info-box strong {
            display: block;
            color: #ef5350;
            margin-bottom: 8px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn-login:hover {
            opacity: 0.9;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="status-container">
        <div class="status-card">
            <div class="status-header">
                <div class="status-icon">🚫</div>
                <h2>Status Tidak Aktif</h2>
            </div>
            <div class="status-body">
                <p class="status-message">
                    <strong>Status tidak aktif. Tidak bisa menggunakan aplikasi.</strong>
                </p>
                
                <div class="info-box">
                    <strong>Pemberitahuan:</strong>
                    Akun Anda saat ini tidak aktif. Untuk dapat menggunakan aplikasi kembali, silakan hubungi administrator untuk mengaktifkan akun Anda.
                </div>

                <div class="info-box">
                    <strong>Langkah Selanjutnya:</strong>
                    <ul style="margin: 0; padding-left: 20px; text-align: left;">
                        <li>Hubungi administrator atau supervisor Anda</li>
                        <li>Berikan ID atau username Anda</li>
                        <li>Minta untuk mengaktifkan akun</li>
                        <li>Setelah diaktifkan, login kembali</li>
                    </ul>
                </div>

                <a href="login.php" class="btn-login">Kembali ke Login</a>
            </div>
        </div>
    </div>
</body>
</html>
