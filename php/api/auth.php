<?php
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($method === 'POST' && $action === 'login') {
    $b = json_decode(file_get_contents('php://input'), true);
    $email    = trim($b['email']    ?? '');
    $password = trim($b['password'] ?? '');
    if (!$email || !$password) jsonError('Email and password required.');

    $stmt = getDB()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) jsonError('Invalid credentials.', 401);

    unset($user['password']);
    $_SESSION['user'] = $user;
    jsonResponse(['message' => 'OK', 'user' => $user]);
}

if ($method === 'POST' && $action === 'logout') {
    session_destroy();
    jsonResponse(['message' => 'Logged out.']);
}

if ($method === 'GET' && $action === 'me') {
    $user = currentUser();
    $user ? jsonResponse($user) : jsonError('Unauthenticated.', 401);
}

jsonError('Not found.', 404);
