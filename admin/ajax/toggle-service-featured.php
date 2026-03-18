<?php
require_once '../../includes/auth.php';
$auth = new Auth();
$auth->requireLogin();

require_once '../../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !isset($_POST['featured'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = (int)$_POST['id'];
$featured = (int)$_POST['featured'];

try {
    $db->update('services', ['is_featured' => $featured], "id = ?", [$id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>