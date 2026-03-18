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
                    'subtitle' => $_POST['subtitle'],
                    'rotating_words' => $_POST['rotating_words'],
                    'media_type' => $_POST['media_type'],
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    'display_order' => (int)$_POST['display_order']
                ];
                
                // Handle file upload
                if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === 0) {
                    $allowedTypes = $_POST['media_type'] === 'video' ? ['mp4', 'webm', 'ogg'] : ['jpg', 'jpeg', 'png', 'gif'];
                    $folder = $_POST['media_type'] === 'video' ? 'heroes/videos' : 'heroes/images';
                    
                    $upload = uploadFile($_FILES['media_file'], $folder, $allowedTypes);
                    if ($upload['success']) {
                        $data['media_path'] = $upload['path'];
                    } else {
                        $error = $upload['message'];
                        break;
                    }
                }
                
                if ($_POST['action'] === 'add' && !isset($data['media_path'])) {
                    $error = 'Media file is required';
                    break;
                }
                
                if ($_POST['action'] === 'add') {
                    $db->insert('heroes', $data);
                    $message = 'Hero slide added successfully!';
                } else {
                    $db->update('heroes', $data, "id = :id", ['id' => $_POST['id']]);
                    $message = 'Hero slide updated successfully!';
                }
                break;
                
            case 'delete':
                // Get media path to delete file
                $hero = $db->fetchOne("SELECT media_path FROM heroes WHERE id = :id", ['id' => $_POST['id']]);
                if ($hero && $hero['media_path']) {
                    $filePath = UPLOAD_PATH . $hero['media_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                $db->delete('heroes', "id = :id", ['id' => $_POST['id']]);
                $message = 'Hero slide deleted successfully!';
                break;
                
            case 'update_order':
                $orders = json_decode($_POST['orders'], true);
                foreach ($orders as $item) {
                    $db->update('heroes', ['display_order' => $item['order']], "id = :id", ['id' => $item['id']]);
                }
                $message = 'Display order updated successfully!';
                break;
        }
    }
}

// Get all heroes
$heroes = $db->fetchAll("SELECT * FROM heroes ORDER BY display_order");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hero Section Manager - GETC Admin</title>
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
                <li><a href="dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                <li><a href="hero-manager.php" class="active"><i class="fas fa-images"></i> Hero Section</a></li>
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
        <div class="main-content">
            <div class="top-bar">
                <h1>Hero Section Manager</h1>
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
            
            <div class="manager-header">
                <h2>Hero Slides</h2>
                <button class="btn-add" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Slide
                </button>
            </div>
            
            <!-- Hero Slides Table -->
            <table class="hero-table" id="heroTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-grip-vertical"></i></th>
                        <th>Preview</th>
                        <th>Title</th>
                        <th>Rotating Words</th>
                        <th>Media Type</th>
                        <th>Status</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sortableBody">
                    <?php foreach ($heroes as $index => $hero): ?>
                    <tr data-id="<?php echo $hero['id']; ?>">
                        <td class="drag-handle">
                            <i class="fas fa-grip-vertical"></i>
                        </td>
                        <td>
                            <?php if ($hero['media_type'] === 'video'): ?>
                            <div class="video-preview">
                                <i class="fas fa-video"></i>
                            </div>
                            <?php else: ?>
                            <img src="<?php echo UPLOAD_URL . $hero['media_path']; ?>" 
                                 alt="<?php echo htmlspecialchars($hero['title']); ?>"
                                 class="hero-preview">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($hero['title']); ?></td>
                        <td><?php echo htmlspecialchars($hero['rotating_words']); ?></td>
                        <td>
                            <span class="status-badge" style="background: #e2e3e5; color: #383d41;">
                                <?php echo ucfirst($hero['media_type']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $hero['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $hero['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <input type="number" class="order-input" value="<?php echo $hero['display_order']; ?>" 
                                   onchange="updateOrder(<?php echo $hero['id']; ?>, this.value)" min="0">
                        </td>
                        <td>
                            <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($hero)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-toggle" onclick="toggleStatus(<?php echo $hero['id']; ?>, <?php echo $hero['is_active'] ? 0 : 1; ?>)">
                                <i class="fas fa-<?php echo $hero['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                            </button>
                            <button class="btn-delete" onclick="deleteHero(<?php echo $hero['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($heroes)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem;">
                            <i class="fas fa-images" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                            <p>No hero slides found. Click "Add New Slide" to create your first hero slide.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Instructions -->
            <div style="margin-top: 2rem; background: #e7f3ff; padding: 1rem; border-radius: 5px;">
                <h4 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                    <i class="fas fa-info-circle"></i> Instructions:
                </h4>
                <ul style="margin-left: 1.5rem; color: #666;">
                    <li>Drag the <i class="fas fa-grip-vertical"></i> handle to reorder slides</li>
                    <li>Rotating words should be comma-separated (e.g., Innovative, Reliable, Global)</li>
                    <li>For videos, supported formats: MP4, WebM, OGG (max 50MB)</li>
                    <li>For images, supported formats: JPG, PNG, GIF (max 10MB)</li>
                    <li>Active slides will be displayed on the website</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="heroModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Hero Slide</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="heroForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="heroId">
                
                <div class="form-group">
                    <label for="title">Slide Title *</label>
                    <input type="text" id="title" name="title" required 
                           placeholder="e.g., Welcome to GETC Ltd">
                </div>
                
                <div class="form-group">
                    <label for="rotating_words">Rotating Words *</label>
                    <input type="text" id="rotating_words" name="rotating_words" required 
                           placeholder="e.g., Innovative, Reliable, Global">
                    <div class="info-text">Comma-separated words that will rotate</div>
                </div>
                
                <div class="form-group">
                    <label for="subtitle">Subtitle</label>
                    <textarea id="subtitle" name="subtitle" rows="2" 
                              placeholder="Enter subtitle text"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="media_type">Media Type *</label>
                        <select id="media_type" name="media_type" onchange="toggleMediaType()" required>
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="0" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="media_file" id="media_label">Upload Image *</label>
                    <input type="file" id="media_file" name="media_file" accept="image/*,video/*">
                    <div class="info-text" id="media_info">Supported: JPG, PNG, GIF (max 10MB)</div>
                    
                    <div id="mediaPreview" class="media-preview" style="display: none;">
                        <p>Current Media:</p>
                        <div id="previewContent"></div>
                    </div>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="is_active" name="is_active" checked>
                    <label for="is_active">Active (show on website)</label>
                </div>
                
                <button type="submit" class="btn-save" id="saveBtn">Save Hero Slide</button>
            </form>
        </div>
    </div>
    
    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>
    
    <!-- Toggle Status Form -->
    <form id="toggleForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="id" id="toggleId">
        <input type="hidden" name="status" id="toggleStatus">
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
        
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Hero Slide';
            document.getElementById('formAction').value = 'add';
            document.getElementById('heroForm').reset();
            document.getElementById('mediaPreview').style.display = 'none';
            document.getElementById('media_file').required = true;
            document.getElementById('saveBtn').textContent = 'Add Hero Slide';
            document.getElementById('heroModal').style.display = 'block';
            toggleMediaType();
        }
        
        function openEditModal(hero) {
            document.getElementById('modalTitle').textContent = 'Edit Hero Slide';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('heroId').value = hero.id;
            document.getElementById('title').value = hero.title || '';
            document.getElementById('subtitle').value = hero.subtitle || '';
            document.getElementById('rotating_words').value = hero.rotating_words || '';
            document.getElementById('media_type').value = hero.media_type;
            document.getElementById('display_order').value = hero.display_order || 0;
            document.getElementById('is_active').checked = hero.is_active == 1;
            document.getElementById('media_file').required = false;
            document.getElementById('saveBtn').textContent = 'Update Hero Slide';
            
            // Show media preview
            if (hero.media_path) {
                const previewDiv = document.getElementById('mediaPreview');
                const previewContent = document.getElementById('previewContent');
                
                if (hero.media_type === 'video') {
                    previewContent.innerHTML = `
                        <video controls style="max-width: 100%; max-height: 150px;">
                            <source src="<?php echo UPLOAD_URL; ?>${hero.media_path}" type="video/mp4">
                        </video>
                    `;
                } else {
                    previewContent.innerHTML = `<img src="<?php echo UPLOAD_URL; ?>${hero.media_path}" style="max-width: 100%; max-height: 150px;">`;
                }
                
                previewDiv.style.display = 'block';
            }
            
            document.getElementById('heroModal').style.display = 'block';
            toggleMediaType();
        }
        
        function closeModal() {
            document.getElementById('heroModal').style.display = 'none';
            document.getElementById('heroForm').reset();
            document.getElementById('mediaPreview').style.display = 'none';
        }
        
        // Toggle media type
        function toggleMediaType() {
            const mediaType = document.getElementById('media_type').value;
            const mediaFile = document.getElementById('media_file');
            const mediaLabel = document.getElementById('media_label');
            const mediaInfo = document.getElementById('media_info');
            
            if (mediaType === 'video') {
                mediaFile.accept = 'video/*';
                mediaLabel.textContent = 'Upload Video *';
                mediaInfo.textContent = 'Supported: MP4, WebM, OGG (max 50MB)';
            } else {
                mediaFile.accept = 'image/*';
                mediaLabel.textContent = 'Upload Image *';
                mediaInfo.textContent = 'Supported: JPG, PNG, GIF (max 10MB)';
            }
        }
        
        // Delete hero
        function deleteHero(id) {
            if (confirm('Are you sure you want to delete this hero slide? This action cannot be undone.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Toggle status
        function toggleStatus(id, newStatus) {
            fetch('ajax/toggle-hero-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id + '&status=' + newStatus
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status');
            });
        }
        
        // Preview uploaded file
        document.getElementById('media_file')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const mediaType = document.getElementById('media_type').value;
            const previewDiv = document.getElementById('mediaPreview');
            const previewContent = document.getElementById('previewContent');
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (mediaType === 'video') {
                        previewContent.innerHTML = `
                            <video controls style="max-width: 100%; max-height: 150px;">
                                <source src="${e.target.result}" type="${file.type}">
                            </video>
                        `;
                    } else {
                        previewContent.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 150px;">`;
                    }
                    
                    previewDiv.style.display = 'block';
                };
                
                reader.readAsDataURL(file);
            } else {
                previewDiv.style.display = 'none';
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('heroModal');
            if (event.target == modal) {
                closeModal();
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
        document.getElementById('heroForm')?.addEventListener('submit', function(e) {
            const mediaFile = document.getElementById('media_file');
            const action = document.getElementById('formAction').value;
            
            if (action === 'add' && !mediaFile.files.length) {
                e.preventDefault();
                alert('Please select a media file to upload');
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
