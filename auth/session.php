<?php
// auth/session.php - Mengelola session driver

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah driver sudah login
 * @return bool
 */
function isDriverLoggedIn() {
    return isset($_SESSION['driver_id']) && isset($_SESSION['driver_name']);
}

/**
 * Login driver
 * @param int $driver_id
 * @param string $driver_name
 */
function loginDriver($driver_id, $driver_name) {
    $_SESSION['driver_id'] = $driver_id;
    $_SESSION['driver_name'] = $driver_name;
    $_SESSION['login_time'] = time();
}

/**
 * Logout driver
 */
function logoutDriver() {
    session_destroy();
    header("Location: login.php");
    exit;
}

/**
 * Get ID driver yang sedang login
 * @return int|null
 */
function getLoggedInDriverId() {
    return isset($_SESSION['driver_id']) ? $_SESSION['driver_id'] : null;
}

/**
 * Get nama driver yang sedang login
 * @return string|null
 */
function getLoggedInDriverName() {
    return isset($_SESSION['driver_name']) ? $_SESSION['driver_name'] : null;
}

/**
 * Redirect ke login jika belum login
 */
function requireDriverLogin() {
    if (!isDriverLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}
?>
