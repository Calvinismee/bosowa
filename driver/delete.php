<?php
require_once '../config/database.php';
require_once '../config/notification.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Ambil nama driver sebelum dihapus
        $stmt = $pdo->prepare("SELECT nama_driver FROM DRIVER WHERE id_user = :id");
        $stmt->execute([':id' => $id]);
        $driver = $stmt->fetch();
        $nama_driver = $driver ? $driver['nama_driver'] : 'Driver';
        
        // Query Hapus
        $sql = "DELETE FROM DRIVER WHERE id_user = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        // Set flash message sukses
        setFlashMessage('success', 'Driver "' . $nama_driver . '" berhasil dihapus!');
        
        // Redirect sukses
        header("Location: read.php");
    } catch (PDOException $e) {
        // Handle error jika ada relasi (foreign key constraint)
        setFlashMessage('error', 'Gagal menghapus data! Driver ini mungkin masih memiliki riwayat transaksi/kendaraan.');
        header("Location: read.php");
    }
} else {
    header("Location: read.php");
}
?>