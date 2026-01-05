<?php
// debug_late.php - Script untuk debug counter terlambat
header('Content-Type: text/html; charset=utf-8');

// Database connection
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

echo "<h1>Debug Perizinan Late Counter</h1>";
echo "<style>body{font-family:monospace;background:#1e293b;color:#fff;padding:20px} table{border-collapse:collapse;width:100%} th,td{border:1px solid #334155;padding:8px;text-align:left} th{background:#4f46e5}</style>";

// 1. Current Server Time
$stmt = $pdo->query("SELECT NOW() as server_time");
$serverTime = $stmt->fetch()['server_time'];
echo "<h2>1. Server Time</h2>";
echo "<p><strong>NOW():</strong> " . $serverTime . "</p>";

// 2. Get tolerance
$tolerance = 15;
try {
    $stmt = $pdo->query("SELECT * FROM perizinan_settings");
    $settings = $stmt->fetchAll();
    echo "<h2>2. Settings Table</h2>";
    if ($settings) {
        echo "<table><tr><th>ID</th><th>Key</th><th>Value</th></tr>";
        foreach ($settings as $s) {
            echo "<tr><td>{$s['id']}</td><td>{$s['setting_key']}</td><td>{$s['setting_value']}</td></tr>";
            if ($s['setting_key'] == 'tolerance_minutes') {
                $tolerance = (int) $s['setting_value'];
            }
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠ Tabel perizinan_settings kosong!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p style='color:yellow'>⚠ Tabel perizinan_settings tidak ditemukan. Menggunakan default: 15 menit</p>";
}

echo "<p><strong>Tolerance:</strong> " . $tolerance . " menit</p>";

// 3. List all active permits (not returned yet)
echo "<h2>3. Perizinan Aktif (Belum Kembali)</h2>";
$stmt = $pdo->query("
    SELECT p.id, p.santri_nis, p.rencana_kembali, p.waktu_kembali, p.status,
           DATE_ADD(p.rencana_kembali, INTERVAL $tolerance MINUTE) as batas_toleransi,
           TIMESTAMPDIFF(MINUTE, DATE_ADD(p.rencana_kembali, INTERVAL $tolerance MINUTE), NOW()) as menit_terlambat,
           CASE WHEN NOW() > DATE_ADD(p.rencana_kembali, INTERVAL $tolerance MINUTE) THEN 'TERLAMBAT' ELSE 'MASIH WAKTU' END as hitung_status
    FROM perizinan p 
    WHERE p.waktu_kembali IS NULL
    ORDER BY p.rencana_kembali ASC
");
$activePermits = $stmt->fetchAll();

if ($activePermits) {
    echo "<table>";
    echo "<tr><th>ID</th><th>NIS</th><th>Rencana Kembali</th><th>Batas (+Toleransi)</th><th>Menit Terlambat</th><th>Hitung Status</th><th>DB Status</th></tr>";
    foreach ($activePermits as $p) {
        $color = ($p['hitung_status'] == 'TERLAMBAT') ? 'color:#ef4444' : 'color:#22c55e';
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['santri_nis']}</td>";
        echo "<td>{$p['rencana_kembali']}</td>";
        echo "<td>{$p['batas_toleransi']}</td>";
        echo "<td>{$p['menit_terlambat']}</td>";
        echo "<td style='{$color};font-weight:bold'>{$p['hitung_status']}</td>";
        echo "<td>{$p['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange'>⚠ Tidak ada perizinan aktif (semua sudah kembali)</p>";
}

// 4. Direct count query
echo "<h2>4. Query Counter Langsung</h2>";
$stmt = $pdo->prepare("
    SELECT COUNT(*) as cnt FROM perizinan 
    WHERE waktu_kembali IS NULL 
    AND NOW() > DATE_ADD(rencana_kembali, INTERVAL ? MINUTE)
");
$stmt->execute([$tolerance]);
$lateCount = $stmt->fetch()['cnt'];
echo "<p><strong>Late Count:</strong> <span style='font-size:24px;color:#ef4444'>" . $lateCount . "</span></p>";

// 5. Count all
echo "<h2>5. Summary</h2>";
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM perizinan WHERE DATE(waktu_keluar) = CURDATE()");
$today = $stmt->fetch()['cnt'];

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM perizinan WHERE waktu_kembali IS NULL");
$active = $stmt->fetch()['cnt'];

echo "<table>";
echo "<tr><th>Metric</th><th>Count</th></tr>";
echo "<tr><td>Izin Hari Ini</td><td>$today</td></tr>";
echo "<tr><td>Sedang Izin (Belum Kembali)</td><td>$active</td></tr>";
echo "<tr><td style='color:#ef4444'>Terlambat (Melewati Batas + Toleransi)</td><td style='color:#ef4444;font-weight:bold'>$lateCount</td></tr>";
echo "</table>";

echo "<h2>6. Recent 5 Perizinan</h2>";
$stmt = $pdo->query("SELECT * FROM perizinan ORDER BY id DESC LIMIT 5");
$recent = $stmt->fetchAll();
if ($recent) {
    echo "<table><tr><th>ID</th><th>NIS</th><th>Waktu Keluar</th><th>Rencana Kembali</th><th>Waktu Kembali</th><th>Status</th></tr>";
    foreach ($recent as $r) {
        echo "<tr>";
        echo "<td>{$r['id']}</td>";
        echo "<td>{$r['santri_nis']}</td>";
        echo "<td>{$r['waktu_keluar']}</td>";
        echo "<td>{$r['rencana_kembali']}</td>";
        echo "<td>" . ($r['waktu_kembali'] ?? '<em>NULL</em>') . "</td>";
        echo "<td>{$r['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr><p><em>Debug script generated at: " . date('Y-m-d H:i:s') . "</em></p>";
?>