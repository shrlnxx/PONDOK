<?php
// c:/xampp/htdocs/pelanggaran/api/users.php
require_once 'config.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    send_json(['status' => 'error', 'message' => 'Unauthorized'], 401);
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$method = $_SERVER['REQUEST_METHOD'];
$currentRole = $_SESSION['role'] ?? 'security';

// 1. LIST USERS (Semua boleh lihat)
if ($action == 'list') {
    $stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY username ASC");
    $data = $stmt->fetchAll();
    send_json($data);
}

// 2. TAMBAH USER (HANYA ADMIN)
if ($action == 'create' && $method == 'POST') {
    if ($currentRole !== 'admin') {
        send_json(['status' => 'error', 'message' => 'Akses Ditolak: Hanya Admin yang boleh menambah user.'], 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');
    $role = $input['role'] ?? 'security'; // Bisa set role user baru

    if (empty($username) || empty($password)) {
        send_json(['status' => 'error', 'message' => 'Username dan Password wajib diisi'], 400);
    }

    // Cek duplikat
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        send_json(['status' => 'error', 'message' => 'Username sudah digunakan'], 400);
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $role]);
        send_json(['status' => 'success', 'message' => 'User berhasil ditambahkan']);
    } catch (PDOException $e) {
        send_json(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// 3. HAPUS USER (HANYA ADMIN)
if ($action == 'delete' && $method == 'POST') {
    if ($currentRole !== 'admin') {
        send_json(['status' => 'error', 'message' => 'Akses Ditolak: Hanya Admin yang boleh menghapus user.'], 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;

    if ($id == $_SESSION['user_id']) {
        send_json(['status' => 'error', 'message' => 'Tidak bisa menghapus akun sendiri'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$id])) {
        send_json(['status' => 'success', 'message' => 'User dihapus']);
    } else {
        send_json(['status' => 'error', 'message' => 'Gagal menghapus'], 500);
    }
}
