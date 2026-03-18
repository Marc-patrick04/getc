<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get product ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: products.php');
    exit;
}

// Get product details
$product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get related products (same category)
$relatedProducts = [];
if ($product['category']) {
    $relatedProducts = $db->fetchAll(
        "SELECT * FROM products WHERE category = ? AND id != ? ORDER BY display_order LIMIT 4",
        [$product['category'], $id]
    );
}

// If no related products, get featured products
if (empty($relatedProducts)) {
    $relatedProducts = $db->fetchAll(
        "SELECT * FROM products WHERE is_featured = true AND id != ? ORDER BY display_order LIMIT 4",
        [$id]
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - GETC Ltd</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .product-detail {
            padding: 100px 0 60px;
            margin-top: 70px;
        }
        
        .product-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-bottom: 60px;
        }
        
        .product-gallery {
            position: relative;
        }
        
        .main-image {
            width: 100%;
            height: 500px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info h1 {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 15px;
        }
        
        .product-meta {
            margin-bottom: 30px;
        }
        
        .product-category {
            display: inline-block;
            padding: 5px 15px;
            background: var(--secondary-orange);
            color: var(--white);
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .product-description {
            margin-bottom: 30px;
        }
        
        .product-description h3 {
            color: var(--primary-blue);
            margin-bottom: 15px;
        }
        
        .product-description p {
            line-height: 1.8;
            color: #666;
        }
        
        .product-actions {
            display: flex;
            gap: 20px;
            margin-top: 30px;
        }
        
        .btn-inquire {
            background: var(--secondary-orange);
            color: var(--white);
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-inquire:hover {
            background: var(--primary-blue);
        }
        
        .btn-whatsapp {
            background: #25D366;
            color: var(--white);
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-whatsapp:hover {
            background: #128C7E;
        }
        
        .related-products {
            margin-top: 60px;
        }
        
        .related-products h2 {
            text-align: center;
            margin-bottom: 40px;
            color: var(--primary-blue);
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .related-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
        }
        
        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .related-image {
            height: 180px;
            overflow: hidden;
        }
        
        .related-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .related-info {
            padding: 15px;
        }
        
        .related-info h4 {
            color: var(--primary-blue);
            margin-bottom: 5px;
        }
        
        .related-info p {
            color: #666;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .product-detail-grid {
                grid-template-columns: 1fr;
            }
            
            .product-actions {
                flex-direction: column;
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

    <!-- Product Detail -->
    <section class="product-detail">
        <div class="container">
            <div class="breadcrumb" style="margin-bottom: 30px;">
                <a href="index.php">Home</a>
                <span>/</span>
                <a href="products.php">Products</a>
                <span>/</span>
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </div>
            
            <div class="product-detail-grid">
                <!-- Product Gallery -->
                <div class="product-gallery">
                    <div class="main-image">
                        <img src="<?php echo UPLOAD_URL . ($product['image_path'] ?: 'products/default-product.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             id="mainProductImage">
                    </div>
                </div>
                
                <!-- Product Info -->
                <div class="product-info">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="product-meta">
                        <?php if ($product['category']): ?>
                        <span class="product-category">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($product['is_featured']): ?>
                        <span class="product-category" style="background: var(--primary-blue); margin-left: 10px;">
                            <i class="fas fa-star"></i> Featured
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description">
                        <h3>Product Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                    
                    <div class="product-actions">
                        <button class="btn-inquire" onclick="inquireProduct(<?php echo $product['id']; ?>)">
                            <i class="fas fa-envelope"></i> Inquire About This Product
                        </button>
                        
                        <?php 
                        $whatsapp = getSetting('whatsapp_number');
                        if ($whatsapp): 
                        ?>
                        <a href="https://wa.me/<?php echo $whatsapp; ?>?text=I'm interested in: <?php echo urlencode($product['name']); ?>" 
                           class="btn-whatsapp" target="_blank">
                            <i class="fab fa-whatsapp"></i> WhatsApp Us
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Share buttons -->
                    <div style="margin-top: 40px;">
                        <h4 style="margin-bottom: 15px;">Share this product:</h4>
                        <div class="social-links">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . '/product-detail.php?id=' . $product['id']); ?>" 
                               target="_blank" style="background: #3b5998;">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . '/product-detail.php?id=' . $product['id']); ?>&text=<?php echo urlencode($product['name']); ?>" 
                               target="_blank" style="background: #1da1f2;">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(SITE_URL . '/product-detail.php?id=' . $product['id']); ?>" 
                               target="_blank" style="background: #0077b5;">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Related Products -->
            <?php if (!empty($relatedProducts)): ?>
            <div class="related-products">
                <h2>Related Products</h2>
                <div class="related-grid">
                    <?php foreach ($relatedProducts as $related): ?>
                    <div class="related-card">
                        <div class="related-image">
                            <img src="<?php echo UPLOAD_URL . ($related['image_path'] ?: 'products/default-product.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($related['name']); ?>">
                        </div>
                        <div class="related-info">
                            <h4><?php echo htmlspecialchars($related['name']); ?></h4>
                            <p><?php echo truncateText($related['description'], 60); ?></p>
                            <a href="product-detail.php?id=<?php echo $related['id']; ?>" class="btn-link">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <!-- Same footer as products.php -->
    </footer>

    <script src="js/main.js"></script>
    <script>
        function inquireProduct(productId) {
            // Redirect to contact page with product info
            window.location.href = 'index.php#contact?product=' + productId;
        }
    </script>
</body>
</html>