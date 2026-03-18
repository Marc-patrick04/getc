<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->requireLogin();

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get counts for dashboard
$productsCount = $db->fetchOne("SELECT COUNT(*) as count FROM products")['count'];
$servicesCount = $db->fetchOne("SELECT COUNT(*) as count FROM services")['count'];
$projectsCount = $db->fetchOne("SELECT COUNT(*) as count FROM projects")['count'];
$teamCount = $db->fetchOne("SELECT COUNT(*) as count FROM team_members")['count'];
$heroesCount = $db->fetchOne("SELECT COUNT(*) as count FROM heroes")['count'];
$videosCount = $db->fetchOne("SELECT COUNT(*) as count FROM videos")['count'];
$feedbackCount = $db->fetchOne("SELECT COUNT(*) as count FROM feedback")['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GETC Ltd</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/table-responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                <li><a href="hero-manager.php"><i class="fas fa-images"></i> Hero Section</a></li>
                <li><a href="product-manager.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="service-manager.php"><i class="fas fa-cogs"></i> Services</a></li>
                <li><a href="project-manager.php"><i class="fas fa-project-diagram"></i> Projects</a></li>
                <li><a href="team-manager.php"><i class="fas fa-users"></i> Team Members</a></li>
                <li><a href="video-manager.php"><i class="fas fa-video"></i> Videos</a></li>
                <li><a href="feedback-manager.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="about-manager.php"><i class="fas fa-info-circle"></i> About Content</a></li>
                <li><a href="settings-manager.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <div class="top-bar">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-info">
                        <h3>Hero Slides</h3>
                        <div class="count"><?php echo $heroesCount; ?></div>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-images"></i>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-info">
                        <h3>Products</h3>
                        <div class="count"><?php echo $productsCount; ?></div>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-info">
                        <h3>Services</h3>
                        <div class="count"><?php echo $servicesCount; ?></div>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-info">
                        <h3>Projects</h3>
                        <div class="count"><?php echo $projectsCount; ?></div>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-info">
                        <h3>Team Members</h3>
                        <div class="count"><?php echo $teamCount; ?></div>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-info">
                        <h3>Videos</h3>
                        <div class="count"><?php echo $videosCount; ?></div>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-video"></i>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-info">
                        <h3>Feedback</h3>
                        <div class="count"><?php echo $feedbackCount; ?></div>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
            </div>
            
            <!-- Recent Items -->
            <div class="recent-items">
                <h2>Recent Feedback</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Company</th>
                            <th>Feedback</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recentFeedback = $db->fetchAll(
                            "SELECT * FROM feedback ORDER BY created_at DESC LIMIT 5"
                        );
                        
                        foreach ($recentFeedback as $fb):
                        ?>
                        <tr>
                            <td><?php echo $fb['customer_name']; ?></td>
                            <td><?php echo $fb['company']; ?></td>
                            <td><?php echo truncateText($fb['feedback_text'], 50); ?></td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" style="color: <?php echo $i <= $fb['rating'] ? '#ffd700' : '#ddd'; ?>"></i>
                                <?php endfor; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $fb['is_approved'] ? 'status-active' : 'status-pending'; ?>">
                                    <?php echo $fb['is_approved'] ? 'Approved' : 'Pending'; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($fb['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

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
