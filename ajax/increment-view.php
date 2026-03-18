<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['video_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$videoId = (int)$_POST['video_id'];

// Increment view count
$db->query("UPDATE videos SET views = COALESCE(views, 0) + 1 WHERE id = ?", [$videoId]);

echo json_encode(['success' => true]);
?>