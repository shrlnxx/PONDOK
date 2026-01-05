<?php
// config_debug.php - Temporary debug config
// USE THIS FOR DEBUGGING, GANTI KEMBALI KE config.php SETELAH SELESAI

// ENABLE ERROR DISPLAY FOR DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Set zona waktu
date_default_timezone_set('Asia/Jakarta');

// Header wajib untuk JSON API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// DATABASE CONNECTION
$host = 'localhost';
$dbname = 'pelanggaran_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database Connection Failed: ' . $e->getMessage()
    ]);
    exit;
}

// SESSION MANAGEMENT
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// HELPER FUNCTIONS
function send_json($data, $status_code = 200)
{
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

function log_debug($msg)
{
    file_put_contents(__DIR__ . '/debug_log.txt', date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}
?>