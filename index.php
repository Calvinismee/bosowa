<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bosowa Driver App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .welcome-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
        }
        .welcome-icon {
            font-size: 80px;
            color: #667eea;
            margin-bottom: 20px;
        }
        .welcome-title {
            font-size: 42px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .welcome-subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }
        .btn-welcome {
            padding: 12px 30px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 8px;
            margin: 8px;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-welcome:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .btn-secondary-custom {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
        }
    </style>
</head>
<body>
    <div class="welcome-card">
        <div class="welcome-icon">
            <i class="fas fa-taxi"></i>
        </div>
        <h1 class="welcome-title">Bosowa Driver App</h1>
        <p class="welcome-subtitle">Manajemen Transaksi Driver</p>
        <hr class="my-4">
        
        <div class="d-grid gap-2">
            <a href="auth/login.php" class="btn btn-welcome btn-primary-custom">
                <i class="fas fa-sign-in-alt"></i> Start
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>