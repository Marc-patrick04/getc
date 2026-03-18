<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->requireLogin();

require_once '../includes/db.php';
require_once '../includes/functions.php';

$message = '';
$error = '';

// Handle Add/Edit/Delete operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $data = [
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'category' => $_POST['category'] ?: null,
                    'duration' => $_POST['duration'] ?: null,
                    'display_order' => (int)$_POST['display_order']
                ];
                
                // Handle video file upload
                if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === 0) {
                    $upload = uploadFile($_FILES['video_file'], 'videos', ['mp4', 'webm', 'ogg', 'mov']);
                    if ($upload['success']) {
                        // Delete old video if editing
                        if ($_POST['action'] === 'edit' && !empty($_POST['current_video'])) {
                            $oldFile = UPLOAD_PATH . $_POST['current_video'];
                            if (file_exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }
                        $data['video_path'] = $upload['path'];
                    } else {
                        $error = $upload['message'];
                        break;
                    }
                }
                
                // Handle thumbnail upload
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
                    $upload = uploadFile($_FILES['thumbnail'], 'videos/thumbnails', ['jpg', 'jpeg', 'png', 'gif']);
                    if ($upload['success']) {
                        // Delete old thumbnail if editing
                        if ($_POST['action'] === 'edit' && !empty($_POST['current_thumbnail'])) {
                            $oldFile = UPLOAD_PATH . $_POST['current_thumbnail'];
                            if (file_exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }
                        $data['thumbnail_path'] = $upload['path'];
                    } else {
                        $error = $upload['message'];
                        break;
                    }
                }
                
                // Handle YouTube/Vimeo URL
                if (!empty($_POST['video_url'])) {
                    $data['video_url'] = $_POST['video_url'];
                    $data['video_path'] = null; // Clear file path if using URL
                    
                    // Auto-generate thumbnail from URL if not provided
                    if (empty($_FILES['thumbnail']['name'])) {
                        $thumbnail = generateVideoThumbnail($_POST['video_url']);
                        if ($thumbnail) {
                            $data['thumbnail_path'] = $thumbnail;
                        }
                    }
                }
                
                if ($_POST['action'] === 'add') {
                    // Ensure either video file or URL is provided
                    if (empty($data['video_path']) && empty($data['video_url'])) {
                        $error = 'Either video file or video URL is required';
                        break;
                    }
                    
                    $db->insert('videos', $data);
                    $message = 'Video added successfully!';
                } else {
                    $db->update('videos', $data, "id = :id", ['id' => $_POST['id']]);
                    $message = 'Video updated successfully!';
                }
                break;
                
            case 'delete':
                // Get file paths to delete
                $video = $db->fetchOne("SELECT video_path, thumbnail_path FROM videos WHERE id = :id", ['id' => $_POST['id']]);
                if ($video) {
                    // Delete video file
                    if ($video['video_path']) {
                        $filePath = UPLOAD_PATH . $video['video_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    
                    // Delete thumbnail
                    if ($video['thumbnail_path']) {
                        $filePath = UPLOAD_PATH . $video['thumbnail_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
                
                $db->delete('videos', "id = :id", ['id' => $_POST['id']]);
                $message = 'Video deleted successfully!';
                break;
                
            case 'update_order':
                $orders = json_decode($_POST['orders'], true);
                foreach ($orders as $item) {
                    $db->update('videos', ['display_order' => $item['order']], "id = :id", ['id' => $item['id']]);
                }
                $message = 'Display order updated successfully!';
                break;
                
            case 'bulk_delete':
                $ids = json_decode($_POST['ids'], true);
                foreach ($ids as $id) {
                    // Get file paths to delete
                    $video = $db->fetchOne("SELECT video_path, thumbnail_path FROM videos WHERE id = :id", ['id' => $id]);
                    if ($video) {
                        if ($video['video_path']) {
                            $filePath = UPLOAD_PATH . $video['video_path'];
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                        
                        if ($video['thumbnail_path']) {
                            $filePath = UPLOAD_PATH . $video['thumbnail_path'];
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                }
                
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->query("DELETE FROM videos WHERE id IN ($placeholders)", $ids);
                $message = 'Selected videos deleted successfully!';
                break;
                
            case 'increment_views':
                $id = (int)$_POST['id'];
                $db->query("UPDATE videos SET views = COALESCE(views, 0) + 1 WHERE id = :id", ['id' => $id]);
                echo json_encode(['success' => true]);
                exit;
        }
    }
}

// Helper function to generate thumbnail from YouTube/Vimeo URL
function generateVideoThumbnail($url) {
    // YouTube
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
        $videoId = $matches[1];
        $thumbnailUrl = "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
        
        // Download and save thumbnail
        $ch = curl_init($thumbnailUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $imageData) {
            $fileName = 'youtube_' . $videoId . '_' . time() . '.jpg';
            $filePath = UPLOAD_PATH . 'videos/thumbnails/' . $fileName;
            
            // Create directory if not exists
            $thumbDir = UPLOAD_PATH . 'videos/thumbnails/';
            if (!file_exists($thumbDir)) {
                mkdir($thumbDir, 0777, true);
            }
            
            if (file_put_contents($filePath, $imageData)) {
                return 'videos/thumbnails/' . $fileName;
            }
        }
    }
    
    // Vimeo
    if (preg_match('/(?:vimeo\.com\/(?:video\/)?(\d+))/', $url, $matches)) {
        $videoId = $matches[1];
        $apiUrl = "https://vimeo.com/api/v2/video/{$videoId}.json";
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data[0]['thumbnail_large'])) {
                $thumbnailUrl = $data[0]['thumbnail_large'];
                
                $ch = curl_init($thumbnailUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $imageData = curl_exec($ch);
                curl_close($ch);
                
                if ($imageData) {
                    $fileName = 'vimeo_' . $videoId . '_' . time() . '.jpg';
                    $filePath = UPLOAD_PATH . 'videos/thumbnails/' . $fileName;
                    
                    $thumbDir = UPLOAD_PATH . 'videos/thumbnails/';
                    if (!file_exists($thumbDir)) {
                        mkdir($thumbDir, 0777, true);
                    }
                    
                    if (file_put_contents($filePath, $imageData)) {
                        return 'videos/thumbnails/' . $fileName;
                    }
                }
            }
        }
    }
    
    return null;
}

// Get all videos
$videos = $db->fetchAll("SELECT * FROM videos ORDER BY display_order, created_at DESC");

// Get unique categories for filter
$categories = $db->fetchAll("SELECT DISTINCT category FROM videos WHERE category IS NOT NULL AND category != '' ORDER BY category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Manager - GETC Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/table-responsive.css">
    <style>
        /* ==========================================
           MOBILE RESPONSIVE STYLES FOR VIDEO MANAGER
           ========================================== */
        
        /* ==========================================
           BASE MOBILE STYLES
           ========================================== */
        @media (max-width: 768px) {
            /* Admin Container Adjustments */
            .admin-container {
                flex-direction: column;
                min-height: 100vh;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                width: 280px;
                height: 100%;
                z-index: 1000;
                transition: left 0.3s ease;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                transition: margin-left 0.3s ease;
            }
            
            .main-content.mobile-sidebar-open {
                margin-left: 0;
            }
            
            /* Mobile Menu Toggle */
            .mobile-menu-toggle {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1100;
                background: var(--primary-blue);
                color: white;
                border: none;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                cursor: pointer;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                transition: all 0.3s ease;
            }
            
            .mobile-menu-toggle:hover {
                transform: scale(1.05);
                background: #0056b3;
            }
            
            .mobile-menu-toggle.active {
                background: #dc3545;
            }
            
            .mobile-menu-toggle .bar {
                display: block;
                width: 20px;
                height: 2px;
                background: white;
                margin: 4px auto;
                transition: all 0.3s ease;
            }
            
            .mobile-menu-toggle.active .bar:nth-child(1) {
                transform: rotate(45deg) translate(5px, 5px);
            }
            
            .mobile-menu-toggle.active .bar:nth-child(2) {
                opacity: 0;
            }
            
            .mobile-menu-toggle.active .bar:nth-child(3) {
                transform: rotate(-45deg) translate(7px, -6px);
            }
            
            /* ==========================================
           HEADER AND NAVIGATION
           ========================================== */
            .top-bar {
                padding: 1rem;
                background: white;
                border-bottom: 1px solid #e0e0e0;
                position: sticky;
                top: 0;
                z-index: 5;
            }
            
            .user-info {
                display: none;
            }
            
            /* ==========================================
           MANAGER HEADER
           ========================================== */
            .manager-header {
                padding: 1rem;
                background: white;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .header-actions {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
            
            .btn-add, .btn-delete-selected {
                width: 100%;
                justify-content: center;
            }
            
            /* ==========================================
           FILTER BAR
           ========================================== */
            .filter-bar {
                background: white;
                padding: 1rem;
                border-bottom: 1px solid #e0e0e0;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .filter-input, .filter-select {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 14px;
                transition: border-color 0.3s ease;
            }
            
            .filter-input:focus, .filter-select:focus {
                outline: none;
                border-color: var(--primary-blue);
                box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            }
            
            .filter-clear {
                color: var(--primary-blue);
                text-decoration: none;
                font-size: 14px;
                padding: 10px 12px;
                border: 1px solid #ddd;
                border-radius: 6px;
                text-align: center;
                transition: all 0.3s ease;
            }
            
            .filter-clear:hover {
                background: #f8f9fa;
                border-color: var(--primary-blue);
            }
            
            /* ==========================================
           TABLE STYLES
           ========================================== */
            .videos-table-container {
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                margin: 1rem;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                max-height: 70vh;
            }
            
            .videos-table {
                width: 100%;
                min-width: 1000px;
                border-collapse: collapse;
                font-size: 14px;
            }
            
            /* Table Headers */
            .videos-table thead th {
                background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
                position: sticky;
                top: 0;
                z-index: 10;
                border-bottom: 2px solid #dee2e6;
                font-weight: 700;
                text-transform: uppercase;
                font-size: 11px;
                letter-spacing: 0.5px;
                color: #495057;
                padding: 12px 8px;
                text-align: left;
                white-space: nowrap;
            }
            
            /* Table Body */
            .videos-table tbody td {
                padding: 12px 8px;
                border-bottom: 1px solid #f0f0f0;
                vertical-align: middle;
                background: white;
            }
            
            .videos-table tbody tr:hover {
                background: #f8f9fa;
            }
            
            /* ==========================================
           THUMBNAIL STYLES
           ========================================== */
            .thumbnail-container {
                position: relative;
                width: 80px;
                height: 45px;
                border-radius: 6px;
                overflow: hidden;
                border: 2px solid #e9ecef;
                background: #f8f9fa;
                transition: all 0.3s ease;
            }
            
            .thumbnail-container:hover {
                border-color: var(--primary-blue);
                transform: scale(1.02);
            }
            
            .video-thumbnail {
                width: 100%;
                height: 100%;
                object-fit: cover;
                cursor: pointer;
                transition: transform 0.3s ease;
            }
            
            .thumbnail-container:hover .video-thumbnail {
                transform: scale(1.05);
            }
            
            .play-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(180deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.6) 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                transition: opacity 0.3s ease;
                cursor: pointer;
            }
            
            .thumbnail-container:hover .play-overlay {
                opacity: 1;
            }
            
            .play-overlay i {
                color: white;
                font-size: 1.2rem;
                text-shadow: 0 2px 4px rgba(0,0,0,0.5);
                transition: transform 0.3s ease;
            }
            
            .thumbnail-container:hover .play-overlay i {
                transform: scale(1.2);
            }
            
            /* ==========================================
           BUTTON STYLES
           ========================================== */
            .btn-play, .btn-edit, .btn-delete {
                background: none;
                border: 1px solid #dee2e6;
                color: #495057;
                padding: 6px 8px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 36px;
                height: 36px;
            }
            
            .btn-play:hover {
                background: #e3f2fd;
                border-color: #90caf9;
                color: #1565c0;
            }
            
            .btn-edit:hover {
                background: #fff3e0;
                border-color: #ffcc02;
                color: #f57c00;
            }
            
            .btn-delete:hover {
                background: #ffebee;
                border-color: #ef9a9a;
                color: #c62828;
            }
            
            /* ==========================================
           CHECKBOX AND DRAG HANDLE
           ========================================== */
            .select-all, .select-item {
                width: 18px;
                height: 18px;
                cursor: pointer;
            }
            
            .drag-handle {
                cursor: grab;
                text-align: center;
                color: #6c757d;
            }
            
            .drag-handle:hover {
                color: var(--primary-blue);
            }
            
            /* ==========================================
           BADGE STYLES
           ========================================== */
            .category-badge, .duration-badge {
                display: inline-block;
                padding: 4px 8px;
                background: #e9ecef;
                color: #495057;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                border: 1px solid #dee2e6;
            }
            
            .views-count {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #495057;
                font-size: 13px;
                font-weight: 600;
            }
            
            .video-source-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            
            .source-local {
                background: #e3f2fd;
                color: #1565c0;
                border: 1px solid #90caf9;
            }
            
            .source-youtube {
                background: #ffebee;
                color: #c62828;
                border: 1px solid #ef9a9a;
            }
            
            .source-vimeo {
                background: #e8f5e9;
                color: #2e7d32;
                border: 1px solid #a5d6a7;
            }
            
            /* ==========================================
           INPUT STYLES
           ========================================== */
            .order-input {
                width: 60px;
                padding: 6px 8px;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                font-size: 13px;
                text-align: center;
            }
            
            .order-input:focus {
                outline: none;
                border-color: var(--primary-blue);
                box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            }
            
            /* ==========================================
           INSTRUCTIONS SECTION
           ========================================== */
            .manager-header + div[style*="margin-top: 2rem"] {
                margin: 1rem;
                background: linear-gradient(180deg, #e3f2fd 0%, #bbdefb 100%);
                border-left: 4px solid var(--primary-blue);
            }
            
            .manager-header + div[style*="margin-top: 2rem"] h4 {
                color: #0d47a1;
                margin-bottom: 8px;
            }
            
            .manager-header + div[style*="margin-top: 2rem"] ul {
                margin-left: 20px;
                color: #1565c0;
            }
            
            .manager-header + div[style*="margin-top: 2rem"] li {
                margin-bottom: 4px;
            }
            
            /* ==========================================
           MODAL STYLES
           ========================================== */
            .modal-content {
                width: 95%;
                max-height: 90vh;
                overflow-y: auto;
                border-radius: 12px;
                padding: 0;
            }
            
            .modal-header {
                background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
                padding: 1rem 1.5rem;
                border-bottom: 1px solid #dee2e6;
            }
            
            .modal-header h2 {
                margin: 0;
                color: #495057;
                font-size: 1.25rem;
            }
            
            .close {
                color: #6c757d;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
                transition: color 0.3s ease;
            }
            
            .close:hover {
                color: #000;
            }
            
            /* ==========================================
           FORM STYLES
           ========================================== */
            .form-group {
                padding: 1rem 1.5rem;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 6px;
                font-weight: 600;
                color: #495057;
                font-size: 13px;
            }
            
            .form-group input[type="text"],
            .form-group input[type="url"],
            .form-group input[type="number"],
            .form-group textarea {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #dee2e6;
                border-radius: 6px;
                font-size: 14px;
                transition: border-color 0.3s ease;
                box-sizing: border-box;
            }
            
            .form-group input[type="text"]:focus,
            .form-group input[type="url"]:focus,
            .form-group input[type="number"]:focus,
            .form-group textarea:focus {
                outline: none;
                border-color: var(--primary-blue);
                box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            }
            
            .form-group textarea {
                min-height: 80px;
                resize: vertical;
            }
            
            .form-row {
                display: flex;
                gap: 15px;
            }
            
            .form-row .form-group {
                flex: 1;
            }
            
            .radio-group {
                display: flex;
                gap: 15px;
            }
            
            .radio-option {
                display: flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                font-size: 14px;
                color: #495057;
            }
            
            .radio-option input[type="radio"] {
                width: 18px;
                height: 18px;
                cursor: pointer;
            }
            
            .info-text {
                font-size: 12px;
                color: #6c757d;
                margin-top: 6px;
                font-style: italic;
            }
            
            .btn-save {
                background: var(--primary-blue);
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                width: 100%;
                margin: 1rem;
            }
            
            .btn-save:hover {
                background: #0056b3;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            }
            
            /* ==========================================
           PREVIEW STYLES
           ========================================== */
            .video-preview, .thumbnail-preview {
                margin-top: 10px;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 8px;
                border: 1px solid #e9ecef;
            }
            
            .video-preview p, .thumbnail-preview p {
                margin: 0 0 8px 0;
                font-size: 12px;
                color: #6c757d;
                font-weight: 600;
            }
            
            #videoPreview video {
                width: 100%;
                max-height: 200px;
                border-radius: 6px;
                background: #000;
            }
            
            #thumbnailContent img {
                max-width: 200px;
                max-height: 150px;
                border-radius: 6px;
                border: 1px solid #dee2e6;
            }
            
            /* ==========================================
           SMALL SCREEN OPTIMIZATIONS
           ========================================== */
            @media (max-width: 480px) {
                .videos-table {
                    min-width: 900px;
                }
                
                /* Adjust column widths for better mobile display */
                .videos-table th:nth-child(1), /* Checkbox */
                .videos-table td:nth-child(1),
                .videos-table th:nth-child(2), /* Drag handle */
                .videos-table td:nth-child(2),
                .videos-table th:nth-child(3), /* Thumbnail */
                .videos-table td:nth-child(3) {
                    width: auto;
                    min-width: 60px;
                }
                
                .videos-table th:nth-child(4), /* Title */
                .videos-table td:nth-child(4) {
                    width: 200px;
                    min-width: 150px;
                }
                

                .videos-table td:nth-child(5),
                .videos-table th:nth-child(6), /* Duration */
                .videos-table td:nth-child(6),
                .videos-table th:nth-child(7), /* Views */
                .videos-table td:nth-child(7),
           
                .videos-table td:nth-child(8),
                .videos-table th:nth-child(9), /* Order */
                .videos-table td:nth-child(9) {
                    width: 80px;
                    min-width: 60px;
                    font-size: 11px;
                    padding: 8px 4px;
                }
                
                /* Adjust title text for better mobile display */
                .videos-table td:nth-child(4) strong {
                    font-size: 13px;
                    display: block;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    max-width: 180px;
                }
                
                .videos-table td:nth-child(4) div {
                    display: none; /* Hide description on very small screens */
                }
                
                /* Adjust badges for small screens */
                .category-badge, .duration-badge, .video-source-badge {
                    font-size: 10px;
                    padding: 2px 6px;
                    white-space: nowrap;
                }
                
                .views-count {
                    font-size: 11px;
                    gap: 4px;
                }
                
                .order-input {
                    width: 45px;
                    padding: 4px 6px;
                    font-size: 11px;
                }
                
                .form-row {
                    flex-direction: column;
                    gap: 10px;
                }
                
                .radio-group {
                    flex-direction: column;
                    gap: 10px;
                }
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle mobile menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><span>GETC</span> Admin</h2>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                <li><a href="hero-manager.php"><i class="fas fa-images"></i> Hero Section</a></li>
                <li><a href="product-manager.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="service-manager.php"><i class="fas fa-cogs"></i> Services</a></li>
                <li><a href="project-manager.php"><i class="fas fa-project-diagram"></i> Projects</a></li>
                <li><a href="team-manager.php"><i class="fas fa-users"></i> Team Members</a></li>
                <li><a href="video-manager.php" class="active"><i class="fas fa-video"></i> Videos</a></li>
                <li><a href="feedback-manager.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="about-manager.php"><i class="fas fa-info-circle"></i> About Content</a></li>
                <li><a href="settings-manager.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1>Video Manager</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="message" id="messageAlert">
                <?php echo $message; ?>
                <span class="close-message" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="error" id="errorAlert">
                <?php echo $error; ?>
                <span class="close-message" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
            <?php endif; ?>
            
            <div class="manager-header">
                <h2>Manage Videos</h2>
                <div class="header-actions">
                    <button class="btn-delete-selected" id="deleteSelectedBtn" onclick="deleteSelected()">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn-add" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add New Video
                    </button>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <input type="text" id="searchInput" class="filter-input" placeholder="Search videos...">
                
                <select id="categoryFilter" class="filter-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <?php if ($cat['category']): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                
                <select id="sourceFilter" class="filter-select">
                    <option value="">All Sources</option>
                    <option value="local">Local Files</option>
                    <option value="youtube">YouTube</option>
                    <option value="vimeo">Vimeo</option>
                </select>
                
                <a href="#" class="filter-clear" onclick="clearFilters()">Clear Filters</a>
            </div>
            
            <!-- Videos Table -->
            <div class="videos-table-container">
                <table class="videos-table" id="videosTable">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" class="select-all" id="selectAll" onclick="toggleSelectAll()">
                            </th>
                            <th width="30"><i class="fas fa-grip-vertical"></i></th>
                            <th>Thumbnail</th>
                            <th>Title</th>
                            <th>Views</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sortableBody">
                        <?php foreach ($videos as $index => $video): 
                            $source = 'local';
                            $sourceClass = 'source-local';
                            if (!empty($video['video_url'])) {
                                if (strpos($video['video_url'], 'youtube') !== false || strpos($video['video_url'], 'youtu.be') !== false) {
                                    $source = 'youtube';
                                    $sourceClass = 'source-youtube';
                                } elseif (strpos($video['video_url'], 'vimeo') !== false) {
                                    $source = 'vimeo';
                                    $sourceClass = 'source-vimeo';
                                }
                            }
                        ?>
                        <tr data-id="<?php echo $video['id']; ?>" 
                            data-title="<?php echo htmlspecialchars($video['title']); ?>"
                            data-category="<?php echo htmlspecialchars($video['category'] ?? ''); ?>"
                            data-source="<?php echo $source; ?>">
                            <td>
                                <input type="checkbox" class="select-item" value="<?php echo $video['id']; ?>" onclick="updateDeleteButton()">
                            </td>
                            <td class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </td>
                            <td>
                                <div class="thumbnail-container">
                                    <?php if ($video['thumbnail_path']): ?>
                                    <img src="<?php echo UPLOAD_URL . $video['thumbnail_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($video['title']); ?>"
                                         class="video-thumbnail"
                                         onclick="playVideo(<?php echo $video['id']; ?>)">
                                    <?php elseif ($video['video_path']): ?>
                                    <div class="video-thumbnail" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-video" style="font-size: 2rem; color: #ccc;"></i>
                                    </div>
                                    <?php else: ?>
                                    <div class="video-thumbnail" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                        <i class="fab fa-<?php echo $source; ?>" style="font-size: 2rem; color: #ccc;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div class="play-overlay" onclick="playVideo(<?php echo $video['id']; ?>)">
                                        <i class="fas fa-play"></i>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($video['title']); ?></strong>
                                <?php if ($video['description']): ?>
                                <div style="font-size: 0.85rem; color: #666; margin-top: 0.3rem;">
                                    <?php echo htmlspecialchars(truncateText($video['description'], 50)); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                          
                            
                            <td>
                                <span class="views-count">
                                    <i class="fas fa-eye"></i> 
                                    <?php echo number_format($video['views'] ?? 0); ?>
                                </span>
                            </td>
                       
                            <td>
                                <input type="number" class="order-input" value="<?php echo $video['display_order']; ?>" 
                                       onchange="updateOrder(<?php echo $video['id']; ?>, this.value)" min="0">
                            </td>
                            <td>
                                <button class="btn-play" onclick="playVideo(<?php echo $video['id']; ?>)" title="Play Video">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($video)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-delete" onclick="deleteVideo(<?php echo $video['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($videos)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 3rem;">
                                <i class="fas fa-video" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p>No videos found. Click "Add New Video" to add your first video.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Instructions -->
            <div style="margin-top: 2rem; background: #e7f3ff; padding: 1rem; border-radius: 5px;">
                <h4 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                    <i class="fas fa-info-circle"></i> Instructions:
                </h4>
                <ul style="margin-left: 1.5rem; color: #666;">
                    <li>Drag the <i class="fas fa-grip-vertical"></i> handle to reorder videos</li>
                    <li>You can upload video files (MP4, WebM, OGG, MOV) or use YouTube/Vimeo URLs</li>
                    <li>Thumbnails are automatically generated for YouTube/Vimeo videos</li>
                    <li>Maximum video file size: 50MB</li>
                    <li>Duration format: MM:SS (e.g., 05:30 for 5 minutes 30 seconds)</li>
                    <li>Use the filter bar to quickly find videos by category or source</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="videoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Video</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="videoForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="videoId">
                <input type="hidden" name="current_video" id="currentVideo">
                <input type="hidden" name="current_thumbnail" id="currentThumbnail">
                
                <div class="form-group">
                    <label for="title">Video Title *</label>
                    <input type="text" id="title" name="title" required 
                           placeholder="e.g., Project Installation Demo">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" 
                               placeholder="e.g., Projects, Tutorials, Events"
                               list="categoryList">
                        <datalist id="categoryList">
                            <?php foreach ($categories as $cat): ?>
                                <?php if ($cat['category']): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Duration</label>
                        <input type="text" id="duration" name="duration" 
                               placeholder="MM:SS (e.g., 05:30)">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Enter video description..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Video Source *</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="source_type" value="file" checked onclick="toggleVideoSource()">
                            <span>Upload File</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="source_type" value="url" onclick="toggleVideoSource()">
                            <span>YouTube/Vimeo URL</span>
                        </label>
                    </div>
                </div>
                
                <div id="fileUploadSection">
                    <div class="form-group">
                        <label for="video_file">Video File</label>
                        <input type="file" id="video_file" name="video_file" accept="video/*">
                        <div class="info-text">Supported formats: MP4, WebM, OGG, MOV. Max size: 50MB</div>
                        
                        <div id="videoPreview" class="video-preview" style="display: none;">
                            <p>Current Video:</p>
                            <video controls style="width: 100%; max-height: 300px;">
                                <source src="" type="video/mp4">
                            </video>
                        </div>
                    </div>
                </div>
                
                <div id="urlUploadSection" style="display: none;">
                    <div class="form-group">
                        <label for="video_url">Video URL</label>
                        <input type="url" id="video_url" name="video_url" 
                               placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/...">
                        <div class="info-text">Paste YouTube or Vimeo video URL</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="thumbnail">Custom Thumbnail (Optional)</label>
                    <input type="file" id="thumbnail" name="thumbnail" accept="image/*">
                    <div class="info-text">Recommended size: 1280x720px. Max size: 2MB</div>
                    
                    <div id="thumbnailPreview" class="thumbnail-preview" style="display: none;">
                        <p>Current Thumbnail:</p>
                        <div id="thumbnailContent"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="0" min="0">
                    </div>
                </div>
                
                <button type="submit" class="btn-save" id="saveBtn">Add Video</button>
            </form>
        </div>
    </div>
    
    <!-- Video Player Modal -->
    <div id="playerModal" class="modal player-modal">
        <div class="modal-content player-content">
            <span class="close" onclick="closePlayerModal()" style="color: white; position: absolute; top: 10px; right: 20px; z-index: 1;">&times;</span>
            <div id="playerContainer"></div>
            <div id="playerInfo" class="video-info"></div>
        </div>
    </div>
    
    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>
    
    <!-- Bulk Delete Form -->
    <form id="bulkDeleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="bulk_delete">
        <input type="hidden" name="ids" id="bulkDeleteIds">
    </form>
    
    <!-- Update Order Form -->
    <form id="orderForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_order">
        <input type="hidden" name="orders" id="orderData">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        // Initialize sortable for drag-drop reordering
        const sortableBody = document.getElementById('sortableBody');
        if (sortableBody) {
            new Sortable(sortableBody, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function(evt) {
                    updateAllOrders();
                }
            });
        }
        
        // Update all orders after drag
        function updateAllOrders() {
            const rows = document.querySelectorAll('#sortableBody tr');
            const orders = [];
            
            rows.forEach((row, index) => {
                const id = row.dataset.id;
                const orderInput = row.querySelector('.order-input');
                if (orderInput) {
                    orderInput.value = index;
                    orders.push({
                        id: id,
                        order: index
                    });
                }
            });
            
            // Submit order update
            document.getElementById('orderData').value = JSON.stringify(orders);
            document.getElementById('orderForm').submit();
        }
        
        // Update single order
        function updateOrder(id, order) {
            const orders = [{
                id: id,
                order: parseInt(order)
            }];
            
            document.getElementById('orderData').value = JSON.stringify(orders);
            document.getElementById('orderForm').submit();
        }
        
        // Filtering functionality
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('categoryFilter').addEventListener('change', filterTable);
        document.getElementById('sourceFilter').addEventListener('change', filterTable);
        
        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const source = document.getElementById('sourceFilter').value;
            const rows = document.querySelectorAll('#sortableBody tr');
            
            rows.forEach(row => {
                const title = row.dataset.title.toLowerCase();
                const rowCategory = row.dataset.category.toLowerCase();
                const rowSource = row.dataset.source;
                
                let show = true;
                
                if (searchTerm && !title.includes(searchTerm)) {
                    show = false;
                }
                
                if (category && rowCategory !== category.toLowerCase()) {
                    show = false;
                }
                
                if (source && rowSource !== source) {
                    show = false;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }
        
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('sourceFilter').value = '';
            filterTable();
        }
        
        // Select all functionality
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.select-item');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateDeleteButton();
        }
        
        function updateDeleteButton() {
            const selectedCount = document.querySelectorAll('.select-item:checked').length;
            const deleteBtn = document.getElementById('deleteSelectedBtn');
            
            if (selectedCount > 0) {
                deleteBtn.classList.add('active');
                deleteBtn.innerHTML = `<i class="fas fa-trash"></i> Delete Selected (${selectedCount})`;
            } else {
                deleteBtn.classList.remove('active');
                deleteBtn.innerHTML = `<i class="fas fa-trash"></i> Delete Selected`;
            }
        }
        
        // Delete selected items
        function deleteSelected() {
            const selected = document.querySelectorAll('.select-item:checked');
            if (selected.length === 0) return;
            
            const ids = Array.from(selected).map(cb => cb.value);
            
            if (confirm(`Are you sure you want to delete ${selected.length} video(s)? This action cannot be undone.`)) {
                document.getElementById('bulkDeleteIds').value = JSON.stringify(ids);
                document.getElementById('bulkDeleteForm').submit();
            }
        }
        
        // Toggle video source
        function toggleVideoSource() {
            const fileSelected = document.querySelector('input[name="source_type"][value="file"]').checked;
            const fileSection = document.getElementById('fileUploadSection');
            const urlSection = document.getElementById('urlUploadSection');
            
            if (fileSelected) {
                fileSection.style.display = 'block';
                urlSection.style.display = 'none';
                document.getElementById('video_url').required = false;
                document.getElementById('video_file').required = true;
            } else {
                fileSection.style.display = 'none';
                urlSection.style.display = 'block';
                document.getElementById('video_url').required = true;
                document.getElementById('video_file').required = false;
            }
        }
        
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Video';
            document.getElementById('formAction').value = 'add';
            document.getElementById('videoForm').reset();
            document.getElementById('videoPreview').style.display = 'none';
            document.getElementById('thumbnailPreview').style.display = 'none';
            document.getElementById('saveBtn').textContent = 'Add Video';
            document.querySelector('input[name="source_type"][value="file"]').checked = true;
            toggleVideoSource();
            document.getElementById('videoModal').style.display = 'block';
        }
        
        function openEditModal(video) {
            document.getElementById('modalTitle').textContent = 'Edit Video';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('videoId').value = video.id;
            document.getElementById('title').value = video.title || '';
            document.getElementById('category').value = video.category || '';
            document.getElementById('duration').value = video.duration || '';
            document.getElementById('description').value = video.description || '';
            document.getElementById('display_order').value = video.display_order || 0;
            document.getElementById('currentVideo').value = video.video_path || '';
            document.getElementById('currentThumbnail').value = video.thumbnail_path || '';
            document.getElementById('saveBtn').textContent = 'Update Video';
            
            // Set source type
            if (video.video_url) {
                document.querySelector('input[name="source_type"][value="url"]').checked = true;
                document.getElementById('video_url').value = video.video_url;
            } else {
                document.querySelector('input[name="source_type"][value="file"]').checked = true;
            }
            toggleVideoSource();
            
            // Show video preview
            if (video.video_path) {
                const previewDiv = document.getElementById('videoPreview');
                const videoPreview = previewDiv.querySelector('video');
                const source = videoPreview.querySelector('source');
                source.src = '<?php echo UPLOAD_URL; ?>' + video.video_path;
                videoPreview.load();
                previewDiv.style.display = 'block';
            }
            
            // Show thumbnail preview
            if (video.thumbnail_path) {
                const previewDiv = document.getElementById('thumbnailPreview');
                const previewContent = document.getElementById('thumbnailContent');
                previewContent.innerHTML = `<img src="<?php echo UPLOAD_URL; ?>${video.thumbnail_path}">`;
                previewDiv.style.display = 'block';
            }
            
            document.getElementById('videoModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('videoModal').style.display = 'none';
            document.getElementById('videoForm').reset();
            document.getElementById('videoPreview').style.display = 'none';
            document.getElementById('thumbnailPreview').style.display = 'none';
        }
        
        // Play video
        function playVideo(id) {
            const video = <?php echo json_encode($videos); ?>.find(v => v.id == id);
            if (!video) return;
            
            const playerContainer = document.getElementById('playerContainer');
            const playerInfo = document.getElementById('playerInfo');
            
            let playerHtml = '';
            
            if (video.video_path) {
                playerHtml = `
                    <video controls autoplay style="width: 100%; max-height: 60vh;">
                        <source src="<?php echo UPLOAD_URL; ?>${video.video_path}" type="video/mp4">
                    </video>
                `;
            } else if (video.video_url) {
                if (video.video_url.includes('youtube') || video.video_url.includes('youtu.be')) {
                    // Extract YouTube ID
                    let videoId = '';
                    const match = video.video_url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
                    if (match) {
                        videoId = match[1];
                        playerHtml = `
                            <iframe width="100%" height="400" 
                                    src="https://www.youtube.com/embed/${videoId}?autoplay=1" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                        `;
                    }
                } else if (video.video_url.includes('vimeo')) {
                    // Extract Vimeo ID
                    const match = video.video_url.match(/(?:vimeo\.com\/(?:video\/)?(\d+))/);
                    if (match) {
                        const videoId = match[1];
                        playerHtml = `
                            <iframe width="100%" height="400" 
                                    src="https://player.vimeo.com/video/${videoId}?autoplay=1" 
                                    frameborder="0" 
                                    allow="autoplay; fullscreen; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                        `;
                    }
                }
            }
            
            playerContainer.innerHTML = playerHtml;
            playerInfo.innerHTML = `<h3>${video.title}</h3><p>${video.description || ''}</p>`;
            
            document.getElementById('playerModal').style.display = 'block';
            
            // Increment view count
            fetch('video-manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=increment_views&id=' + id
            });
        }
        
        function closePlayerModal() {
            document.getElementById('playerModal').style.display = 'none';
            document.getElementById('playerContainer').innerHTML = '';
        }
        
        // Delete video
        function deleteVideo(id) {
            if (confirm('Are you sure you want to delete this video? This action cannot be undone.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Preview video file
        document.getElementById('video_file')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewDiv = document.getElementById('videoPreview');
            const videoPreview = previewDiv.querySelector('video');
            const source = videoPreview.querySelector('source');
            
            if (file) {
                // Check file size (max 50MB)
                if (file.size > 50 * 1024 * 1024) {
                    alert('File size must be less than 50MB');
                    this.value = '';
                    return;
                }
                
                const url = URL.createObjectURL(file);
                source.src = url;
                videoPreview.load();
                previewDiv.style.display = 'block';
            } else {
                previewDiv.style.display = 'none';
            }
        });
        
        // Preview thumbnail
        document.getElementById('thumbnail')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewDiv = document.getElementById('thumbnailPreview');
            const previewContent = document.getElementById('thumbnailContent');
            
            if (file) {
                // Check file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewContent.innerHTML = `<img src="${e.target.result}">`;
                    previewDiv.style.display = 'block';
                };
                
                reader.readAsDataURL(file);
            } else {
                previewDiv.style.display = 'none';
            }
        });
        
        // Duration format validation
        document.getElementById('duration')?.addEventListener('input', function(e) {
            const value = this.value;
            if (value && !value.match(/^\d{1,2}:\d{2}$/)) {
                this.setCustomValidity('Please use format MM:SS (e.g., 05:30)');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('videoModal');
            const playerModal = document.getElementById('playerModal');
            
            if (event.target == modal) {
                closeModal();
            }
            if (event.target == playerModal) {
                closePlayerModal();
            }
        }
        
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messageAlert = document.getElementById('messageAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            if (messageAlert) {
                messageAlert.style.display = 'none';
            }
            
            if (errorAlert) {
                errorAlert.style.display = 'none';
            }
        }, 5000);
        
        // Form validation
        document.getElementById('videoForm')?.addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const fileSelected = document.querySelector('input[name="source_type"][value="file"]').checked;
            const urlSelected = document.querySelector('input[name="source_type"][value="url"]').checked;
            
            if (!title) {
                e.preventDefault();
                alert('Please enter a video title');
                return;
            }
            
            if (fileSelected) {
                const videoFile = document.getElementById('video_file').files;
                const action = document.getElementById('formAction').value;
                if (action === 'add' && videoFile.length === 0) {
                    e.preventDefault();
                    alert('Please select a video file to upload');
                }
            }
            
            if (urlSelected) {
                const videoUrl = document.getElementById('video_url').value.trim();
                if (!videoUrl) {
                    e.preventDefault();
                    alert('Please enter a video URL');
                } else if (!videoUrl.match(/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|vimeo\.com)/)) {
                    e.preventDefault();
                    alert('Please enter a valid YouTube or Vimeo URL');
                }
            }
        });
        
        // Initialize checkboxes
        document.querySelectorAll('.select-item').forEach(checkbox => {
            checkbox.addEventListener('change', updateDeleteButton);
        });

        // Mobile Menu Toggle Script
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');

            if (mobileMenuToggle && sidebar && mainContent) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    mainContent.classList.toggle('mobile-sidebar-open');
                    mobileMenuToggle.classList.toggle('active');
                });

                // Close sidebar when clicking on a link (mobile only)
                const sidebarLinks = sidebar.querySelectorAll('a');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 768) {
                            sidebar.classList.remove('active');
                            mainContent.classList.remove('mobile-sidebar-open');
                            mobileMenuToggle.classList.remove('active');
                        }
                    });
                });

                // Close sidebar when clicking outside (mobile only)
                mainContent.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                        // Only close if clicking on main content, not on modals or other interactive elements
                        if (!e.target.closest('.modal') && !e.target.closest('.sidebar')) {
                            sidebar.classList.remove('active');
                            mainContent.classList.remove('mobile-sidebar-open');
                            mobileMenuToggle.classList.remove('active');
                        }
                    }
                });

                // Handle window resize
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 768) {
                        sidebar.classList.remove('active');
                        mainContent.classList.remove('mobile-sidebar-open');
                        mobileMenuToggle.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>
