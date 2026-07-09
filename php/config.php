<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'acadocs');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/';
$baseDir = dirname(dirname(str_replace('\\', '/', $scriptPath)));
if ($baseDir === '.' || $baseDir === '\\' || $baseDir === '') {
    $baseDir = '/';
}
define('BASE_URL', rtrim($baseDir, '/') . '/');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user'])) {
        header('Location: ' . BASE_URL . 'login');
        exit;
    }
}

function currentUser(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['user'] ?? null;
}

function hasRole(string ...$roles): bool {
    $user = currentUser();
    return $user && in_array($user['role'], $roles, true);
}

function jsonResponse(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $status = 400): void {
    jsonResponse(['error' => $message], $status);
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

if (session_status() === PHP_SESSION_NONE) session_start();
