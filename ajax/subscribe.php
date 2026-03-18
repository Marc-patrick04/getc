<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}

// Check if subscribers table exists, if not create it
try {
    $db->query("CREATE TABLE IF NOT EXISTS subscribers (
        id SERIAL PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert subscriber
    $db->insert('subscribers', ['email' => $email]);
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    // Check if it's a duplicate entry error
    if ($e->getCode() == 23505) { // Unique violation
        echo json_encode(['success' => false, 'message' => 'Email already subscribed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>