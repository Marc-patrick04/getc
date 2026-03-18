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
                // Handle file upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload = uploadFile($_FILES['image'], 'products');
                    if ($upload['success']) {
                        $data = [
                            'name' => $_POST['name'],
                            'description' => $_POST['description'],
                            'image_path' => $upload['path'],
                            'category' => $_POST['category'],
                            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                            'display_order' => $_POST['display_order']
                        ];
                        
                        $db->insert('products', $data);
                        $message = 'Product added successfully!';
                    } else {
                        $error = $upload['message'];
                    }
                }
                break;
                
            case 'edit':
                $data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'category' => $_POST['category'],
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'display_order' => $_POST['display_order']
                ];
                
                // Handle new image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload = uploadFile($_FILES['image'], 'products');
                    if ($upload['success']) {
                        $data['image_path'] = $upload['path'];
                    }
                }
                
                $db->update('products', $data, "id = :id", ['id' => $_POST['id']]);
                $message = 'Product updated successfully!';
                break;
                
            case 'delete':
                // Get image path to delete file
                $product = $db->fetchOne("SELECT image_path FROM products WHERE id = :id", ['id' => $_POST['id']]);
                if ($product && $product['image_path']) {
                    $filePath = UPLOAD_PATH . $product['image_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                $db->delete('products', "id = :id", ['id' => $_POST['id']]);
                $message = 'Product deleted successfully!';
                break;
        }
    }
}

// Get all products
$products = $db->fetchAll("SELECT * FROM products ORDER BY display_order");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Manager - GETC Admin</title>
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
                <li><a href="hero-manager.php"><i class="fas fa-images"></i> Hero Section</a></li>
                <li><a href="product-manager.php" class="active"><i class="fas fa-box"></i> Products</a></li>
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
                <h1>Product Manager</h1>
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
            
            <div class="manager-header">
                <h2>Products List</h2>
                <button class="btn-add" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Product
                </button>
            </div>
            
            <table class="hero-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Featured</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <?php if ($product['image_path']): ?>
                            <img src="<?php echo UPLOAD_URL . $product['image_path']; ?>" 
                                 alt="<?php echo $product['name']; ?>" class="hero-preview">
                            <?php endif; ?>
                        </td>
                        <td><?php echo $product['name']; ?></td>
                        <td><?php echo $product['category']; ?></td>
                        <td><?php echo truncateText($product['description'], 50); ?></td>
                        <td>
                            <?php if ($product['is_featured']): ?>
                            <span class="featured-badge">Featured</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $product['display_order']; ?></td>
                        <td>
                            <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Product</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="productForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="productId">
                
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <div id="currentImage" style="margin-top: 10px;"></div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="is_featured" name="is_featured">
                        Feature this product
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" value="0">
                </div>
                
                <button type="submit" class="btn-save">Save Product</button>
            </form>
        </div>
    </div>
    
    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productForm').reset();
            document.getElementById('currentImage').innerHTML = '';
            document.getElementById('productModal').style.display = 'block';
        }
        
        function openEditModal(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('productId').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('category').value = product.category || '';
            document.getElementById('description').value = product.description || '';
            document.getElementById('is_featured').checked = product.is_featured == 1;
            document.getElementById('display_order').value = product.display_order || 0;
            
            if (product.image_path) {
                document.getElementById('currentImage').innerHTML = 
                    '<img src="<?php echo UPLOAD_URL; ?>' + product.image_path + '" style="max-width: 100px; max-height: 100px;">' +
                    '<p>Current image. Upload new to replace.</p>';
            }
            
            document.getElementById('productModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                closeModal();
            }
        }

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
