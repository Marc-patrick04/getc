<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 12;
$offset = ($page - 1) * $itemsPerPage;

// Build query
$sql = "SELECT * FROM products WHERE 1=1";
$countSql = "SELECT COUNT(*) as total FROM products WHERE 1=1";
$params = [];

if ($category) {
    $sql .= " AND category = ?";
    $countSql .= " AND category = ?";
    $params[] = $category;
}

if ($search) {
    $sql .= " AND (name ILIKE ? OR description ILIKE ?)";
    $countSql .= " AND (name ILIKE ? OR description ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total count for pagination
$totalResult = $db->fetchOne($countSql, $params);
$totalItems = $totalResult['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Add pagination
$sql .= " ORDER BY display_order, name LIMIT ? OFFSET ?";
$params[] = $itemsPerPage;
$params[] = $offset;

// Get products
$products = $db->fetchAll($sql, $params);

// Get unique categories for filter
$categories = $db->fetchAll("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");

// Get featured products
$featuredProducts = $db->fetchAll("SELECT * FROM products WHERE is_featured = true ORDER BY display_order LIMIT 4");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Products - GETC Ltd | Global Electrical Technology Company</title>
    <meta name="description" content="Explore our wide range of electrical technology products at GETC Ltd. Quality solutions for your electrical needs.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Products Page Specific Styles */
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
        
        .breadcrumb span {
            margin: 0 10px;
        }
        
        /* Products Section */
        .products-section {
            padding: 60px 0;
        }
        
        /* Filters */
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
            min-width: 200px;
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
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .product-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--secondary-orange);
            color: var(--white);
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 1;
        }
        
        .product-badge.featured {
            background: var(--primary-blue);
        }
        
        .product-image {
            height: 250px;
            overflow: hidden;
            position: relative;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.1);
        }
        
        .product-overlay {
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
        
        .product-card:hover .product-overlay {
            opacity: 1;
        }
        
        .product-overlay .btn {
            transform: translateY(20px);
            transition: 0.3s;
        }
        
        .product-card:hover .product-overlay .btn {
            transform: translateY(0);
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-category {
            color: var(--secondary-orange);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .product-info h3 {
            color: var(--primary-blue);
            margin-bottom: 10px;
            font-size: 1.3rem;
        }
        
        .product-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .product-meta a {
            color: var(--secondary-orange);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
        }
        
        .product-meta a:hover {
            gap: 10px;
            color: var(--primary-blue);
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
        
        .pagination .disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        /* Featured Products Section */
        .featured-products {
            background: var(--light-gray);
            padding: 60px 0;
        }
        
        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .featured-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
        }
        
        .featured-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .featured-image {
            height: 180px;
            overflow: hidden;
        }
        
        .featured-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .featured-info {
            padding: 15px;
        }
        
        .featured-info h4 {
            color: var(--primary-blue);
            margin-bottom: 5px;
        }
        
        /* No Results */
        .no-results {
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
        
        .no-results p {
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Category Pills */
        .category-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
            justify-content: center;
        }
        
        .category-pill {
            padding: 8px 20px;
            background: var(--white);
            border: 1px solid #ddd;
            border-radius: 30px;
            text-decoration: none;
            color: var(--dark-gray);
            transition: 0.3s;
        }
        
        .category-pill:hover,
        .category-pill.active {
            background: var(--secondary-orange);
            color: var(--white);
            border-color: var(--secondary-orange);
        }
        
        /* Quick View Modal */
        .quick-view-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
        }
        
        .quick-view-content {
            position: relative;
            background: var(--white);
            width: 90%;
            max-width: 1000px;
            margin: 50px auto;
            border-radius: 10px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .quick-view-image {
            height: 100%;
            min-height: 400px;
        }
        
        .quick-view-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .quick-view-details {
            padding: 30px;
        }
        
        .quick-view-details h2 {
            color: var(--primary-blue);
            margin-bottom: 10px;
        }
        
        .quick-view-category {
            color: var(--secondary-orange);
            margin-bottom: 20px;
        }
        
        .quick-view-description {
            margin-bottom: 30px;
            line-height: 1.8;
        }
        
        .close-modal {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 2rem;
            color: var(--white);
            cursor: pointer;
            z-index: 1;
        }
        
        @media (max-width: 768px) {
            .quick-view-content {
                grid-template-columns: 1fr;
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
                <li><a href="products.php" class="active">Products</a></li>
                <li><a href="services.php">Services</a></li>
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
            <h1>Our Products</h1>
            <p>Discover our comprehensive range of electrical technology solutions</p>
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <span>Products</span>
            </div>
        </div>
    </section>

    <!-- Category Pills -->
    <div class="container">
        <div class="category-pills">
            <a href="products.php" class="category-pill <?php echo !$category ? 'active' : ''; ?>">All Products</a>
            <?php foreach ($categories as $cat): ?>
                <?php if ($cat['category']): ?>
                <a href="?category=<?php echo urlencode($cat['category']); ?>" 
                   class="category-pill <?php echo $category == $cat['category'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['category']); ?>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
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
                    
                    <select class="filter-select" onchange="sortProducts(this.value)">
                        <option value="default">Sort by: Default</option>
                        <option value="name_asc">Name: A to Z</option>
                        <option value="name_desc">Name: Z to A</option>
                        <option value="newest">Newest First</option>
                    </select>
                </div>
                
                <div class="search-box">
                    <form method="GET" id="searchForm">
                        <?php if ($category): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                        <?php endif; ?>
                        <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <?php if ($category || $search): ?>
                <a href="products.php" class="reset-filters">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
                <?php endif; ?>
            </div>

            <!-- Results Count -->
            <p style="margin-bottom: 20px; color: #666;">
                Showing <?php echo count($products); ?> of <?php echo $totalItems; ?> products
            </p>

            <!-- Products Grid -->
            <?php if (count($products) > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php if ($product['is_featured']): ?>
                    <div class="product-badge featured">Featured</div>
                    <?php endif; ?>
                    
                    <div class="product-image">
                        <img src="<?php echo UPLOAD_URL . ($product['image_path'] ?: 'products/default-product.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="product-overlay">
                            <button class="btn btn-primary" onclick="quickView(<?php echo $product['id']; ?>)">
                                <i class="fas fa-eye"></i> Quick View
                            </button>
                        </div>
                    </div>
                    
                    <div class="product-info">
                        <?php if ($product['category']): ?>
                        <div class="product-category">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        
                        <p class="product-description">
                            <?php echo truncateText($product['description'], 100); ?>
                        </p>
                        
                        <div class="product-meta">
                            <a href="product-detail.php?id=<?php echo $product['id']; ?>">
                                Learn More <i class="fas fa-arrow-right"></i>
                            </a>
                            <button class="btn-link" onclick="inquireProduct(<?php echo $product['id']; ?>)">
                                <i class="fas fa-envelope"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php else: ?>
                <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
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
                <i class="fas fa-box-open"></i>
                <h3>No Products Found</h3>
                <p>We couldn't find any products matching your criteria.</p>
                <a href="products.php" class="btn btn-primary">View All Products</a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Featured Products Section -->
    <?php if (count($featuredProducts) > 0): ?>
    <section class="featured-products">
        <div class="container">
            <h2 class="section-title">Featured <span>Products</span></h2>
            <div class="featured-grid">
                <?php foreach ($featuredProducts as $product): ?>
                <div class="featured-card">
                    <div class="featured-image">
                        <img src="<?php echo UPLOAD_URL . ($product['image_path'] ?: 'products/default-product.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="featured-info">
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p><?php echo truncateText($product['description'], 60); ?></p>
                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn-link">
                            View Details <i class="fas fa-arrow-right"></i>
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
            <h2 style="font-size: 2rem; margin-bottom: 20px;">Need a Custom Solution?</h2>
            <p style="font-size: 1.1rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto;">
                Contact our team for customized electrical technology solutions tailored to your specific requirements.
            </p>
            <a href="index.php#contact" class="btn btn-primary" style="background: var(--white); color: var(--primary-blue);">
                Get in Touch
            </a>
        </div>
    </section>

    <!-- Quick View Modal -->
    <div id="quickViewModal" class="quick-view-modal">
        <span class="close-modal" onclick="closeQuickView()">&times;</span>
        <div class="quick-view-content" id="quickViewContent">
            <!-- Content will be loaded via AJAX -->
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
                    <h4>Our Products</h4>
                    <ul>
                        <?php 
                        $footerProducts = array_slice($products, 0, 5);
                        foreach ($footerProducts as $product): 
                        ?>
                        <li><a href="product-detail.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></li>
                        <?php endforeach; ?>
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
        // Filter by category
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
        
        // Sort products
        function sortProducts(sortBy) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sortBy);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
        
        // Quick view function
        function quickView(productId) {
            // Show modal
            document.getElementById('quickViewModal').style.display = 'block';
            
            // Load product details via AJAX
            fetch('ajax/get-product.php?id=' + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const content = `
                            <div class="quick-view-image">
                                <img src="${data.product.image}" alt="${data.product.name}">
                            </div>
                            <div class="quick-view-details">
                                <h2>${data.product.name}</h2>
                                <p class="quick-view-category">${data.product.category || 'Uncategorized'}</p>
                                <div class="quick-view-description">
                                    ${data.product.description}
                                </div>
                                <div class="product-actions">
                                    <a href="product-detail.php?id=${data.product.id}" class="btn btn-primary">
                                        View Full Details
                                    </a>
                                    <button class="btn btn-secondary" onclick="inquireProduct(${data.product.id})">
                                        <i class="fas fa-envelope"></i> Inquire Now
                                    </button>
                                </div>
                            </div>
                        `;
                        document.getElementById('quickViewContent').innerHTML = content;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // Close quick view
        function closeQuickView() {
            document.getElementById('quickViewModal').style.display = 'none';
            document.getElementById('quickViewContent').innerHTML = '';
        }
        
        // Inquire about product
        function inquireProduct(productId) {
            // Redirect to contact page with product info
            window.location.href = `index.php#contact?product=${productId}`;
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('quickViewModal');
            if (event.target == modal) {
                closeQuickView();
            }
        }
        
        // Handle search form submission
        document.getElementById('searchForm')?.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="search"]');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                // Remove search parameter and reload
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                window.location.href = url.toString();
            }
        });
        
        // Highlight active category
        document.querySelectorAll('.category-pill').forEach(pill => {
            pill.addEventListener('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href);
                window.location.href = url.toString();
            });
        });
        
        // Lazy load images
        const images = document.querySelectorAll('.product-image img, .featured-image img');
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
        
        // Track product views for analytics
        function trackProductView(productId, productName) {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'view_item', {
                    'items': [{
                        'id': productId,
                        'name': productName,
                        'category': 'Product'
                    }]
                });
            }
        }
    </script>
</body>
</html>