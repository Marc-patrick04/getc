<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit;
}

$project = $db->fetchOne("SELECT * FROM projects WHERE id = ?", [$_GET['id']]);

if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}

// Format project data
$projectData = [
    'id' => $project['id'],
    'title' => htmlspecialchars($project['title']),
    'description' => nl2br(htmlspecialchars($project['description'])),
    'category' => htmlspecialchars($project['category']),
    'client_name' => htmlspecialchars($project['client_name']),
    'completion_date' => $project['completion_date'] ? formatDate($project['completion_date'], 'F Y') : 'N/A',
    'status' => $project['status'] ?? 'completed',
    'image' => UPLOAD_URL . ($project['image_path'] ?: 'projects/default-project.jpg')
];

echo json_encode(['success' => true, 'project' => $projectData]);
?>