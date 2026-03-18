<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

$product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$_GET['id']]);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Format product data
$productData = [
    'id' => $product['id'],
    'name' => htmlspecialchars($product['name']),
    'description' => nl2br(htmlspecialchars($product['description'])),
    'category' => htmlspecialchars($product['category']),
    'image' => UPLOAD_URL . ($product['image_path'] ?: 'products/default-product.jpg')
];

echo json_encode(['success' => true, 'product' => $productData]);
?>