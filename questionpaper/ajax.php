<?php
/**
 * Lightweight AJAX endpoint for the chained selects
 * (course -> subcourse -> subject).
 */
require_once __DIR__ . '/includes/functions.php';
require_login();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($action === 'subcourses' && $id > 0) {
    $st = db()->prepare('SELECT id, name FROM subcourses WHERE course_id = ? ORDER BY name');
    $st->execute([$id]);
    echo json_encode(['ok' => true, 'data' => $st->fetchAll()]);
    exit;
}

if ($action === 'subjects' && $id > 0) {
    $st = db()->prepare('SELECT id, name FROM subjects WHERE subcourse_id = ? ORDER BY name');
    $st->execute([$id]);
    echo json_encode(['ok' => true, 'data' => $st->fetchAll()]);
    exit;
}

echo json_encode(['ok' => false, 'data' => []]);
exit;