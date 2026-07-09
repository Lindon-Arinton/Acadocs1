<?php
require_once __DIR__ . '/../config.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// GET /api/announcements[?id=X]
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare('SELECT * FROM announcements WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $row ? jsonResponse($row) : jsonError('Not found.', 404);
    }
    $rows = $db->query('SELECT * FROM announcements ORDER BY date DESC')->fetchAll();
    jsonResponse($rows);
}

// POST /api/announcements
if ($method === 'POST') {
    $b = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare(
        'INSERT INTO announcements (type, title, content, date, status) VALUES (?,?,?,?,?)'
    );
    $stmt->execute([
        $b['type']    ?? 'Announcement',
        $b['title']   ?? '',
        $b['content'] ?? '',
        $b['date']    ?? date('Y-m-d'),
        $b['status']  ?? 'active',
    ]);
    jsonResponse(['id' => $db->lastInsertId(), 'message' => 'Created.'], 201);
}

// PUT /api/announcements?id=X
if ($method === 'PUT' && $id) {
    $b = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare(
        'UPDATE announcements SET type=?, title=?, content=?, date=?, status=? WHERE id=?'
    );
    $stmt->execute([
        $b['type'], $b['title'], $b['content'], $b['date'], $b['status'], $id
    ]);
    jsonResponse(['message' => 'Updated.']);
}

// DELETE /api/announcements?id=X
if ($method === 'DELETE' && $id) {
    $db->prepare('DELETE FROM announcements WHERE id=?')->execute([$id]);
    jsonResponse(['message' => 'Deleted.']);
}

jsonError('Method not allowed.', 405);
