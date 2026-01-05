<?php
// fix_perizinan.php
// Emergency Fix untuk Modul Perizinan
// Akses: http://pondok.test/fix_perizinan.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Emergency Fix - Modul Perizinan</h1>";
echo "<pre style='background:#f4f4f4; padding:15px; border:1px solid #ddd; border-radius:5px;'>";

try {
    require_once 'api/config.php';

    echo "✅ Config loaded successfully\n\n";

    // 1. CHECK IF TABLES EXIST
    echo "=== STEP 1: Checking Database Tables ===\n";
    
    $tables_to_check = ['perizinan', 'perizinan_settings', 'perizinan_print_log', 'santri', 'users'];
    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' MISSING!\n";
        }
    }
    echo "\n";

    // 2. CREATE TABLES IF MISSING
    echo "=== STEP 2: Creating Missing Tables ===\n";
    
    // Create perizinan table
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql1);
    echo "✅ Table 'perizinan' created/verified\n";

    // Create perizinan_settings table
    $sql2 = "CREATE TABLE IF NOT EXISTS perizinan_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql2);
    echo "✅ Table 'perizinan_settings' created/verified\n";

    // Insert default settings
    $defaults = [
        'default_duration_hours' => '3',
        'tolerance_minutes' => '15',
        'max_night_hour' => '17:00'
    ];
    
    foreach ($defaults as $k => $v) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO perizinan_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$k, $v]);
    }
    echo "✅ Default settings inserted\n";

    // Create perizinan_print_log table
    $sql3 = "CREATE TABLE IF NOT EXISTS perizinan_print_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        perizinan_id INT NOT NULL,
        printed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        printed_by INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql3);
    echo "✅ Table 'perizinan_print_log' created/verified\n\n";

    // 2.5. FIX USERS TABLE (Add fullname if missing)
    echo "=== STEP 2.5: Checking Users Table Structure ===\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'fullname'");
    if ($stmt->rowCount() == 0) {
        echo "⚠️  Column 'fullname' not found in users table\n";
        echo "🔧 Adding 'fullname' column...\n";
        
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN fullname VARCHAR(100) DEFAULT NULL AFTER username");
            echo "✅ Column 'fullname' added successfully\n";
            
            // Update fullname from username as fallback
            $pdo->exec("UPDATE users SET fullname = username WHERE fullname IS NULL");
            echo "✅ Default fullname values set\n";
        } catch (PDOException $e) {
            echo "⚠️  Could not add fullname column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✅ Column 'fullname' exists in users table\n";
    }
    echo "\n";

    // 3. CHECK IF SANTRI TABLE HAS DATA
    echo "=== STEP 3: Checking Data ===\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM santri");
    $santriCount = $stmt->fetch()['cnt'];
    echo "📊 Jumlah Santri: $santriCount\n";
    
    if ($santriCount == 0) {
        echo "⚠️  WARNING: Tidak ada data santri! Silakan input data santri terlebih dahulu.\n";
    }

    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    $userCount = $stmt->fetch()['cnt'];
    echo "📊 Jumlah Users: $userCount\n";

    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM perizinan");
    $perizinanCount = $stmt->fetch()['cnt'];
    echo "📊 Jumlah Perizinan: $perizinanCount\n\n";

    // 4. TEST INSERT (DRY RUN)
    echo "=== STEP 4: Testing Table Structure ===\n";
    
    $stmt = $pdo->query("DESCRIBE perizinan");
    echo "Columns in 'perizinan' table:\n";
    while ($row = $stmt->fetch()) {
        echo "  - {$row['Field']} ({$row['Type']}) " . 
             ($row['Null'] == 'NO' ? '[REQUIRED]' : '[OPTIONAL]') . "\n";
    }
    echo "\n";

    // 5. CHECK API CONFIG
    echo "=== STEP 5: Checking API Configuration ===\n";
    
    if (file_exists('api/perizinan.php')) {
        echo "✅ File 'api/perizinan.php' exists\n";
    } else {
        echo "❌ File 'api/perizinan.php' NOT FOUND!\n";
    }

    if (file_exists('api/santri.php')) {
        echo "✅ File 'api/santri.php' exists\n";
    } else {
        echo "❌ File 'api/santri.php' NOT FOUND!\n";
    }
    
    // Check debug log
    if (file_exists('api/debug_log.txt')) {
        echo "✅ Debug log exists\n";
        echo "\n📝 Last 10 lines of debug log:\n";
        echo "---\n";
        $log = file_get_contents('api/debug_log.txt');
        $lines = explode("\n", $log);
        $last10 = array_slice($lines, -10);
        echo implode("\n", $last10);
        echo "\n---\n";
    } else {
        echo "⚠️  Debug log not found (will be created on first API call)\n";
    }

    echo "\n";
    echo "=== DIAGNOSIS COMPLETE ===\n\n";
    echo "🎉 Database struktur sudah diperbaiki!\n";
    echo "🔍 Jika masih error, cek debug_log.txt di folder api/\n";
    echo "\n";
    echo "📌 Next Steps:\n";
    echo "1. Refresh halaman perizinan\n";
    echo "2. Coba input izin lagi\n";
    echo "3. Jika masih error, kirim screenshot 'api/debug_log.txt'\n";

} catch (PDOException $e) {
    echo "\n❌ DATABASE ERROR:\n";
    echo $e->getMessage() . "\n\n";
    echo "SOLUSI:\n";
    echo "1. Pastikan MySQL sudah running (Laragon 'Start All')\n";
    echo "2. Pastikan database 'pelanggaran_db' sudah ada\n";
    echo "3. Jalankan setup_database.php terlebih dahulu\n";
} catch (Exception $e) {
    echo "\n❌ ERROR:\n";
    echo $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='perizinan.html' style='font-size:18px; font-weight:bold; background:blue; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>⬅️ KEMBALI KE PERIZINAN</a>";
?>
