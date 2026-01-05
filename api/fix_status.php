<?php
// fix_status.php - Script untuk memperbaiki status yang tidak konsisten
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$dbname = 'pelanggaran_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

echo "<h1>Fix Perizinan Status</h1>";
echo "<style>body{font-family:monospace;background:#1e293b;color:#fff;padding:20px} table{border-collapse:collapse;width:100%} th,td{border:1px solid #334155;padding:8px;text-align:left} th{background:#4f46e5} .success{color:#22c55e} .error{color:#ef4444}</style>";

// 1. Find inconsistent records (status AKTIF but already returned)
echo "<h2>1. Mencari record tidak konsisten...</h2>";
$stmt = $pdo->query("
    SELECT id, santri_nis, status, waktu_kembali 
    FROM perizinan 
    WHERE (status = 'AKTIF' OR status = 'TERLAMBAT') 
    AND waktu_kembali IS NOT NULL
");
$inconsistent1 = $stmt->fetchAll();

// 2. Find records where waktu_kembali IS NULL but status is SELESAI
$stmt = $pdo->query("
    SELECT id, santri_nis, status, waktu_kembali 
    FROM perizinan 
    WHERE waktu_kembali IS NULL 
    AND status IN ('SELESAI', 'TEPAT WAKTU')
");
$inconsistent2 = $stmt->fetchAll();

// 3. Find records with status AKTIF (blocking new permits)
$stmt = $pdo->query("
    SELECT id, santri_nis, status, waktu_kembali, rencana_kembali 
    FROM perizinan 
    WHERE status IN ('AKTIF')
");
$activeRecords = $stmt->fetchAll();

echo "<h3>Records dengan status AKTIF/TERLAMBAT tapi waktu_kembali sudah diisi:</h3>";
if ($inconsistent1) {
    echo "<table><tr><th>ID</th><th>NIS</th><th>Status</th><th>Waktu Kembali</th></tr>";
    foreach ($inconsistent1 as $r) {
        echo "<tr><td>{$r['id']}</td><td>{$r['santri_nis']}</td><td>{$r['status']}</td><td>{$r['waktu_kembali']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='success'>✓ Tidak ditemukan</p>";
}

echo "<h3>Records dengan status AKTIF (blocking new permits):</h3>";
if ($activeRecords) {
    echo "<table><tr><th>ID</th><th>NIS</th><th>Status</th><th>Waktu Kembali</th><th>Rencana Kembali</th></tr>";
    foreach ($activeRecords as $r) {
        echo "<tr><td>{$r['id']}</td><td>{$r['santri_nis']}</td><td>{$r['status']}</td><td>" . ($r['waktu_kembali'] ?? 'NULL') . "</td><td>{$r['rencana_kembali']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='success'>✓ Tidak ditemukan</p>";
}

// Auto-fix with confirmation
if (isset($_GET['fix']) && $_GET['fix'] == 'yes') {
    echo "<h2>2. Memperbaiki data...</h2>";

    // Fix: Records with AKTIF or TERLAMBAT status but waktu_kembali filled -> change to SELESAI
    $stmt = $pdo->prepare("
        UPDATE perizinan 
        SET status = CASE 
            WHEN status = 'TERLAMBAT' THEN 'TERLAMBAT'
            ELSE 'SELESAI' 
        END
        WHERE status IN ('AKTIF', 'TERLAMBAT') 
        AND waktu_kembali IS NOT NULL
    ");
    $stmt->execute();
    $fixed1 = $stmt->rowCount();
    echo "<p class='success'>✓ Marked $fixed1 records as completed (status tetap karena sudah kembali)</p>";

    // Fix: Records with waktu_kembali NULL but status is SELESAI -> change to AKTIF
    $stmt = $pdo->prepare("
        UPDATE perizinan 
        SET status = 'AKTIF' 
        WHERE waktu_kembali IS NULL 
        AND status IN ('SELESAI', 'TEPAT WAKTU')
    ");
    $stmt->execute();
    $fixed2 = $stmt->rowCount();
    echo "<p class='success'>✓ Fixed $fixed2 records (SELESAI → AKTIF karena belum kembali)</p>";

    echo "<h3>Done! <a href='debug_late.php' style='color:#60a5fa'>Cek Debug Page</a></h3>";
} else {
    echo "<h2>2. Untuk memperbaiki, klik link berikut:</h2>";
    echo "<p><a href='?fix=yes' style='color:#f59e0b;font-size:18px'>⚠️ KLIK UNTUK AUTO-FIX</a></p>";
}

// Show all current data
echo "<h2>3. Semua Data Perizinan</h2>";
$stmt = $pdo->query("SELECT id, santri_nis, status, waktu_kembali FROM perizinan ORDER BY id DESC");
$all = $stmt->fetchAll();
if ($all) {
    echo "<table><tr><th>ID</th><th>NIS</th><th>Status</th><th>Waktu Kembali</th></tr>";
    foreach ($all as $r) {
        $kembali = $r['waktu_kembali'] ?? '<em style="color:#f59e0b">NULL</em>';
        echo "<tr><td>{$r['id']}</td><td>{$r['santri_nis']}</td><td>{$r['status']}</td><td>$kembali</td></tr>";
    }
    echo "</table>";
}
?>