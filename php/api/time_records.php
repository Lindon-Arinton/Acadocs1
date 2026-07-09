<?php
require_once __DIR__ . '/../config.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    $date = $_GET['date'] ?? null;
    if ($date) {
        $stmt = $db->prepare('SELECT * FROM time_records WHERE date = ? ORDER BY employee_name');
        $stmt->execute([$date]);
    } else {
        $stmt = $db->query('SELECT * FROM time_records ORDER BY date DESC, employee_name');
    }
    $rows = $stmt->fetchAll();

    // Attendance summary
    $summary = [
        'present' => 0, 'late' => 0, 'absent' => 0, 'on_leave' => 0,
    ];
    foreach ($rows as $r) {
        match ($r['status']) {
            'Present'  => $summary['present']++,
            'Late'     => $summary['late']++,
            'Absent'   => $summary['absent']++,
            'On Leave' => $summary['on_leave']++,
            default    => null,
        };
    }
    jsonResponse(['records' => $rows, 'summary' => $summary]);
}

if ($method === 'POST') {
    $b = json_decode(file_get_contents('php://input'), true);
    $db->prepare(
        'INSERT INTO time_records (date, employee_name, employee_id, time_in, time_out, status, remarks) VALUES (?,?,?,?,?,?,?)'
    )->execute([
        $b['date']          ?? date('Y-m-d'),
        $b['employee_name'] ?? '',
        $b['employee_id']   ?? '',
        $b['time_in']       ?? null,
        $b['time_out']      ?? null,
        $b['status']        ?? 'Present',
        $b['remarks']       ?? '',
    ]);
    jsonResponse(['id' => $db->lastInsertId(), 'message' => 'Created.'], 201);
}

if ($method === 'PUT' && $id) {
    $b = json_decode(file_get_contents('php://input'), true);
    $db->prepare(
        'UPDATE time_records SET time_in=?, time_out=?, status=?, remarks=? WHERE id=?'
    )->execute([$b['time_in'], $b['time_out'], $b['status'], $b['remarks'] ?? '', $id]);
    jsonResponse(['message' => 'Updated.']);
}

if ($method === 'DELETE' && $id) {
    $db->prepare('DELETE FROM time_records WHERE id=?')->execute([$id]);
    jsonResponse(['message' => 'Deleted.']);
}

jsonError('Method not allowed.', 405);
