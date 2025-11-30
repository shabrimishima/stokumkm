<?php
/**
 * File: auth/auth_check.php
 * Fungsi: Cek apakah user sudah login
 */

if(!isset($_SESSION)) {
    session_start();
}

// Cek apakah user sudah login
if(!isset($_SESSION['user_id'])) {
    // PERBAIKAN: Gunakan path absolute dari root project
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    
    // Ambil base path (misal: /logistik)
    $base_path = str_replace('/pages/dashboard.php', '', $script);
    $base_path = str_replace('/pages/produk.php', '', $base_path);
    $base_path = str_replace('/pages/kategori.php', '', $base_path);
    // Tambahkan halaman lain jika ada
    
    // Redirect ke login
    header('Location: ' . $protocol . $host . $base_path . '/auth/login.php');
    exit;
}

// Regenerate session ID untuk keamanan (setiap 30 menit)
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Helper function untuk cek kepemilikan data
function isOwner($user_id) {
    return $_SESSION['user_id'] == $user_id;
}

// Helper untuk get user info
function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'],
        'nama' => $_SESSION['nama_lengkap'],
        'email' => $_SESSION['email'],
        'usaha' => $_SESSION['nama_usaha']
    ];
}
?>