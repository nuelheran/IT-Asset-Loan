<?php
/**
 * Konfigurasi Database
 * Sesuaikan DB_HOST, DB_USER, DB_PASS, DB_NAME dengan server Anda
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'it_asset_loan');

// Base URL aplikasi - sesuaikan jika folder berbeda
define('BASE_URL', '/it-asset-loan/');

// Koneksi mysqli
function getConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('Koneksi database gagal: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
