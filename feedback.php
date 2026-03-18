<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Handle feedback submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $customer_name = $_POST['customer_name'] ?? '';
    $company = $_POST['company'] ?? '';
    $email = $_POST['email'] ?? '';
    $feedback_text = $_POST['feedback_text'] ?? '';
    $rating = (int)($_POST['rating'] ?? 5);
    
    // Validate inputs
    $errors = [];
    if (empty($customer_name)) $errors[] = 'Name is required';
    if (empty($feedback_text)) $errors[] = 'Feedback is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['customer_image']) && $_FILES['customer_image']['error'] === 0) {
        $upload = uploadFile($_FILES['customer_image'], 'feedback');
        if ($upload['success']) {
            $image_path = $upload['path'];
        }
    }
    
    if (empty($errors)) {
        // Insert feedback (not approved by default)
        $data = [
            'customer_name' => $customer_name,
            'company' => $company,
            'email' => $email,
            'feedback_text' => $feedback_text,
            'rating' => $rating,
            'image_path' => $image_path,
            'is_approved' => 'false'
        ];
        
        $db->insert('feedback', $data);
        $message = 'Thank you for your feedback! It will be displayed after moderation.';
    } else {
        $error = implode('<br>', $errors);
    }
}

// Get filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$itemsPerPage = 9;
$offset = ($page - 1) * $itemsPerPage;

// Build query for approved feedback - FIXED: Separate count query
$sql = "SELECT * FROM feedback WHERE is_approved = true";
$countSql = "SELECT COUNT(*) as total FROM feedback WHERE is_approved = true";
$params = [];
$countParams = [];

if ($rating > 0) {
    $sql .= " AND rating = ?";
    $countSql .= " AND rating = ?";
    $params[] = $rating;
    $countParams[] = $rating;
}

// Add sorting for main query only
switch ($sort) {
    case 'highest':
        $sql .= " ORDER BY rating DESC, created_at DESC";
        break;
    case 'lowest':
        $sql .= " ORDER BY rating ASC, created_at DESC";
        break;
    default:
        $sql .= " ORDER BY created_at DESC";
}

// Get total count for pagination - using separate count query without ORDER BY
$totalResult = $db->fetchOne($countSql, $countParams);
$totalItems = $totalResult ? $totalResult['total'] : 0;
$totalPages = ceil($totalItems / $itemsPerPage);

// Add pagination to main query
$sql .= " LIMIT ? OFFSET ?";
$params[] = $itemsPerPage;
$params[] = $offset;

// Get feedback
$feedbackList = $db->fetchAll($sql, $params);

// Get statistics - FIXED: Use separate queries for each statistic
$stats = [
    'total' => 0,
    'avg_rating' => 0,
    'five_star' => 0,
    'four_star' => 0,
    'three_star' => 0,
    'two_star' => 0,
    'one_star' => 0,
];

// Get total count
$totalResult = $db->fetchOne("SELECT COUNT(*) as count FROM feedback WHERE is_approved = true");
$stats['total'] = $totalResult ? $totalResult['count'] : 0;

// Get average rating
$avgResult = $db->fetchOne("SELECT COALESCE(AVG(rating), 0) as avg FROM feedback WHERE is_approved = true");
$stats['avg_rating'] = $avgResult ? round($avgResult['avg'], 1) : 0;

// Get rating counts
$fiveResult = $db->fetchOne("SELECT COUNT(*) as count FROM feedback WHERE is_approved = true AND rating = 5");
$stats['five_star'] = $fiveResult ? $fiveResult['count'] : 0;

$fourResult = $db->fetchOne("SELECT COUNT(*) as count FROM feedback WHERE is_approved = true AND rating = 4");
$stats['four_star'] = $fourResult ? $fourResult['count'] : 0;

$threeResult = $db->fetchOne("SELECT COUNT(*) as count FROM feedback WHERE is_approved = true AND rating = 3");
$stats['three_star'] = $threeResult ? $threeResult['count'] : 0;

$twoResult = $db->fetchOne("SELECT COUNT(*) as count FROM feedback WHERE is_approved = true AND rating = 2");
$stats['two_star'] = $twoResult ? $twoResult['count'] : 0;

$oneResult = $db->fetchOne("SELECT COUNT(*) as count FROM feedback WHERE is_approved = true AND rating = 1");
$stats['one_star'] = $oneResult ? $oneResult['count'] : 0;

// Get featured feedback
$featuredFeedback = $db->fetchAll(
    "SELECT * FROM feedback WHERE is_approved = true AND rating >= 4 ORDER BY created_at DESC LIMIT 3"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Feedback - GETC Ltd | Global Electrical Technology Company</title>
    <meta name="description" content="Read what our customers say about GETC Ltd. See their experiences with our electrical technology products and services.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Feedback Page Specific Styles */
        .page-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-orange));
            color: var(--white);
            padding: 100px 0 60px;
            margin-top: 70px;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .page-header p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .breadcrumb {
            margin-top: 1rem;
        }
        
        .breadcrumb a {
            color: var(--white);
            text-decoration: none;
            opacity: 0.8;
            transition: 0.3s;
        }
        
        .breadcrumb a:hover {
            opacity: 1;
        }
        
        /* Stats Section */
        .stats-section {
            padding: 60px 0;
            background: var(--light-gray);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            text-align: center;
        }
        
        .stat-card {
            background: var(--white);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary-blue);
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1rem;
        }
        
        .stat-rating {
            color: var(--yellow-accent);
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .stat-rating .average {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-blue);
            margin-right: 10px;
        }
        
        /* Rating Distribution */
        .rating-distribution {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .rating-label {
            width: 60px;
            color: #666;
        }
        
        .bar-container {
            flex: 1;
            height: 20px;
            background: #eee;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 100%;
            background: var(--secondary-orange);
            border-radius: 10px;
            transition: width 0.3s;
        }
        
        .rating-count {
            width: 50px;
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Feedback Filters */
        .filters-section {
            padding: 30px 0;
            background: var(--white);
            border-bottom: 1px solid #eee;
        }
        
        .filters-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .rating-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .rating-filter {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: var(--dark-gray);
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .rating-filter:hover,
        .rating-filter.active {
            background: var(--secondary-orange);
            color: var(--white);
            border-color: var(--secondary-orange);
        }
        
        .rating-filter i {
            color: var(--yellow-accent);
        }
        
        .sort-select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .sort-select:focus {
            outline: none;
            border-color: var(--secondary-orange);
        }
        
        /* Feedback Grid */
        .feedback-section {
            padding: 60px 0;
        }
        
        .feedback-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .feedback-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
            position: relative;
        }
        
        .feedback-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .feedback-card.featured {
            border: 2px solid var(--secondary-orange);
        }
        
        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--secondary-orange);
            color: var(--white);
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 1;
        }
        
        .feedback-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-orange));
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .customer-image {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid var(--white);
            overflow: hidden;
        }
        
        .customer-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .customer-initials {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: bold;
            border: 3px solid var(--white);
        }
        
        .customer-info h3 {
            margin-bottom: 5px;
            font-size: 1.2rem;
        }
        
        .customer-info p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .feedback-body {
            padding: 20px;
        }
        
        .feedback-rating {
            margin-bottom: 15px;
        }
        
        .feedback-rating i {
            color: var(--yellow-accent);
            font-size: 1.1rem;
            margin-right: 2px;
        }
        
        .feedback-rating i.far {
            color: #ddd;
        }
        
        .feedback-text {
            color: #666;
            line-height: 1.8;
            margin-bottom: 15px;
            font-style: italic;
        }
        
        .feedback-text::before,
        .feedback-text::after {
            content: '"';
            color: var(--secondary-orange);
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .feedback-date {
            color: #999;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .feedback-date i {
            color: var(--secondary-orange);
        }
        
        /* Testimonial Slider */
        .testimonials-section {
            background: var(--light-gray);
            padding: 60px 0;
        }
        
        .testimonial-slider {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
            overflow: hidden;
        }
        
        .testimonial-track {
            display: flex;
            transition: transform 0.5s ease;
        }
        
        .testimonial-slide {
            flex: 0 0 100%;
            padding: 20px;
        }
        
        .testimonial-card {
            background: var(--white);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
        }
        
        .quote-icon {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 4rem;
            color: var(--secondary-orange);
            opacity: 0.1;
        }
        
        .testimonial-card p {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .testimonial-author img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .slider-controls {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .slider-control {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--white);
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: 0.3s;
        }
        
        .slider-control:hover {
            background: var(--secondary-orange);
            color: var(--white);
        }
        
        /* Submit Feedback Form */
        .submit-section {
            padding: 60px 0;
            background: var(--white);
        }
        
        .submit-container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--light-gray);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .submit-container h2 {
            text-align: center;
            color: var(--primary-blue);
            margin-bottom: 30px;
        }
        
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
            margin-bottom: 20px;
        }
        
        .rating-input input {
            display: none;
        }
        
        .rating-input label {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input:checked ~ label {
            color: var(--yellow-accent);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-orange);
            box-shadow: 0 0 0 3px rgba(241, 108, 32, 0.1);
        }
        
        .form-group input[type="file"] {
            padding: 8px;
            border: 2px dashed #ddd;
            background: var(--white);
        }
        
        .form-group input[type="file"]:hover {
            border-color: var(--secondary-orange);
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: var(--secondary-orange);
            color: var(--white);
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .btn-submit:hover {
            background: var(--primary-blue);
        }
        
        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 40px;
        }
        
        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 5px;
            background: var(--white);
            color: var(--dark-gray);
            text-decoration: none;
            transition: 0.3s;
            border: 1px solid #eee;
        }
        
        .pagination a:hover {
            background: var(--secondary-orange);
            color: var(--white);
            border-color: var(--secondary-orange);
        }
        
        .pagination .active {
            background: var(--primary-blue);
            color: var(--white);
            border-color: var(--primary-blue);
        }
        
        /* No Results */
        .no-results {
            text-align: center;
            padding: 60px 20px;
        }
        
        .no-results i {
            font-size: 4rem;
            color: var(--secondary-orange);
            margin-bottom: 20px;
        }
        
        .no-results h3 {
            font-size: 1.5rem;
            color: var(--dark-gray);
            margin-bottom: 10px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .feedback-grid {
                grid-template-columns: 1fr;
            }
            
            .filters-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .submit-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <span style="color: <?php echo PRIMARY_BLUE; ?>;">GETC</span>
                    <span style="color: <?php echo SECONDARY_ORANGE; ?>;">Ltd</span>
                </a>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php#home">Home</a></li>
                <li><a href="index.php#about">About</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="projects.php">Projects</a></li>
                <li><a href="videos.php">Videos</a></li>
                <li><a href="feedback.php" class="active">Feedback</a></li>
                <li><a href="index.php#contact">Contact</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Customer Feedback</h1>
            <p>See what our clients say about our products and services</p>
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <span>Feedback</span>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Reviews</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-rating">
                        <span class="average"><?php echo $stats['avg_rating']; ?></span>
                        <span>/5</span>
                    </div>
                    <div>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star" style="color: <?php echo $i <= round($stats['avg_rating']) ? '#ffd700' : '#ddd'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="stat-label">Average Rating</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['five_star']; ?></div>
                    <div class="stat-label">5-Star Reviews</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total'] > 0 ? round(($stats['five_star'] / $stats['total']) * 100) : 0; ?>%</div>
                    <div class="stat-label">Satisfaction Rate</div>
                </div>
            </div>
            
            <!-- Rating Distribution -->
            <div class="rating-distribution">
                <h3 style="margin-bottom: 15px; color: var(--primary-blue);">Rating Distribution</h3>
                <?php for ($i = 5; $i >= 1; $i--): 
                    $count = $i == 5 ? $stats['five_star'] : 
                            ($i == 4 ? $stats['four_star'] : 
                            ($i == 3 ? $stats['three_star'] : 
                            ($i == 2 ? $stats['two_star'] : $stats['one_star'])));
                    $percentage = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
                ?>
                <div class="rating-bar">
                    <span class="rating-label"><?php echo $i; ?> Star</span>
                    <div class="bar-container">
                        <div class="bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <span class="rating-count"><?php echo $count; ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- Filters Section -->
    <section class="filters-section">
        <div class="container">
            <div class="filters-container">
                <div class="rating-filters">
                    <a href="feedback.php" class="rating-filter <?php echo !$rating ? 'active' : ''; ?>">
                        All Reviews
                    </a>
                    <a href="?rating=5" class="rating-filter <?php echo $rating == 5 ? 'active' : ''; ?>">
                        5 <i class="fas fa-star"></i>
                    </a>
                    <a href="?rating=4" class="rating-filter <?php echo $rating == 4 ? 'active' : ''; ?>">
                        4 <i class="fas fa-star"></i>
                    </a>
                    <a href="?rating=3" class="rating-filter <?php echo $rating == 3 ? 'active' : ''; ?>">
                        3 <i class="fas fa-star"></i>
                    </a>
                    <a href="?rating=2" class="rating-filter <?php echo $rating == 2 ? 'active' : ''; ?>">
                        2 <i class="fas fa-star"></i>
                    </a>
                    <a href="?rating=1" class="rating-filter <?php echo $rating == 1 ? 'active' : ''; ?>">
                        1 <i class="fas fa-star"></i>
                    </a>
                </div>
                
                <select class="sort-select" onchange="sortFeedback(this.value)">
                    <option value="latest" <?php echo $sort == 'latest' ? 'selected' : ''; ?>>Most Recent</option>
                    <option value="highest" <?php echo $sort == 'highest' ? 'selected' : ''; ?>>Highest Rating</option>
                    <option value="lowest" <?php echo $sort == 'lowest' ? 'selected' : ''; ?>>Lowest Rating</option>
                </select>
            </div>
        </div>
    </section>

    <!-- Feedback Grid -->
    <section class="feedback-section">
        <div class="container">
            <?php if (count($feedbackList) > 0): ?>
            <div class="feedback-grid">
                <?php foreach ($feedbackList as $feedback): ?>
                <div class="feedback-card <?php echo in_array($feedback['id'], array_column($featuredFeedback, 'id')) ? 'featured' : ''; ?>">
                    <?php if (in_array($feedback['id'], array_column($featuredFeedback, 'id'))): ?>
                    <div class="featured-badge">
                        <i class="fas fa-star"></i> Featured
                    </div>
                    <?php endif; ?>
                    
                    <div class="feedback-header">
                        <?php if ($feedback['image_path']): ?>
                        <div class="customer-image">
                            <img src="<?php echo UPLOAD_URL . $feedback['image_path']; ?>" 
                                 alt="<?php echo htmlspecialchars($feedback['customer_name']); ?>">
                        </div>
                        <?php else: ?>
                        <div class="customer-initials">
                            <?php echo strtoupper(substr($feedback['customer_name'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="customer-info">
                            <h3><?php echo htmlspecialchars($feedback['customer_name']); ?></h3>
                            <?php if ($feedback['company']): ?>
                            <p><?php echo htmlspecialchars($feedback['company']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="feedback-body">
                        <div class="feedback-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $feedback['rating']): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        
                        <p class="feedback-text"><?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?></p>
                        
                        <div class="feedback-date">
                            <i class="fas fa-calendar-alt"></i>
                            <?php echo formatDate($feedback['created_at']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
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
            
            <?php else: ?>
            <!-- No Results -->
            <div class="no-results">
                <i class="fas fa-comment-slash"></i>
                <h3>No Feedback Found</h3>
                <p>Be the first to share your experience with us!</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Featured Testimonials Slider -->
    <?php if (!empty($featuredFeedback)): ?>
    <section class="testimonials-section">
        <div class="container">
            <h2 class="section-title">Featured <span>Testimonials</span></h2>
            
            <div class="testimonial-slider">
                <div class="testimonial-track" id="testimonialTrack">
                    <?php foreach ($featuredFeedback as $testimonial): ?>
                    <div class="testimonial-slide">
                        <div class="testimonial-card">
                            <div class="quote-icon">
                                <i class="fas fa-quote-right"></i>
                            </div>
                            <p><?php echo htmlspecialchars($testimonial['feedback_text']); ?></p>
                            
                            <div class="testimonial-author">
                                <?php if ($testimonial['image_path']): ?>
                                <img src="<?php echo UPLOAD_URL . $testimonial['image_path']; ?>" 
                                     alt="<?php echo htmlspecialchars($testimonial['customer_name']); ?>">
                                <?php endif; ?>
                                <div>
                                    <h4><?php echo htmlspecialchars($testimonial['customer_name']); ?></h4>
                                    <?php if ($testimonial['company']): ?>
                                    <p><?php echo htmlspecialchars($testimonial['company']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="margin-top: 15px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" style="color: <?php echo $i <= $testimonial['rating'] ? '#ffd700' : '#ddd'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="slider-controls">
                    <button class="slider-control" onclick="prevSlide()">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="slider-control" onclick="nextSlide()">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Submit Feedback Form -->
    <section class="submit-section">
        <div class="container">
            <div class="submit-container">
                <h2>Share Your Experience</h2>
                
                <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="rating-input">
                        <input type="radio" name="rating" value="5" id="star5" checked>
                        <label for="star5"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" name="rating" value="4" id="star4">
                        <label for="star4"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" name="rating" value="3" id="star3">
                        <label for="star3"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" name="rating" value="2" id="star2">
                        <label for="star2"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" name="rating" value="1" id="star1">
                        <label for="star1"><i class="fas fa-star"></i></label>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="customer_name" placeholder="Your Name *" required>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="company" placeholder="Company Name">
                    </div>
                    
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Your Email *" required>
                    </div>
                    
                    <div class="form-group">
                        <textarea name="feedback_text" placeholder="Your Feedback *" rows="5" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_image" style="display: block; margin-bottom: 5px; color: #666;">
                            Your Photo (Optional)
                        </label>
                        <input type="file" name="customer_image" id="customer_image" accept="image/*">
                    </div>
                    
                    <button type="submit" name="submit_feedback" class="btn-submit">
                        Submit Feedback
                    </button>
                </form>
                
                <p style="text-align: center; margin-top: 20px; color: #666; font-size: 0.9rem;">
                    Your feedback will be reviewed before appearing on the site.
                </p>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section style="background: var(--primary-blue); color: var(--white); padding: 60px 0; text-align: center;">
        <div class="container">
            <h2 style="font-size: 2rem; margin-bottom: 20px;">Ready to Work With Us?</h2>
            <p style="font-size: 1.1rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto;">
                Join our satisfied customers and experience the GETC difference.
            </p>
            <a href="index.php#contact" class="btn btn-primary" style="background: var(--white); color: var(--primary-blue);">
                Contact Us Today
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>GETC Ltd</h3>
                    <p>Global Electrical Technology Company Limited - Providing innovative electrical solutions worldwide.</p>
                </div>
                
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php#home">Home</a></li>
                        <li><a href="index.php#about">About</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="services.php">Services</a></li>
                        <li><a href="projects.php">Projects</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Feedback</h4>
                    <ul>
                        <li><a href="#submit">Submit Feedback</a></li>
                        <li><a href="?rating=5">5-Star Reviews</a></li>
                        <li><a href="?sort=latest">Latest Reviews</a></li>
                        <li><a href="?sort=highest">Top Rated</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Newsletter</h4>
                    <p>Subscribe to get updates on our latest products and services.</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Your Email">
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> GETC Ltd. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp -->
    <?php 
    $whatsapp = getSetting('whatsapp_number');
    if ($whatsapp): 
    ?>
    <a href="https://wa.me/<?php echo $whatsapp; ?>" class="floating-whatsapp" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>
    <?php endif; ?>

    <script src="js/main.js"></script>
    <script>
        // Sort feedback
        function sortFeedback(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
        
        // Testimonial slider
        let currentSlide = 0;
        const track = document.getElementById('testimonialTrack');
        const slides = document.querySelectorAll('.testimonial-slide');
        const totalSlides = slides.length;
        
        function updateSlider() {
            if (track) {
                track.style.transform = `translateX(-${currentSlide * 100}%)`;
            }
        }
        
        function nextSlide() {
            if (currentSlide < totalSlides - 1) {
                currentSlide++;
            } else {
                currentSlide = 0;
            }
            updateSlider();
        }
        
        function prevSlide() {
            if (currentSlide > 0) {
                currentSlide--;
            } else {
                currentSlide = totalSlides - 1;
            }
            updateSlider();
        }
        
        // Auto advance slides every 5 seconds
        if (totalSlides > 1) {
            setInterval(nextSlide, 5000);
        }
        
        // Preview uploaded image
        document.getElementById('customer_image')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You can preview the image here if needed
                    console.log('Image selected:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const name = this.querySelector('input[name="customer_name"]').value.trim();
            const email = this.querySelector('input[name="email"]').value.trim();
            const feedback = this.querySelector('textarea[name="feedback_text"]').value.trim();
            
            if (!name || !email || !feedback) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
        
        // Rating stars hover effect
        const ratingLabels = document.querySelectorAll('.rating-input label');
        ratingLabels.forEach(label => {
            label.addEventListener('mouseenter', function() {
                const current = this;
                const allLabels = document.querySelectorAll('.rating-input label');
                allLabels.forEach(l => l.style.color = '#ddd');
                
                let found = false;
                allLabels.forEach(l => {
                    if (l === current || found) {
                        l.style.color = '#ffd700';
                    }
                    if (l === current) {
                        found = true;
                    }
                });
            });
            
            label.addEventListener('mouseleave', function() {
                const checked = document.querySelector('.rating-input input:checked');
                const allLabels = document.querySelectorAll('.rating-input label');
                
                allLabels.forEach(l => l.style.color = '#ddd');
                
                if (checked) {
                    let found = false;
                    allLabels.forEach(l => {
                        if (l.htmlFor === checked.id || found) {
                            l.style.color = '#ffd700';
                        }
                        if (l.htmlFor === checked.id) {
                            found = true;
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>