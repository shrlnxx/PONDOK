<?php
require_once 'api/config.php';

echo "<h2>Check Tables</h2>";
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(", ", $tables) . "<br>";

    if (in_array('perizinan', $tables)) {
        echo "<h3>Structure 'perizinan'</h3>";
        $cols = $pdo->query("DESCRIBE perizinan")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($cols, true) . "</pre>";
    } else {
        echo "<h3 style='color:red'>Table 'perizinan' DOES NOT EXIST!</h3>";
    }

    echo "<h3>Check User Session</h3>";
    session_start();
    echo "User ID: " . ($_SESSION['user_id'] ?? 'Not Set') . "<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
