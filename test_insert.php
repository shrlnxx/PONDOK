<?php
// MOCK SESSION
session_start();
$_SESSION['user_id'] = 1; // Assume generic admin

require_once 'api/config.php';

echo "<h2>Test Insert Perizinan</h2>";

$nis = '12345'; // Must exist in santri table. If not, this will fail if FK exists or logic checks.
// Let's first ensure a santri exists
$pdo->query("INSERT IGNORE INTO santri (nis, nama, kelas, asrama) VALUES ('12345', 'Uji Coba Santri', '1A', 'Asrama 1')");

$tujuan = 'Tes Debugging';
$keluar = date('Y-m-d H:i:s');
$kembali = date('Y-m-d H:i:s', strtotime('+3 hours'));
$ket = 'Testing via script';
$user_id = 1;

try {
    $stmt = $pdo->prepare("INSERT INTO perizinan (santri_nis, tujuan_izin, waktu_keluar, rencana_kembali, keterangan, petugas_keluar_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nis, $tujuan, $keluar, $kembali, $ket, $user_id]);
    echo "<h3>SUCCESS! Inserted ID: " . $pdo->lastInsertId() . "</h3>";
} catch (PDOException $e) {
    echo "<h3 style='color:red'>FAILED: " . $e->getMessage() . "</h3>";
}
