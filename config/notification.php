<?php
// Helper untuk menangani notifikasi dengan Session Flash Message

session_start();

/**
 * Set Flash Message
 * @param string $type - 'success', 'error', 'warning', 'info'
 * @param string $message - Pesan notifikasi
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get dan hapus Flash Message
 * @return array|null - Flash message atau null jika tidak ada
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Display Flash Message sebagai HTML Alert
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    
    if ($flash) {
        $alertClass = 'alert-info';
        $icon = '&#9432;';
        
        switch($flash['type']) {
            case 'success':
                $alertClass = 'alert-success';
                $icon = '&#10003;';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                $icon = '&#10005;';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                $icon = '&#9888;';
                break;
        }
        
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo '<strong>' . $icon . '</strong> ' . htmlspecialchars($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}

/**
 * Display notifikasi dengan SweetAlert2
 */
function displaySweetAlertMessage() {
    $flash = getFlashMessage();
    
    if ($flash) {
        $icon = 'info';
        
        switch($flash['type']) {
            case 'success':
                $icon = 'success';
                break;
            case 'error':
                $icon = 'error';
                break;
            case 'warning':
                $icon = 'warning';
                break;
        }
        
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
        echo '<script>
            Swal.fire({
                icon: "' . $icon . '",
                title: "' . ($flash['type'] == 'success' ? 'Berhasil' : 'Perhatian') . '",
                text: "' . htmlspecialchars($flash['message']) . '",
                confirmButtonText: "OK"
            });
        </script>';
    }
}
?>
