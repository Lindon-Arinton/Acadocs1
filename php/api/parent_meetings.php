<?php
require_once __DIR__ . '/../config.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    $rows = $db->query('SELECT * FROM parent_meetings ORDER BY date DESC')->fetchAll();
    jsonResponse($rows);
}

if ($method === 'POST') {
    $b = json_decode(file_get_contents('php://input'), true);
    $actual   = (int)($b['actual_attendance']  ?? 0);
    $expected = (int)($b['expected_parents']   ?? 1);
    $rate     = $expected > 0 ? round($actual / $expected * 100, 2) : 0;

    $db->prepare(
        'INSERT INTO parent_meetings (title, date, expected_parents, actual_attendance, attendance_rate) VALUES (?,?,?,?,?)'
    )->execute([$b['title'], $b['date'], $expected, $actual, $rate]);
    jsonResponse(['id' => $db->lastInsertId(), 'attendance_rate' => $rate, 'message' => 'Created.'], 201);
}

if ($method === 'PUT' && $id) {
    $b      = json_decode(file_get_contents('php://input'), true);
    $actual   = (int)($b['actual_attendance'] ?? 0);
    $expected = (int)($b['expected_parents']  ?? 1);
    $rate     = $expected > 0 ? round($actual / $expected * 100, 2) : 0;
    $db->prepare(
        'UPDATE parent_meetings SET title=?, date=?, expected_parents=?, actual_attendance=?, attendance_rate=? WHERE id=?'
    )->execute([$b['title'], $b['date'], $expected, $actual, $rate, $id]);
    jsonResponse(['message' => 'Updated.']);
}

if ($method === 'DELETE' && $id) {
    $db->prepare('DELETE FROM parent_meetings WHERE id=?')->execute([$id]);
    jsonResponse(['message' => 'Deleted.']);
}

jsonError('Method not allowed.', 405);
