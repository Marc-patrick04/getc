<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$featured = isset($_GET['featured']) ? (bool)$_GET['featured'] : false;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 9;
$offset = ($page - 1) * $itemsPerPage;

// Build query
$sql = "SELECT * FROM services WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM services WHERE 1=1";
$params = [];
$countParams = [];

if ($category) {
    $sql .= " AND category = ?";
    $countSql .= " AND category = ?";
    $params[] = $category;
    $countParams[] = $category;
}

if ($featured) {
    $sql .= " AND is_featured = true";
    $countSql .= " AND is_featured = true";
}

if ($search) {
    $sql .= " AND (name ILIKE ? OR description ILIKE ?)";
    $countSql .= " AND (name ILIKE ? OR description ILIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
}

// Get total count for pagination
$totalResult = $db->fetchOne($countSql, $countParams);
$totalItems = $totalResult ? $totalResult['total'] : 0;
$totalPages = ceil($totalItems / $itemsPerPage);

// Add sorting and pagination to main query
$sql .= " ORDER BY display_order, name LIMIT ? OFFSET ?";
$params[] = $itemsPerPage;
$params[] = $offset;

// Get services
$services = $db->fetchAll($sql, $params);

// Get unique categories for filter
$categories = $db->fetchAll("SELECT DISTINCT category FROM services WHERE category IS NOT NULL AND category != '' ORDER BY category");

// Get service statistics
$stats = [
    'total' => $totalItems,
    'featured' => $db->fetchOne("SELECT COUNT(*) as count FROM services WHERE is_featured = true")['count'] ?? 0,
];

// Get featured services
$featuredServices = $db->fetchAll(
    "SELECT * FROM services WHERE is_featured = true ORDER BY display_order LIMIT 4"
);

// Get popular services (you can customize this based on your needs)
$popularServices = $db->fetchAll(
    "SELECT * FROM services ORDER BY display_order LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - GETC Ltd | Global Electrical Technology Company</title>
    <meta name="description" content="Explore our comprehensive range of electrical technology services. GETC Ltd provides innovative solutions for all your electrical needs.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Services Page Specific Styles */
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

        /* Intro Section */
        .intro-section {
            padding: 60px 0;
            background: var(--white);
        }
        
        .intro-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        
        .intro-content h2 {
            color: var(--primary-blue);
            font-size: 2rem;
            margin-bottom: 20px;
        }
        
        .intro-content p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 30px;
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
        
        .stat-icon {
            font-size: 2rem;
            color: var(--secondary-orange);
            margin-bottom: 15px;
        }

        /* Services Layout */
        .services-section {
            padding: 60px 0;
        }
        
        .services-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 40px;
        }
        
        /* Filters Bar */
        .filters-bar {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .filter-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            min-width: 150px;
        }
        
        .filter-select:focus {
            border-color: var(--secondary-orange);
            outline: none;
        }
        
        .filter-checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 10px 15px;
            background: #f5f5f5;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .filter-checkbox input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .filter-checkbox label {
            cursor: pointer;
            color: #666;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            min-width: 250px;
        }
        
        .search-box input:focus {
            border-color: var(--secondary-orange);
            outline: none;
        }
        
        .search-box button {
            padding: 10px 20px;
            background: var(--secondary-orange);
            color: var(--white);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .search-box button:hover {
            background: var(--primary-blue);
        }
        
        .reset-filters {
            color: var(--secondary-orange);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .service-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .service-badge {
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
        
        .service-badge.featured {
            background: var(--primary-blue);
            left: 10px;
            right: auto;
        }
        
        .service-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .service-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .service-card:hover .service-image img {
            transform: scale(1.1);
        }
        
        .service-icon {
            position: absolute;
            bottom: -25px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: var(--secondary-orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.8rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: 0.3s;
            z-index: 2;
        }
        
        .service-card:hover .service-icon {
            transform: rotate(360deg);
            background: var(--primary-blue);
        }
        
        .service-content {
            padding: 30px 20px 20px;
            flex: 1;
        }
        
        .service-category {
            color: var(--secondary-orange);
            font-size: 0.9rem;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .service-content h3 {
            color: var(--primary-blue);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .service-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .service-features {
            list-style: none;
            margin-bottom: 20px;
        }
        
        .service-features li {
            color: #666;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        
        .service-features i {
            color: var(--secondary-orange);
            font-size: 0.8rem;
        }
        
        .service-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .service-link {
            color: var(--secondary-orange);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
        }
        
        .service-link:hover {
            gap: 10px;
            color: var(--primary-blue);
        }
        
        /* Sidebar */
        .services-sidebar {
            background: var(--white);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .sidebar-section {
            margin-bottom: 30px;
        }
        
        .sidebar-section h3 {
            color: var(--primary-blue);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--secondary-orange);
            font-size: 1.2rem;
        }
        
        .category-list {
            list-style: none;
        }
        
        .category-list li {
            margin-bottom: 10px;
        }
        
        .category-list a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #666;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 5px;
            transition: 0.3s;
        }
        
        .category-list a:hover,
        .category-list a.active {
            background: var(--secondary-orange);
            color: var(--white);
        }
        
        .category-list a:hover .count,
        .category-list a.active .count {
            background: var(--white);
            color: var(--secondary-orange);
        }
        
        .category-list .count {
            background: #eee;
            color: #666;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            transition: 0.3s;
        }
        
        .popular-service {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .popular-icon {
            width: 50px;
            height: 50px;
            background: var(--light-gray);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-orange);
            font-size: 1.5rem;
        }
        
        .popular-info h4 {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .popular-info h4 a {
            color: var(--dark-gray);
            text-decoration: none;
        }
        
        .popular-info h4 a:hover {
            color: var(--secondary-orange);
        }
        
        .popular-info p {
            color: #999;
            font-size: 0.85rem;
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
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        /* Featured Services */
        .featured-section {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-orange));
            color: var(--white);
            padding: 60px 0;
        }
        
        .featured-section .section-title {
            color: var(--white);
        }
        
        .featured-section .section-title span {
            color: var(--yellow-accent);
        }
        
        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .featured-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .featured-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.2);
        }
        
        .featured-icon {
            width: 80px;
            height: 80px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--secondary-orange);
            font-size: 2.5rem;
        }
        
        .featured-card h3 {
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .featured-card p {
            opacity: 0.9;
            line-height: 1.6;
        }
        
        /* Process Section */
        .process-section {
            padding: 60px 0;
            background: var(--white);
        }
        
        .process-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .process-step {
            text-align: center;
            position: relative;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background: var(--secondary-orange);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0 auto 20px;
            position: relative;
            z-index: 1;
        }
        
        .process-step:not(:last-child) .step-number::after {
            content: '';
            position: absolute;
            top: 30px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--secondary-orange);
            opacity: 0.3;
            z-index: -1;
        }
        
        .process-step h3 {
            color: var(--primary-blue);
            margin-bottom: 10px;
        }
        
        .process-step p {
            color: #666;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .services-layout {
                grid-template-columns: 1fr;
            }
            
            .services-sidebar {
                order: -1;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                flex-direction: column;
            }
            
            .search-box input {
                min-width: auto;
            }
            
            .process-step:not(:last-child) .step-number::after {
                display: none;
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
                <li><a href="services.php" class="active">Services</a></li>
                <li><a href="projects.php">Projects</a></li>
                <li><a href="videos.php">Videos</a></li>
                <li><a href="feedback.php">Feedback</a></li>
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
            <h1>Our Services</h1>
            <p>Comprehensive electrical technology solutions tailored to your needs</p>
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <span>Services</span>
            </div>
        </div>
    </section>

    <!-- Intro Section -->
    <section class="intro-section">
        <div class="container">
            <div class="intro-content">
                <h2>What We Offer</h2>
                <p>At GETC Ltd, we provide a comprehensive range of electrical technology services designed to meet the evolving needs of modern industries. From consultation and design to installation and maintenance, our expert team delivers innovative solutions that drive efficiency, reliability, and sustainability.</p>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Services</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['featured']; ?></div>
                    <div class="stat-label">Featured Services</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Happy Clients</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Services Section -->
    <section class="services-section">
        <div class="container">
            <div class="services-layout">
                <!-- Main Content -->
                <div class="main-content">
                    <!-- Filters Bar -->
                    <div class="filters-bar">
                        <div class="filter-group">
                            <select class="filter-select" onchange="filterByCategory(this.value)">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <?php if ($cat['category']): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                            <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="filter-checkbox">
                                <input type="checkbox" id="featuredFilter" onchange="filterByFeatured(this.checked)" <?php echo $featured ? 'checked' : ''; ?>>
                                <label for="featuredFilter">Featured Only</label>
                            </div>
                        </div>
                        
                        <div class="search-box">
                            <form method="GET" id="searchForm">
                                <?php if ($category): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                                <?php endif; ?>
                                <?php if ($featured): ?>
                                <input type="hidden" name="featured" value="1">
                                <?php endif; ?>
                                <input type="text" name="search" placeholder="Search services..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit"><i class="fas fa-search"></i></button>
                            </form>
                        </div>
                        
                        <?php if ($category || $featured || $search): ?>
                        <a href="services.php" class="reset-filters">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Results Count -->
                    <p style="margin-bottom: 20px; color: #666;">
                        Showing <?php echo count($services); ?> of <?php echo $totalItems; ?> services
                    </p>

                    <!-- Services Grid -->
                    <?php if (count($services) > 0): ?>
                    <div class="services-grid">
                        <?php foreach ($services as $service): ?>
                        <div class="service-card">
                            <?php if ($service['is_featured']): ?>
                            <div class="service-badge featured">
                                <i class="fas fa-star"></i> Featured
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($service['category']): ?>
                            <div class="service-category"><?php echo htmlspecialchars($service['category']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($service['image_path']): ?>
                            <div class="service-image">
                                <img src="<?php echo UPLOAD_URL . $service['image_path']; ?>" 
                                     alt="<?php echo htmlspecialchars($service['name']); ?>">
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($service['icon']): ?>
                            <div class="service-icon">
                                <i class="fas fa-<?php echo htmlspecialchars($service['icon']); ?>"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="service-content">
                                <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                                <p class="service-description">
                                    <?php echo truncateText($service['description'], 100); ?>
                                </p>
                                
                                <!-- Sample features (you can add a features field to your database) -->
                                <ul class="service-features">
                                    <li><i class="fas fa-check-circle"></i> Expert consultation</li>
                                    <li><i class="fas fa-check-circle"></i> Customized solutions</li>
                                    <li><i class="fas fa-check-circle"></i> 24/7 support</li>
                                </ul>
                            </div>
                            
                            <div class="service-footer">
                                <a href="service-detail.php?id=<?php echo $service['id']; ?>" class="service-link">
                                    Learn More <i class="fas fa-arrow-right"></i>
                                </a>
                                <button class="btn-link" onclick="inquireService(<?php echo $service['id']; ?>)">
                                    <i class="fas fa-envelope"></i>
                                </button>
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
                        <i class="fas fa-tools"></i>
                        <h3>No Services Found</h3>
                        <p>We couldn't find any services matching your criteria.</p>
                        <a href="services.php" class="btn btn-primary">View All Services</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="services-sidebar">
                    <!-- Categories -->
                    <?php if (!empty($categories)): ?>
                    <div class="sidebar-section">
                        <h3><i class="fas fa-folder"></i> Categories</h3>
                        <ul class="category-list">
                            <li>
                                <a href="services.php" class="<?php echo !$category ? 'active' : ''; ?>">
                                    <span>All Categories</span>
                                    <span class="count"><?php echo $stats['total']; ?></span>
                                </a>
                            </li>
                            <?php foreach ($categories as $cat): ?>
                                <?php if ($cat['category']): 
                                    $catCount = $db->fetchOne("SELECT COUNT(*) as count FROM services WHERE category = ?", [$cat['category']])['count'];
                                ?>
                                <li>
                                    <a href="?category=<?php echo urlencode($cat['category']); ?>" 
                                       class="<?php echo $category == $cat['category'] ? 'active' : ''; ?>">
                                        <span><?php echo htmlspecialchars($cat['category']); ?></span>
                                        <span class="count"><?php echo $catCount; ?></span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Popular Services -->
                    <?php if (!empty($popularServices)): ?>
                    <div class="sidebar-section">
                        <h3><i class="fas fa-fire"></i> Popular Services</h3>
                        <?php foreach ($popularServices as $service): ?>
                        <div class="popular-service">
                            <div class="popular-icon">
                                <i class="fas fa-<?php echo $service['icon'] ?: 'cog'; ?>"></i>
                            </div>
                            <div class="popular-info">
                                <h4><a href="service-detail.php?id=<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></a></h4>
                                <p><?php echo truncateText($service['description'], 50); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Why Choose Us -->
                    <div class="sidebar-section">
                        <h3><i class="fas fa-check-circle"></i> Why Choose Us</h3>
                        <ul style="list-style: none;">
                            <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-medal" style="color: var(--secondary-orange);"></i>
                                <span>10+ Years Experience</span>
                            </li>
                            <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-users" style="color: var(--secondary-orange);"></i>
                                <span>Expert Team</span>
                            </li>
                            <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-clock" style="color: var(--secondary-orange);"></i>
                                <span>24/7 Support</span>
                            </li>
                            <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-globe" style="color: var(--secondary-orange);"></i>
                                <span>Global Reach</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Contact CTA -->
                    <div class="sidebar-section">
                        <div style="background: linear-gradient(135deg, var(--primary-blue), var(--secondary-orange)); padding: 25px; border-radius: 10px; color: var(--white); text-align: center;">
                            <i class="fas fa-headset" style="font-size: 3rem; margin-bottom: 15px;"></i>
                            <h4 style="margin-bottom: 10px;">Need Help?</h4>
                            <p style="margin-bottom: 15px; font-size: 0.9rem;">Our experts are ready to assist you</p>
                            <a href="index.php#contact" class="btn" style="background: var(--white); color: var(--primary-blue);">
                                Contact Us
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Services Section -->
    <?php if (!empty($featuredServices)): ?>
    <section class="featured-section">
        <div class="container">
            <h2 class="section-title">Featured <span>Services</span></h2>
            <div class="featured-grid">
                <?php foreach ($featuredServices as $service): ?>
                <div class="featured-card">
                    <div class="featured-icon">
                        <i class="fas fa-<?php echo $service['icon'] ?: 'star'; ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p><?php echo truncateText($service['description'], 100); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Process Section -->
    <section class="process-section">
        <div class="container">
            <h2 class="section-title">Our <span>Process</span></h2>
            <div class="process-steps">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <h3>Consultation</h3>
                    <p>We discuss your requirements and understand your specific needs</p>
                </div>
                <div class="process-step">
                    <div class="step-number">2</div>
                    <h3>Planning</h3>
                    <p>Our experts develop a customized solution strategy</p>
                </div>
                <div class="process-step">
                    <div class="step-number">3</div>
                    <h3>Execution</h3>
                    <p>We implement the solution with precision and care</p>
                </div>
                <div class="process-step">
                    <div class="step-number">4</div>
                    <h3>Support</h3>
                    <p>Ongoing maintenance and support for your peace of mind</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section style="background: var(--light-gray); padding: 60px 0; text-align: center;">
        <div class="container">
            <h2 style="font-size: 2rem; margin-bottom: 20px; color: var(--primary-blue);">Ready to Get Started?</h2>
            <p style="font-size: 1.1rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto; color: #666;">
                Contact us today to discuss how our services can benefit your business.
            </p>
            <a href="index.php#contact" class="btn btn-primary">
                Request a Consultation
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
                    <h4>Our Services</h4>
                    <ul>
                        <?php 
                        $footerServices = array_slice($services, 0, 5);
                        foreach ($footerServices as $service): 
                        ?>
                        <li><a href="service-detail.php?id=<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Newsletter</h4>
                    <p>Subscribe to get updates on our latest services.</p>
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
    <a href="https://wa.me/<?php echo $whatsapp; ?>?text=I'm interested in your services" class="floating-whatsapp" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>
    <?php endif; ?>

    <script src="js/main.js"></script>
    <script>
        // Filter functions
        function filterByCategory(category) {
            const url = new URL(window.location.href);
            if (category) {
                url.searchParams.set('category', category);
            } else {
                url.searchParams.delete('category');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
        
        function filterByFeatured(checked) {
            const url = new URL(window.location.href);
            if (checked) {
                url.searchParams.set('featured', '1');
            } else {
                url.searchParams.delete('featured');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
        
        // Inquire about service
        function inquireService(serviceId) {
            window.location.href = 'index.php#contact?service=' + serviceId;
        }
        
        // Handle search form
        document.getElementById('searchForm')?.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="search"]');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                window.location.href = url.toString();
            }
        });
        
        // Lazy load images
        const images = document.querySelectorAll('.service-image img');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    </script>
</body>
</html>