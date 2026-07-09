<?php
require_once __DIR__ . '/../config.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$type   = $_GET['type'] ?? 'canteen';   // canteen | funds

// ── Canteen Records ───────────────────────────────────────────
if ($type === 'canteen') {
    if ($method === 'GET') {
        $rows = $db->query('SELECT * FROM canteen_records ORDER BY date DESC')->fetchAll();
        $summary = $db->query(
            'SELECT SUM(revenue) AS total_revenue, SUM(expenses) AS total_expenses,
                    SUM(net_income) AS total_net_income, SUM(transaction_count) AS total_transactions
             FROM canteen_records'
        )->fetch();
        jsonResponse(['records' => $rows, 'summary' => $summary]);
    }

    if ($method === 'POST') {
        $b = json_decode(file_get_contents('php://input'), true);
        $db->prepare(
            'INSERT INTO canteen_records (date, description, revenue, expenses, transaction_count) VALUES (?,?,?,?,?)'
        )->execute([$b['date'], $b['description'], $b['revenue'], $b['expenses'], $b['transaction_count'] ?? 0]);
        jsonResponse(['id' => $db->lastInsertId(), 'message' => 'Created.'], 201);
    }

    if ($method === 'DELETE' && $id) {
        $db->prepare('DELETE FROM canteen_records WHERE id=?')->execute([$id]);
        jsonResponse(['message' => 'Deleted.']);
    }
}

// ── School Funds ──────────────────────────────────────────────
if ($type === 'funds') {
    if ($method === 'GET') {
        $rows = $db->query('SELECT * FROM school_funds ORDER BY date DESC')->fetchAll();
        $current = end($rows);
        jsonResponse(['records' => $rows, 'current_balance' => $current['balance'] ?? 0]);
    }

    if ($method === 'POST') {
        $b = json_decode(file_get_contents('php://input'), true);
        // Compute running balance from last record
        $last = $db->query('SELECT balance FROM school_funds ORDER BY date DESC LIMIT 1')->fetch();
        $balance = ($last['balance'] ?? 0) + (float)$b['amount'];
        $db->prepare(
            'INSERT INTO school_funds (date, category, description, particulars, amount, balance, prepared_by) VALUES (?,?,?,?,?,?,?)'
        )->execute([$b['date'], $b['category'], $b['description'], $b['particulars'] ?? '', $b['amount'], $balance, $b['prepared_by'] ?? '']);
        jsonResponse(['id' => $db->lastInsertId(), 'balance' => $balance, 'message' => 'Created.'], 201);
    }
}

jsonError('Method not allowed.', 405);
