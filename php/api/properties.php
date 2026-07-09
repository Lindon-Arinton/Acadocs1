<?php
require_once __DIR__ . '/../config.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    $building = $_GET['building'] ?? null;
    $room     = $_GET['room']     ?? null;

    $where  = [];
    $params = [];
    if ($building) { $where[] = 'building_name = ?'; $params[] = $building; }
    if ($room)     { $where[] = 'room_number = ?';    $params[] = $room; }
    $clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("SELECT * FROM room_properties $clause ORDER BY building_name, room_number, item_name");
    $stmt->execute($params);
    jsonResponse($stmt->fetchAll());
}

if ($method === 'POST') {
    $b = json_decode(file_get_contents('php://input'), true);
    $db->prepare(
        'INSERT INTO room_properties (room_number, building_name, item_name, quantity, condition_status, last_inspection, remarks) VALUES (?,?,?,?,?,?,?)'
    )->execute([
        $b['room_number']     ?? '',
        $b['building_name']   ?? '',
        $b['item_name']       ?? '',
        $b['quantity']        ?? 1,
        $b['condition_status'] ?? 'Good',
        $b['last_inspection'] ?? date('Y-m-d'),
        $b['remarks']         ?? '',
    ]);
    jsonResponse(['id' => $db->lastInsertId(), 'message' => 'Created.'], 201);
}

if ($method === 'PUT' && $id) {
    $b = json_decode(file_get_contents('php://input'), true);
    $db->prepare(
        'UPDATE room_properties SET quantity=?, condition_status=?, last_inspection=?, remarks=? WHERE id=?'
    )->execute([$b['quantity'], $b['condition_status'], $b['last_inspection'], $b['remarks'] ?? '', $id]);
    jsonResponse(['message' => 'Updated.']);
}

if ($method === 'DELETE' && $id) {
    $db->prepare('DELETE FROM room_properties WHERE id=?')->execute([$id]);
    jsonResponse(['message' => 'Deleted.']);
}

jsonError('Method not allowed.', 405);
