<?php
require_once 'api/config.php';

try {
    echo "<h1>Memperbaiki Kolom Status...</h1>";

    // Ubah status_penanganan menjadi VARCHAR agar fleksibel (tidak kaku seperti ENUM)
    try {
        $pdo->exec("ALTER TABLE pelanggaran MODIFY COLUMN status_penanganan VARCHAR(50) DEFAULT 'Belum Ditangani'");
        echo "✅ Kolom 'status_penanganan' berhasil diubah menjadi VARCHAR.<br>";

        // Update data 'Belum' yang kosong atau error menjadi 'Belum Ditangani'
        $pdo->exec("UPDATE pelanggaran SET status_penanganan = 'Belum Ditangani' WHERE status_penanganan = '' OR status_penanganan IS NULL");
        echo "✅ Data status kosong diperbaiki menjadi 'Belum Ditangani'.<br>";

    } catch (PDOException $e) {
        echo "ℹ️ Error: " . $e->getMessage() . "<br>";
    }

    echo "<h2 style='color:green'>Selesai! Sekarang status pasti tersimpan.</h2>";
    echo "<p>Silakan kembali ke <a href='riwayat.html'>Halaman Riwayat</a> dan coba update lagi.</p>";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage();
}
