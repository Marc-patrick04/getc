<?php
function uploadFile($file, $folder, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'mp4']) {
    $targetDir = UPLOAD_PATH . $folder . '/';
    
    // Create directory if not exists
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = time() . '_' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if file type is allowed
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Check file size (max 50MB)
    if ($file['size'] > 50000000) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'path' => $folder . '/' . $fileName];
    } else {
        return ['success' => false, 'message' => 'Error uploading file'];
    }
}

function getSetting($key) {
    global $db;
    $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = :key", ['key' => $key]);
    return $result ? $result['setting_value'] : null;
}

function updateSetting($key, $value) {
    global $db;
    return $db->update('settings', ['setting_value' => $value], "setting_key = :key", ['key' => $key]);
}

function getHeroes() {
    global $db;
    return $db->fetchAll("SELECT * FROM heroes WHERE is_active = true ORDER BY display_order");
}

function getProducts($limit = null) {
    global $db;
    $sql = "SELECT * FROM products ORDER BY display_order";
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    return $db->fetchAll($sql);
}

function getServices($limit = null) {
    global $db;
    $sql = "SELECT * FROM services ORDER BY display_order";
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    return $db->fetchAll($sql);
}

function getProjects($limit = null) {
    global $db;
    $sql = "SELECT * FROM projects ORDER BY display_order";
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    return $db->fetchAll($sql);
}

function getTeamMembers($limit = null) {
    global $db;
    $sql = "SELECT * FROM team_members ORDER BY display_order";
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    return $db->fetchAll($sql);
}

function getVideos($limit = null) {
    global $db;
    $sql = "SELECT * FROM videos ORDER BY display_order";
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    return $db->fetchAll($sql);
}

function getFeedback($limit = null) {
    global $db;
    $sql = "SELECT * FROM feedback WHERE is_approved = true ORDER BY created_at DESC";
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    return $db->fetchAll($sql);
}

function getAboutContent($section) {
    global $db;
    return $db->fetchOne("SELECT * FROM about_content WHERE section = :section", ['section' => $section]);
}

function truncateText($text, $limit = 100) {
    if (strlen($text) > $limit) {
        return substr($text, 0, $limit) . '...';
    }
    return $text;
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}
?>