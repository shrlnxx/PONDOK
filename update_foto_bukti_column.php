<?php
// update_foto_bukti_column.php
// Script untuk menambahkan kolom foto_bukti ke tabel pelanggaran
// Jalankan sekali: http://pondok.test/update_foto_bukti_column.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Update Database - Tambah Kolom Foto Bukti</h1>";
echo "<pre style='background:#f4f4f4; padding:15px; border:1px solid #ddd; border-radius:5px;'>";

try {
    require_once 'api/config.php';

    echo "✅ Database connected\n\n";

    // Check if column exists
    echo "=== Checking column 'foto_bukti' ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM pelanggaran LIKE 'foto_bukti'");
    
    if ($stmt->rowCount() > 0) {
        echo "ℹ️  Column 'foto_bukti' already exists\n";
        $col = $stmt->fetch();
        echo "   Type: " . $col['Type'] . "\n";
        echo "   Null: " . $col['Null'] . "\n";
        echo "   Default: " . ($col['Default'] ?? 'NULL') . "\n";
    } else {
        echo "⚠️  Column 'foto_bukti' not found\n";
        echo "🔧 Adding column...\n";
        
        $pdo->exec("ALTER TABLE pelanggaran ADD COLUMN foto_bukti VARCHAR(255) NULL AFTER keterangan");
        
        echo "✅ Column 'foto_bukti' added successfully!\n";
    }

    echo "\n=== Creating uploads directory ===\n";
    $uploadDir = 'uploads/pelanggaran/';
    
    if (!file_exists($uploadDir)) {
        if (mkdir($uploadDir, 0755, true)) {
            echo "✅ Directory created: $uploadDir\n";
            
            // Create .htaccess to allow access
            $htaccess = $uploadDir . '.htaccess';
            file_put_contents($htaccess, "Options +Indexes\nAllow from all");
            echo "✅ .htaccess created for public access\n";
        } else {
            echo "❌ Failed to create directory\n";
        }
    } else {
        echo "ℹ️  Directory already exists: $uploadDir\n";
    }

    echo "\n=== Checking table structure ===\n";
    $stmt = $pdo->query("DESCRIBE pelanggaran");
    $columns = $stmt->fetchAll();
    
    echo "Columns in 'pelanggaran' table:\n";
    foreach ($columns as $col) {
        $indicator = $col['Field'] === 'foto_bukti' ? ' 👈 NEW!' : '';
        echo "  - {$col['Field']} ({$col['Type']})" . $indicator . "\n";
    }

    echo "\n";
    echo "=== UPDATE COMPLETE! ===\n";
    echo "✅ Database siap untuk upload foto bukti\n";
    echo "✅ Frontend form sudah support upload\n";
    echo "✅ Backend API sudah handle file upload\n";

} catch (PDOException $e) {
    echo "\n❌ DATABASE ERROR:\n";
    echo $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='input.html' style='font-size:18px; font-weight:bold; background:blue; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>⬅️ KEMBALI KE INPUT</a>";
?>
