<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->requireLogin();

require_once '../includes/db.php';
require_once '../includes/functions.php';

$message = '';
$error = '';

// Handle content updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_about':
            case 'update_vision':
            case 'update_mission':
            case 'update_values':
                $section = str_replace('update_', '', $_POST['action']);
                $data = [
                    'title' => $_POST['title'],
                    'content' => $_POST['content']
                ];
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload = uploadFile($_FILES['image'], 'about', ['jpg', 'jpeg', 'png', 'gif']);
                    if ($upload['success']) {
                        // Delete old image
                        $current = $db->fetchOne("SELECT image_path FROM about_content WHERE section = :section", ['section' => $section]);
                        if ($current && $current['image_path']) {
                            $oldFile = UPLOAD_PATH . $current['image_path'];
                            if (file_exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }
                        $data['image_path'] = $upload['path'];
                    } else {
                        $error = $upload['message'];
                        break;
                    }
                }
                
                // Handle icon upload for values (if applicable)
                if ($section === 'values' && isset($_FILES['icon']) && $_FILES['icon']['error'] === 0) {
                    $upload = uploadFile($_FILES['icon'], 'about/icons', ['jpg', 'jpeg', 'png', 'gif', 'svg']);
                    if ($upload['success']) {
                        $data['icon_path'] = $upload['path'];
                    }
                }
                
                // Check if section exists
                $exists = $db->fetchOne("SELECT id FROM about_content WHERE section = :section", ['section' => $section]);
                
                if ($exists) {
                    $db->update('about_content', $data, "section = :section", ['section' => $section]);
                } else {
                    $data['section'] = $section;
                    $db->insert('about_content', $data);
                }
                
                $message = ucfirst($section) . ' content updated successfully!';
                break;
                
            case 'add_core_value':
                $data = [
                    'section' => 'core_value',
                    'title' => $_POST['title'],
                    'content' => $_POST['content'],
                    'display_order' => (int)$_POST['display_order']
                ];
                
                // Handle icon upload
                if (isset($_FILES['icon']) && $_FILES['icon']['error'] === 0) {
                    $upload = uploadFile($_FILES['icon'], 'about/icons', ['jpg', 'jpeg', 'png', 'gif', 'svg']);
                    if ($upload['success']) {
                        $data['icon_path'] = $upload['path'];
                    }
                }
                
                $db->insert('about_content', $data);
                $message = 'Core value added successfully!';
                break;
                
            case 'edit_core_value':
                $data = [
                    'title' => $_POST['title'],
                    'content' => $_POST['content'],
                    'display_order' => (int)$_POST['display_order']
                ];
                
                // Handle icon upload
                if (isset($_FILES['icon']) && $_FILES['icon']['error'] === 0) {
                    // Delete old icon
                    $current = $db->fetchOne("SELECT icon_path FROM about_content WHERE id = :id", ['id' => $_POST['id']]);
                    if ($current && $current['icon_path']) {
                        $oldFile = UPLOAD_PATH . $current['icon_path'];
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    
                    $upload = uploadFile($_FILES['icon'], 'about/icons', ['jpg', 'jpeg', 'png', 'gif', 'svg']);
                    if ($upload['success']) {
                        $data['icon_path'] = $upload['path'];
                    }
                }
                
                $db->update('about_content', $data, "id = :id", ['id' => $_POST['id']]);
                $message = 'Core value updated successfully!';
                break;
                
            case 'delete_core_value':
                // Get icon path to delete
                $value = $db->fetchOne("SELECT icon_path FROM about_content WHERE id = :id", ['id' => $_POST['id']]);
                if ($value && $value['icon_path']) {
                    $filePath = UPLOAD_PATH . $value['icon_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                $db->delete('about_content', "id = :id", ['id' => $_POST['id']]);
                $message = 'Core value deleted successfully!';
                break;
                
            case 'update_team':
                $data = [
                    'title' => 'Our Team',
                    'content' => $_POST['content']
                ];
                
                $db->update('about_content', $data, "section = 'team'");
                $message = 'Team section updated successfully!';
                break;
                
            case 'update_history':
                $data = [
                    'title' => 'Our History',
                    'content' => $_POST['content']
                ];
                
                $db->update('about_content', $data, "section = 'history'");
                $message = 'History section updated successfully!';
                break;
                
            case 'update_order':
                $orders = json_decode($_POST['orders'], true);
                foreach ($orders as $item) {
                    $db->update('about_content', ['display_order' => $item['order']], "id = :id", ['id' => $item['id']]);
                }
                $message = 'Display order updated successfully!';
                break;
        }
    }
}

// Get about sections
$about = $db->fetchOne("SELECT * FROM about_content WHERE section = 'about'");
$vision = $db->fetchOne("SELECT * FROM about_content WHERE section = 'vision'");
$mission = $db->fetchOne("SELECT * FROM about_content WHERE section = 'mission'");
$team = $db->fetchOne("SELECT * FROM about_content WHERE section = 'team'");
$history = $db->fetchOne("SELECT * FROM about_content WHERE section = 'history'");

// Get core values (multiple entries)
$coreValues = $db->fetchAll("SELECT * FROM about_content WHERE section = 'core_value' ORDER BY display_order");

// Get company statistics
$stats = [
    'projects' => $db->fetchOne("SELECT COUNT(*) as count FROM projects")['count'] ?? 0,
    'clients' => $db->fetchOne("SELECT COUNT(DISTINCT client_name) as count FROM projects WHERE client_name IS NOT NULL")['count'] ?? 0,
    'team_members' => $db->fetchOne("SELECT COUNT(*) as count FROM team_members")['count'] ?? 0,
    'years' => date('Y') - 2010, // Assuming company founded in 2010
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Section Manager - GETC Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/table-responsive.css">
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
                <li><a href="about-manager.php" class="active"><i class="fas fa-info-circle"></i> About Content</a></li>
                <li><a href="settings-manager.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1>About Section Manager</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="message" id="messageAlert">
                <?php echo $message; ?>
                <span class="close-message" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="error" id="errorAlert">
                <?php echo $error; ?>
                <span class="close-message" onclick="this.parentElement.style.display='none'">&times;</span>
            </div>
            <?php endif; ?>
            
            <!-- Company Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['years']; ?>+</h3>
                        <p>Years of Experience</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['projects']; ?></h3>
                        <p>Projects Completed</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['clients']; ?></h3>
                        <p>Happy Clients</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['team_members']; ?></h3>
                        <p>Team Members</p>
                    </div>
                </div>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="tabs">
                <button class="tab active" onclick="showTab('about')">About Us</button>
                <button class="tab" onclick="showTab('vision')">Vision</button>
                <button class="tab" onclick="showTab('mission')">Mission</button>
                <button class="tab" onclick="showTab('values')">Core Values</button>
                <button class="tab" onclick="showTab('team')">Team Section</button>
                <button class="tab" onclick="showTab('history')">History</button>
            </div>
            
            <!-- About Us Tab -->
            <div id="tab-about" class="tab-pane active">
                <div class="sections-grid">
                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="fas fa-info-circle"></i> About Us</h3>
                        </div>
                        <div class="section-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_about">
                                
                                <div class="form-group">
                                    <label for="about_title">Section Title</label>
                                    <input type="text" id="about_title" name="title" 
                                           value="<?php echo htmlspecialchars($about['title'] ?? 'About GETC Ltd'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="about_content">Content</label>
                                    <textarea id="about_content" name="content"><?php echo htmlspecialchars($about['content'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="about_image">About Image</label>
                                    <input type="file" id="about_image" name="image" accept="image/*">
                                    <div class="info-text">Recommended size: 800x600px. Max size: 2MB</div>
                                    
                                    <?php if ($about && $about['image_path']): ?>
                                    <div class="image-preview">
                                        <img src="<?php echo UPLOAD_URL . $about['image_path']; ?>">
                                        <p>Current image. Upload new to replace.</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" class="btn-save">Update About Section</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Vision Tab -->
            <div id="tab-vision" class="tab-pane">
                <div class="sections-grid">
                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="fas fa-eye"></i> Our Vision</h3>
                        </div>
                        <div class="section-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_vision">
                                
                                <div class="form-group">
                                    <label for="vision_title">Section Title</label>
                                    <input type="text" id="vision_title" name="title" 
                                           value="<?php echo htmlspecialchars($vision['title'] ?? 'Our Vision'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="vision_content">Content</label>
                                    <textarea id="vision_content" name="content"><?php echo htmlspecialchars($vision['content'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="vision_image">Vision Image (Optional)</label>
                                    <input type="file" id="vision_image" name="image" accept="image/*">
                                    
                                    <?php if ($vision && $vision['image_path']): ?>
                                    <div class="image-preview">
                                        <img src="<?php echo UPLOAD_URL . $vision['image_path']; ?>">
                                        <p>Current image. Upload new to replace.</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" class="btn-save">Update Vision</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mission Tab -->
            <div id="tab-mission" class="tab-pane">
                <div class="sections-grid">
                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="fas fa-bullseye"></i> Our Mission</h3>
                        </div>
                        <div class="section-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_mission">
                                
                                <div class="form-group">
                                    <label for="mission_title">Section Title</label>
                                    <input type="text" id="mission_title" name="title" 
                                           value="<?php echo htmlspecialchars($mission['title'] ?? 'Our Mission'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="mission_content">Content</label>
                                    <textarea id="mission_content" name="content"><?php echo htmlspecialchars($mission['content'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="mission_image">Mission Image (Optional)</label>
                                    <input type="file" id="mission_image" name="image" accept="image/*">
                                    
                                    <?php if ($mission && $mission['image_path']): ?>
                                    <div class="image-preview">
                                        <img src="<?php echo UPLOAD_URL . $mission['image_path']; ?>">
                                        <p>Current image. Upload new to replace.</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" class="btn-save">Update Mission</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Core Values Tab -->
            <div id="tab-values" class="tab-pane">
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-star"></i> Core Values</h3>
                    </div>
                    <div class="section-body">
                        <button class="btn-add" onclick="openAddValueModal()">
                            <i class="fas fa-plus"></i> Add Core Value
                        </button>
                        
                        <div class="core-values-list" id="coreValuesList">
                            <?php foreach ($coreValues as $index => $value): ?>
                            <div class="core-value-item" data-id="<?php echo $value['id']; ?>">
                                <div class="drag-handle">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                                
                                <div class="core-value-icon">
                                    <?php if ($value['icon_path']): ?>
                                    <img src="<?php echo UPLOAD_URL . $value['icon_path']; ?>" alt="<?php echo htmlspecialchars($value['title']); ?>">
                                    <?php else: ?>
                                    <i class="fas fa-star"></i>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="core-value-content">
                                    <h4><?php echo htmlspecialchars($value['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($value['content']); ?></p>
                                </div>
                                
                                <div class="core-value-actions">
                                    <button class="btn-edit" onclick="openEditValueModal(<?php echo htmlspecialchars(json_encode($value)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-delete" onclick="deleteValue(<?php echo $value['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($coreValues)): ?>
                            <p style="text-align: center; color: #999; padding: 2rem;">
                                No core values added yet. Click "Add Core Value" to create your first value.
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Team Section Tab -->
            <div id="tab-team" class="tab-pane">
                <div class="sections-grid">
                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="fas fa-users"></i> Team Section</h3>
                        </div>
                        <div class="section-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_team">
                                
                                <div class="form-group">
                                    <label for="team_content">Team Section Description</label>
                                    <textarea id="team_content" name="content"><?php echo htmlspecialchars($team['content'] ?? ''); ?></textarea>
                                    <div class="info-text">This text appears above the team members grid</div>
                                </div>
                                
                                <button type="submit" class="btn-save">Update Team Section</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- History Tab -->
            <div id="tab-history" class="tab-pane">
                <div class="sections-grid">
                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="fas fa-history"></i> Company History</h3>
                        </div>
                        <div class="section-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_history">
                                
                                <div class="form-group">
                                    <label for="history_content">History Content</label>
                                    <textarea id="history_content" name="content"><?php echo htmlspecialchars($history['content'] ?? ''); ?></textarea>
                                    <div class="info-text">Share your company's journey and milestones</div>
                                </div>
                                
                                <button type="submit" class="btn-save">Update History</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Instructions -->
            <div style="margin-top: 2rem; background: #e7f3ff; padding: 1rem; border-radius: 5px;">
                <h4 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                    <i class="fas fa-info-circle"></i> Instructions:
                </h4>
                <ul style="margin-left: 1.5rem; color: #666;">
                    <li>Use the tabs above to navigate between different sections of the About page</li>
                    <li>Core values can be reordered by dragging the <i class="fas fa-grip-vertical"></i> handle</li>
                    <li>Upload images for each section to make them more engaging</li>
                    <li>Statistics are automatically calculated from your data</li>
                    <li>The team section description appears above the team member grid</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Add Core Value Modal -->
    <div id="valueModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="valueModalTitle">Add Core Value</h2>
                <span class="close" onclick="closeValueModal()">&times;</span>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="valueForm">
                <input type="hidden" name="action" id="valueAction" value="add_core_value">
                <input type="hidden" name="id" id="valueId">
                
                <div class="form-group">
                    <label for="value_title">Value Title *</label>
                    <input type="text" id="value_title" name="title" required 
                           placeholder="e.g., Integrity, Innovation, Excellence">
                </div>
                
                <div class="form-group">
                    <label for="value_content">Description *</label>
                    <textarea id="value_content" name="content" required 
                              placeholder="Describe this core value..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="value_icon">Icon (Optional)</label>
                        <input type="file" id="value_icon" name="icon" accept="image/*">
                        <div class="info-text">Recommended: 64x64px PNG or SVG</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="value_order">Display Order</label>
                        <input type="number" id="value_order" name="display_order" value="0" min="0">
                    </div>
                </div>
                
                <div id="valueIconPreview" class="image-preview" style="display: none;">
                    <p>Icon Preview:</p>
                    <div id="iconPreviewContent"></div>
                </div>
                
                <button type="submit" class="btn-save" id="valueSaveBtn">Add Core Value</button>
            </form>
        </div>
    </div>
    
    <!-- Hidden Forms -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_core_value">
        <input type="hidden" name="id" id="deleteId">
    </form>
    
    <form id="orderForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_order">
        <input type="hidden" name="orders" id="orderData">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        // Initialize sortable for core values
        const coreValuesList = document.getElementById('coreValuesList');
        if (coreValuesList) {
            new Sortable(coreValuesList, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function(evt) {
                    updateAllOrders();
                }
            });
        }
        
        // Update all orders after drag
        function updateAllOrders() {
            const items = document.querySelectorAll('.core-value-item');
            const orders = [];
            
            items.forEach((item, index) => {
                const id = item.dataset.id;
                orders.push({
                    id: id,
                    order: index
                });
            });
            
            // Submit order update
            document.getElementById('orderData').value = JSON.stringify(orders);
            document.getElementById('orderForm').submit();
        }
        
        // Tab functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
        
        // Core Value Modal functions
        function openAddValueModal() {
            document.getElementById('valueModalTitle').textContent = 'Add Core Value';
            document.getElementById('valueAction').value = 'add_core_value';
            document.getElementById('valueForm').reset();
            document.getElementById('valueIconPreview').style.display = 'none';
            document.getElementById('valueSaveBtn').textContent = 'Add Core Value';
            document.getElementById('valueModal').style.display = 'block';
        }
        
        function openEditValueModal(value) {
            document.getElementById('valueModalTitle').textContent = 'Edit Core Value';
            document.getElementById('valueAction').value = 'edit_core_value';
            document.getElementById('valueId').value = value.id;
            document.getElementById('value_title').value = value.title || '';
            document.getElementById('value_content').value = value.content || '';
            document.getElementById('value_order').value = value.display_order || 0;
            document.getElementById('valueSaveBtn').textContent = 'Update Core Value';
            
            // Show icon preview if exists
            if (value.icon_path) {
                const previewDiv = document.getElementById('valueIconPreview');
                const previewContent = document.getElementById('iconPreviewContent');
                previewContent.innerHTML = `<img src="<?php echo UPLOAD_URL; ?>${value.icon_path}" style="max-width: 64px;">`;
                previewDiv.style.display = 'block';
            }
            
            document.getElementById('valueModal').style.display = 'block';
        }
        
        function closeValueModal() {
            document.getElementById('valueModal').style.display = 'none';
            document.getElementById('valueForm').reset();
            document.getElementById('valueIconPreview').style.display = 'none';
        }
        
        // Delete core value
        function deleteValue(id) {
            if (confirm('Are you sure you want to delete this core value? This action cannot be undone.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Preview icon
        document.getElementById('value_icon')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewDiv = document.getElementById('valueIconPreview');
            const previewContent = document.getElementById('iconPreviewContent');
            
            if (file) {
                // Check file size (max 1MB)
                if (file.size > 1 * 1024 * 1024) {
                    alert('File size must be less than 1MB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewContent.innerHTML = `<img src="${e.target.result}" style="max-width: 64px;">`;
                    previewDiv.style.display = 'block';
                };
                
                reader.readAsDataURL(file);
            } else {
                previewDiv.style.display = 'none';
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('valueModal');
            if (event.target == modal) {
                closeValueModal();
            }
        }
        
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messageAlert = document.getElementById('messageAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            if (messageAlert) {
                messageAlert.style.display = 'none';
            }
            
            if (errorAlert) {
                errorAlert.style.display = 'none';
            }
        }, 5000);
        
        // Form validation for core values
        document.getElementById('valueForm')?.addEventListener('submit', function(e) {
            const title = document.getElementById('value_title').value.trim();
            const content = document.getElementById('value_content').value.trim();
            
            if (!title || !content) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });

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
