<?php
// c:/xampp/htdocs/pelanggaran/api/auth.php
require_once 'config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

// LOGIN
if ($action == 'login' && $method == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    try {
        // Coba ambil user beserta role-nya
        // Gunakan catch untuk fallback jika kolom role belum ada
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Cek apakah key 'role' ada di array $user
            if (array_key_exists('role', $user)) {
                $_SESSION['role'] = $user['role'];
            } else {
                $_SESSION['role'] = 'admin'; // Fallback jadi admin jika kolom error
            }

            send_json(['status' => 'success', 'message' => 'Login berhasil']);
        } else {
            send_json(['status' => 'error', 'message' => 'Username atau password salah'], 401);
        }
    } catch (PDOException $e) {
        // Jika error database (misal kolom tidak ditemukan), kirim pesan error
        send_json(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()], 500);
    }
}

// LOGOUT
if ($action == 'logout') {
    session_destroy();
    send_json(['status' => 'success']);
}

// CHECK SESSION
if ($action == 'check') {
    if (isset($_SESSION['user_id'])) {
        send_json([
            'status' => 'authenticated',
            'user' => $_SESSION['username'],
            'role' => $_SESSION['role'] ?? 'admin'
        ]);
    } else {
        send_json(['status' => 'guest']);
    }
}
