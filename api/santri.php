<?php
require_once 'config.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$method = $_SERVER['REQUEST_METHOD'];

// LIST ALL SANTRI (Limit 500)
if ($action == 'list') {
    $stmt = $pdo->query("SELECT * FROM santri ORDER BY nama ASC LIMIT 500");
    $data = $stmt->fetchAll();
    send_json($data);
}

// SEARCH
if ($action == 'search') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';

    if (strlen($q) < 2) {
        echo json_encode([]);
        exit;
    }

    // Cari berdasarkan nama ATAU NIS
    $stmt = $pdo->prepare("SELECT nis, nama, kelas, asrama FROM santri WHERE nama LIKE ? OR nis LIKE ? LIMIT 15");
    $stmt->execute(["%$q%", "%$q%"]);
    $results = $stmt->fetchAll();

    echo json_encode($results);
    exit;
}

// Security Check untuk aksi lain (Upload/Delete)
if (!isset($_SESSION['user_id'])) {
    send_json(['status' => 'error', 'message' => 'Unauthorized'], 401);
}

// TAMBAH SANTRI MANUAL
if ($action == 'create' && $method == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $nis = trim($input['nis'] ?? '');
    $nama = trim($input['nama'] ?? '');
    $kelas = trim($input['kelas'] ?? '');
    $asrama = trim($input['asrama'] ?? '');

    if (empty($nis) || empty($nama)) {
        send_json(['status' => 'error', 'message' => 'NIS dan Nama wajib diisi'], 400);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO santri (nis, nama, kelas, asrama) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nama = VALUES(nama), kelas = VALUES(kelas), asrama = VALUES(asrama)");
        $stmt->execute([$nis, $nama, $kelas, $asrama]);

        send_json(['status' => 'success', 'message' => 'Data santri berhasil disimpan']);
    } catch (PDOException $e) {
        send_json(['status' => 'error', 'message' => 'Gagal input: ' . $e->getMessage()], 500);
    }
}

// IMPORT EXCEL
if ($action == 'import' && $method == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['data'])) {
        send_json(['status' => 'error', 'message' => 'No data provided'], 400);
    }

    $count = 0;
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO santri (nis, nama, kelas, asrama) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nama = VALUES(nama), kelas = VALUES(kelas), asrama = VALUES(asrama)");

        foreach ($input['data'] as $row) {
            // Normalisasi key lowercase biar aman
            $r = array_change_key_case($row, CASE_LOWER);

            $nis = $r['nis'] ?? null;
            $nama = $r['nama'] ?? null;
            $kelas = $r['kelas'] ?? '-';
            $asrama = $r['asrama'] ?? '-';

            if ($nis && $nama) {
                $stmt->execute([$nis, $nama, $kelas, $asrama]);
                $count++;
            }
        }
        $pdo->commit();
        send_json(['status' => 'success', 'message' => "$count data santri berhasil diimport!"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        send_json(['status' => 'error', 'message' => 'Import gagal: ' . $e->getMessage()], 500);
    }
}
