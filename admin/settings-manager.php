<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->requireLogin();

require_once '../includes/db.php';
require_once '../includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        // Update company settings
        $settings = [
            'company_name' => $_POST['company_name'],
            'company_email' => $_POST['company_email'],
            'company_phone' => $_POST['company_phone'],
            'company_address' => $_POST['company_address'],
            'whatsapp_number' => $_POST['whatsapp_number'],
            'facebook_url' => $_POST['facebook_url'],
            'twitter_url' => $_POST['twitter_url'],
            'linkedin_url' => $_POST['linkedin_url'],
            'youtube_url' => $_POST['youtube_url']
        ];
        
        foreach ($settings as $key => $value) {
            updateSetting($key, $value);
        }
        
        $message = 'Settings updated successfully!';
    }
    
    if (isset($_POST['change_password'])) {
        // Change password
        $userId = $_SESSION['user_id'];
        $oldPassword = $_POST['old_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } else {
            if ($auth->changePassword($userId, $oldPassword, $newPassword)) {
                $message = 'Password changed successfully!';
            } else {
                $error = 'Current password is incorrect';
            }
        }
    }
    
    if (isset($_POST['update_profile'])) {
        // Update profile
        $userId = $_SESSION['user_id'];
        $data = [
            'email' => $_POST['email'],
            'telephone' => $_POST['telephone']
        ];
        
        if ($auth->updateProfile($userId, $data)) {
            $message = 'Profile updated successfully!';
        } else {
            $error = 'Error updating profile';
        }
    }
}

// Get current user data
$user = $db->fetchOne(
    "SELECT * FROM users WHERE id = ?",
    [$_SESSION['user_id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - GETC Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
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
                <li><a href="feedback-manager.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="about-manager.php"><i class="fas fa-info-circle"></i> About Content</a></li>
                <li><a href="settings-manager.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1>Settings</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="settings-container">
                <!-- Company Settings -->
                <div class="settings-card">
                    <h2>Company Information</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="company_name">Company Name</label>
                            <input type="text" id="company_name" name="company_name" 
                                   value="<?php echo getSetting('company_name'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="company_email">Company Email</label>
                            <input type="email" id="company_email" name="company_email" 
                                   value="<?php echo getSetting('company_email'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="company_phone">Company Phone</label>
                            <input type="text" id="company_phone" name="company_phone" 
                                   value="<?php echo getSetting('company_phone'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="company_address">Company Address</label>
                            <textarea id="company_address" name="company_address" rows="3"><?php echo getSetting('company_address'); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="whatsapp_number">WhatsApp Number (with country code)</label>
                            <input type="text" id="whatsapp_number" name="whatsapp_number" 
                                   value="<?php echo getSetting('whatsapp_number'); ?>">
                        </div>
                        
                        <button type="submit" name="update_settings" class="btn-save">Save Settings</button>
                    </form>
                </div>
                
                <!-- Social Media Settings -->
                <div class="settings-card">
                    <h2>Social Media Links</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="facebook_url">Facebook URL</label>
                            <input type="url" id="facebook_url" name="facebook_url" 
                                   value="<?php echo getSetting('facebook_url') ?: ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="twitter_url">Twitter URL</label>
                            <input type="url" id="twitter_url" name="twitter_url" 
                                   value="<?php echo getSetting('twitter_url') ?: ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="linkedin_url">LinkedIn URL</label>
                            <input type="url" id="linkedin_url" name="linkedin_url" 
                                   value="<?php echo getSetting('linkedin_url') ?: ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="youtube_url">YouTube URL</label>
                            <input type="url" id="youtube_url" name="youtube_url" 
                                   value="<?php echo getSetting('youtube_url') ?: ''; ?>">
                        </div>
                        
                        <button type="submit" name="update_settings" class="btn-save">Save Settings</button>
                    </form>
                </div>
                
                <!-- Profile Settings -->
                <div class="settings-card">
                    <h2>Profile Settings</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo $user['email']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="telephone">Telephone</label>
                            <input type="text" id="telephone" name="telephone" 
                                   value="<?php echo $user['telephone']; ?>">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn-save">Update Profile</button>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div class="settings-card">
                    <h2>Change Password</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="old_password">Current Password</label>
                            <input type="password" id="old_password" name="old_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn-save">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
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
