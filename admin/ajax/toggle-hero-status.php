<?php
require_once '../../includes/auth.php';
$auth = new Auth();
$auth->requireLogin();

require_once '../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = (int)$_POST['id'];
$status = (int)$_POST['status'];

try {
    $db->update('heroes', ['is_active' => $status], "id = ?", [$id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>