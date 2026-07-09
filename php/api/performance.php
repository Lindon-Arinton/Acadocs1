<?php
require_once __DIR__ . '/../config.php';

$db          = getDB();
$method      = $_SERVER['REQUEST_METHOD'];
$school_year = $_GET['school_year'] ?? '2025-2026';

if ($method === 'GET') {
    $byLevel = $db->prepare(
        'SELECT * FROM performance_by_level WHERE school_year = ? ORDER BY grade_level'
    );
    $byLevel->execute([$school_year]);

    $bySubject = $db->prepare(
        'SELECT * FROM performance_by_subject WHERE school_year = ? ORDER BY mps DESC'
    );
    $bySubject->execute([$school_year]);

    $kpi = $db->prepare(
        'SELECT * FROM kpi_snapshots WHERE school_year = ? ORDER BY recorded_at DESC LIMIT 1'
    );
    $kpi->execute([$school_year]);

    $enrollment = $db->prepare(
        'SELECT * FROM enrollment_by_level WHERE school_year = ? ORDER BY grade_level'
    );
    $enrollment->execute([$school_year]);

    jsonResponse([
        'kpi'          => $kpi->fetch() ?: null,
        'by_level'     => $byLevel->fetchAll(),
        'by_subject'   => $bySubject->fetchAll(),
        'enrollment'   => $enrollment->fetchAll(),
    ]);
}

jsonError('Method not allowed.', 405);
