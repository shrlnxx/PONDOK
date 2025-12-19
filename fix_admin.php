<?php
// fix_admin.php
require_once 'api/config.php';

try {
    echo "<h1>Set Admin Awal</h1>";

    // Ubah semua user jadi admin agar Anda tidak terkunci
    $pdo->exec("UPDATE users SET role = 'admin'");

    echo "✅ Semua user sekarang adalah ADMIN.<br>";
    echo "Sekarang Anda punya akses penuh ke menu Petugas.<br>";
    echo "Silakan login ulang untuk memperbarui sesi.<br>";
    echo "<br><a href='index.html'>Login Ulang</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
