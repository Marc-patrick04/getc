<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 9;
$offset = ($page - 1) * $itemsPerPage;

// Build query
$sql = "SELECT * FROM projects WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM projects WHERE 1=1";
$params = [];
$countParams = [];

if ($category) {
    $sql .= " AND category = ?";
    $countSql .= " AND category = ?";
    $params[] = $category;
    $countParams[] = $category;
}

if ($status) {
    $sql .= " AND status = ?";
    $countSql .= " AND status = ?";
    $params[] = $status;
    $countParams[] = $status;
}

if ($year > 0) {
    $sql .= " AND EXTRACT(YEAR FROM completion_date) = ?";
    $countSql .= " AND EXTRACT(YEAR FROM completion_date) = ?";
    $params[] = $year;
    $countParams[] = $year;
}

if ($search) {
    $sql .= " AND (title ILIKE ? OR description ILIKE ? OR client_name ILIKE ?)";
    $countSql .= " AND (title ILIKE ? OR description ILIKE ? OR client_name ILIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
}

// Get total count for pagination
$totalResult = $db->fetchOne($countSql, $countParams);
$totalItems = $totalResult ? $totalResult['total'] : 0;
$totalPages = ceil($totalItems / $itemsPerPage);

// Add sorting and pagination to main query
$sql .= " ORDER BY completion_date DESC, display_order LIMIT ? OFFSET ?";
$params[] = $itemsPerPage;
$params[] = $offset;

// Get projects
$projects = $db->fetchAll($sql, $params);

// Get unique categories for filter
$categories = $db->fetchAll("SELECT DISTINCT category FROM projects WHERE category IS NOT NULL AND category != '' ORDER BY category");

// Get unique years for filter
$years = $db->fetchAll("SELECT DISTINCT EXTRACT(YEAR FROM completion_date) as year FROM projects WHERE completion_date IS NOT NULL ORDER BY year DESC");

// Get project statistics
$stats = [
    'total' => $totalItems,
    'completed' => $db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE status = 'completed'")['count'] ?? 0,
    'ongoing' => $db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE status = 'ongoing'")['count'] ?? 0,
    'upcoming' => $db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE status = 'upcoming'")['count'] ?? 0,
];

// Get featured projects
$featuredProjects = $db->fetchAll(
    "SELECT * FROM projects WHERE is_featured = true ORDER BY completion_date DESC LIMIT 3"
);

// Get latest projects for sidebar
$latestProjects = $db->fetchAll(
    "SELECT * FROM projects ORDER BY completion_date DESC LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Projects - GETC Ltd | Global Electrical Technology Company</title>
    <meta name="description" content="Explore our completed and ongoing electrical technology projects. See how GETC Ltd delivers innovative solutions worldwide.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Projects Page Specific Styles */
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
        
        .stat-icon {
            font-size: 2rem;
            color: var(--secondary-orange);
            margin-bottom: 15px;
        }

        /* Projects Layout */
        .projects-section {
            padding: 60px 0;
        }
        
        .projects-layout {
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
        
        /* Projects Grid */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .project-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
            position: relative;
        }
        
        .project-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .project-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 15px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 1;
        }
        
        .project-badge.completed {
            background: #28a745;
            color: var(--white);
        }
        
        .project-badge.ongoing {
            background: var(--secondary-orange);
            color: var(--white);
        }
        
        .project-badge.upcoming {
            background: var(--primary-blue);
            color: var(--white);
        }
        
        .project-badge.featured {
            background: #ffd700;
            color: var(--dark-gray);
            left: 10px;
            right: auto;
        }
        
        .project-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .project-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .project-card:hover .project-image img {
            transform: scale(1.1);
        }
        
        .project-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: 0.3s;
        }
        
        .project-card:hover .project-overlay {
            opacity: 1;
        }
        
        .project-overlay .btn {
            transform: translateY(20px);
            transition: 0.3s;
        }
        
        .project-card:hover .project-overlay .btn {
            transform: translateY(0);
        }
        
        .project-info {
            padding: 20px;
        }
        
        .project-category {
            color: var(--secondary-orange);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .project-info h3 {
            color: var(--primary-blue);
            margin-bottom: 10px;
            font-size: 1.3rem;
        }
        
        .project-client {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }
        
        .project-client i {
            color: var(--secondary-orange);
        }
        
        .project-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .project-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .project-date {
            color: #999;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .project-date i {
            color: var(--secondary-orange);
        }
        
        .project-link {
            color: var(--secondary-orange);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
        }
        
        .project-link:hover {
            gap: 10px;
            color: var(--primary-blue);
        }
        
        /* Sidebar */
        .projects-sidebar {
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
        
        .year-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .year-list a {
            padding: 5px 15px;
            background: #f5f5f5;
            border-radius: 5px;
            color: #666;
            text-decoration: none;
            transition: 0.3s;
        }
        
        .year-list a:hover,
        .year-list a.active {
            background: var(--secondary-orange);
            color: var(--white);
        }
        
        .latest-project {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .latest-thumb {
            width: 80px;
            height: 80px;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .latest-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .latest-info h4 {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .latest-info h4 a {
            color: var(--dark-gray);
            text-decoration: none;
        }
        
        .latest-info h4 a:hover {
            color: var(--secondary-orange);
        }
        
        .latest-info p {
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
        
        /* Featured Projects */
        .featured-section {
            background: var(--light-gray);
            padding: 60px 0;
        }
        
        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .featured-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .featured-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .featured-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .featured-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .featured-card:hover .featured-image img {
            transform: scale(1.05);
        }
        
        .featured-content {
            padding: 20px;
            flex: 1;
        }
        
        .featured-content h3 {
            color: var(--primary-blue);
            margin-bottom: 10px;
        }
        
        .featured-content p {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .featured-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .projects-layout {
                grid-template-columns: 1fr;
            }
            
            .projects-sidebar {
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
            
            .featured-grid {
                grid-template-columns: 1fr;
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
                <li><a href="projects.php" class="active">Projects</a></li>
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
            <h1>Our Projects</h1>
            <p>Delivering innovative electrical solutions across the globe</p>
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <span>Projects</span>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Projects</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['completed']; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['ongoing']; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['upcoming']; ?></div>
                    <div class="stat-label">Upcoming</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Projects Section -->
    <section class="projects-section">
        <div class="container">
            <div class="projects-layout">
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
                            
                            <select class="filter-select" onchange="filterByStatus(this.value)">
                                <option value="">All Status</option>
                                <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="ongoing" <?php echo $status == 'ongoing' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="upcoming" <?php echo $status == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            </select>
                            
                            <select class="filter-select" onchange="filterByYear(this.value)">
                                <option value="">All Years</option>
                                <?php foreach ($years as $y): ?>
                                    <?php if ($y['year']): ?>
                                    <option value="<?php echo $y['year']; ?>" 
                                            <?php echo $year == $y['year'] ? 'selected' : ''; ?>>
                                        <?php echo $y['year']; ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="search-box">
                            <form method="GET" id="searchForm">
                                <?php if ($category): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                                <?php endif; ?>
                                <?php if ($status): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                                <?php endif; ?>
                                <?php if ($year): ?>
                                <input type="hidden" name="year" value="<?php echo $year; ?>">
                                <?php endif; ?>
                                <input type="text" name="search" placeholder="Search projects..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit"><i class="fas fa-search"></i></button>
                            </form>
                        </div>
                        
                        <?php if ($category || $status || $year || $search): ?>
                        <a href="projects.php" class="reset-filters">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Results Count -->
                    <p style="margin-bottom: 20px; color: #666;">
                        Showing <?php echo count($projects); ?> of <?php echo $totalItems; ?> projects
                    </p>

                    <!-- Projects Grid -->
                    <?php if (count($projects) > 0): ?>
                    <div class="projects-grid">
                        <?php foreach ($projects as $project): ?>
                        <div class="project-card">
                            <?php if ($project['is_featured']): ?>
                            <div class="project-badge featured">
                                <i class="fas fa-star"></i> Featured
                            </div>
                            <?php endif; ?>
                            
                            <div class="project-badge <?php echo $project['status'] ?? 'completed'; ?>">
                                <?php 
                                switch($project['status'] ?? 'completed') {
                                    case 'ongoing':
                                        echo 'In Progress';
                                        break;
                                    case 'upcoming':
                                        echo 'Upcoming';
                                        break;
                                    default:
                                        echo 'Completed';
                                }
                                ?>
                            </div>
                            
                            <div class="project-image">
                                <img src="<?php echo UPLOAD_URL . ($project['image_path'] ?: 'projects/default-project.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($project['title']); ?>">
                                <div class="project-overlay">
                                    <button class="btn btn-primary" onclick="quickView(<?php echo $project['id']; ?>)">
                                        <i class="fas fa-eye"></i> Quick View
                                    </button>
                                </div>
                            </div>
                            
                            <div class="project-info">
                                <?php if ($project['category']): ?>
                                <div class="project-category">
                                    <i class="fas fa-folder"></i> <?php echo htmlspecialchars($project['category']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                                
                                <?php if ($project['client_name']): ?>
                                <div class="project-client">
                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($project['client_name']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <p class="project-description">
                                    <?php echo truncateText($project['description'], 100); ?>
                                </p>
                                
                                <div class="project-meta">
                                    <?php if ($project['completion_date']): ?>
                                    <span class="project-date">
                                        <i class="fas fa-calendar-alt"></i> 
                                        <?php echo formatDate($project['completion_date'], 'M Y'); ?>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <a href="project-detail.php?id=<?php echo $project['id']; ?>" class="project-link">
                                        Details <i class="fas fa-arrow-right"></i>
                                    </a>
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
                        <i class="fas fa-folder-open"></i>
                        <h3>No Projects Found</h3>
                        <p>We couldn't find any projects matching your criteria.</p>
                        <a href="projects.php" class="btn btn-primary">View All Projects</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="projects-sidebar">
                    <!-- Categories -->
                    <?php if (!empty($categories)): ?>
                    <div class="sidebar-section">
                        <h3><i class="fas fa-folder"></i> Categories</h3>
                        <ul class="category-list">
                            <li>
                                <a href="projects.php" class="<?php echo !$category ? 'active' : ''; ?>">
                                    <span>All Categories</span>
                                    <span class="count"><?php echo $stats['total']; ?></span>
                                </a>
                            </li>
                            <?php foreach ($categories as $cat): ?>
                                <?php if ($cat['category']): 
                                    $catCount = $db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE category = ?", [$cat['category']])['count'];
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

                    <!-- Years -->
                    <?php if (!empty($years)): ?>
                    <div class="sidebar-section">
                        <h3><i class="fas fa-calendar"></i> Years</h3>
                        <div class="year-list">
                            <a href="projects.php" class="<?php echo !$year ? 'active' : ''; ?>">All</a>
                            <?php foreach ($years as $y): ?>
                                <?php if ($y['year']): ?>
                                <a href="?year=<?php echo $y['year']; ?>" 
                                   class="<?php echo $year == $y['year'] ? 'active' : ''; ?>">
                                    <?php echo $y['year']; ?>
                                </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Latest Projects -->
                    <?php if (!empty($latestProjects)): ?>
                    <div class="sidebar-section">
                        <h3><i class="fas fa-clock"></i> Latest Projects</h3>
                        <?php foreach ($latestProjects as $project): ?>
                        <div class="latest-project">
                            <div class="latest-thumb">
                                <img src="<?php echo UPLOAD_URL . ($project['image_path'] ?: 'projects/default-project.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($project['title']); ?>">
                            </div>
                            <div class="latest-info">
                                <h4><a href="project-detail.php?id=<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['title']); ?></a></h4>
                                <?php if ($project['completion_date']): ?>
                                <p><i class="fas fa-calendar"></i> <?php echo formatDate($project['completion_date'], 'M Y'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Download Brochure
                    <div class="sidebar-section">
                        <div style="background: linear-gradient(135deg, var(--primary-blue), var(--secondary-orange)); padding: 25px; border-radius: 10px; color: var(--white); text-align: center;">
                            <i class="fas fa-file-pdf" style="font-size: 3rem; margin-bottom: 15px;"></i>
                            <h4 style="margin-bottom: 10px;">Project Brochure</h4>
                            <p style="margin-bottom: 15px; font-size: 0.9rem;">Download our complete project portfolio</p>
                            <a href="downloads/project-brochure.pdf" class="btn" style="background: var(--white); color: var(--primary-blue);">
                                <i class="fas fa-download"></i> Download PDF
                            </a>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Projects -->
    <?php if (!empty($featuredProjects)): ?>
    <section class="featured-section">
        <div class="container">
            <h2 class="section-title">Featured <span>Projects</span></h2>
            <div class="featured-grid">
                <?php foreach ($featuredProjects as $project): ?>
                <div class="featured-card">
                    <div class="featured-image">
                        <img src="<?php echo UPLOAD_URL . ($project['image_path'] ?: 'projects/default-project.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($project['title']); ?>">
                        <div class="project-badge <?php echo $project['status'] ?? 'completed'; ?>" style="position: absolute; top: 10px; right: 10px;">
                            <?php echo ucfirst($project['status'] ?? 'Completed'); ?>
                        </div>
                    </div>
                    <div class="featured-content">
                        <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                        <?php if ($project['client_name']): ?>
                        <p style="color: var(--secondary-orange); margin-bottom: 10px;">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($project['client_name']); ?>
                        </p>
                        <?php endif; ?>
                        <p><?php echo truncateText($project['description'], 120); ?></p>
                    </div>
                    <div class="featured-footer">
                        <?php if ($project['completion_date']): ?>
                        <span><i class="fas fa-calendar-alt"></i> <?php echo formatDate($project['completion_date'], 'M Y'); ?></span>
                        <?php endif; ?>
                        <a href="project-detail.php?id=<?php echo $project['id']; ?>" class="project-link">
                            View Project <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Call to Action -->
    <section style="background: var(--primary-blue); color: var(--white); padding: 60px 0; text-align: center;">
        <div class="container">
            <h2 style="font-size: 2rem; margin-bottom: 20px;">Have a Project in Mind?</h2>
            <p style="font-size: 1.1rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto;">
                Let's discuss how we can help bring your electrical technology project to life.
            </p>
            <a href="index.php#contact" class="btn btn-primary" style="background: var(--white); color: var(--primary-blue);">
                Start Your Project
            </a>
        </div>
    </section>

    <!-- Quick View Modal -->
    <div id="quickViewModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeQuickView()">&times;</span>
            <div id="quickViewContent"></div>
        </div>
    </div>

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
                    <h4>Project Categories</h4>
                    <ul>
                        <?php 
                        $footerCategories = array_slice($categories, 0, 5);
                        foreach ($footerCategories as $cat): 
                        ?>
                        <li><a href="?category=<?php echo urlencode($cat['category']); ?>"><?php echo htmlspecialchars($cat['category']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Newsletter</h4>
                    <p>Subscribe to get updates on our latest projects.</p>
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
    <a href="https://wa.me/<?php echo $whatsapp; ?>?text=I'm interested in your projects" class="floating-whatsapp" target="_blank">
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
        
        function filterByStatus(status) {
            const url = new URL(window.location.href);
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
        
        function filterByYear(year) {
            const url = new URL(window.location.href);
            if (year) {
                url.searchParams.set('year', year);
            } else {
                url.searchParams.delete('year');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
        
        // Quick view function
        function quickView(projectId) {
            const modal = document.getElementById('quickViewModal');
            modal.style.display = 'block';
            
            // Fetch project details via AJAX
            fetch('ajax/get-project.php?id=' + projectId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const content = `
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <img src="${data.project.image}" alt="${data.project.title}" style="width: 100%; border-radius: 5px;">
                                </div>
                                <div>
                                    <h2 style="color: var(--primary-blue); margin-bottom: 10px;">${data.project.title}</h2>
                                    <p style="color: var(--secondary-orange); margin-bottom: 15px;">
                                        <i class="fas fa-building"></i> ${data.project.client_name || 'Confidential'}
                                    </p>
                                    <p style="margin-bottom: 20px; line-height: 1.8;">${data.project.description}</p>
                                    <p style="margin-bottom: 10px;"><strong>Category:</strong> ${data.project.category || 'General'}</p>
                                    <p style="margin-bottom: 20px;"><strong>Completed:</strong> ${data.project.completion_date || 'N/A'}</p>
                                    <a href="project-detail.php?id=${data.project.id}" class="btn btn-primary">View Full Details</a>
                                </div>
                            </div>
                        `;
                        document.getElementById('quickViewContent').innerHTML = content;
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function closeQuickView() {
            document.getElementById('quickViewModal').style.display = 'none';
            document.getElementById('quickViewContent').innerHTML = '';
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
        const images = document.querySelectorAll('.project-image img, .featured-image img, .latest-thumb img');
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
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('quickViewModal');
            if (event.target == modal) {
                closeQuickView();
            }
        }
    </script>
</body>
</html>