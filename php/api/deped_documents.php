<?php
require_once __DIR__ . '/../config.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare('SELECT * FROM deped_documents WHERE id=?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $row ? jsonResponse($row) : jsonError('Not found.', 404);
    }
    $rows = $db->query('SELECT * FROM deped_documents ORDER BY due_date')->fetchAll();
    jsonResponse($rows);
}

if ($method === 'POST') {
    $b = json_decode(file_get_contents('php://input'), true);
    $db->prepare(
        'INSERT INTO deped_documents (document_type, description, due_date, status, completion_rate, prepared_by, last_updated) VALUES (?,?,?,?,?,?,?)'
    )->execute([
        $b['document_type']   ?? '',
        $b['description']     ?? '',
        $b['due_date']        ?? date('Y-m-d'),
        $b['status']          ?? 'Pending',
        $b['completion_rate'] ?? 0,
        $b['prepared_by']     ?? '',
        $b['last_updated']    ?? date('Y-m-d'),
    ]);
    jsonResponse(['id' => $db->lastInsertId(), 'message' => 'Created.'], 201);
}

if ($method === 'PUT' && $id) {
    $b = json_decode(file_get_contents('php://input'), true);
    $db->prepare(
        'UPDATE deped_documents SET status=?, completion_rate=?, last_updated=? WHERE id=?'
    )->execute([$b['status'], $b['completion_rate'], date('Y-m-d'), $id]);
    jsonResponse(['message' => 'Updated.']);
}

if ($method === 'DELETE' && $id) {
    $db->prepare('DELETE FROM deped_documents WHERE id=?')->execute([$id]);
    jsonResponse(['message' => 'Deleted.']);
}

jsonError('Method not allowed.', 405);
