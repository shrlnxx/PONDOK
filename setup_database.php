<?php
// setup_database.php
// AUTO-INSTALLER: Membuat Database & Tabel Otomatis
// Jalankan file ini di browser: http://pondok.test/setup_database.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Konfigurasi Database (Sesuaikan dengan Laragon/cPanel)
$host = 'localhost';
$user = 'ponw6793_keamanan';     // Production: ponw6793_keamanan
$pass = 'NgwIvUQIbK$C5mYA';      // Production password
$dbname = 'ponw6793_keamanan';

echo "<h1>Setup Database Otomatis</h1>";
echo "<pre style='background:#f4f4f4; padding:15px; border:1px solid #ddd; border-radius:5px;'>";

try {
    // 2. Koneksi ke Server MySQL (Tanpa memilih database dulu)
    echo "1. Menghubungkan ke MySQL Server... ";
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<span style='color:green'>BERHASIL</span>\n";

    // 3. Buat Database Otomatis
    echo "2. Membuat Database '$dbname'... ";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "<span style='color:green'>OK (Database Siap)</span>\n";

    // 4. Pilih Database yang baru dibuat
    $pdo->exec("USE `$dbname`");

    // 5. Buat Tabel Users
    echo "3. Membuat tabel 'users'... ";
    $sqlUsers = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'security', 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlUsers);
    echo "<span style='color:green'>OK</span>\n";

    // 6. Buat Tabel Santri
    echo "4. Membuat tabel 'santri'... ";
    $sqlSantri = "CREATE TABLE IF NOT EXISTS santri (
        nis VARCHAR(20) PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        kelas VARCHAR(50),
        asrama VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlSantri);
    echo "<span style='color:green'>OK</span>\n";

    // 7. Buat Tabel Pelanggaran
    echo "5. Membuat tabel 'pelanggaran'... ";
    $sqlPelanggaran = "CREATE TABLE IF NOT EXISTS pelanggaran (
        id INT AUTO_INCREMENT PRIMARY KEY,
        santri_nis VARCHAR(20) NOT NULL,
        tanggal_kejadian DATE NOT NULL,
        jenis_pelanggaran VARCHAR(255) NOT NULL,
        kategori VARCHAR(50) DEFAULT 'Ringan',
        keterangan TEXT,
        foto_bukti VARCHAR(255) NULL,
        pencatat_id INT,
        penangan_id INT,
        status_penanganan VARCHAR(50) DEFAULT 'Baru', 
        tindakan_diambil TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL
    )";
    // Note: Foreign key constraint bisa error kalau table terhapus sebagian, 
    // jadi kita buat table simpel dulu, atau tambahkan constraint terpisah jika perlu.
    // Tapi untuk setup awal ini aman.
    $pdo->exec($sqlPelanggaran);
    echo "<span style='color:green'>OK</span>\n";

    // 8. Buat Tabel Perizinan
    echo "6. Membuat tabel 'perizinan'... ";
    $sqlPerizinan = "CREATE TABLE IF NOT EXISTS perizinan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        santri_nis VARCHAR(20) NOT NULL,
        waktu_keluar DATETIME NOT NULL,
        tujuan_izin VARCHAR(255) NOT NULL,
        rencana_kembali DATETIME NOT NULL,
        waktu_kembali DATETIME NULL,
        status VARCHAR(50) DEFAULT 'Keluar',
        keterangan TEXT,
        petugas_keluar_id INT,
        petugas_kembali_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlPerizinan);
    echo "<span style='color:green'>OK</span>\n";

    // 9. Buat Tabel Perizinan Settings
    echo "7. Membuat tabel 'perizinan_settings'... ";
    $sqlSettings = "CREATE TABLE IF NOT EXISTS perizinan_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($sqlSettings);
    echo "<span style='color:green'>OK</span>\n";

    // 10. Insert Default Settings
    echo "8. Menambahkan Default Settings... ";
    $defaults = [
        'default_duration_hours' => '3',
        'tolerance_minutes' => '15',
        'max_night_hour' => '17:00'
    ];
    foreach ($defaults as $k => $v) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO perizinan_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$k, $v]);
    }
    echo "<span style='color:green'>OK</span>\n";

    // 11. Buat Tabel Perizinan Print Log
    echo "9. Membuat tabel 'perizinan_print_log'... ";
    $sqlPrintLog = "CREATE TABLE IF NOT EXISTS perizinan_print_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        perizinan_id INT NOT NULL,
        printed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        printed_by INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($sqlPrintLog);
    echo "<span style='color:green'>OK</span>\n";

    // 12. Buat User Admin Default
    echo "10. Membuat User Admin Default... ";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $passHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
        $stmt->execute([$passHash]);
        echo "<span style='color:blue'>User Dibuat: admin / admin123</span>\n";
    } else {
        echo "<span style='color:orange'>User sudah ada (Skip)</span>\n";
    }

    echo "\n---------------------------------------------------\n";
    echo "<b style='color:green'>🎉 INSTALASI SELESAI! SEMUA DIBUAT OTOMATIS.</b>\n";
    echo "---------------------------------------------------\n";
    echo "</pre>";
    echo "<br><a href='index.html' style='font-size:20px; font-weight:bold; background:blue; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>MASUK KE APLIKASI >></a>";

} catch (PDOException $e) {
    echo "<span style='color:red'>GAGAL!</span>\n";
    echo "\n❌ <b>Error:</b> " . $e->getMessage();
    echo "\n\n<b style='color:red'>SOLUSI:</b>";
    echo "\n1. Pastikan Laragon 'Start All' sudah diklik.";
    echo "\n2. Pastikan password root di Laragon memang kosong (default).";
    echo "</pre>";
}
?>
