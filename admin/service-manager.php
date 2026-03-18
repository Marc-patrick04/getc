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
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'category' => $_POST['category'] ?: null,
                    'icon' => $_POST['icon'] ?: null,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'display_order' => (int)$_POST['display_order']
                ];
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload = uploadFile($_FILES['image'], 'services', ['jpg', 'jpeg', 'png', 'gif']);
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
                
                if ($_POST['action'] === 'add') {
                    $db->insert('services', $data);
                    $message = 'Service added successfully!';
                } else {
                    $db->update('services', $data, "id = :id", ['id' => $_POST['id']]);
                    $message = 'Service updated successfully!';
                }
                break;
                
            case 'delete':
                // Get image path to delete file
                $service = $db->fetchOne("SELECT image_path FROM services WHERE id = :id", ['id' => $_POST['id']]);
                if ($service && $service['image_path']) {
                    $filePath = UPLOAD_PATH . $service['image_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                $db->delete('services', "id = :id", ['id' => $_POST['id']]);
                $message = 'Service deleted successfully!';
                break;
                
            case 'update_order':
                $orders = json_decode($_POST['orders'], true);
                foreach ($orders as $item) {
                    $db->update('services', ['display_order' => $item['order']], "id = :id", ['id' => $item['id']]);
                }
                $message = 'Display order updated successfully!';
                break;
                
            case 'bulk_delete':
                $ids = json_decode($_POST['ids'], true);
                foreach ($ids as $id) {
                    // Get image path to delete file
                    $service = $db->fetchOne("SELECT image_path FROM services WHERE id = :id", ['id' => $id]);
                    if ($service && $service['image_path']) {
                        $filePath = UPLOAD_PATH . $service['image_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
                
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->query("DELETE FROM services WHERE id IN ($placeholders)", $ids);
                $message = 'Selected services deleted successfully!';
                break;
        }
    }
}

// Get all services
$services = $db->fetchAll("SELECT * FROM services ORDER BY display_order, name");

// Get unique categories for filter
$categories = $db->fetchAll("SELECT DISTINCT category FROM services WHERE category IS NOT NULL AND category != '' ORDER BY category");

// Get icon list for selection
$icons = [
    'bolt' => 'Bolt (Electrical)',
    'cog' => 'Cog (Mechanical)',
    'gears' => 'Gears (Industrial)',
    'lightbulb' => 'Lightbulb (Innovation)',
    'plug' => 'Plug (Connection)',
    'microchip' => 'Microchip (Technology)',
    'server' => 'Server (IT)',
    'wifi' => 'WiFi (Wireless)',
    'solar-panel' => 'Solar Panel (Renewable)',
    'wind' => 'Wind (Renewable)',
    'battery-full' => 'Battery (Power)',
    'industry' => 'Industry (Manufacturing)',
    'robot' => 'Robot (Automation)',
    'chart-line' => 'Chart (Analytics)',
    'shield-alt' => 'Shield (Security)',
    'tools' => 'Tools (Maintenance)',
    'hammer' => 'Hammer (Construction)',
    'paint-roller' => 'Paint Roller (Finishing)',
    'hard-hat' => 'Hard Hat (Safety)',
    'certificate' => 'Certificate (Quality)'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Manager - GETC Admin</title>
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
                <li><a href="service-manager.php" class="active"><i class="fas fa-cogs"></i> Services</a></li>
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
                <h1>Service Manager</h1>
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
                <h2>Manage Services</h2>
                <div class="header-actions">
                    <button class="btn-delete-selected" id="deleteSelectedBtn" onclick="deleteSelected()">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn-add" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add New Service
                    </button>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <input type="text" id="searchInput" class="filter-input" placeholder="Search services..." style="flex: 1;">
                
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
                
                <select id="featuredFilter" class="filter-select">
                    <option value="">All Services</option>
                    <option value="1">Featured Only</option>
                    <option value="0">Non-Featured Only</option>
                </select>
                
                <a href="#" class="filter-clear" onclick="clearFilters()">Clear Filters</a>
            </div>
            
            <!-- Services Table -->
            <div class="services-table-container">
                <table class="hero-table" id="servicesTable">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" class="select-all" id="selectAll" onclick="toggleSelectAll()">
                            </th>
                            <th width="30"><i class="fas fa-grip-vertical"></i></th>
                            <th>Image/Icon</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sortableBody">
                        <?php foreach ($services as $index => $service): ?>
                        <tr data-id="<?php echo $service['id']; ?>" data-category="<?php echo htmlspecialchars($service['category'] ?? ''); ?>" data-featured="<?php echo $service['is_featured']; ?>">
                            <td>
                                <input type="checkbox" class="select-item" value="<?php echo $service['id']; ?>" onclick="updateDeleteButton()">
                            </td>
                            <td class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </td>
                            <td>
                                <?php if ($service['image_path']): ?>
                                <img src="<?php echo UPLOAD_URL . $service['image_path']; ?>" 
                                     alt="<?php echo htmlspecialchars($service['name']); ?>"
                                     class="hero-preview">
                                <?php elseif ($service['icon']): ?>
                                <div class="service-icon">
                                    <i class="fas fa-<?php echo htmlspecialchars($service['icon']); ?>"></i>
                                </div>
                                <?php else: ?>
                                <div class="service-icon">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($service['name']); ?></strong>
                            </td>
                            <td>
                                <?php if ($service['category']): ?>
                                <span class="category-badge"><?php echo htmlspecialchars($service['category']); ?></span>
                                <?php else: ?>
                                <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars(truncateText($service['description'], 80)); ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $service['is_featured'] ? 'status-featured' : 'status-normal'; ?>">
                                    <?php echo $service['is_featured'] ? 'Featured' : 'Normal'; ?>
                                </span>
                            </td>
                            <td>
                                <input type="number" class="order-input" value="<?php echo $service['display_order']; ?>" 
                                       onchange="updateOrder(<?php echo $service['id']; ?>, this.value)" min="0">
                            </td>
                            <td>
                                <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($service)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-toggle" onclick="toggleFeatured(<?php echo $service['id']; ?>, <?php echo $service['is_featured'] ? 0 : 1; ?>)">
                                    <i class="fas fa-<?php echo $service['is_featured'] ? 'star' : 'star-half-alt'; ?>"></i>
                                </button>
                                <button class="btn-delete" onclick="deleteService(<?php echo $service['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 3rem;">
                                <i class="fas fa-cogs" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p>No services found. Click "Add New Service" to create your first service.</p>
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
                    <li>Drag the <i class="fas fa-grip-vertical"></i> handle to reorder services</li>
                    <li>Use the filter bar to quickly find services by name, category, or featured status</li>
                    <li>Select multiple services using checkboxes for bulk operations</li>
                    <li>Icons use Font Awesome 6 - enter icon names without the "fa-" prefix (e.g., "bolt", "cog")</li>
                    <li>Featured services will be highlighted on the website</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Service</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="serviceForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="serviceId">
                <input type="hidden" name="current_image" id="currentImage">
                
                <div class="form-group">
                    <label for="name">Service Name *</label>
                    <input type="text" id="name" name="name" required 
                           placeholder="e.g., Electrical Installation">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" 
                               placeholder="e.g., Installation, Maintenance"
                               list="categoryList">
                        <datalist id="categoryList">
                            <?php foreach ($categories as $cat): ?>
                                <?php if ($cat['category']): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="form-group">
                        <label for="icon">Icon Name</label>
                        <input type="text" id="icon" name="icon" 
                               placeholder="e.g., bolt, cog, lightbulb"
                               list="iconList">
                        <datalist id="iconList">
                            <?php foreach (array_keys($icons) as $icon): ?>
                                <option value="<?php echo $icon; ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <div class="info-text">Font Awesome 6 icon name (without fa- prefix)</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required 
                              placeholder="Enter detailed service description..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="0" min="0">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_featured" name="is_featured">
                        <label for="is_featured">Feature this service</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image">Service Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <div class="info-text">Recommended size: 600x400px. Max size: 5MB</div>
                    
                    <div id="imagePreview" class="image-preview" style="display: none;">
                        <p>Current Image:</p>
                        <div id="previewContent"></div>
                    </div>
                </div>
                
                <button type="submit" class="btn-save" id="saveBtn">Add Service</button>
            </form>
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
        document.getElementById('featuredFilter').addEventListener('change', filterTable);
        
        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const featured = document.getElementById('featuredFilter').value;
            const rows = document.querySelectorAll('#sortableBody tr');
            
            rows.forEach(row => {
                const name = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                const description = row.querySelector('td:nth-child(6)').textContent.toLowerCase();
                const rowCategory = row.dataset.category.toLowerCase();
                const rowFeatured = row.dataset.featured;
                
                let show = true;
                
                if (searchTerm && !name.includes(searchTerm) && !description.includes(searchTerm)) {
                    show = false;
                }
                
                if (category && rowCategory !== category.toLowerCase()) {
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
            
            if (confirm(`Are you sure you want to delete ${selected.length} service(s)? This action cannot be undone.`)) {
                document.getElementById('bulkDeleteIds').value = JSON.stringify(ids);
                document.getElementById('bulkDeleteForm').submit();
            }
        }
        
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Service';
            document.getElementById('formAction').value = 'add';
            document.getElementById('serviceForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('saveBtn').textContent = 'Add Service';
            document.getElementById('serviceModal').style.display = 'block';
        }
        
        function openEditModal(service) {
            document.getElementById('modalTitle').textContent = 'Edit Service';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('serviceId').value = service.id;
            document.getElementById('name').value = service.name || '';
            document.getElementById('category').value = service.category || '';
            document.getElementById('icon').value = service.icon || '';
            document.getElementById('description').value = service.description || '';
            document.getElementById('display_order').value = service.display_order || 0;
            document.getElementById('is_featured').checked = service.is_featured == 1;
            document.getElementById('currentImage').value = service.image_path || '';
            document.getElementById('saveBtn').textContent = 'Update Service';
            
            // Show image preview
            if (service.image_path) {
                const previewDiv = document.getElementById('imagePreview');
                const previewContent = document.getElementById('previewContent');
                previewContent.innerHTML = `<img src="<?php echo UPLOAD_URL; ?>${service.image_path}" style="max-width: 100%; max-height: 150px;">`;
                previewDiv.style.display = 'block';
            }
            
            document.getElementById('serviceModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('serviceModal').style.display = 'none';
            document.getElementById('serviceForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
        }
        
        // Delete service
        function deleteService(id) {
            if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Toggle featured status
        function toggleFeatured(id, newStatus) {
            fetch('ajax/toggle-service-featured.php', {
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
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('serviceModal');
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
        document.getElementById('serviceForm')?.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const description = document.getElementById('description').value.trim();
            
            if (!name || !description) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
        
        // Initialize checkboxes
        document.querySelectorAll('.select-item').forEach(checkbox => {
            checkbox.addEventListener('change', updateDeleteButton);
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
