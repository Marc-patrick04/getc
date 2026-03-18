<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 9;
$offset = ($page - 1) * $itemsPerPage;

// Get videos with pagination
$sql = "SELECT * FROM videos ORDER BY display_order, created_at DESC LIMIT ? OFFSET ?";
$videos = $db->fetchAll($sql, [$itemsPerPage, $offset]);

// Get total count for pagination
$totalResult = $db->fetchOne("SELECT COUNT(*) as total FROM videos");
$totalItems = $totalResult['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Get featured/recent videos for sidebar
$recentVideos = $db->fetchAll("SELECT * FROM videos ORDER BY created_at DESC LIMIT 5");
$popularVideos = $db->fetchAll("SELECT * FROM videos ORDER BY views DESC LIMIT 5");

// Get categories (if you add category field to videos table)
$categories = $db->fetchAll("SELECT DISTINCT category FROM videos WHERE category IS NOT NULL AND category != '' ORDER BY category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Gallery - GETC Ltd | Global Electrical Technology Company</title>
    <meta name="description" content="Watch our project videos, demonstrations, and customer testimonials. See GETC Ltd's electrical technology solutions in action.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Videos Page Specific Styles */
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

        /* Videos Layout */
        .videos-section {
            padding: 60px 0;
        }
        
        .videos-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 40px;
        }
        
        /* Main Videos Grid */
        .videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .video-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
            cursor: pointer;
        }
        
        .video-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .video-thumbnail {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .video-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .video-card:hover .video-thumbnail img {
            transform: scale(1.1);
        }
        
        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: var(--secondary-orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.5rem;
            transition: 0.3s;
            opacity: 0.9;
        }
        
        .video-card:hover .play-button {
            background: var(--primary-blue);
            transform: translate(-50%, -50%) scale(1.1);
        }
        
        .video-duration {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: var(--white);
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        
        .video-info {
            padding: 20px;
        }
        
        .video-info h3 {
            color: var(--primary-blue);
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .video-info p {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .video-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 0.9rem;
        }
        
        .video-meta i {
            margin-right: 5px;
            color: var(--secondary-orange);
        }
        
        .video-views {
            display: flex;
            align-items: center;
        }
        
        /* Sidebar */
        .video-sidebar {
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
        
        .sidebar-video {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .sidebar-video:hover {
            transform: translateX(5px);
        }
        
        .sidebar-thumbnail {
            position: relative;
            width: 100px;
            height: 70px;
            border-radius: 5px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .sidebar-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .sidebar-play {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 30px;
            height: 30px;
            background: var(--secondary-orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 0.8rem;
            opacity: 0;
            transition: 0.3s;
        }
        
        .sidebar-video:hover .sidebar-play {
            opacity: 1;
        }
        
        .sidebar-info h4 {
            color: var(--dark-gray);
            font-size: 0.95rem;
            margin-bottom: 5px;
            line-height: 1.4;
        }
        
        .sidebar-info p {
            color: #999;
            font-size: 0.8rem;
        }
        
        .sidebar-info p i {
            color: var(--secondary-orange);
            margin-right: 3px;
        }
        
        /* Category Pills */
        .category-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 30px 0 40px;
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
        
        /* Video Player Modal */
        .video-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .video-modal.active {
            display: flex;
        }
        
        .video-modal-content {
            position: relative;
            width: 90%;
            max-width: 1000px;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .video-modal-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.8), transparent);
            color: var(--white);
            z-index: 1;
        }
        
        .video-modal-header h2 {
            margin-bottom: 5px;
            font-size: 1.5rem;
        }
        
        .video-modal-header p {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
            transition: 0.3s;
            z-index: 2;
        }
        
        .close-modal:hover {
            background: var(--secondary-orange);
            transform: rotate(90deg);
        }
        
        .video-modal-body {
            position: relative;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
        }
        
        #modalVideoPlayer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .video-modal-footer {
            padding: 20px;
            background: #111;
            color: var(--white);
        }
        
        .video-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .video-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #999;
        }
        
        .video-stats i {
            color: var(--secondary-orange);
        }
        
        .video-description {
            line-height: 1.8;
            color: #ccc;
        }
        
        .share-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .share-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #333;
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: 0.3s;
        }
        
        .share-btn:hover {
            transform: translateY(-3px);
        }
        
        .share-btn.facebook:hover { background: #3b5998; }
        .share-btn.twitter:hover { background: #1da1f2; }
        .share-btn.linkedin:hover { background: #0077b5; }
        .share-btn.whatsapp:hover { background: #25D366; }
        
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
        
        /* No Videos */
        .no-videos {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .no-videos i {
            font-size: 4rem;
            color: var(--secondary-orange);
            margin-bottom: 20px;
        }
        
        .no-videos h3 {
            font-size: 1.5rem;
            color: var(--dark-gray);
            margin-bottom: 10px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .videos-layout {
                grid-template-columns: 1fr;
            }
            
            .video-sidebar {
                order: -1;
            }
            
            .page-header h1 {
                font-size: 2rem;
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
                <li><a href="videos.php" class="active">Videos</a></li>
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
            <h1>Video Gallery</h1>
            <p>Watch our projects, demonstrations, and success stories in action</p>
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <span>Videos</span>
            </div>
        </div>
    </section>

    <!-- Category Filters (if categories exist) -->
    <?php if (!empty($categories)): ?>
    <div class="container">
        <div class="category-pills">
            <a href="videos.php" class="category-pill <?php echo !isset($_GET['category']) ? 'active' : ''; ?>">All Videos</a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo urlencode($cat['category']); ?>" 
                   class="category-pill <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['category']) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['category']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Videos Section -->
    <section class="videos-section">
        <div class="container">
            <div class="videos-layout">
                <!-- Main Videos Grid -->
                <div class="main-videos">
                    <?php if (count($videos) > 0): ?>
                    <div class="videos-grid">
                        <?php foreach ($videos as $video): ?>
                        <div class="video-card" onclick="playVideo(<?php echo $video['id']; ?>)">
                            <div class="video-thumbnail">
                                <img src="<?php echo UPLOAD_URL . ($video['thumbnail_path'] ?: 'videos/default-thumbnail.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($video['title']); ?>">
                                <div class="play-button">
                                    <i class="fas fa-play"></i>
                                </div>
                                <?php if (isset($video['duration'])): ?>
                                <span class="video-duration"><?php echo $video['duration']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="video-info">
                                <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                                <p><?php echo truncateText($video['description'], 80); ?></p>
                                <div class="video-meta">
                                    <span class="video-views">
                                        <i class="fas fa-eye"></i> 
                                        <?php echo number_format($video['views'] ?? 0); ?> views
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo formatDate($video['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php else: ?>
                        <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php else: ?>
                        <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <!-- No Videos -->
                    <div class="no-videos">
                        <i class="fas fa-video-slash"></i>
                        <h3>No Videos Available</h3>
                        <p>Check back soon for new project videos and demonstrations.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="video-sidebar">
                    <!-- Recent Videos -->
                    <?php if (!empty($recentVideos)): ?>
                    <div class="sidebar-section">
                        <h3><i class="fas fa-clock"></i> Recent Videos</h3>
                        <?php foreach ($recentVideos as $video): ?>
                        <div class="sidebar-video" onclick="playVideo(<?php echo $video['id']; ?>)">
                            <div class="sidebar-thumbnail">
                                <img src="<?php echo UPLOAD_URL . ($video['thumbnail_path'] ?: 'videos/default-thumbnail.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($video['title']); ?>">
                                <div class="sidebar-play">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                            <div class="sidebar-info">
                                <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                <p><i class="fas fa-eye"></i> <?php echo number_format($video['views'] ?? 0); ?> views</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Popular Videos -->
                    <?php if (!empty($popularVideos)): ?>
                    <div class="sidebar-section">
                        <h3><i class="fas fa-fire"></i> Popular Videos</h3>
                        <?php foreach ($popularVideos as $video): ?>
                        <div class="sidebar-video" onclick="playVideo(<?php echo $video['id']; ?>)">
                            <div class="sidebar-thumbnail">
                                <img src="<?php echo UPLOAD_URL . ($video['thumbnail_path'] ?: 'videos/default-thumbnail.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($video['title']); ?>">
                                <div class="sidebar-play">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                            <div class="sidebar-info">
                                <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                <p><i class="fas fa-eye"></i> <?php echo number_format($video['views'] ?? 0); ?> views</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Subscribe Section -->
                    <div class="sidebar-section">
                        <h3><i class="fas fa-bell"></i> Stay Updated</h3>
                        <p style="margin-bottom: 15px; color: #666;">Subscribe to get notified about new videos</p>
                        <form id="subscribeForm" style="display: flex; gap: 10px;">
                            <input type="email" placeholder="Your Email" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            <button type="submit" style="background: var(--secondary-orange); color: var(--white); border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Follow Us -->
                    <div class="sidebar-section">
                        <h3><i class="fas fa-share-alt"></i> Follow Us</h3>
                        <div class="social-links" style="justify-content: flex-start;">
                            <a href="<?php echo getSetting('youtube_url') ?: '#'; ?>" target="_blank" style="background: #FF0000;">
                                <i class="fab fa-youtube"></i>
                            </a>
                            <a href="<?php echo getSetting('facebook_url') ?: '#'; ?>" target="_blank" style="background: #3b5998;">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="<?php echo getSetting('linkedin_url') ?: '#'; ?>" target="_blank" style="background: #0077b5;">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="<?php echo getSetting('instagram_url') ?: '#'; ?>" target="_blank" style="background: #e4405f;">
                                <i class="fab fa-instagram"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section style="background: var(--light-gray); padding: 60px 0; text-align: center;">
        <div class="container">
            <h2 style="font-size: 2rem; margin-bottom: 20px; color: var(--primary-blue);">Want to See More?</h2>
            <p style="font-size: 1.1rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto; color: #666;">
                Follow our YouTube channel for the latest project videos, tutorials, and company updates.
            </p>
            <a href="<?php echo getSetting('youtube_url') ?: '#'; ?>" class="btn btn-primary" target="_blank" style="background: #FF0000;">
                <i class="fab fa-youtube"></i> Subscribe on YouTube
            </a>
        </div>
    </section>

    <!-- Video Player Modal -->
    <div id="videoModal" class="video-modal">
        <div class="video-modal-content">
            <div class="video-modal-header">
                <h2 id="modalVideoTitle"></h2>
                <p id="modalVideoDate"></p>
            </div>
            <span class="close-modal" onclick="closeVideoModal()">&times;</span>
            
            <div class="video-modal-body">
                <video id="modalVideoPlayer" controls>
                    <source src="" type="video/mp4">
                </video>
            </div>
            
            <div class="video-modal-footer">
                <div class="video-stats">
                    <span id="modalVideoViews"><i class="fas fa-eye"></i> 0 views</span>
                    <span><i class="fas fa-calendar"></i> <span id="modalVideoDateFooter"></span></span>
                </div>
                <p class="video-description" id="modalVideoDescription"></p>
                
                <div class="share-buttons">
                    <span style="color: #999; margin-right: 10px;">Share:</span>
                    <a href="#" id="shareFacebook" class="share-btn facebook" target="_blank">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" id="shareTwitter" class="share-btn twitter" target="_blank">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" id="shareLinkedin" class="share-btn linkedin" target="_blank">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="#" id="shareWhatsapp" class="share-btn whatsapp" target="_blank">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
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
                    <h4>Video Categories</h4>
                    <ul>
                        <li><a href="videos.php?category=projects">Project Showcase</a></li>
                        <li><a href="videos.php?category=tutorials">Tutorials</a></li>
                        <li><a href="videos.php?category=testimonials">Testimonials</a></li>
                        <li><a href="videos.php?category=events">Events</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Newsletter</h4>
                    <p>Subscribe to get updates on our latest videos and projects.</p>
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
        // Video data store
        let videosData = <?php 
            $allVideos = $db->fetchAll("SELECT * FROM videos ORDER BY display_order");
            echo json_encode($allVideos); 
        ?>;
        
        // Play video function
        function playVideo(videoId) {
            const video = videosData.find(v => v.id == videoId);
            if (!video) return;
            
            // Check if it's a YouTube video
            const isYoutube = video.video_path.includes('youtube.com') || video.video_path.includes('youtu.be');
            
            // Update modal content
            document.getElementById('modalVideoTitle').textContent = video.title;
            document.getElementById('modalVideoDate').textContent = 'Uploaded: ' + formatDate(video.created_at);
            document.getElementById('modalVideoDateFooter').textContent = formatDate(video.created_at);
            document.getElementById('modalVideoDescription').textContent = video.description || 'No description available.';
            document.getElementById('modalVideoViews').innerHTML = '<i class="fas fa-eye"></i> ' + 
                (video.views ? Number(video.views).toLocaleString() : '0') + ' views';
            
            // Handle YouTube video
            if (isYoutube) {
                // Extract YouTube video ID
                let videoId = '';
                if (video.video_path.includes('youtu.be/')) {
                    videoId = video.video_path.split('youtu.be/')[1].split('?')[0];
                } else {
                    const urlParams = new URLSearchParams(new URL(video.video_path).search);
                    videoId = urlParams.get('v');
                }
                
                // Fallback regex for more complex URLs
                if (!videoId) {
                    const match = video.video_path.match(/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
                    if (match && match[1]) {
                        videoId = match[1];
                    }
                }
                
                // Create YouTube iframe
                const modalBody = document.querySelector('.video-modal-body');
                modalBody.innerHTML = `
                    <iframe id="modalVideoPlayer" width="100%" height="100%" 
                        src="https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&enablejsapi=1" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                `;
            } else {
                // Handle local video
                const videoPlayer = document.getElementById('modalVideoPlayer');
                videoPlayer.src = 'uploads/' + video.video_path;
                videoPlayer.style.display = 'block';
            }
            
            // Update share links
            const currentUrl = encodeURIComponent(window.location.href.split('?')[0] + '?video=' + videoId);
            const videoTitle = encodeURIComponent(video.title);
            
            document.getElementById('shareFacebook').href = `https://www.facebook.com/sharer/sharer.php?u=${currentUrl}`;
            document.getElementById('shareTwitter').href = `https://twitter.com/intent/tweet?url=${currentUrl}&text=${videoTitle}`;
            document.getElementById('shareLinkedin').href = `https://www.linkedin.com/sharing/share-offsite/?url=${currentUrl}`;
            document.getElementById('shareWhatsapp').href = `https://wa.me/?text=${videoTitle}%20${currentUrl}`;
            
            // Show modal
            document.getElementById('videoModal').classList.add('active');
            
            // Track view (optional - increment view count via AJAX)
            incrementViewCount(videoId);
        }
        
        // Close video modal
        function closeVideoModal() {
            const videoPlayer = document.getElementById('modalVideoPlayer');
            videoPlayer.pause();
            videoPlayer.currentTime = 0;
            document.getElementById('videoModal').classList.remove('active');
        }
        
        // Format date function
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(dateString).toLocaleDateString(undefined, options);
        }
        
        // Increment view count via AJAX
        function incrementViewCount(videoId) {
            fetch('ajax/increment-view.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'video_id=' + videoId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update view count in UI if needed
                    console.log('View count updated');
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVideoModal();
            }
        });
        
        // Close modal when clicking outside
        document.getElementById('videoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVideoModal();
            }
        });
        
        // Handle video from URL parameter
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const videoId = urlParams.get('video');
            if (videoId) {
                playVideo(parseInt(videoId));
            }
        });
        
        // Newsletter form submission
        document.getElementById('subscribeForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            if (email) {
                // Send to server
                fetch('ajax/subscribe.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'email=' + encodeURIComponent(email)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Thank you for subscribing!');
                        this.reset();
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
        
        // Lazy load images
        const images = document.querySelectorAll('.video-thumbnail img, .sidebar-thumbnail img');
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