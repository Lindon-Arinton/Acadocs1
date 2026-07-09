<?php
require_once __DIR__ . '/../config.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// GET /api/documents[?id=X&teacher_id=Y]
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare(
            'SELECT d.*, t.name AS teacher_name FROM documents d
             JOIN teachers t ON t.id = d.teacher_id WHERE d.id = ?'
        );
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        if (!$doc) jsonError('Not found.', 404);

        $fstmt = $db->prepare('SELECT * FROM document_feedback WHERE document_id = ? ORDER BY date');
        $fstmt->execute([$id]);
        $doc['feedback'] = $fstmt->fetchAll();
        jsonResponse($doc);
    }

    $where  = '';
    $params = [];
    if (!empty($_GET['teacher_id'])) {
        $where    = 'WHERE d.teacher_id = ?';
        $params[] = (int)$_GET['teacher_id'];
    }

    $rows = $db->prepare(
        "SELECT d.*, t.name AS teacher_name FROM documents d
         JOIN teachers t ON t.id = d.teacher_id $where ORDER BY d.date_submitted DESC"
    );
    $rows->execute($params);
    jsonResponse($rows->fetchAll());
}

// POST /api/documents
if ($method === 'POST') {
    $b = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare(
        'INSERT INTO documents (teacher_id, type, subject, grade_level, date_submitted, status) VALUES (?,?,?,?,?,?)'
    );
    $stmt->execute([
        $b['teacher_id']     ?? 0,
        $b['type']           ?? 'DLL',
        $b['subject']        ?? '',
        $b['grade_level']    ?? '',
        $b['date_submitted'] ?? date('Y-m-d H:i:s'),
        $b['status']         ?? 'Submitted',
    ]);
    jsonResponse(['id' => $db->lastInsertId(), 'message' => 'Created.'], 201);
}

// POST /api/documents/feedback?id=X (add feedback to a document)
if ($method === 'POST' && $id && str_contains($_SERVER['REQUEST_URI'], '/feedback')) {
    $b = json_decode(file_get_contents('php://input'), true);
    $stmt = $db->prepare(
        'INSERT INTO document_feedback (document_id, author, comment, date) VALUES (?,?,?,?)'
    );
    $stmt->execute([$id, $b['author'] ?? '', $b['comment'] ?? '', $b['date'] ?? date('Y-m-d')]);

    $db->prepare("UPDATE documents SET status='Reviewed' WHERE id=?")->execute([$id]);
    jsonResponse(['id' => $db->lastInsertId(), 'message' => 'Feedback added.'], 201);
}

// PUT /api/documents?id=X
if ($method === 'PUT' && $id) {
    $b = json_decode(file_get_contents('php://input'), true);
    $db->prepare(
        'UPDATE documents SET type=?, subject=?, grade_level=?, status=? WHERE id=?'
    )->execute([$b['type'], $b['subject'], $b['grade_level'], $b['status'], $id]);
    jsonResponse(['message' => 'Updated.']);
}

// DELETE /api/documents?id=X
if ($method === 'DELETE' && $id) {
    $db->prepare('DELETE FROM documents WHERE id=?')->execute([$id]);
    jsonResponse(['message' => 'Deleted.']);
}

jsonError('Method not allowed.', 405);
