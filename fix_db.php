<?php
// fix_db.php (Update for Users Role)
require_once 'api/config.php';

try {
    echo "<h1>Memperbaiki Tabel Users...</h1>";

    // Tambah kolom role jika belum ada
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'security'");
        echo "✅ Kolom 'role' berhasil ditambahkan ke tabel users.<br>";
    } catch (PDOException $e) {
        echo "ℹ️ Kolom 'role' mungkin sudah ada (Error: " . $e->getMessage() . ")<br>";
    }

    echo "<h2 style='color:green'>Database User Siap!</h2>";
    echo "<p>Silakan tutup halaman ini dan coba tambah petugas lagi.</p>";
    echo "<a href='users.html'>Kembali ke Menu Petugas</a>";

} catch (Exception $e) {
    echo "<h1 style='color:red'>Fatal Error</h1>";
    echo $e->getMessage();
}
