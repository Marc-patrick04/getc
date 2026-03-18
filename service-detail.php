<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get service ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: services.php');
    exit;
}

// Get service details
$service = $db->fetchOne("SELECT * FROM services WHERE id = ?", [$id]);

if (!$service) {
    header('Location: services.php');
    exit;
}

// Get related services (same category)
$relatedServices = [];
if ($service['category']) {
    $relatedServices = $db->fetchAll(
        "SELECT * FROM services WHERE category = ? AND id != ? ORDER BY display_order LIMIT 3",
        [$service['category'], $id]
    );
}

// If no related services, get featured services
if (empty($relatedServices)) {
    $relatedServices = $db->fetchAll(
        "SELECT * FROM services WHERE is_featured = true AND id != ? ORDER BY display_order LIMIT 3",
        [$id]
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['name']); ?> - GETC Ltd Services</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .service-detail {
            padding: 100px 0 60px;
            margin-top: 70px;
        }
        
        .service-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-bottom: 60px;
        }
        
        .service-image {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .service-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .service-info h1 {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 15px;
        }
        
        .service-meta {
            margin-bottom: 30px;
        }
        
        .service-category {
            display: inline-block;
            padding: 5px 15px;
            background: var(--secondary-orange);
            color: var(--white);
            border-radius: 5px;
            font-size: 0.9rem;
            margin-right: 10px;
        }
        
        .service-featured {
            display: inline-block;
            padding: 5px 15px;
            background: var(--primary-blue);
            color: var(--white);
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .service-description {
            margin-bottom: 30px;
        }
        
        .service-description h3 {
            color: var(--primary-blue);
            margin-bottom: 15px;
        }
        
        .service-description p {
            line-height: 1.8;
            color: #666;
            margin-bottom: 20px;
        }
        
        .service-features {
            margin-bottom: 30px;
        }
        
        .service-features h3 {
            color: var(--primary-blue);
            margin-bottom: 15px;
        }
        
        .service-features ul {
            list-style: none;
        }
        
        .service-features li {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .service-features i {
            color: var(--secondary-orange);
        }
        
        .service-actions {
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
            text-decoration: none;
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
        
        .related-services {
            margin-top: 60px;
        }
        
        .related-services h2 {
            text-align: center;
            margin-bottom: 40px;
            color: var(--primary-blue);
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .related-card {
            background: var(--white);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: 0.3s;
        }
        
        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .related-icon {
            width: 70px;
            height: 70px;
            background: var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--secondary-orange);
            font-size: 2rem;
        }
        
        .related-card h4 {
            color: var(--primary-blue);
            margin-bottom: 10px;
        }
        
        .related-card p {
            color: #666;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <!-- Same navigation as services.php -->
    </nav>

    <section class="service-detail">
        <div class="container">
            <div class="breadcrumb" style="margin-bottom: 30px;">
                <a href="index.php">Home</a>
                <span>/</span>
                <a href="services.php">Services</a>
                <span>/</span>
                <span><?php echo htmlspecialchars($service['name']); ?></span>
            </div>
            
            <div class="service-detail-grid">
                <div class="service-image">
                    <img src="<?php echo UPLOAD_URL . ($service['image_path'] ?: 'services/default-service.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($service['name']); ?>">
                </div>
                
                <div class="service-info">
                    <h1><?php echo htmlspecialchars($service['name']); ?></h1>
                    
                    <div class="service-meta">
                        <?php if ($service['category']): ?>
                        <span class="service-category">
                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($service['category']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($service['is_featured']): ?>
                        <span class="service-featured">
                            <i class="fas fa-star"></i> Featured Service
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="service-description">
                        <h3>Service Overview</h3>
                        <p><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                    </div>
                    
                    <div class="service-features">
                        <h3>Key Features</h3>
                        <ul>
                            <li><i class="fas fa-check-circle"></i> Expert consultation and planning</li>
                            <li><i class="fas fa-check-circle"></i> Customized solutions for your needs</li>
                            <li><i class="fas fa-check-circle"></i> Professional installation and setup</li>
                            <li><i class="fas fa-check-circle"></i> Ongoing maintenance and support</li>
                            <li><i class="fas fa-check-circle"></i> 24/7 emergency assistance</li>
                        </ul>
                    </div>
                    
                    <div class="service-actions">
                        <button class="btn-inquire" onclick="inquireService(<?php echo $service['id']; ?>)">
                            <i class="fas fa-envelope"></i> Inquire About This Service
                        </button>
                        
                        <?php 
                        $whatsapp = getSetting('whatsapp_number');
                        if ($whatsapp): 
                        ?>
                        <a href="https://wa.me/<?php echo $whatsapp; ?>?text=I'm interested in your service: <?php echo urlencode($service['name']); ?>" 
                           class="btn-whatsapp" target="_blank">
                            <i class="fab fa-whatsapp"></i> WhatsApp Us
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Related Services -->
            <?php if (!empty($relatedServices)): ?>
            <div class="related-services">
                <h2>Related Services</h2>
                <div class="related-grid">
                    <?php foreach ($relatedServices as $related): ?>
                    <div class="related-card">
                        <div class="related-icon">
                            <i class="fas fa-<?php echo $related['icon'] ?: 'cog'; ?>"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($related['name']); ?></h4>
                        <p><?php echo truncateText($related['description'], 80); ?></p>
                        <a href="service-detail.php?id=<?php echo $related['id']; ?>" class="btn-link">
                            Learn More <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <!-- Same footer as services.php -->
    </footer>

    <script>
        function inquireService(serviceId) {
            window.location.href = 'index.php#contact?service=' + serviceId;
        }
    </script>
</body>
</html>