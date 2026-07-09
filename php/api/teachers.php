<?php
require_once __DIR__ . '/../config.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// GET /api/teachers[?id=X]
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare(
            'SELECT t.*, GROUP_CONCAT(ts.subject ORDER BY ts.subject SEPARATOR ",") AS subjects
             FROM teachers t
             LEFT JOIN teacher_subjects ts ON ts.teacher_id = t.id
             WHERE t.id = ?
             GROUP BY t.id'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) jsonError('Not found.', 404);
        $row['subjects'] = $row['subjects'] ? explode(',', $row['subjects']) : [];
        jsonResponse($row);
    }

    $rows = $db->query(
        'SELECT t.*, GROUP_CONCAT(ts.subject ORDER BY ts.subject SEPARATOR ",") AS subjects
         FROM teachers t
         LEFT JOIN teacher_subjects ts ON ts.teacher_id = t.id
         GROUP BY t.id
         ORDER BY t.name'
    )->fetchAll();

    foreach ($rows as &$r) {
        $r['subjects'] = $r['subjects'] ? explode(',', $r['subjects']) : [];
    }
    jsonResponse($rows);
}

// POST /api/teachers
if ($method === 'POST') {
    $b = json_decode(file_get_contents('php://input'), true);
    $db->beginTransaction();
    $stmt = $db->prepare(
        'INSERT INTO teachers (employee_id, name, email, grade_level, submission_rate) VALUES (?,?,?,?,?)'
    );
    $stmt->execute([
        $b['employee_id']     ?? '',
        $b['name']            ?? '',
        $b['email']           ?? '',
        $b['grade_level']     ?? '',
        $b['submission_rate'] ?? 0,
    ]);
    $teacherId = $db->lastInsertId();

    foreach (($b['subjects'] ?? []) as $subject) {
        $db->prepare('INSERT INTO teacher_subjects (teacher_id, subject) VALUES (?,?)')->execute([$teacherId, $subject]);
    }
    $db->commit();
    jsonResponse(['id' => $teacherId, 'message' => 'Created.'], 201);
}

// PUT /api/teachers?id=X
if ($method === 'PUT' && $id) {
    $b = json_decode(file_get_contents('php://input'), true);
    $db->beginTransaction();
    $db->prepare(
        'UPDATE teachers SET employee_id=?, name=?, email=?, grade_level=?, submission_rate=? WHERE id=?'
    )->execute([$b['employee_id'], $b['name'], $b['email'], $b['grade_level'], $b['submission_rate'], $id]);

    $db->prepare('DELETE FROM teacher_subjects WHERE teacher_id=?')->execute([$id]);
    foreach (($b['subjects'] ?? []) as $subject) {
        $db->prepare('INSERT INTO teacher_subjects (teacher_id, subject) VALUES (?,?)')->execute([$id, $subject]);
    }
    $db->commit();
    jsonResponse(['message' => 'Updated.']);
}

// DELETE /api/teachers?id=X
if ($method === 'DELETE' && $id) {
    $db->prepare('DELETE FROM teachers WHERE id=?')->execute([$id]);
    jsonResponse(['message' => 'Deleted.']);
}

jsonError('Method not allowed.', 405);
