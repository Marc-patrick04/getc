<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GETC Ltd - Global Electrical Technology Company</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php
    require_once 'includes/db.php';
    require_once 'includes/functions.php';
    
    // Get data for sections
    $heroes = getHeroes();
    $products = getProducts(6);
    $services = getServices(6);
    $projects = getProjects(6);
    $team = getTeamMembers(4);
    $feedback = getFeedback(3);
    $videos = getVideos(3);
    $about = getAboutContent('about');
    $vision = getAboutContent('vision');
    $mission = getAboutContent('mission');
    $coreValues = getAboutContent('core_values');
    $whatsapp = getSetting('whatsapp_number');
    ?>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <a href="index.php?v=<?php echo time(); ?>">
                    <span style="color: <?php echo PRIMARY_BLUE; ?>;">GETC</span>
                    <span style="color: <?php echo SECONDARY_ORANGE; ?>;">Ltd</span>
                </a>
            </div>
            <ul class="nav-menu">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="products.php?v=<?php echo time(); ?>">Products</a></li>
                <li><a href="services.php?v=<?php echo time(); ?>">Services</a></li>
                <li><a href="projects.php?v=<?php echo time(); ?>">Projects</a></li>
                <li><a href="videos.php?v=<?php echo time(); ?>">Videos</a></li>
                <li><a href="feedback.php?v=<?php echo time(); ?>">Feedback</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="admin/login.php?v=<?php echo time(); ?>">Login</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Rotating Words -->
    <section id="home" class="hero">
        <div class="hero-slider">
            <?php foreach ($heroes as $index => $hero): ?>
            <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
                 data-media-type="<?php echo $hero['media_type']; ?>">
                <?php if ($hero['media_type'] === 'video'): ?>
                <video autoplay muted loop class="hero-media">
                    <source src="<?php echo UPLOAD_URL . $hero['media_path']; ?>" type="video/mp4">
                </video>
                <?php else: ?>
                <img src="<?php echo UPLOAD_URL . $hero['media_path']; ?>" alt="Hero Image" class="hero-media">
                <?php endif; ?>
                
                <div class="hero-content">
                    <h1 class="hero-title"><?php echo $hero['title']; ?></h1>
                    <p class="hero-subtitle"><?php echo $hero['subtitle']; ?></p>
                    <a href="#contact" class="btn btn-primary">Get in Touch</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="hero-controls">
            <button class="prev"><i class="fas fa-chevron-left"></i></button>
            <button class="next"><i class="fas fa-chevron-right"></i></button>
        </div>
        
        <!-- Navigation Dots -->
        <div class="hero-dots">
            <?php foreach ($heroes as $index => $hero): ?>
            <button class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>">
                <span></span>
            </button>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <h2 class="section-title">About <span>GETC Ltd</span></h2>
            <div class="about-grid">
                <div class="about-content">
                    <p><?php echo $about['content']; ?></p>
                    
                    <div class="vision-mission">
                        <div class="vision">
                            <h3><i class="fas fa-eye"></i> Vision</h3>
                            <p><?php echo $vision['content']; ?></p>
                        </div>
                        <div class="mission">
                            <h3><i class="fas fa-bullseye"></i> Mission</h3>
                            <p><?php echo $mission['content']; ?></p>
                        </div>
                    </div>
                    
                    <div class="core-values">
                        <h3><i class="fas fa-star"></i> Core Values</h3>
                        <p><?php echo $coreValues['content']; ?></p>
                    </div>
                </div>
                <?php if ($about['image_path']): ?>
                <div class="about-image">
                    <img src="<?php echo UPLOAD_URL . $about['image_path']; ?>" alt="About GETC Ltd">
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Products Section with Horizontal Scroll -->
    <section class="products">
        <div class="container">
            <h2 class="section-title">Our <span>Products</span></h2>
            <div class="scroll-container">
                <div class="scroll-controls">
                    <button class="scroll-prev" data-target="products-scroll">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="scroll-next" data-target="products-scroll">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="scroll-wrapper products-scroll">
                    <?php foreach ($products as $product): ?>
                    <div class="scroll-item">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo UPLOAD_URL . $product['image_path']; ?>" 
                                     alt="<?php echo $product['name']; ?>">
                            </div>
                            <div class="product-info">
                                <h3><?php echo $product['name']; ?></h3>
                                <p><?php echo truncateText($product['description'], 100); ?></p>
                                <a href="products.php?id=<?php echo $product['id']; ?>" class="btn-link">Learn More <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section with Horizontal Scroll -->
    <section class="services">
        <div class="container">
            <h2 class="section-title">Our <span>Services</span></h2>
            <div class="scroll-container">
                <div class="scroll-controls">
                    <button class="scroll-prev" data-target="services-scroll">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="scroll-next" data-target="services-scroll">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="scroll-wrapper services-scroll">
                    <?php foreach ($services as $service): ?>
                    <div class="scroll-item">
                        <div class="service-card">
                            <?php if ($service['icon']): ?>
                            <i class="fas fa-<?php echo $service['icon']; ?> service-icon"></i>
                            <?php endif; ?>
                            <?php if ($service['image_path']): ?>
                            <img src="<?php echo UPLOAD_URL . $service['image_path']; ?>" 
                                 alt="<?php echo $service['name']; ?>" class="service-image">
                            <?php endif; ?>
                            <h3><?php echo $service['name']; ?></h3>
                            <p><?php echo truncateText($service['description'], 80); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Projects Section with Horizontal Scroll -->
    <section class="projects">
        <div class="container">
            <h2 class="section-title">Completed <span>Projects</span></h2>
            <div class="scroll-container">
                <div class="scroll-wrapper projects-scroll">
                    <?php foreach ($projects as $project): ?>
                    <div class="scroll-item">
                        <div class="project-card">
                            <div class="project-image">
                                <img src="<?php echo UPLOAD_URL . $project['image_path']; ?>" 
                                     alt="<?php echo $project['title']; ?>">
                            </div>
                            <div class="project-info">
                                <h3><?php echo $project['title']; ?></h3>
                                <p class="client"><i class="fas fa-building"></i> <?php echo $project['client_name']; ?></p>
                                <p><?php echo truncateText($project['description'], 80); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team">
        <div class="container">
            <h2 class="section-title">Our <span>Team</span></h2>
            <div class="team-grid">
                <?php foreach ($team as $member): ?>
                <div class="team-card">
                    <div class="team-image">
                        <img src="<?php echo UPLOAD_URL . $member['image_path']; ?>" 
                             alt="<?php echo $member['name']; ?>">
                        <?php if ($member['social_linkedin']): ?>
                        <a href="<?php echo $member['social_linkedin']; ?>" class="social-link" target="_blank">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="team-info">
                        <h3><?php echo $member['name']; ?></h3>
                        <p class="position"><?php echo $member['position']; ?></p>
                        <p class="bio"><?php echo truncateText($member['bio'], 100); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Video Showcase -->
    <section class="videos">
        <div class="container">
            <h2 class="section-title">Our <span>Work in Action</span></h2>
            <div class="video-grid">
                <?php foreach ($videos as $video): ?>
                <div class="video-card" onclick="playVideo(<?php echo $video['id']; ?>)">
                    <div class="video-thumbnail">
                        <?php if ($video['thumbnail_path']): ?>
                        <img src="<?php echo UPLOAD_URL . $video['thumbnail_path']; ?>" 
                             alt="<?php echo $video['title']; ?>">
                        <?php else: ?>
                        <div style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; height: 100%;">
                            <i class="fas fa-video" style="font-size: 3rem; color: #ccc;"></i>
                        </div>
                        <?php endif; ?>
                        <div class="play-overlay">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                    <h4><?php echo $video['title']; ?></h4>
                    <?php if ($video['description']): ?>
                    <p class="video-description"><?php echo truncateText($video['description'], 80); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Customer Feedback -->
    <section class="feedback" style="background: var(--white);">
        <div class="container">
            <h2 class="section-title">Customer <span>Feedback</span></h2>
            <p class="section-subtitle">See what our clients say about our products and services</p>
            <div class="feedback-slider">
                <?php foreach ($feedback as $fb): ?>
                <div class="feedback-card">
                    <div class="feedback-content">
                        <i class="fas fa-quote-left"></i>
                        <p><?php echo $fb['feedback_text']; ?></p>
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $fb['rating'] ? 'active' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="feedback-author">
                        <?php if ($fb['image_path']): ?>
                        <img src="<?php echo UPLOAD_URL . $fb['image_path']; ?>" alt="<?php echo $fb['customer_name']; ?>">
                        <?php endif; ?>
                        <div>
                            <h4><?php echo $fb['customer_name']; ?></h4>
                            <p><?php echo $fb['company']; ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section with WhatsApp -->
    <section id="contact" class="contact">
        <div class="container">
            <h2 class="section-title">Contact <span>Us</span></h2>
            <div class="contact-grid">
                <div class="contact-info">
                    <h3>Get in Touch</h3>
                    <p><i class="fas fa-phone"></i> <?php echo getSetting('company_phone'); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo getSetting('company_email'); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo getSetting('company_address'); ?></p>
                    
                    <?php if ($whatsapp): ?>
                    <a href="https://wa.me/<?php echo $whatsapp; ?>" class="whatsapp-btn" target="_blank">
                        <i class="fab fa-whatsapp"></i> Chat on WhatsApp
                    </a>
                    <?php endif; ?>
                    
                    <div class="social-links">
                        <?php if (getSetting('facebook_url')): ?>
                        <a href="<?php echo getSetting('facebook_url'); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('twitter_url')): ?>
                        <a href="<?php echo getSetting('twitter_url'); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('linkedin_url')): ?>
                        <a href="<?php echo getSetting('linkedin_url'); ?>" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                        <?php endif; ?>
                        <?php if (getSetting('youtube_url')): ?>
                        <a href="<?php echo getSetting('youtube_url'); ?>" target="_blank"><i class="fab fa-youtube"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="contact-form">
                    <form id="contactForm" method="POST">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="subject" placeholder="Subject">
                        </div>
                        <div class="form-group">
                            <textarea name="message" placeholder="Your Message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
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
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="services.php">Services</a></li>
                        <li><a href="projects.php">Projects</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Our Policies</h4>
                    <ul>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">Shipping Policy</a></li>
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

    <!-- Video Modal -->
    <div id="videoModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <video id="modalVideo" controls>
                <source src="" type="video/mp4">
            </video>
        </div>
    </div>

    <!-- Floating WhatsApp Button -->
    <?php if ($whatsapp): ?>
    <a href="https://wa.me/<?php echo $whatsapp; ?>" class="floating-whatsapp" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>
    <?php endif; ?>

    <!-- Video Data for JavaScript -->
    <script>
        window.videoData = <?php echo json_encode(array_map(function($video) {
            return [
                'id' => $video['id'],
                'title' => $video['title'],
                'description' => $video['description'] ?? '',
                'video_path' => $video['video_path'] ? UPLOAD_URL . $video['video_path'] : null,
                'video_url' => $video['video_url'] ?? null,
                'thumbnail_path' => $video['thumbnail_path'] ? UPLOAD_URL . $video['thumbnail_path'] : null
            ];
        }, $videos)); ?>;
    </script>

    <script src="js/main.js"></script>
</body>
</html>
