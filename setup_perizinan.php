<?php
require_once 'api/config.php';

try {
    // 1. Table: perizinan
    $sql1 = "CREATE TABLE IF NOT EXISTS perizinan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        santri_nis VARCHAR(20) NOT NULL,
        tujuan_izin VARCHAR(255) NOT NULL,
        waktu_keluar DATETIME NOT NULL,
        rencana_kembali DATETIME NOT NULL,
        waktu_kembali DATETIME DEFAULT NULL,
        keterangan TEXT,
        petugas_keluar_id INT NOT NULL,
        petugas_kembali_id INT DEFAULT NULL,
        status ENUM('AKTIF', 'SELESAI', 'TERLAMBAT') DEFAULT 'AKTIF',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (santri_nis),
        INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql1);

    // 2. Table: perizinan_settings
    $sql2 = "CREATE TABLE IF NOT EXISTS perizinan_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql2);

    // Insert Default Settings if not exist
    $defaults = [
        'default_duration_hours' => '3',
        'tolerance_minutes' => '15',
        'max_night_hour' => '17:00'
    ];
    foreach ($defaults as $k => $v) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO perizinan_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$k, $v]);
    }

    // 3. Table: perizinan_print_log
    $sql3 = "CREATE TABLE IF NOT EXISTS perizinan_print_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        perizinan_id INT NOT NULL,
        printed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        printed_by INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql3);

    echo "Setup Database Perizinan Berhasil!";

} catch (PDOException $e) {
    die("Error Setup DB: " . $e->getMessage());
}
