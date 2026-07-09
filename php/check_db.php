<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/config.php';
try {
    $db = getDB();
    echo "connected\n";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "tables: " . implode(', ', $tables) . "\n";
    $stmt = $db->query("SHOW COLUMNS FROM users");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "users columns: " . implode(', ', $cols) . "\n";

    $stmt = $db->prepare('SELECT email, password, role FROM users WHERE email = ?');
    $stmt->execute(['principal@school.edu']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    var_export($user);
    echo "\n";
    if ($user) {
        echo password_verify('admin123', $user['password']) ? "VERIFY_OK\n" : "VERIFY_FAIL\n";
    }
} catch (Exception $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}
