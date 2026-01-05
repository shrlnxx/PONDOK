<?php
// 1. CONFIGURATION
// Matikan semua output error ke browser agar RESPON JSON BERSIH
error_reporting(0);
ini_set('display_errors', 0);

// Set zona waktu
date_default_timezone_set('Asia/Jakarta');

// Header wajib untuk JSON API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 2. DATABASE CONNECTION
$host = 'localhost';
$dbname = 'pelanggaran_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Jika koneksi gagal, kirim JSON error (bukan text HTML!)
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database Connection Failed']);
    exit;
}

// 3. SESSION MANAGEMENT
// Mulai session jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 4. HELPER FUNCTIONS
function send_json($data, $status_code = 200)
{
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}
