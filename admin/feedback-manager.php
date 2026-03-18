<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->requireLogin();

require_once '../includes/db.php';
require_once '../includes/functions.php';

$message = '';
$error = '';

// Handle operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve':
                $id = (int)$_POST['id'];
                $db->update('feedback', ['is_approved' => 'true'], "id = :id", ['id' => $id]);
                $message = 'Feedback approved successfully!';
                break;
                
            case 'reject':
                $id = (int)$_POST['id'];
                $db->update('feedback', ['is_approved' => 'false'], "id = :id", ['id' => $id]);
                $message = 'Feedback rejected successfully!';
                break;
                
            case 'feature':
                $id = (int)$_POST['id'];
                $featured = (int)$_POST['featured'];
                $db->update('feedback', ['is_featured' => $featured], "id = :id", ['id' => $id]);
                $message = $featured ? 'Feedback featured successfully!' : 'Feedback unfeatured successfully!';
                break;
                
            case 'delete':
                // Get image path to delete file
                $feedback = $db->fetchOne("SELECT image_path FROM feedback WHERE id = :id", ['id' => $_POST['id']]);
                if ($feedback && $feedback['image_path']) {
                    $filePath = UPLOAD_PATH . $feedback['image_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                $db->delete('feedback', "id = :id", ['id' => $_POST['id']]);
                $message = 'Feedback deleted successfully!';
                break;
                
            case 'bulk_approve':
                $ids = json_decode($_POST['ids'], true);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->query("UPDATE feedback SET is_approved = true WHERE id IN ($placeholders)", $ids);
                $message = 'Selected feedback approved successfully!';
                break;
                
            case 'bulk_reject':
                $ids = json_decode($_POST['ids'], true);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->query("UPDATE feedback SET is_approved = false WHERE id IN ($placeholders)", $ids);
                $message = 'Selected feedback rejected successfully!';
                break;
                
            case 'bulk_delete':
                $ids = json_decode($_POST['ids'], true);
                
                // Get all image paths to delete
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $feedbacks = $db->fetchAll("SELECT image_path FROM feedback WHERE id IN ($placeholders)", $ids);
                
                foreach ($feedbacks as $feedback) {
                    if ($feedback['image_path']) {
                        $filePath = UPLOAD_PATH . $feedback['image_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
                
                $db->query("DELETE FROM feedback WHERE id IN ($placeholders)", $ids);
                $message = 'Selected feedback deleted successfully!';
                break;
                
            case 'reply':
                $id = (int)$_POST['id'];
                $reply = $_POST['reply'];
                $db->update('feedback', ['admin_reply' => $reply, 'replied_at' => date('Y-m-d H:i:s')], "id = :id", ['id' => $id]);
                
                // Optional: Send email notification to customer
                if (isset($_POST['notify_customer']) && $_POST['notify_customer'] == '1') {
                    $feedback = $db->fetchOne("SELECT * FROM feedback WHERE id = :id", ['id' => $id]);
                    if ($feedback && $feedback['email']) {
                        // Send email logic here
                        sendReplyEmail($feedback['email'], $feedback['customer_name'], $reply);
                    }
                }
                
                $message = 'Reply sent successfully!';
                break;
        }
    }
}

// Helper function to send reply email
function sendReplyEmail($to, $name, $reply) {
    $subject = "Response to your feedback - GETC Ltd";
    $message = "
    <html>
    <head>
        <title>Response to your feedback</title>
    </head>
    <body>
        <h2>Dear $name,</h2>
        <p>Thank you for your feedback. Here's our response:</p>
        <div style='background: #f5f5f5; padding: 15px; border-left: 4px solid #f16c20;'>
            $reply
        </div>
        <p>Best regards,<br>GETC Ltd Team</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: noreply@getcltd.com' . "\r\n";
    
    mail($to, $subject, $message, $headers);
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 20;
$offset = ($page - 1) * $itemsPerPage;

// Build query
$sql = "SELECT * FROM feedback WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM feedback WHERE 1=1";
$params = [];
$countParams = [];

if ($status !== 'all') {
    if ($status === 'approved') {
        $sql .= " AND is_approved = true";
        $countSql .= " AND is_approved = true";
    } elseif ($status === 'pending') {
        $sql .= " AND is_approved = false";
        $countSql .= " AND is_approved = false";
    } elseif ($status === 'featured') {
        $sql .= " AND is_featured = true";
        $countSql .= " AND is_featured = true";
    } elseif ($status === 'replied') {
        $sql .= " AND admin_reply IS NOT NULL";
        $countSql .= " AND admin_reply IS NOT NULL";
    }
}

if ($rating > 0) {
    $sql .= " AND rating = :rating";
    $countSql .= " AND rating = :rating";
    $params['rating'] = $rating;
    $countParams['rating'] = $rating;
}

if ($search) {
    $sql .= " AND (customer_name ILIKE :search1 OR company ILIKE :search2 OR feedback_text ILIKE :search3 OR email ILIKE :search4)";
    $countSql .= " AND (customer_name ILIKE :search1 OR company ILIKE :search2 OR feedback_text ILIKE :search3 OR email ILIKE :search4)";
    $searchTerm = "%$search%";
    $params['search1'] = $searchTerm;
    $params['search2'] = $searchTerm;
    $params['search3'] = $searchTerm;
    $params['search4'] = $searchTerm;
    $countParams['search1'] = $searchTerm;
    $countParams['search2'] = $searchTerm;
    $countParams['search3'] = $searchTerm;
    $countParams['search4'] = $searchTerm;
}

// Add sorting
switch ($sort) {
    case 'oldest':
        $sql .= " ORDER BY created_at ASC";
        break;
    case 'highest':
        $sql .= " ORDER BY rating DESC, created_at DESC";
        break;
    case 'lowest':
        $sql .= " ORDER BY rating ASC, created_at DESC";
        break;
    default:
        $sql .= " ORDER BY created_at DESC";
}

// Get total count for pagination
$totalResult = $db->fetchOne($countSql, $countParams);
$totalItems = $totalResult ? $totalResult['total'] : 0;
$totalPages = ceil($totalItems / $itemsPerPage);

// Add pagination
$sql .= " LIMIT :limit OFFSET :offset";
$params['limit'] = $itemsPerPage;
$params['offset'] = $offset;

// Get feedback
$feedbackList = $db->fetchAll($sql, $params);

// Get statistics
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM feedback")['count'] ?? 0,
    'pending' => $db->fetchOne("SELECT COUNT(*) as count FROM feedback WHERE is_approved = false")['count'] ?? 0,
    'approved' => $db->fetchOne("SELECT COUNT(*) as count FROM feedback WHERE is_approved = true")['count'] ?? 0,
    'featured' => $db->fetchOne("SELECT COUNT(*) as count FROM feedback WHERE is_featured = true")['count'] ?? 0,
    'replied' => $db->fetchOne("SELECT COUNT(*) as count FROM feedback WHERE admin_reply IS NOT NULL")['count'] ?? 0,
    'avg_rating' => round($db->fetchOne("SELECT AVG(rating) as avg FROM feedback WHERE is_approved = true")['avg'] ?? 0, 1),
];

// Get rating distribution
$ratingStats = [];
for ($i = 5; $i >= 1; $i--) {
    $ratingStats[$i] = $db->fetchOne("SELECT COUNT(*) as count FROM feedback WHERE rating = :rating AND is_approved = true", ['rating' => $i])['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Manager - GETC Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/table-responsive.css">
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
                <li><a href="video-manager.php"><i class="fas fa-video"></i> Videos</a></li>
                <li><a href="feedback-manager.php" class="active"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="about-manager.php"><i class="fas fa-info-circle"></i> About Content</a></li>
                <li><a href="settings-manager.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1>Feedback Manager</h1>
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
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Feedback</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Pending Approval</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['approved']; ?></h3>
                        <p>Approved</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['featured']; ?></h3>
                        <p>Featured</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-reply"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['replied']; ?></h3>
                        <p>Replied</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['avg_rating']; ?></h3>
                        <p>Avg Rating</p>
                    </div>
                </div>
            </div>
            
            <!-- Rating Distribution -->
            <div class="rating-distribution">
                <h3 style="margin-bottom: 1rem; color: var(--primary-blue);">Rating Distribution</h3>
                <?php for ($i = 5; $i >= 1; $i--): 
                    $percentage = $stats['approved'] > 0 ? round(($ratingStats[$i] / $stats['approved']) * 100) : 0;
                ?>
                <div class="rating-bar">
                    <span class="rating-label"><?php echo $i; ?> Star</span>
                    <div class="bar-container">
                        <div class="bar-fill" style="width: <?php echo $percentage; ?>%;">
                            <?php if ($percentage > 15): ?><?php echo $percentage; ?>%<?php endif; ?>
                        </div>
                    </div>
                    <span class="rating-count"><?php echo $ratingStats[$i]; ?></span>
                </div>
                <?php endfor; ?>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <input type="text" id="searchInput" class="filter-input" placeholder="Search by name, company, email or feedback..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select id="statusFilter" class="filter-select">
                    <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Feedback</option>
                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                    <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="featured" <?php echo $status == 'featured' ? 'selected' : ''; ?>>Featured</option>
                    <option value="replied" <?php echo $status == 'replied' ? 'selected' : ''; ?>>Replied</option>
                </select>
                
                <select id="ratingFilter" class="filter-select">
                    <option value="0" <?php echo $rating == 0 ? 'selected' : ''; ?>>All Ratings</option>
                    <option value="5" <?php echo $rating == 5 ? 'selected' : ''; ?>>5 Stars</option>
                    <option value="4" <?php echo $rating == 4 ? 'selected' : ''; ?>>4 Stars</option>
                    <option value="3" <?php echo $rating == 3 ? 'selected' : ''; ?>>3 Stars</option>
                    <option value="2" <?php echo $rating == 2 ? 'selected' : ''; ?>>2 Stars</option>
                    <option value="1" <?php echo $rating == 1 ? 'selected' : ''; ?>>1 Star</option>
                </select>
                
                <select id="sortFilter" class="filter-select">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="highest" <?php echo $sort == 'highest' ? 'selected' : ''; ?>>Highest Rated</option>
                    <option value="lowest" <?php echo $sort == 'lowest' ? 'selected' : ''; ?>>Lowest Rated</option>
                </select>
                
                <a href="feedback-manager.php" class="filter-clear">Clear Filters</a>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <select id="bulkAction">
                    <option value="">Select Action</option>
                    <option value="approve">Approve Selected</option>
                    <option value="reject">Reject Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button class="btn-bulk" onclick="executeBulkAction()">Apply to Selected</button>
                <span id="selectedCount" style="color: #666; margin-left: auto;">0 selected</span>
            </div>
            
            <!-- Feedback Table -->
            <div class="feedback-table-container">
                <table class="feedback-table" id="feedbackTable">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" class="select-all" id="selectAll" onclick="toggleSelectAll()">
                            </th>
                            <th>Customer</th>
                            <th>Rating</th>
                            <th>Feedback</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedbackList as $feedback): 
                            $rowClass = !$feedback['is_approved'] ? 'pending' : '';
                        ?>
                        <tr class="<?php echo $rowClass; ?>" data-id="<?php echo $feedback['id']; ?>">
                            <td>
                                <input type="checkbox" class="select-item" value="<?php echo $feedback['id']; ?>" onclick="updateSelectedCount()">
                            </td>
                            <td>
                                <div class="customer-info">
                                    <?php if ($feedback['image_path']): ?>
                                    <img src="<?php echo UPLOAD_URL . $feedback['image_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($feedback['customer_name']); ?>"
                                         class="customer-image">
                                    <?php else: ?>
                                    <div class="customer-initials">
                                        <?php echo strtoupper(substr($feedback['customer_name'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="customer-details">
                                        <h4><?php echo htmlspecialchars($feedback['customer_name']); ?></h4>
                                        <?php if ($feedback['company']): ?>
                                        <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($feedback['company']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($feedback['email']): ?>
                                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($feedback['email']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $feedback['rating']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star empty"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td>
                                <div class="feedback-text">
                                    <div class="preview">
                                        <?php echo htmlspecialchars(truncateText($feedback['feedback_text'], 100)); ?>
                                    </div>
                                    <div class="full">
                                        <strong>Feedback:</strong>
                                        <p><?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?></p>
                                        <?php if ($feedback['admin_reply']): ?>
                                        <div class="admin-reply">
                                            <strong>Admin Reply:</strong>
                                            <p><?php echo nl2br(htmlspecialchars($feedback['admin_reply'])); ?></p>
                                            <small>Replied on <?php echo date('M d, Y', strtotime($feedback['replied_at'])); ?></small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($feedback['is_featured']): ?>
                                <span class="status-badge status-featured">Featured</span>
                                <?php endif; ?>
                                
                                <?php if ($feedback['is_approved']): ?>
                                <span class="status-badge status-approved">Approved</span>
                                <?php else: ?>
                                <span class="status-badge status-pending">Pending</span>
                                <?php endif; ?>
                                
                                <?php if ($feedback['admin_reply']): ?>
                                <span class="status-badge status-replied">Replied</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></div>
                                <small style="color: #999;"><?php echo date('h:i A', strtotime($feedback['created_at'])); ?></small>
                            </td>
                            <td>
                                <button class="btn-view" onclick="viewFeedback(<?php echo htmlspecialchars(json_encode($feedback)); ?>)" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if (!$feedback['is_approved']): ?>
                                <button class="btn-approve" onclick="updateStatus(<?php echo $feedback['id']; ?>, 'approve')" title="Approve">
                                    <i class="fas fa-check"></i>
                                </button>
                                <?php else: ?>
                                <button class="btn-reject" onclick="updateStatus(<?php echo $feedback['id']; ?>, 'reject')" title="Reject">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                                
                                <button class="btn-feature" onclick="toggleFeature(<?php echo $feedback['id']; ?>, <?php echo $feedback['is_featured'] ? 0 : 1; ?>)" title="<?php echo $feedback['is_featured'] ? 'Remove Featured' : 'Make Featured'; ?>">
                                    <i class="fas fa-<?php echo $feedback['is_featured'] ? 'star' : 'star-half-alt'; ?>"></i>
                                </button>
                                
                                <button class="btn-reply" onclick="openReplyModal(<?php echo htmlspecialchars(json_encode($feedback)); ?>)" title="Reply">
                                    <i class="fas fa-reply"></i>
                                </button>
                                
                                <button class="btn-delete" onclick="deleteFeedback(<?php echo $feedback['id']; ?>)" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($feedbackList)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem;">
                                <i class="fas fa-comments" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p>No feedback found matching your criteria.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php 
                $urlParams = $_GET;
                unset($urlParams['page']);
                $baseUrl = '?' . http_build_query($urlParams);
                $baseUrl .= $urlParams ? '&' : '?';
                ?>
                
                <?php if ($page > 1): ?>
                <a href="<?php echo $baseUrl; ?>page=<?php echo $page - 1; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php else: ?>
                <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="<?php echo $baseUrl; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="<?php echo $baseUrl; ?>page=<?php echo $page + 1; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php else: ?>
                <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Instructions -->
            <div style="margin-top: 2rem; background: #e7f3ff; padding: 1rem; border-radius: 5px;">
                <h4 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                    <i class="fas fa-info-circle"></i> Instructions:
                </h4>
                <ul style="margin-left: 1.5rem; color: #666;">
                    <li><span class="status-badge status-pending">Pending</span> - Feedback waiting for approval</li>
                    <li><span class="status-badge status-approved">Approved</span> - Feedback visible on website</li>
                    <li><span class="status-badge status-featured">Featured</span> - Highlighted in testimonials section</li>
                    <li><span class="status-badge status-replied">Replied</span> - Admin has responded to customer</li>
                    <li>Hover over feedback text to see full content and any replies</li>
                    <li>Use bulk actions to manage multiple feedback entries at once</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- View Feedback Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Feedback Details</h2>
                <span class="close" onclick="closeViewModal()">&times;</span>
            </div>
            <div class="modal-body" id="viewModalContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
    
    <!-- Reply Modal -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reply to Feedback</h2>
                <span class="close" onclick="closeReplyModal()">&times;</span>
            </div>
            <form method="POST" id="replyForm">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="id" id="replyId">
                
                <div class="modal-body">
                    <div class="feedback-detail" id="replyCustomerInfo"></div>
                    
                    <div class="form-group">
                        <label for="reply">Your Reply *</label>
                        <textarea id="reply" name="reply" required placeholder="Enter your response to this customer..."></textarea>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="notify_customer" name="notify_customer" value="1" checked>
                        <label for="notify_customer">Send email notification to customer</label>
                    </div>
                </div>
                
                <button type="submit" class="btn-save">Send Reply</button>
            </form>
        </div>
    </div>
    
    <!-- Hidden Forms for Actions -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" id="statusAction">
        <input type="hidden" name="id" id="statusId">
    </form>
    
    <form id="featureForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="feature">
        <input type="hidden" name="id" id="featureId">
        <input type="hidden" name="featured" id="featureValue">
    </form>
    
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>
    
    <form id="bulkForm" method="POST" style="display: none;">
        <input type="hidden" name="action" id="bulkAction">
        <input type="hidden" name="ids" id="bulkIds">
    </form>
    
    <script>
        // Filter functionality
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
        
        document.getElementById('statusFilter').addEventListener('change', applyFilters);
        document.getElementById('ratingFilter').addEventListener('change', applyFilters);
        document.getElementById('sortFilter').addEventListener('change', applyFilters);
        
        function applyFilters() {
            const url = new URL(window.location.href);
            url.searchParams.set('search', document.getElementById('searchInput').value);
            url.searchParams.set('status', document.getElementById('statusFilter').value);
            url.searchParams.set('rating', document.getElementById('ratingFilter').value);
            url.searchParams.set('sort', document.getElementById('sortFilter').value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
        
        // Selection functionality
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.select-item');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const selectedCount = document.querySelectorAll('.select-item:checked').length;
            document.getElementById('selectedCount').textContent = selectedCount + ' selected';
        }
        
        // Bulk actions
        function executeBulkAction() {
            const action = document.getElementById('bulkAction').value;
            const selected = document.querySelectorAll('.select-item:checked');
            
            if (selected.length === 0) {
                alert('Please select at least one item');
                return;
            }
            
            if (!action) {
                alert('Please select an action');
                return;
            }
            
            let confirmMessage = '';
            let bulkAction = '';
            
            switch(action) {
                case 'approve':
                    confirmMessage = `Approve ${selected.length} feedback item(s)?`;
                    bulkAction = 'bulk_approve';
                    break;
                case 'reject':
                    confirmMessage = `Reject ${selected.length} feedback item(s)?`;
                    bulkAction = 'bulk_reject';
                    break;
                case 'delete':
                    confirmMessage = `Delete ${selected.length} feedback item(s)? This action cannot be undone.`;
                    bulkAction = 'bulk_delete';
                    break;
            }
            
            if (confirm(confirmMessage)) {
                const ids = Array.from(selected).map(cb => cb.value);
                document.getElementById('bulkAction').value = bulkAction;
                document.getElementById('bulkIds').value = JSON.stringify(ids);
                document.getElementById('bulkForm').submit();
            }
        }
        
        // Update status (approve/reject)
        function updateStatus(id, action) {
            const actionText = action === 'approve' ? 'approve' : 'reject';
            if (confirm(`Are you sure you want to ${actionText} this feedback?`)) {
                document.getElementById('statusAction').value = action;
                document.getElementById('statusId').value = id;
                document.getElementById('statusForm').submit();
            }
        }
        
        // Toggle featured
        function toggleFeature(id, featured) {
            const actionText = featured ? 'feature' : 'unfeature';
            if (confirm(`Are you sure you want to ${actionText} this feedback?`)) {
                document.getElementById('featureId').value = id;
                document.getElementById('featureValue').value = featured;
                document.getElementById('featureForm').submit();
            }
        }
        
        // Delete feedback
        function deleteFeedback(id) {
            if (confirm('Are you sure you want to delete this feedback? This action cannot be undone.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // View feedback details
        function viewFeedback(feedback) {
            const content = document.getElementById('viewModalContent');
            
            let html = `
                <div class="feedback-detail">
                    <div class="label">Customer:</div>
                    <div class="value">${feedback.customer_name} ${feedback.company ? ' - ' + feedback.company : ''}</div>
                </div>
                
                <div class="feedback-detail">
                    <div class="label">Email:</div>
                    <div class="value">${feedback.email || 'Not provided'}</div>
                </div>
                
                <div class="feedback-detail">
                    <div class="label">Rating:</div>
                    <div class="value">
                        ${getStarRating(feedback.rating)}
                    </div>
                </div>
                
                <div class="feedback-detail">
                    <div class="label">Feedback:</div>
                    <div class="value">${nl2br(feedback.feedback_text)}</div>
                </div>
                
                <div class="feedback-detail">
                    <div class="label">Status:</div>
                    <div class="value">
                        ${feedback.is_approved ? '✅ Approved' : '⏳ Pending'}
                        ${feedback.is_featured ? ' | ⭐ Featured' : ''}
                    </div>
                </div>
                
                <div class="feedback-detail">
                    <div class="label">Submitted:</div>
                    <div class="value">${new Date(feedback.created_at).toLocaleString()}</div>
                </div>
            `;
            
            if (feedback.admin_reply) {
                html += `
                    <div class="feedback-detail">
                        <div class="label">Admin Reply:</div>
                        <div class="value">
                            ${nl2br(feedback.admin_reply)}
                            <br><small>Replied on ${new Date(feedback.replied_at).toLocaleString()}</small>
                        </div>
                    </div>
                `;
            }
            
            if (feedback.image_path) {
                html += `
                    <div class="feedback-detail">
                        <div class="label">Customer Photo:</div>
                        <div class="value">
                            <img src="<?php echo UPLOAD_URL; ?>${feedback.image_path}" style="max-width: 100px; border-radius: 5px;">
                        </div>
                    </div>
                `;
            }
            
            content.innerHTML = html;
            document.getElementById('viewModal').style.display = 'block';
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }
        
        // Reply modal
        function openReplyModal(feedback) {
            document.getElementById('replyId').value = feedback.id;
            
            const customerInfo = document.getElementById('replyCustomerInfo');
            customerInfo.innerHTML = `
                <p><strong>Customer:</strong> ${feedback.customer_name} ${feedback.company ? ' - ' + feedback.company : ''}</p>
                <p><strong>Feedback:</strong> ${feedback.feedback_text.substring(0, 200)}${feedback.feedback_text.length > 200 ? '...' : ''}</p>
                <p><strong>Rating:</strong> ${getStarRating(feedback.rating)}</p>
            `;
            
            document.getElementById('replyModal').style.display = 'block';
        }
        
        function closeReplyModal() {
            document.getElementById('replyModal').style.display = 'none';
            document.getElementById('replyForm').reset();
        }
        
        // Helper functions
        function getStarRating(rating) {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= rating) {
                    stars += '<i class="fas fa-star" style="color: #ffd700;"></i>';
                } else {
                    stars += '<i class="far fa-star" style="color: #ddd;"></i>';
                }
            }
            return stars;
        }
        
        function nl2br(str) {
            return str.replace(/\n/g, '<br>');
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const viewModal = document.getElementById('viewModal');
            const replyModal = document.getElementById('replyModal');
            
            if (event.target == viewModal) {
                closeViewModal();
            }
            if (event.target == replyModal) {
                closeReplyModal();
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
        
        // Initialize checkboxes
        document.querySelectorAll('.select-item').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
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
