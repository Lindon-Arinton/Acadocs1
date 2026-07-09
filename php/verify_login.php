<?php
require_once __DIR__ . '/config.php';
$db = getDB();
$checks = [
    ['principal@school.edu', 'admin123'],
    ['maria.santos@school.edu', 'teacher123'],
    ['secretary@school.edu', 'sec123'],
    ['canteen@school.edu', 'canteen123'],
    ['disbursing@school.edu', 'disb123'],
    ['adas@school.edu', 'adas123'],
];
foreach ($checks as [$email, $password]) {
    $stmt = $db->prepare('SELECT email, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo $email . ': missing' . PHP_EOL;
        continue;
    }
    echo $email . ':' . (password_verify($password, $user['password']) ? 'match' : 'no') . PHP_EOL;
}
