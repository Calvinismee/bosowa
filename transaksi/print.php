<?php
require_once '../config/database.php';
require_once '../config/notification.php';
require_once '../auth/session.php';

requireDriverLogin();

$driver_id = getLoggedInDriverId();
$driver_name = getLoggedInDriverName();

// Cek ID transaksi
if (!isset($_GET['id'])) {
    die("ID transaksi tidak ditemukan!");
}

$id = $_GET['id'];

// Ambil data transaksi
$stmt = $pdo->prepare("
    SELECT t.*, 
        k.plat_kendaraan,
        CASE 
            WHEN tt.id_transaksi IS NOT NULL THEN 'Tunai'
            WHEN tq.id_transaksi IS NOT NULL THEN 'QRIS'
            ELSE 'Belum Ditentukan'
        END as metode_pembayaran,
        tt.status_setoran,
        tt.tanggal_setoran,
        r.jenis as nama_rute,
        kt.golongan,
        rt.harga as harga_rute
    FROM TRANSAKSI t
    LEFT JOIN TRANSAKSI_TUNAI tt ON t.id_transaksi = tt.id_transaksi
    LEFT JOIN TRANSAKSI_QRIS tq ON t.id_transaksi = tq.id_transaksi
    LEFT JOIN RUTE_TARIF rt ON t.id_rute_tarif = rt.id_rute_tarif
    LEFT JOIN RUTE r ON rt.id_rute = r.id_rute
    LEFT JOIN KATEGORI_TARIF kt ON rt.id_tarif = kt.id_tarif
    LEFT JOIN KENDARAAN k ON t.id_user = k.id_user AND k.status = 'aktif'
    WHERE t.id_transaksi = :id AND t.id_user = :driver_id
");
$stmt->execute([':id' => $id, ':driver_id' => $driver_id]);
$transaksi = $stmt->fetch();

if (!$transaksi) {
    die("Transaksi tidak ditemukan!");
}

// Ambil detail biaya
$stmt = $pdo->prepare("SELECT * FROM DETAIL_BIAYA WHERE id_transaksi = :id ORDER BY jenis_biaya");
$stmt->execute([':id' => $id]);
$detail_biaya = $stmt->fetchAll();

// Hitung total biaya
$total_biaya = 0;
foreach ($detail_biaya as $biaya) {
    $total_biaya += $biaya['jumlah'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Transaksi #<?= $id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            .print-hide {
                display: none !important;
            }
            .card {
                border: 1px solid #000 !important;
                box-shadow: none !important;
            }
            .btn {
                display: none !important;
            }
        }
        
        @media screen {
            .print-hide {
                display: block;
            }
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }

        .print-container {
            max-width: 800px;
            margin: 10px auto;
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .print-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .print-header h1 {
            font-size: 22px;
            margin: 0;
            font-weight: bold;
        }

        .print-header .subtitle {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }

        .transaction-id {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-bottom: 12px;
        }

        .section {
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            background-color: #f0f0f0;
            padding: 6px 10px;
            margin-bottom: 8px;
            border-left: 4px solid #667eea;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
            font-size: 12px;
        }

        .info-row label {
            font-weight: 600;
            width: 40%;
        }

        .info-row span {
            text-align: right;
            width: 60%;
        }

        .amount {
            font-weight: bold;
            color: #333;
        }

        .total-section {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 12px;
        }

        .total-row.grand-total {
            border-top: 2px solid #333;
            padding-top: 8px;
            font-size: 13px;
            font-weight: bold;
            color: #333;
        }

        .divider {
            border: 0;
            border-top: 1px dashed #999;
            margin: 10px 0;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
        }

        .action-buttons {
            text-align: center;
            margin-top: 20px;
            gap: 10px;
            display: flex;
            justify-content: center;
            print: none;
        }

        .badge {
            font-size: 12px;
            padding: 0;
            background-color: transparent !important;
            font-weight: 600;
        }
        .badge.bg-success {
            color: #198754 !important;
        }
        .badge.bg-info {
            color: #0dcaf0 !important;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Header -->
        <div class="print-header">
            <h1><i class="fas fa-taxi" style="margin-right: 10px;"></i>TAKSI APP</h1>
            <div class="subtitle">Struk Transaksi</div>
        </div>

        <!-- Transaction ID -->
        <div class="transaction-id">
            <strong>Nomor Transaksi: #<?= str_pad($transaksi['id_transaksi'], 6, '0', STR_PAD_LEFT) ?></strong>
        </div>

        <!-- Driver Info Section -->
        <div class="section">
            <div class="section-title">INFORMASI PENGEMUDI</div>
            <div class="info-row">
                <label>Nama Pengemudi:</label>
                <span><?= htmlspecialchars($driver_name) ?></span>
            </div>
            <div class="info-row">
                <label>Plat Nomor Kendaraan:</label>
                <span><?= $transaksi['plat_kendaraan'] ? htmlspecialchars($transaksi['plat_kendaraan']) : '-' ?></span>
            </div>
            <div class="info-row">
                <label>Tanggal Transaksi:</label>
                <span><?= date('d/m/Y', strtotime($transaksi['tanggal_dibuat'])) ?></span>
            </div>
        </div>

        <!-- Route Info Section -->
        <div class="section">
            <div class="section-title">DETAIL RUTE & TARIF</div>
            <div class="info-row">
                <label>Rute:</label>
                <span><?= htmlspecialchars($transaksi['nama_rute']) ?></span>
            </div>
            <div class="info-row">
                <label>Kategori:</label>
                <span><?= htmlspecialchars($transaksi['golongan']) ?></span>
            </div>
            <div class="info-row">
                <label>Tarif Rute:</label>
                <span class="amount">Rp <?= number_format($transaksi['harga_rute'], 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- Additional Charges Section -->
        <?php if ($detail_biaya): ?>
        <div class="section">
            <div class="section-title">BIAYA TAMBAHAN</div>
            <?php foreach ($detail_biaya as $biaya): ?>
            <div class="info-row">
                <label><?= htmlspecialchars($biaya['jenis_biaya']) ?>:</label>
                <span class="amount">Rp <?= number_format($biaya['jumlah'], 0, ',', '.') ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <hr class="divider">

        <!-- Total Section -->
        <div class="total-section">
            <div class="total-row">
                <label>Subtotal (Tarif Rute):</label>
                <span class="amount">Rp <?= number_format($transaksi['harga_rute'], 0, ',', '.') ?></span>
            </div>
            <?php if ($total_biaya > 0): ?>
            <div class="total-row">
                <label>Biaya Tambahan:</label>
                <span class="amount">Rp <?= number_format($total_biaya, 0, ',', '.') ?></span>
            </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <label>TOTAL TRANSAKSI:</label>
                <span>Rp <?= number_format($transaksi['total'], 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- Payment Method Section -->
        <div class="section" style="margin-top: 20px;">
            <div class="section-title">METODE PEMBAYARAN</div>
            <div class="info-row">
                <label>Metode:</label>
                <span>
                    <?php if ($transaksi['metode_pembayaran'] == 'Tunai'): ?>
                        <span class="badge bg-success"><i class="fas fa-money-bill-wave"></i> TUNAI</span>
                    <?php elseif ($transaksi['metode_pembayaran'] == 'QRIS'): ?>
                        <span class="badge bg-info"><i class="fas fa-qrcode"></i> QRIS</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Belum Ditentukan</span>
                    <?php endif; ?>
                </span>
            </div>
            <?php if ($transaksi['metode_pembayaran'] == 'Tunai' && $transaksi['status_setoran']): ?>
            <div class="info-row">
                <label>Status Setoran:</label>
                <span><?= htmlspecialchars($transaksi['status_setoran']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($transaksi['metode_pembayaran'] == 'Tunai' && $transaksi['tanggal_setoran']): ?>
            <div class="info-row">
                <label>Tanggal Setoran:</label>
                <span><?= date('d/m/Y', strtotime($transaksi['tanggal_setoran'])) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Terima kasih telah menggunakan Taksi App</p>
            <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>

    <!-- Action Buttons (untuk screen, bukan print) -->
    <div class="action-buttons print-hide">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            <i class="fas fa-times"></i> Tutup
        </button>
    </div>

    <script>
        // Auto print jika dibuka dari link print
        // Uncomment untuk auto print
        // window.print();
    </script>
</body>
</html>
