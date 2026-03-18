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
                    'position' => $_POST['position'],
                    'bio' => $_POST['bio'],
                    'social_linkedin' => $_POST['social_linkedin'] ?: null,
                    'display_order' => (int)$_POST['display_order']
                ];
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload = uploadFile($_FILES['image'], 'team', ['jpg', 'jpeg', 'png', 'gif']);
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
                    $db->insert('team_members', $data);
                    $message = 'Team member added successfully!';
                } else {
                    $db->update('team_members', $data, "id = :id", ['id' => $_POST['id']]);
                    $message = 'Team member updated successfully!';
                }
                break;
                
            case 'delete':
                // Get image path to delete file
                $member = $db->fetchOne("SELECT image_path FROM team_members WHERE id = :id", ['id' => $_POST['id']]);
                if ($member && $member['image_path']) {
                    $filePath = UPLOAD_PATH . $member['image_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                $db->delete('team_members', "id = :id", ['id' => $_POST['id']]);
                $message = 'Team member deleted successfully!';
                break;
                
            case 'update_order':
                $orders = json_decode($_POST['orders'], true);
                foreach ($orders as $item) {
                    $db->update('team_members', ['display_order' => $item['order']], "id = :id", ['id' => $item['id']]);
                }
                $message = 'Display order updated successfully!';
                break;
                
            case 'bulk_delete':
                $ids = json_decode($_POST['ids'], true);
                foreach ($ids as $id) {
                    // Get image path to delete file
                    $member = $db->fetchOne("SELECT image_path FROM team_members WHERE id = :id", ['id' => $id]);
                    if ($member && $member['image_path']) {
                        $filePath = UPLOAD_PATH . $member['image_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
                
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->query("DELETE FROM team_members WHERE id IN ($placeholders)", $ids);
                $message = 'Selected team members deleted successfully!';
                break;
        }
    }
}

// Get all team members
$teamMembers = $db->fetchAll("SELECT * FROM team_members ORDER BY display_order, name");

// Get positions for filter
$positions = $db->fetchAll("SELECT DISTINCT position FROM team_members WHERE position IS NOT NULL AND position != '' ORDER BY position");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Manager - GETC Admin</title>
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
                <li><a href="team-manager.php" class="active"><i class="fas fa-users"></i> Team Members</a></li>
                <li><a href="video-manager.php"><i class="fas fa-video"></i> Videos</a></li>
                <li><a href="feedback-manager.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="about-manager.php"><i class="fas fa-info-circle"></i> About Content</a></li>
                <li><a href="settings-manager.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1>Team Manager</h1>
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
                <h2>Manage Team Members</h2>
                <div class="header-actions">
                    <button class="btn-delete-selected" id="deleteSelectedBtn" onclick="deleteSelected()">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn-add" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add New Member
                    </button>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <input type="text" id="searchInput" class="filter-input" placeholder="Search by name or position...">
                
                <select id="positionFilter" class="filter-select">
                    <option value="">All Positions</option>
                    <?php foreach ($positions as $pos): ?>
                        <?php if ($pos['position']): ?>
                        <option value="<?php echo htmlspecialchars($pos['position']); ?>">
                            <?php echo htmlspecialchars($pos['position']); ?>
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                
                <a href="#" class="filter-clear" onclick="clearFilters()">Clear Filters</a>
            </div>
            
            <!-- Team Table -->
            <div class="team-table-container">
                <table class="hero-table" id="teamTable">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" class="select-all" id="selectAll" onclick="toggleSelectAll()">
                            </th>
                            <th width="30"><i class="fas fa-grip-vertical"></i></th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Bio</th>
                            <th>LinkedIn</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sortableBody">
                        <?php foreach ($teamMembers as $index => $member): ?>
                        <tr data-id="<?php echo $member['id']; ?>" 
                            data-name="<?php echo htmlspecialchars($member['name']); ?>"
                            data-position="<?php echo htmlspecialchars($member['position'] ?? ''); ?>">
                            <td>
                                <input type="checkbox" class="select-item" value="<?php echo $member['id']; ?>" onclick="updateDeleteButton()">
                            </td>
                            <td class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </td>
                            <td>
                                <?php if ($member['image_path']): ?>
                                <img src="<?php echo UPLOAD_URL . $member['image_path']; ?>" 
                                     alt="<?php echo htmlspecialchars($member['name']); ?>"
                                     class="hero-preview">
                                <?php else: ?>
                                <div class="member-initials">
                                    <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($member['name']); ?></strong>
                            </td>
                            <td>
                                <?php echo $member['position'] ? htmlspecialchars($member['position']) : '<span style="color: #999;">-</span>'; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars(truncateText($member['bio'] ?? '', 80)); ?>
                            </td>
                            <td>
                                <?php if ($member['social_linkedin']): ?>
                                <a href="<?php echo htmlspecialchars($member['social_linkedin']); ?>" 
                                   target="_blank" class="linkedin-link">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                                <?php else: ?>
                                <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="number" class="order-input" value="<?php echo $member['display_order']; ?>" 
                                       onchange="updateOrder(<?php echo $member['id']; ?>, this.value)" min="0">
                            </td>
                            <td>
                                <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($member)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-delete" onclick="deleteMember(<?php echo $member['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($teamMembers)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 3rem;">
                                <i class="fas fa-users" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p>No team members found. Click "Add New Member" to add your first team member.</p>
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
                    <li>Drag the <i class="fas fa-grip-vertical"></i> handle to reorder team members</li>
                    <li>Upload square images (1:1 ratio) for best results - they will be displayed as circles</li>
                    <li>LinkedIn URLs should start with https://www.linkedin.com/in/</li>
                    <li>Use the filter bar to quickly find team members by name or position</li>
                    <li>Select multiple members using checkboxes for bulk operations</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="teamModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Team Member</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="teamForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="memberId">
                <input type="hidden" name="current_image" id="currentImage">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required 
                               placeholder="e.g., John Doe">
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position/Title *</label>
                        <input type="text" id="position" name="position" required 
                               placeholder="e.g., CEO, Electrical Engineer"
                               list="positionList">
                        <datalist id="positionList">
                            <?php foreach ($positions as $pos): ?>
                                <?php if ($pos['position']): ?>
                                <option value="<?php echo htmlspecialchars($pos['position']); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="bio">Biography</label>
                    <textarea id="bio" name="bio" placeholder="Enter team member's biography, expertise, etc."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="social_linkedin">LinkedIn Profile URL</label>
                        <input type="url" id="social_linkedin" name="social_linkedin" 
                               placeholder="https://www.linkedin.com/in/username">
                        <div class="social-preview">
                            <i class="fab fa-linkedin"></i> 
                            <span id="linkedinPreview" style="color: #666; font-size: 0.85rem;"></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="0" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image">Profile Photo</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <div class="info-text">Recommended: Square image, at least 300x300px. Max size: 2MB</div>
                    
                    <div id="imagePreview" class="image-preview" style="display: none;">
                        <p>Current Photo:</p>
                        <div id="previewContent"></div>
                    </div>
                </div>
                
                <button type="submit" class="btn-save" id="saveBtn">Add Team Member</button>
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
        document.getElementById('positionFilter').addEventListener('change', filterTable);
        
        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const position = document.getElementById('positionFilter').value;
            const rows = document.querySelectorAll('#sortableBody tr');
            
            rows.forEach(row => {
                const name = row.dataset.name.toLowerCase();
                const rowPosition = row.dataset.position.toLowerCase();
                
                let show = true;
                
                if (searchTerm && !name.includes(searchTerm) && !rowPosition.includes(searchTerm)) {
                    show = false;
                }
                
                if (position && rowPosition !== position.toLowerCase()) {
                    show = false;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }
        
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('positionFilter').value = '';
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
            
            if (confirm(`Are you sure you want to delete ${selected.length} team member(s)? This action cannot be undone.`)) {
                document.getElementById('bulkDeleteIds').value = JSON.stringify(ids);
                document.getElementById('bulkDeleteForm').submit();
            }
        }
        
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Team Member';
            document.getElementById('formAction').value = 'add';
            document.getElementById('teamForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('linkedinPreview').textContent = '';
            document.getElementById('saveBtn').textContent = 'Add Team Member';
            document.getElementById('teamModal').style.display = 'block';
        }
        
        function openEditModal(member) {
            document.getElementById('modalTitle').textContent = 'Edit Team Member';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('memberId').value = member.id;
            document.getElementById('name').value = member.name || '';
            document.getElementById('position').value = member.position || '';
            document.getElementById('bio').value = member.bio || '';
            document.getElementById('social_linkedin').value = member.social_linkedin || '';
            document.getElementById('display_order').value = member.display_order || 0;
            document.getElementById('currentImage').value = member.image_path || '';
            document.getElementById('saveBtn').textContent = 'Update Team Member';
            
            // Update LinkedIn preview
            if (member.social_linkedin) {
                document.getElementById('linkedinPreview').textContent = member.social_linkedin;
            }
            
            // Show image preview
            if (member.image_path) {
                const previewDiv = document.getElementById('imagePreview');
                const previewContent = document.getElementById('previewContent');
                previewContent.innerHTML = `<img src="<?php echo UPLOAD_URL; ?>${member.image_path}">`;
                previewDiv.style.display = 'block';
            }
            
            document.getElementById('teamModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('teamModal').style.display = 'none';
            document.getElementById('teamForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('linkedinPreview').textContent = '';
        }
        
        // Delete member
        function deleteMember(id) {
            if (confirm('Are you sure you want to delete this team member? This action cannot be undone.')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Preview uploaded image
        document.getElementById('image')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewDiv = document.getElementById('imagePreview');
            const previewContent = document.getElementById('previewContent');
            
            if (file) {
                // Check file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }
                
                // Check file type
                if (!file.type.match('image.*')) {
                    alert('Please select an image file');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewContent.innerHTML = `<img src="${e.target.result}">`;
                    previewDiv.style.display = 'block';
                };
                
                reader.readAsDataURL(file);
            } else {
                previewDiv.style.display = 'none';
            }
        });
        
        // Preview LinkedIn URL
        document.getElementById('social_linkedin')?.addEventListener('input', function(e) {
            const preview = document.getElementById('linkedinPreview');
            if (this.value) {
                preview.textContent = this.value;
            } else {
                preview.textContent = '';
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('teamModal');
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
        document.getElementById('teamForm')?.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const position = document.getElementById('position').value.trim();
            
            if (!name || !position) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
            
            // Validate LinkedIn URL if provided
            const linkedin = document.getElementById('social_linkedin').value;
            if (linkedin && !linkedin.match(/^https?:\/\/(www\.)?linkedin\.com\/.*$/)) {
                e.preventDefault();
                alert('Please enter a valid LinkedIn URL');
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
