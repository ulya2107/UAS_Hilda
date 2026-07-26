<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "toko_bunga";

try {
    $db = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Konfigurasi Midtrans Payment Gateway (Sandbox/Testing secara default)
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-fJmZ55V9_7P7Y9-L65R7J2nB'); // Silakan ganti dengan Server Key Sandbox Anda
define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-nS9r_NlF22Q2J7vK'); // Silakan ganti dengan Client Key Sandbox Anda
define('MIDTRANS_IS_PRODUCTION', false); // Set ke true jika menggunakan akun production (Live)
?>
