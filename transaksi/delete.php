<?php
require_once '../config/database.php';
require_once '../config/notification.php';
require_once '../auth/session.php';

requireDriverLogin();

$driver_id = getLoggedInDriverId();

if (!isset($_GET['id'])) {
    setFlashMessage('error', 'ID transaksi tidak ditemukan!');
    header("Location: read.php");
    exit;
}

$id = $_GET['id'];

try {
    // Cek ownership transaksi
    $stmt = $pdo->prepare("SELECT * FROM TRANSAKSI WHERE id_transaksi = :id AND id_user = :driver_id");
    $stmt->execute([':id' => $id, ':driver_id' => $driver_id]);
    $transaksi = $stmt->fetch();

    if (!$transaksi) {
        throw new Exception("Transaksi tidak ditemukan!");
    }

    // Hapus transaksi
    $stmt = $pdo->prepare("DELETE FROM TRANSAKSI WHERE id_transaksi = :id");
    $stmt->execute([':id' => $id]);

    setFlashMessage('success', 'Transaksi berhasil dihapus!');
    header("Location: read.php");
} catch (Exception $e) {
    setFlashMessage('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
    header("Location: read.php");
}
?>
