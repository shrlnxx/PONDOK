<?php
// TEMPORARY: Use debug config to see errors
require_once 'config_debug.php';

// Wrap everything in try-catch to catch ALL errors
try {

// log_debug() function already defined in config_debug.php - no need to redeclare

// Auth Check
if (!isset($_SESSION['user_id'])) {
    log_debug("Auth failed: Session user_id not set");
    send_json(['status' => 'error', 'message' => 'Unauthorized - Silahkan Login Ulang'], 401);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

log_debug("Request: $method $action");

// 1. DASHBOARD STATS
if ($action == 'stats') {
    // ... code ...
    try {
        // Izin Hari Ini (Created today)
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM perizinan WHERE DATE(waktu_keluar) = CURDATE()");
        $today = $stmt->fetch()['cnt'];

        // Sedang Izin (Status AKTIF)
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM perizinan WHERE status = 'AKTIF'");
        $active = $stmt->fetch()['cnt'];

        // Terlambat Kembali (Status TERLAMBAT & Belum Kembali)
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM perizinan WHERE status = 'TERLAMBAT' AND waktu_kembali IS NULL");
        $late = $stmt->fetch()['cnt'];

        // Auto-update status to TERLAMBAT if overdue
        // Auto-update status to TERLAMBAT if overdue
        $tolerance = 15; // default fallback
        $s = $pdo->query("SELECT setting_value FROM perizinan_settings WHERE setting_key = 'tolerance_minutes'");
        if ($r = $s->fetch())
            $tolerance = (int) $r['setting_value'];

        // Use PHP time instead of MySQL NOW() to ensure timezone consistency (Asia/Jakarta)
        $current_time = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("UPDATE perizinan SET status = 'TERLAMBAT' WHERE status = 'AKTIF' AND ? > DATE_ADD(rencana_kembali, INTERVAL ? MINUTE)");
        $stmt->execute([$current_time, $tolerance]);

        // Recent Activity
        $stmt = $pdo->query("
            SELECT p.*, s.nama, s.kelas 
            FROM perizinan p 
            JOIN santri s ON p.santri_nis = s.nis 
            ORDER BY p.waktu_keluar DESC LIMIT 10
        ");
        $recent = $stmt->fetchAll();

        send_json([
            'today' => $today,
            'active' => $active,
            'late' => $late,
            'recent' => $recent
        ]);
    } catch (PDOException $e) {
        log_debug("Stats Error: " . $e->getMessage());
        send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// 2. CREATE IZIN
if ($action == 'create' && $method == 'POST') {
    $raw_input = file_get_contents('php://input');
    log_debug("Raw Input: " . $raw_input);

    $input = json_decode($raw_input, true);

    $nis = $input['nis'] ?? '';
    $tujuan = $input['tujuan'] ?? '';
    // Fix Date Format from HTML datetime-local (replace T with space)
    $keluar = str_replace('T', ' ', $input['waktu_keluar'] ?? date('Y-m-d H:i:s'));
    $kembali = str_replace('T', ' ', $input['rencana_kembali'] ?? '');
    $ket = $input['keterangan'] ?? '';
    $user_id = $_SESSION['user_id'];

    // Ensure seconds are added if missing to make valid MySQL datetime
    if (strlen($keluar) == 16)
        $keluar .= ':00'; // YYYY-MM-DD HH:MM -> :SS
    if (strlen($kembali) == 16)
        $kembali .= ':00';

    log_debug("Processing Create: NIS=$nis, Keluar=$keluar, Kembali=$kembali");

    if (empty($nis) || empty($tujuan) || empty($kembali)) {
        log_debug("Validation Failed: Empty fields");
        send_json(['status' => 'error', 'message' => 'Data tidak lengkap. Pastikan Nama Santri dipilih.'], 400);
    }

    // Cek duplikasi izin aktif
    $stmt = $pdo->prepare("SELECT id FROM perizinan WHERE santri_nis = ? AND status IN ('AKTIF', 'TERLAMBAT')");
    $stmt->execute([$nis]);
    if ($stmt->fetch()) {
        log_debug("Validation Failed: Duplicate active permit for $nis");
        send_json(['status' => 'error', 'message' => 'Santri ini masih memiliki izin aktif yang belum kembali!'], 400);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO perizinan (santri_nis, tujuan_izin, waktu_keluar, rencana_kembali, keterangan, petugas_keluar_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nis, $tujuan, $keluar, $kembali, $ket, $user_id]);

        $lastId = $pdo->lastInsertId();
        log_debug("Success Create ID: " . $lastId);

        send_json(['status' => 'success', 'message' => 'Izin berhasil dicatat', 'id' => $lastId]);
    } catch (PDOException $e) {
        log_debug("DB Insert Error: " . $e->getMessage());
        send_json(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()], 500);
    }
}

// 3. LIST DATA
if ($action == 'list') {
    $start = $_GET['start'] ?? '';
    $end = $_GET['end'] ?? '';
    $status = $_GET['status'] ?? '';

    $sql = "SELECT p.*, s.nama, s.kelas, s.asrama, u.fullname as petugas 
            FROM perizinan p 
            JOIN santri s ON p.santri_nis = s.nis
            LEFT JOIN users u ON p.petugas_keluar_id = u.id 
            WHERE 1=1";

    $params = [];

    if ($start && $end) {
        $sql .= " AND DATE(p.waktu_keluar) BETWEEN ? AND ?";
        $params[] = $start;
        $params[] = $end;
    }

    if ($status) {
        $sql .= " AND p.status = ?";
        $params[] = $status;
    }

    // New Filters: Name and Class
    $nama = $_GET['nama'] ?? '';
    $kelas = $_GET['kelas'] ?? '';

    if ($nama) {
        $sql .= " AND s.nama LIKE ?";
        $params[] = "%$nama%";
    }

    if ($kelas) {
        $sql .= " AND s.kelas LIKE ?";
        $params[] = "%$kelas%";
    }

    $sql .= " ORDER BY p.waktu_keluar DESC LIMIT 200";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        send_json($stmt->fetchAll());
    } catch (Exception $e) {
        log_debug("List Error: " . $e->getMessage());
        send_json([]);
    }
}

// 4. RETURN (KEMBALI)
if ($action == 'return' && $method == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    $user_id = $_SESSION['user_id'];

    try {
        // 1. Get Plan Data & Tolerance
        $stmt = $pdo->prepare("SELECT rencana_kembali FROM perizinan WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            send_json(['status' => 'error', 'message' => 'Data not found'], 404);
        }

        $tolerance = 15;
        $s = $pdo->query("SELECT setting_value FROM perizinan_settings WHERE setting_key = 'tolerance_minutes'");
        if ($r = $s->fetch())
            $tolerance = (int) $r['setting_value'];

        // 2. Check Lateness
        $plan = new DateTime($row['rencana_kembali']);
        $now = new DateTime(); // Current Time

        // Add tolerance to plan
        $limit = clone $plan;
        $limit->modify("+$tolerance minutes");

        $status = 'SELESAI';
        if ($now > $limit) {
            $status = 'TERLAMBAT'; // Returned Late
        }

        // 3. Update
        $stmt = $pdo->prepare("UPDATE perizinan SET status = ?, waktu_kembali = NOW(), petugas_kembali_id = ? WHERE id = ?");
        $stmt->execute([$status, $user_id, $id]);

        $msg = ($status == 'TERLAMBAT') ? 'Santri kembali terlambat! Status tercatat.' : 'Pengembalian berhasil dicatat.';

        send_json(['status' => 'success', 'message' => $msg, 'late' => ($status == 'TERLAMBAT')]);
    } catch (PDOException $e) {
        log_debug("Return Error: " . $e->getMessage());
        send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// 5. GET/SET SETTINGS
if ($action == 'settings') {
    if ($method == 'GET') {
        $stmt = $pdo->query("SELECT * FROM perizinan_settings");
        $data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // key => value
        send_json($data);
    } elseif ($method == 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        foreach ($input as $k => $v) {
            $stmt = $pdo->prepare("UPDATE perizinan_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$v, $k]);
        }
        send_json(['status' => 'success']);
    }
}

// 6. READ SINGLE (FOR PRINT)
if ($action == 'read') {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("
        SELECT p.*, s.nama, s.kelas, s.asrama, u.fullname as petugas_nama
        FROM perizinan p
        JOIN santri s ON p.santri_nis = s.nis
        LEFT JOIN users u ON p.petugas_keluar_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if ($data) {
        // Log print
        $pdo->prepare("INSERT INTO perizinan_print_log (perizinan_id, printed_by) VALUES (?, ?)")
            ->execute([$id, $_SESSION['user_id']]);
        send_json($data);
    } else {
        send_json(['status' => 'error', 'message' => 'Not Found'], 404);
    }
}

// Catch ALL errors and return as JSON
} catch (Throwable $e) {
    log_debug("FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    send_json([
        'status' => 'error',
        'message' => 'Server Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
?>
