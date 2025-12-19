<?php
// emergency_fix.php
require_once 'api/config.php';

echo "<h1>Perbaikan Darurat Akun</h1>";

try {
    // 1. Pastikan kolom role ada
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'admin'");
        echo "✅ Kolom Role ditambahkan.<br>";
    } catch (Exception $e) {
        echo "ℹ️ Kolom Role sudah ada.<br>";
    }

    // 2. Reset Password admin
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    // Cek apakah user admin ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetch()) {
        // Update
        $stmt = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE username = 'admin'");
        $stmt->execute([$pass]);
        echo "✅ Password user 'admin' di-reset jadi 'admin123'.<br>";
    } else {
        // Create
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
        $stmt->execute([$pass]);
        echo "✅ User 'admin' dibuat baru dengan password 'admin123'.<br>";
    }

    echo "<h2 style='color:green'>Selesai. Coba login sekarang.</h2>";
    echo "Username: <b>admin</b><br>";
    echo "Password: <b>admin123</b><br>";
    echo "<br><a href='index.html'>Ke Login Page</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
