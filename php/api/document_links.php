<?php
require_once __DIR__ . '/../config.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    $category = $_GET['category'] ?? null;
    if ($category) {
        $stmt = $db->prepare('SELECT * FROM document_links WHERE category=? ORDER BY date_added DESC');
        $stmt->execute([$category]);
    } else {
        $stmt = $db->query('SELECT * FROM document_links ORDER BY date_added DESC');
    }
    jsonResponse($stmt->fetchAll());
}

if ($method === 'POST') {
    $b = json_decode(file_get_contents('php://input'), true);
    $db->prepare(
        'INSERT INTO document_links (category, title, description, url, added_by, date_added, access_level) VALUES (?,?,?,?,?,?,?)'
    )->execute([
        $b['category']     ?? 'Forms',
        $b['title']        ?? '',
        $b['description']  ?? '',
        $b['url']          ?? '',
        $b['added_by']     ?? '',
        $b['date_added']   ?? date('Y-m-d'),
        $b['access_level'] ?? 'All Users',
    ]);
    jsonResponse(['id' => $db->lastInsertId(), 'message' => 'Created.'], 201);
}

if ($method === 'DELETE' && $id) {
    $db->prepare('DELETE FROM document_links WHERE id=?')->execute([$id]);
    jsonResponse(['message' => 'Deleted.']);
}

jsonError('Method not allowed.', 405);
