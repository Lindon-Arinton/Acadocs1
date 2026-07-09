<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/config.php';
$db = getDB();
$updates = [
    'principal@school.edu' => 'admin123',
    'maria.santos@school.edu' => 'teacher123',
    'juan.delacruz@school.edu' => 'teacher123',
    'secretary@school.edu' => 'sec123',
    'canteen@school.edu' => 'canteen123',
    'disbursing@school.edu' => 'disb123',
    'adas@school.edu' => 'adas123',
];
foreach ($updates as $email => $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('UPDATE users SET password = ? WHERE email = ?');
    $stmt->execute([$hash, $email]);
    echo "Updated $email\n";
}

$stmt = $db->query("SELECT email, password FROM users WHERE email IN ('principal@school.edu','maria.santos@school.edu','juan.delacruz@school.edu','secretary@school.edu','canteen@school.edu','disbursing@school.edu','adas@school.edu')");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
