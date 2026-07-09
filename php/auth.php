<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path   = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$action = basename($path);   // login | logout | me

// ── POST /auth/login ──────────────────────────────────────────
if ($method === 'POST' && $action === 'login') {
    $body = json_decode(file_get_contents('php://input'), true);
    $email    = trim($body['email']    ?? '');
    $password = trim($body['password'] ?? '');

    if (!$email || !$password) {
        jsonError('Email and password are required.');
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonError('Invalid email or password.', 401);
    }

    $_SESSION['user_id'] = $user['id'];
    unset($user['password']);
    jsonResponse(['message' => 'Login successful.', 'user' => $user]);
}

// ── POST /auth/logout ─────────────────────────────────────────
if ($method === 'POST' && $action === 'logout') {
    session_destroy();
    jsonResponse(['message' => 'Logged out.']);
}

// ── GET /auth/me ──────────────────────────────────────────────
if ($method === 'GET' && $action === 'me') {
    if (empty($_SESSION['user_id'])) {
        jsonError('Unauthenticated.', 401);
    }
    $db   = getDB();
    $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonError('User not found.', 404);
    }
    jsonResponse($user);
}

jsonError('Route not found.', 404);
