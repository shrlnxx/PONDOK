<?php
// reset_user.php
require_once 'api/config.php';

echo "<h1>Reset Paksa Admin (Safe Mode)</h1>";

try {
    // 1. Pastikan kolom role ada dulu (Anti-Gagal)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'security'");
        echo "✅ Cek Kolom Role: OK.<br>";
    } catch (Exception $e) {
        // Ignore if exists, berarti sudah ada
    }

    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $role = 'admin';

    // 2. Cek apakah user admin sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // UPDATE user yang sudah ada (Aman dari error Foreign Key)
        $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ? WHERE username = ?");
        $stmt->execute([$password, $role, $username]);
        echo "✅ User 'admin' ditemukan. Password berhasil di-reset menjadi 'admin123'.<br>";
    } else {
        // INSERT user baru jika belum ada
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $password, $role]);
        echo "✅ User 'admin' belum ada. Berhasil dibuat baru.<br>";
    }

    echo "<hr>";
    echo "Username: <b>admin</b><br>";
    echo "Password: <b>admin123</b><br>";
    echo "<hr>";
    echo "<h3><a href='index.html'>>> LOGIN SEKARANG <<</a></h3>";

} catch (Exception $e) {
    echo "<h1>Error :(</h1>";
    echo "Gagal: " . $e->getMessage();
}
