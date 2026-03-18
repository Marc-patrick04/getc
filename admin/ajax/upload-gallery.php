<?php
require_once '../../includes/auth.php';
$auth = new Auth();
$auth->requireLogin();

require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if project_id is set
if (!isset($_POST['project_id'])) {
    echo json_encode(['success' => false, 'message' => 'Project ID missing']);
    exit;
}

$projectId = (int)$_POST['project_id'];

// Validate project ID
if ($projectId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit;
}

// Check if files were uploaded
if (!isset($_FILES['gallery'])) {
    echo json_encode(['success' => false, 'message' => 'No files uploaded']);
    exit;
}

// Get current gallery
$project = $db->fetchOne("SELECT gallery FROM projects WHERE id = ?", [$projectId]);
$currentGallery = [];
if ($project && $project['gallery']) {
    $currentGallery = json_decode($project['gallery'], true);
    if (!is_array($currentGallery)) {
        $currentGallery = [];
    }
}

$uploadedImages = [];
$files = $_FILES['gallery'];

// Handle multiple file uploads
if (is_array($files['name'])) {
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === 0) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $upload = uploadFile($file, 'projects/gallery', ['jpg', 'jpeg', 'png', 'gif']);
            if ($upload['success']) {
                $uploadedImages[] = $upload['path'];
            }
        }
    }
} else {
    // Single file upload
    if ($files['error'] === 0) {
        $upload = uploadFile($files, 'projects/gallery', ['jpg', 'jpeg', 'png', 'gif']);
        if ($upload['success']) {
            $uploadedImages[] = $upload['path'];
        }
    }
}

if (!empty($uploadedImages)) {
    $newGallery = array_merge($currentGallery, $uploadedImages);
    $db->update('projects', ['gallery' => json_encode($newGallery)], "id = ?", [$projectId]);
    echo json_encode(['success' => true, 'images' => $uploadedImages]);
} else {
    echo json_encode(['success' => false, 'message' => 'No images were uploaded successfully']);
}
?>