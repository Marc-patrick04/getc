<?php
require_once '../../includes/auth.php';
$auth = new Auth();
$auth->requireLogin();

require_once '../../includes/db.php';

header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if required parameters are set
if (!isset($_POST['id']) || !isset($_POST['featured'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$id = (int)$_POST['id'];
$featured = (int)$_POST['featured'];

// Validate ID
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit;
}

try {
    // Update the featured status
    $result = $db->update('projects', ['is_featured' => $featured], "id = ?", [$id]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>