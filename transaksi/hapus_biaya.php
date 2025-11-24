<?php
require_once '../config/database.php';
require_once '../config/notification.php';
require_once '../auth/session.php';

requireDriverLogin();

$driver_id = getLoggedInDriverId();

if (!isset($_GET['id']) || !isset($_GET['jenis'])) {
    setFlashMessage('error', 'Parameter tidak lengkap!');
    header("Location: read.php");
    exit;
}

$id = $_GET['id'];
$jenis = $_GET['jenis'];

try {
    // Cek ownership transaksi
    $stmt = $pdo->prepare("SELECT * FROM TRANSAKSI WHERE id_transaksi = :id AND id_user = :driver_id");
    $stmt->execute([':id' => $id, ':driver_id' => $driver_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception("Transaksi tidak ditemukan!");
    }

    // Hapus biaya
    $stmt = $pdo->prepare("DELETE FROM DETAIL_BIAYA WHERE id_transaksi = :id AND jenis_biaya = :jenis");
    $stmt->execute([':id' => $id, ':jenis' => $jenis]);

    setFlashMessage('success', 'Biaya berhasil dihapus!');
    header("Location: edit_biaya.php?id=" . $id);
} catch (Exception $e) {
    setFlashMessage('error', 'Gagal menghapus biaya: ' . $e->getMessage());
    header("Location: edit_biaya.php?id=" . $id);
}
?>
