<?php
require_once 'config.php';

// Wajib Login untuk akses Modul Pelanggaran
if (!isset($_SESSION['user_id'])) {
    send_json(['status' => 'error', 'message' => 'Unauthorized'], 401);
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$method = $_SERVER['REQUEST_METHOD'];

// CREATE NEW PELANGGARAN
if ($action == 'create' && $method == 'POST') {
    // Bisa dari FormData (POST standard) atau JSON Body
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $santri_nis = $input['santri_nis'] ?? '';
    // Handle tanggal: kalau kosong pakai hari ini
    $tanggal = !empty($input['tanggal_kejadian']) ? $input['tanggal_kejadian'] : date('Y-m-d');
    $jenis = $input['jenis'] ?? '';
    $kategori = $input['kategori'] ?? 'Ringan';
    $keterangan = $input['keterangan'] ?? '';
    $pencatat_id = $_SESSION['user_id'];

    if (empty($santri_nis) || empty($jenis)) {
        send_json(['status' => 'error', 'message' => 'Data tidak lengkap (NIS/Jenis wajib)'], 400);
    }

    // HANDLE FILE UPLOAD (OPSIONAL)
    $foto_path = null;
    
    if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_bukti'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            send_json(['status' => 'error', 'message' => 'Format file tidak valid. Gunakan JPG, PNG, atau JPEG.'], 400);
        }
        
        // Validate file size (max 2MB)
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            send_json(['status' => 'error', 'message' => 'Ukuran file terlalu besar. Maksimal 2MB.'], 400);
        }
        
        // Create uploads directory if not exists
        $uploadDir = '../uploads/pelanggaran/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('pel_' . date('Ymd') . '_') . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $foto_path = 'uploads/pelanggaran/' . $filename; // Relative path for database
        } else {
            send_json(['status' => 'error', 'message' => 'Gagal mengupload file'], 500);
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO pelanggaran (santri_nis, tanggal_kejadian, jenis_pelanggaran, kategori, keterangan, pencatat_id, foto_bukti) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$santri_nis, $tanggal, $jenis, $kategori, $keterangan, $pencatat_id, $foto_path]);

        send_json(['status' => 'success', 'message' => 'Pelanggaran berhasil dicatat', 'foto_uploaded' => !empty($foto_path)]);
    } catch (Exception $e) {
        // If database insert fails, delete uploaded file
        if ($foto_path && file_exists('../' . $foto_path)) {
            unlink('../' . $foto_path);
        }
        send_json(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()], 500);
    }
}

// LIST DATA (Support Filter Tanggal & Petugas)
if ($action == 'list') {
    // Join users u1 -> Pencatat (Pelapor)
    // Join users u2 -> Penangan (Yang update status)
    $sql = "SELECT p.*, s.nama, s.kelas, s.asrama, 
                   u1.username as pencatat, 
                   u2.username as penangan
            FROM pelanggaran p 
            JOIN santri s ON p.santri_nis = s.nis 
            LEFT JOIN users u1 ON p.pencatat_id = u1.id
            LEFT JOIN users u2 ON p.penangan_id = u2.id";

    $params = [];
    $where = [];

    // Filter Tanggal (Wajib ada Start & End untuk Laporan)
    if (isset($_GET['start']) && isset($_GET['end']) && !empty($_GET['start'])) {
        $where[] = "DATE(p.tanggal_kejadian) BETWEEN ? AND ?";
        $params[] = $_GET['start'];
        $params[] = $_GET['end'];
        $limit = ""; // Kalau filter tanggal, ambil semua (unlimited)
    } else {
        $limit = "LIMIT 100"; // Default dashboard cuma 100
    }

    if (count($where) > 0) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY p.tanggal_kejadian DESC, p.created_at DESC $limit";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    send_json(['data' => $data]);
}

// RIWAYAT PELANGGARAN PER SANTRI
if ($action == 'history_santri') {
    $nis = $_GET['nis'] ?? '';
    if (!$nis)
        send_json([]); // Return empty if no NIS

    $stmt = $pdo->prepare("SELECT * FROM pelanggaran WHERE santri_nis = ? ORDER BY tanggal_kejadian DESC");
    $stmt->execute([$nis]);
    $data = $stmt->fetchAll();

    send_json($data);
}

// UPDATE STATUS
if ($action == 'update_status' && $method == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $id = $input['id'] ?? null;
    $status = $input['status'] ?? 'Proses';
    $tindakan = $input['tindakan'] ?? '';

    // Validasi ID
    if (!$id) {
        send_json(['status' => 'error', 'message' => 'ID Pelanggaran tidak valid'], 400);
    }

    try {
        // Cek dulu apakah ID ada
        $check = $pdo->prepare("SELECT id FROM pelanggaran WHERE id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) {
            send_json(['status' => 'error', 'message' => 'Data pelanggaran tidak ditemukan'], 404);
        }

        $userId = $_SESSION['user_id'] ?? 1; // Fallback to 1 (Admin) if session lost

        $stmt = $pdo->prepare("UPDATE pelanggaran SET status_penanganan = ?, tindakan_diambil = ?, penangan_id = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$status, $tindakan, $userId, $id]);

        if ($result) {
            send_json(['status' => 'success', 'message' => 'Status berhasil diupdate']);
        } else {
            send_json(['status' => 'error', 'message' => 'Gagal mengupdate database'], 500);
        }
    } catch (PDOException $e) {
        send_json(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()], 500);
    }
}

// DASHBOARD STATS
if ($action == 'stats') {
    // Total Pelanggaran
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pelanggaran");
    $total = $stmt->fetch()['total'];

    // Pelanggaran Hari Ini
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as today FROM pelanggaran WHERE tanggal_kejadian = ?");
    $stmt->execute([$today]);
    $todayCount = $stmt->fetch()['today'];

    // Status (Pending vs Selesai)
    $stmt = $pdo->query("SELECT status_penanganan, COUNT(*) as count FROM pelanggaran GROUP BY status_penanganan");
    $statuses = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Recent Activities
    $stmt = $pdo->query("SELECT p.*, s.nama FROM pelanggaran p JOIN santri s ON p.santri_nis = s.nis ORDER BY p.created_at DESC LIMIT 5");
    $recent = $stmt->fetchAll();

    send_json([
        'total' => $total,
        'today' => $todayCount,
        'proses' => $statuses['Proses'] ?? 0,
        'selesai' => $statuses['Selesai'] ?? 0,
        'recent' => $recent
    ]);
}
