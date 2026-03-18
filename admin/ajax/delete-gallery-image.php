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
if (!isset($_POST['project_id']) || !isset($_POST['image_path'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$projectId = (int)$_POST['project_id'];
$imagePath = $_POST['image_path'];

// Validate project ID
if ($projectId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit;
}

// Get current gallery
$project = $db->fetchOne("SELECT gallery FROM projects WHERE id = ?", [$projectId]);
if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}

if (!$project['gallery']) {
    echo json_encode(['success' => false, 'message' => 'No gallery found']);
    exit;
}

$gallery = json_decode($project['gallery'], true);
if (!is_array($gallery)) {
    echo json_encode(['success' => false, 'message' => 'Invalid gallery data']);
    exit;
}

// Remove image from array
$gallery = array_values(array_diff($gallery, [$imagePath]));

// Delete physical file
$filePath = UPLOAD_PATH . $imagePath;
if (file_exists($filePath)) {
    if (!unlink($filePath)) {
        // Log error but continue with database update
        error_log("Failed to delete file: " . $filePath);
    }
}

// Update database
$newGallery = !empty($gallery) ? json_encode($gallery) : null;
$db->update('projects', ['gallery' => $newGallery], "id = ?", [$projectId]);

echo json_encode(['success' => true]);
?>