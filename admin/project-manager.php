<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->requireLogin();

require_once '../includes/db.php';
require_once '../includes/functions.php';

$message = '';
$error = '';

// Handle Add/Edit/Delete operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $data = [
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'client_name' => $_POST['client_name'] ?: null,
                    'category' => $_POST['category'] ?: null,
                    'status' => $_POST['status'],
                    'completion_date' => $_POST['completion_date'] ?: null,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'display_order' => (int)$_POST['display_order']
                ];
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload = uploadFile($_FILES['image'], 'projects', ['jpg', 'jpeg', 'png', 'gif']);
                    if ($upload['success']) {
                        // Delete old image if editing
                        if ($_POST['action'] === 'edit' && !empty($_POST['current_image'])) {
                            $oldFile = UPLOAD_PATH . $_POST['current_image'];
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
                
                // Handle gallery images (multiple)
                if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
                    $galleryImages = [];
                    $files = $_FILES['gallery'];
                    
                    for ($i = 0; $i < count($files['name']); $i++) {
                        if ($files['error'][$i] === 0) {
                            $file = [
                                'name' => $files['name'][$i],
                                'type' => $files['type'][$i],
                                'tmp_name' => $files['tmp_name'][$i],
                                'error' => $files['error'][$i],
                                'size' => $files['size'][$i]
                            ];
                            
                            $upload = uploadFile($file, 'projects/gallery', ['jpg', 'jpeg', 'png', 'gif']);
                            if ($upload['success']) {
                                $galleryImages[] = $upload['path'];
                            }
                        }
                    }
                    
                    if (!empty($galleryImages)) {
                        // Get existing gallery
                        if ($_POST['action'] === 'edit' && !empty($_POST['current_gallery'])) {
                            $existing = json_decode($_POST['current_gallery'], true) ?: [];
                            $galleryImages = array_merge($existing, $galleryImages);
                        }
                        $data['gallery'] = json_encode($galleryImages);
                    }
                }
                
                if ($_POST['action'] === 'add') {
                    $db->insert('projects', $data);
                    $message = 'Project added successfully!';
                } else {
                    $db->update('projects', $data, "id = :id", ['id' => $_POST['id']]);
                    $message = 'Project updated successfully!';
                }
                break;
                
            case 'delete':
                // Get image paths to delete files
                $project = $db->fetchOne("SELECT image_path, gallery FROM projects WHERE id = :id", ['id' => $_POST['id']]);
                if ($project) {
                    // Delete main image
                    if ($project['image_path']) {
                        $filePath = UPLOAD_PATH . $project['image_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    
                    // Delete gallery images
                    if ($project['gallery']) {
                        $gallery = json_decode($project['gallery'], true);
                        if (is_array($gallery)) {
                            foreach ($gallery as $imgPath) {
                                $filePath = UPLOAD_PATH . $imgPath;
                                if (file_exists($filePath)) {
                                    unlink($filePath);
                                }
                            }
                        }
                    }
                }
                
                $db->delete('projects', "id = :id", ['id' => $_POST['id']]);
                $message = 'Project deleted successfully!';
                break;
                
            case 'update_order':
                $orders = json_decode($_POST['orders'], true);
                foreach ($orders as $item) {
                    $db->update('projects', ['display_order' => $item['order']], "id = :id", ['id' => $item['id']]);
                }
                $message = 'Display order updated successfully!';
                break;
                
            case 'bulk_delete':
                $ids = json_decode($_POST['ids'], true);
                foreach ($ids as $id) {
                    // Get image paths to delete files
                    $project = $db->fetchOne("SELECT image_path, gallery FROM projects WHERE id = :id", ['id' => $id]);
                    if ($project) {
                        if ($project['image_path']) {
                            $filePath = UPLOAD_PATH . $project['image_path'];
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                        
                        if ($project['gallery']) {
                            $gallery = json_decode($project['gallery'], true);
                            if (is_array($gallery)) {
                                foreach ($gallery as $imgPath) {
                                    $filePath = UPLOAD_PATH . $imgPath;
                                    if (file_exists($filePath)) {
                                        unlink($filePath);
                                    }
                                }
                            }
                        }
                    }
                }
                
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->query("DELETE FROM projects WHERE id IN ($placeholders)", $ids);
                $message = 'Selected projects deleted successfully!';
                break;
                
            case 'delete_gallery_image':
                $projectId = $_POST['project_id'];
                $imagePath = $_POST['image_path'];
                
                $project = $db->fetchOne("SELECT gallery FROM projects WHERE id = :id", ['id' => $projectId]);
                if ($project && $project['gallery']) {
                    $gallery = json_decode($project['gallery'], true);
                    if (is_array($gallery)) {
                        // Remove image from array
                        $gallery = array_values(array_diff($gallery, [$imagePath]));
                        
                        // Delete file
                        $filePath = UPLOAD_PATH . $imagePath;
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        
                        // Update database
                        $newGallery = !empty($gallery) ? json_encode($gallery) : null;
                        $db->update('projects', ['gallery' => $newGallery], "id = :id", ['id' => $projectId]);
                        
                        echo json_encode(['success' => true]);
                        exit;
                    }
                }
                
                echo json_encode(['success' => false, 'message' => 'Image not found']);
                exit;
        }
    }
}

// Get all projects
$projects = $db->fetchAll("SELECT * FROM projects ORDER BY display_order, completion_date DESC");

// Get unique categories for filter
$categories = $db->fetchAll("SELECT DISTINCT category FROM projects WHERE category IS NOT NULL AND category != '' ORDER BY category");

// Get unique statuses
$statuses = ['completed', 'ongoing', 'upcoming'];

// Get unique years for filter
$years = $db->fetchAll("SELECT DISTINCT EXTRACT(YEAR FROM completion_date) as year FROM projects WHERE completion_date IS NOT NULL ORDER BY year DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Manager - GETC Admin</title>
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
                <li><a href="project-manager.php" class="active"><i class="fas fa-project-diagram"></i> Projects</a></li>
                <li><a href="team-manager.php"><i class="fas fa-users"></i> Team Members</a></li>
                <li><a href="video-manager.php"><i class="fas fa-video"></i> Videos</a></li>
                <li><a href="feedback-manager.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="about-manager.php"><i class="fas fa-info-circle"></i> About Content</a></li>
                <li><a href="settings-manager.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1>Project Manager</h1>
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
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($projects); ?></h3>
                        <p>Total Projects</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count(array_filter($projects, function($p) { return $p['status'] === 'completed'; })); ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count(array_filter($projects, function($p) { return $p['status'] === 'ongoing'; })); ?></h3>
                        <p>In Progress</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count(array_filter($projects, function($p) { return $p['status'] === 'upcoming'; })); ?></h3>
                        <p>Upcoming</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count(array_filter($projects, function($p) { return $p['is_featured'] == 1; })); ?></h3>
                        <p>Featured</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php 
                            $galleryCount = 0;
                            foreach ($projects as $project) {
                                if ($project['gallery']) {
                                    $gallery = json_decode($project['gallery'], true);
                                    if (is_array($gallery)) {
                                        $galleryCount += count($gallery);
                                    }
                                }
                            }
                            echo $galleryCount;
                        ?></h3>
                        <p>Gallery Images</p>
                    </div>
                </div>
            </div>
            
            <div class="manager-header">
                <h2>Manage Projects</h2>
                <div class="header-actions">
                    <button class="btn-delete-selected" id="deleteSelectedBtn" onclick="deleteSelected()">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn-add" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add New Project
                    </button>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <input type="text" id="searchInput" class="filter-input" placeholder="Search projects...">
                
                <select id="categoryFilter" class="filter-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <?php if ($cat['category']): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                
                <select id="statusFilter" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="completed">Completed</option>
                    <option value="ongoing">In Progress</option>
                    <option value="upcoming">Upcoming</option>
                </select>
                
                <select id="yearFilter" class="filter-select">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y): ?>
                        <?php if ($y['year']): ?>
                        <option value="<?php echo $y['year']; ?>">
                            <?php echo $y['year']; ?>
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                
                <select id="featuredFilter" class="filter-select">
                    <option value="">All Projects</option>
                    <option value="1">Featured Only</option>
                    <option value="0">Non-Featured Only</option>
                </select>
                
                <a href="#" class="filter-clear" onclick="clearFilters()">Clear Filters</a>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <select id="bulkAction">
                    <option value="">Select Action</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button class="btn-bulk" onclick="executeBulkAction()">Apply to Selected</button>
                <span id="selectedCount" style="color: #666; margin-left: auto;">0 selected</span>
            </div>
            
            <!-- Projects Table -->
            <div class="projects-table-container">
                <table class="projects-table" id="projectsTable">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" class="select-all" id="selectAll" onclick="toggleSelectAll()">
                            </th>
                            <th width="30"><i class="hero-table"></i></th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Client</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Featured</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sortableBody">
                        <?php foreach ($projects as $index => $project): ?>
                        <tr data-id="<?php echo $project['id']; ?>" 
                            data-category="<?php echo htmlspecialchars($project['category'] ?? ''); ?>"
                            data-status="<?php echo $project['status'] ?? 'completed'; ?>"
                            data-year="<?php echo $project['completion_date'] ? date('Y', strtotime($project['completion_date'])) : ''; ?>"
                            data-featured="<?php echo $project['is_featured']; ?>">
                            <td>
                                <input type="checkbox" class="select-item" value="<?php echo $project['id']; ?>" onclick="updateDeleteButton()">
                            </td>
                            <td class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </td>
                            <td>
                                <?php if ($project['image_path']): ?>
                                <img src="<?php echo UPLOAD_URL . $project['image_path']; ?>" 
                                     alt="<?php echo htmlspecialchars($project['title']); ?>"
                                     class="project-image">
                                <?php else: ?>
                                <div class="project-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image" style="color: #ccc;"></i>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($project['gallery']): 
                                    $galleryCount = count(json_decode($project['gallery'], true) ?: []);
                                    if ($galleryCount > 0):
                                ?>
                                <span class="gallery-indicator" title="<?php echo $galleryCount; ?> gallery images">
                                    <i class="fas fa-images"></i> <?php echo $galleryCount; ?>
                                </span>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                            </td>
                            <td>
                                <?php echo $project['client_name'] ? htmlspecialchars($project['client_name']) : '<span style="color: #999;">-</span>'; ?>
                            </td>
                            <td>
                                <?php if ($project['category']): ?>
                                <span class="category-badge"><?php echo htmlspecialchars($project['category']); ?></span>
                                <?php else: ?>
                                <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $project['status'] ?? 'completed'; ?>">
                                    <?php 
                                    switch($project['status'] ?? 'completed') {
                                        case 'ongoing': echo 'In Progress'; break;
                                        case 'upcoming': echo 'Upcoming'; break;
                                        default: echo 'Completed';
                                    }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $project['completion_date'] ? date('M Y', strtotime($project['completion_date'])) : '-'; ?>
                            </td>
                            <td>
                                <?php if ($project['is_featured']): ?>
                                <span class="featured-badge"><i class="fas fa-star"></i> Featured</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="number" class="order-input" value="<?php echo $project['display_order']; ?>" 
                                       onchange="updateOrder(<?php echo $project['id']; ?>, this.value)" min="0">
                            </td>
                            <td>
                                <button class="btn-gallery" onclick="openGalleryModal(<?php echo $project['id']; ?>)" title="Manage Gallery">
                                    <i class="fas fa-images"></i>
                                </button>
                                <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($project)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-toggle" onclick="toggleFeatured(<?php echo $project['id']; ?>, <?php echo $project['is_featured'] ? 0 : 1; ?>)" title="Toggle Featured">
                                    <i class="fas fa-<?php echo $project['is_featured'] ? 'star' : 'star-half-alt'; ?>"></i>
                                </button>
                                <button class="btn-delete" onclick="deleteProject(<?php echo $project['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="11" style="text-align: center; padding: 3rem;">
                                <i class="fas fa-project-diagram" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p>No projects found. Click "Add New Project" to create your first project.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Instructions -->
            <div style="margin-top: 2rem; background: #e7f3ff; padding: 1rem; border-radius: 5px;">
                <h4 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                    <i class="fas fa-info-circle"></i> Instructions:
                </h4>
                <ul style="margin-left: 1.5rem; color: #666;">
                    <li>Drag the <i class="fas fa-grip-vertical"></i> handle to reorder projects</li>
                    <li>Use the filter bar to quickly find projects by category, status, year, or featured status</li>
                    <li>Click the <i class="fas fa-images"></i> button to manage project gallery images</li>
                    <li>Select multiple projects using checkboxes for bulk operations</li>
                    <li>Featured projects will be highlighted on the website</li>
                    <li>Project status can be: Completed, In Progress, or Upcoming</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Project</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="projectForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="projectId">
                <input type="hidden" name="current_image" id="currentImage">
                <input type="hidden" name="current_gallery" id="currentGallery">
                
                <div class="form-group">
                    <label for="title">Project Title *</label>
                    <input type="text" id="title" name="title" required 
                           placeholder="e.g., Industrial Power Plant Installation">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="client_name">Client Name</label>
                        <input type="text" id="client_name" name="client_name" 
                               placeholder="e.g., ABC Manufacturing">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" 
                               placeholder="e.g., Industrial, Commercial"
                               list="categoryList">
                        <datalist id="categoryList">
                            <?php foreach ($categories as $cat): ?>
                                <?php if ($cat['category']): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="completed">Completed</option>
                            <option value="ongoing">In Progress</option>
                            <option value="upcoming">Upcoming</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="completion_date">Completion Date</label>
                        <input type="date" id="completion_date" name="completion_date">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required 
                              placeholder="Enter detailed project description..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="0" min="0">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_featured" name="is_featured">
                        <label for="is_featured">Feature this project</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image">Main Project Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <div class="info-text">Recommended size: 800x600px. Max size: 5MB</div>
                    
                    <div id="imagePreview" class="image-preview" style="display: none;">
                        <p>Current Image:</p>
                        <div id="previewContent"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Gallery Images</label>
                    <input type="file" id="gallery" name="gallery[]" accept="image/*" multiple>
                    <div class="info-text">You can select multiple images. Max 10 images, 5MB each.</div>
                    
                    <div id="galleryPreview" class="gallery-preview"></div>
                </div>
                
                <button type="submit" class="btn-save" id="saveBtn">Add Project</button>
            </form>
        </div>
    </div>
    
    <!-- Gallery Management Modal -->
    <div id="galleryModal" class="modal">
        <div class="modal-content gallery-modal">
            <div class="modal-header">
                <h2>Manage Gallery Images</h2>
                <span class="close" onclick="closeGalleryModal()">&times;</span>
            </div>
            
            <div id="galleryContent">
                <!-- Gallery images will be loaded here -->
            </div>
            
            <div style="margin-top: 1rem;">
                <input type="file" id="galleryUpload" accept="image/*" multiple>
                <button class="btn-add-gallery" onclick="uploadGalleryImages()">
                    <i class="fas fa-upload"></i> Upload Images
                </button>
            </div>
        </div>
    </div>
    
    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>
    
    <!-- Bulk Delete Form -->
    <form id="bulkDeleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="bulk_delete">
        <input type="hidden" name="ids" id="bulkDeleteIds">
    </form>
    
    <!-- Toggle Featured Form -->
    <form id="toggleForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="id" id="toggleId">
        <input type="hidden" name="featured" id="toggleFeatured">
    </form>
    
    <!-- Update Order Form -->
    <form id="orderForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_order">
        <input type="hidden" name="orders" id="orderData">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        // Initialize sortable for drag-drop reordering
        const sortableBody = document.getElementById('sortableBody');
        if (sortableBody) {
            new Sortable(sortableBody, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function(evt) {
                    updateAllOrders();
                }
            });
        }
        
        // Update all orders after drag
        function updateAllOrders() {
            const rows = document.querySelectorAll('#sortableBody tr');
            const orders = [];
            
            rows.forEach((row, index) => {
                const id = row.dataset.id;
                const orderInput = row.querySelector('.order-input');
                if (orderInput) {
                    orderInput.value = index;
                    orders.push({
                        id: id,
                        order: index
                    });
                }
            });
            
            // Submit order update
            document.getElementById('orderData').value = JSON.stringify(orders);
            document.getElementById('orderForm').submit();
        }
        
        // Update single order
        function updateOrder(id, order) {
            const orders = [{
                id: id,
                order: parseInt(order)
            }];
            
            document.getElementById('orderData').value = JSON.stringify(orders);
            document.getElementById('orderForm').submit();
        }
        
        // Filtering functionality
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('categoryFilter').addEventListener('change', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);
        document.getElementById('yearFilter').addEventListener('change', filterTable);
        document.getElementById('featuredFilter').addEventListener('change', filterTable);
        
        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            const year = document.getElementById('yearFilter').value;
            const featured = document.getElementById('featuredFilter').value;
            const rows = document.querySelectorAll('#sortableBody tr');
            
            rows.forEach(row => {
                const title = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                const client = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
                const rowCategory = row.dataset.category.toLowerCase();
                const rowStatus = row.dataset.status;
                const rowYear = row.dataset.year;
                const rowFeatured = row.dataset.featured;
                
                let show = true;
                
                if (searchTerm && !title.includes(searchTerm) && !client.includes(searchTerm)) {
                    show = false;
                }
                
                if (category && rowCategory !== category.toLowerCase()) {
                    show = false;
                }
                
                if (status && rowStatus !== status) {
                    show = false;
                }
                
                if (year && rowYear !== year) {
                    show = false;
                }
                
                if (featured !== '' && rowFeatured !== featured) {
                    show = false;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }
        
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('yearFilter').value = '';
            document.getElementById('featuredFilter').value = '';
            filterTable();
        }
        
        // Select all functionality
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.select-item');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateDeleteButton();
        }
        
        function updateDeleteButton() {
            const selectedCount = document.querySelectorAll('.select-item:checked').length;
            const deleteBtn = document.getElementById('deleteSelectedBtn');
            
            if (selectedCount > 0) {
                deleteBtn.classList.add('active');
                deleteBtn.innerHTML = `<i class="fas fa-trash"></i> Delete Selected (${selectedCount})`;
            } else {
                deleteBtn.classList.remove('active');
                deleteBtn.innerHTML = `<i class="fas fa-trash"></i> Delete Selected`;
            }
        }
        
        // Delete selected items
        function deleteSelected() {
            const selected = document.querySelectorAll('.select-item:checked');
            if (selected.length === 0) return;
            
            const ids = Array.from(selected).map(cb => cb.value);
            
            if (confirm(`Are you sure you want to delete ${selected.length} project(s)? This action cannot be undone.`)) {
                document.getElementById('bulkDeleteIds').value = JSON.stringify(ids);
                document.getElementById('bulkDeleteForm').submit();
            }
        }
        
        // Bulk actions functionality
        function executeBulkAction() {
            const action = document.getElementById('bulkAction').value;
            const selected = document.querySelectorAll('.select-item:checked');
            
            if (selected.length === 0) {
                alert('Please select at least one project');
                return;
            }
            
            if (!action) {
                alert('Please select an action');
                return;
            }
            
            let confirmMessage = '';
            
            switch(action) {
                case 'delete':
                    confirmMessage = `Delete ${selected.length} project(s)? This action cannot be undone.`;
                    break;
                default:
                    alert('Invalid action selected');
                    return;
            }
            
            if (confirm(confirmMessage)) {
                const ids = Array.from(selected).map(cb => cb.value);
                document.getElementById('bulkDeleteIds').value = JSON.stringify(ids);
                document.getElementById('bulkDeleteForm').submit();
            }
        }
        
        // Update selected count for bulk actions
        function updateSelectedCount() {
            const selectedCount = document.querySelectorAll('.select-item:checked').length;
            document.getElementById('selectedCount').textContent = selectedCount + ' selected';
        }
        
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Project';
            document.getElementById('formAction').value = 'add';
            document.getElementById('projectForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('galleryPreview').innerHTML = '';
            document.getElementById('saveBtn').textContent = 'Add Project';
            document.getElementById('projectModal').style.display = 'block';
        }
        
        function openEditModal(project) {
            document.getElementById('modalTitle').textContent = 'Edit Project';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('projectId').value = project.id;
            document.getElementById('title').value = project.title || '';
            document.getElementById('client_name').value = project.client_name || '';
            document.getElementById('category').value = project.category || '';
            document.getElementById('status').value = project.status || 'completed';
            document.getElementById('completion_date').value = project.completion_date || '';
            document.getElementById('description').value = project.description || '';
            document.getElementById('display_order').value = project.display_order || 0;
            document.getElementById('is_featured').checked = project.is_featured == 1;
            document.getElementById('currentImage').value = project.image_path || '';
            document.getElementById('currentGallery').value = project.gallery || '';
            document.getElementById('saveBtn').textContent = 'Update Project';
            
            // Show image preview
            if (project.image_path) {
                const previewDiv = document.getElementById('imagePreview');
                const previewContent = document.getElementById('previewContent');
                previewContent.innerHTML = `<img src="<?php echo UPLOAD_URL; ?>${project.image_path}" style="max-width: 100%; max-height: 150px;">`;
                previewDiv.style.display = 'block';
            }
            
            // Show gallery preview
            if (project.gallery) {
                const gallery = JSON.parse(project.gallery);
                displayGalleryPreview(gallery);
            }
            
            document.getElementById('projectModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('projectModal').style.display = 'none';
            document.getElementById('projectForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('galleryPreview').innerHTML = '';
        }
        
        // Gallery functions
        function openGalleryModal(projectId) {
            const project = <?php echo json_encode($projects); ?>.find(p => p.id == projectId);
            if (!project) return;
            
            const galleryContent = document.getElementById('galleryContent');
            let html = '<div class="gallery-grid">';
            
            if (project.gallery) {
                const gallery = JSON.parse(project.gallery);
                gallery.forEach(img => {
                    html += `
                        <div class="gallery-item">
                            <img src="<?php echo UPLOAD_URL; ?>${img}">
                            <div class="remove-image" onclick="deleteGalleryImage(${projectId}, '${img}')">
                                <i class="fas fa-times"></i>
                            </div>
                        </div>
                    `;
                });
            }
            
            html += '</div>';
            galleryContent.innerHTML = html;
            
            document.getElementById('galleryModal').style.display = 'block';
            window.currentProjectId = projectId;
        }
        
        function closeGalleryModal() {
            document.getElementById('galleryModal').style.display = 'none';
        }
        
        function displayGalleryPreview(images) {
            const preview = document.getElementById('galleryPreview');
            let html = '';
            
            images.forEach(img => {
                html += `
                    <div class="gallery-item">
                        <img src="<?php echo UPLOAD_URL; ?>${img}">
                    </div>
                `;
            });
            
            preview.innerHTML = html;
        }
        
        function uploadGalleryImages() {
            const files = document.getElementById('galleryUpload').files;
            if (files.length === 0) return;
            
            const formData = new FormData();
            formData.append('action', 'upload_gallery');
            formData.append('project_id', window.currentProjectId);
            
            for (let i = 0; i < files.length; i++) {
                formData.append('gallery[]', files[i]);
            }
            
            fetch('ajax/upload-gallery.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error uploading images');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error uploading images');
            });
        }
        
        function deleteGalleryImage(projectId, imagePath) {
            if (confirm('Are you sure you want to delete this image?')) {
                fetch('ajax/delete-gallery-image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'project_id=' + projectId + '&image_path=' + encodeURIComponent(imagePath)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting image');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting image');
                });
            }
        }
        
        // Delete project
        function deleteProject(id) {
            if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Toggle featured status
        function toggleFeatured(id, newStatus) {
            fetch('ajax/toggle-project-featured.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id + '&featured=' + newStatus
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating featured status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating featured status');
            });
        }
        
        // Preview uploaded image
        document.getElementById('image')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewDiv = document.getElementById('imagePreview');
            const previewContent = document.getElementById('previewContent');
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewContent.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 150px;">`;
                    previewDiv.style.display = 'block';
                };
                
                reader.readAsDataURL(file);
            } else {
                previewDiv.style.display = 'none';
            }
        });
        
        // Preview gallery images
        document.getElementById('gallery')?.addEventListener('change', function(e) {
            const files = e.target.files;
            const preview = document.getElementById('galleryPreview');
            let html = '';
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    html += `
                        <div class="gallery-item">
                            <img src="${e.target.result}" style="width: 100%; height: 80px; object-fit: cover;">
                        </div>
                    `;
                    preview.innerHTML = html;
                };
                
                reader.readAsDataURL(file);
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('projectModal');
            const galleryModal = document.getElementById('galleryModal');
            
            if (event.target == modal) {
                closeModal();
            }
            if (event.target == galleryModal) {
                closeGalleryModal();
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
        
        // Form validation
        document.getElementById('projectForm')?.addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            
            if (!title || !description) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
        
        // Initialize checkboxes
        document.querySelectorAll('.select-item').forEach(checkbox => {
            checkbox.addEventListener('change', updateDeleteButton);
            checkbox.addEventListener('change', updateSelectedCount);
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
