<?php
// Start session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_user_id']) || $_SESSION['admin_user_role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Include database connection
include '../includes/db_connection.php';
include '../includes/functions.php';

// Get categories
$sql = "SELECT * FROM categories ORDER BY name ASC";
$result = mysqli_query($conn, $sql);
$categories = array();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
}

// Initialize messages
$success = '';
$error = '';

// Process add/edit form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if it's add or edit
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $is_edit = $edit_id > 0;
    
    // Get form data
    $name = clean_input($_POST['name']);
    $description = clean_input($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate form data
    if (empty($name)) {
        $error = 'Please enter a category name';
    } else {
        // Handle image upload
        $image_name = '';
        $upload_success = true;
        
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "../assets/images/categories/";
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $image_name = strtolower(str_replace(' ', '-', $name)) . '-' . time() . '.' . $file_extension;
            $target_file = $target_dir . $image_name;
            
            // Check file type
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
            if (!in_array($file_extension, $allowed_types)) {
                $error = 'Only JPG, JPEG, PNG & GIF files are allowed';
                $upload_success = false;
            }
            
            // Check file size (max 5MB)
            if ($_FILES['image']['size'] > 5000000) {
                $error = 'File is too large. Maximum size is 5MB';
                $upload_success = false;
            }
            
            // Upload file
            if ($upload_success && !move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $error = 'Error uploading file';
                $upload_success = false;
            }
        } else if (!$is_edit) {
            // Image is required for new categories
            $error = 'Please upload an image';
            $upload_success = false;
        }
        
        if ($upload_success) {
            if ($is_edit) {
                // Update existing category
                if (!empty($image_name)) {
                    // With new image
                    $sql = "UPDATE categories SET name = ?, description = ?, image = ?, is_active = ?, updated_at = NOW() WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sssii", $name, $description, $image_name, $is_active, $edit_id);
                } else {
                    // Without new image
                    $sql = "UPDATE categories SET name = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssii", $name, $description, $is_active, $edit_id);
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Category updated successfully';
                } else {
                    $error = 'Error updating category: ' . mysqli_error($conn);
                }
            } else {
                // Add new category
                $sql = "INSERT INTO categories (name, description, image, is_active, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssi", $name, $description, $image_name, $is_active);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Category added successfully';
                } else {
                    $error = 'Error adding category: ' . mysqli_error($conn);
                }
            }
            
            // Reload categories
            $result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
            $categories = array();
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $categories[] = $row;
                }
            }
        }
    }
}

// Process delete request
if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
    $delete_id = intval($_GET['delete']);
    
    // Check if category has menu items
    $sql = "SELECT COUNT(*) as item_count FROM menu_items WHERE category_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['item_count'] > 0) {
        $error = 'Cannot delete category because it has menu items associated with it. Please delete or move those items first.';
    } else {
        // Get category image to delete
        $sql = "SELECT image FROM categories WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $image_path = "../assets/images/categories/" . $row['image'];
            
            // Delete image file if exists
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Delete category
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Category deleted successfully';
            
            // Reload categories
            $result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
            $categories = array();
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $categories[] = $row;
                }
            }
        } else {
            $error = 'Error deleting category: ' . mysqli_error($conn);
        }
    }
}

// Include header
include 'includes/header.php';
?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Categories</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus me-2"></i>Add New Category
                    </button>
                </div>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Categories Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="categoriesTable">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Menu Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($categories) > 0): ?>
                                        <?php foreach ($categories as $category): ?>
                                            <?php
                                            // Get number of items in this category
                                            $sql = "SELECT COUNT(*) as item_count FROM menu_items WHERE category_id = " . $category['id'];
                                            $result = mysqli_query($conn, $sql);
                                            $row = mysqli_fetch_assoc($result);
                                            $item_count = $row['item_count'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <img src="../assets/images/categories/<?php echo $category['image']; ?>" alt="<?php echo $category['name']; ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                </td>
                                                <td><?php echo $category['name']; ?></td>
                                                <td><?php echo substr($category['description'], 0, 100); ?><?php echo (strlen($category['description']) > 100) ? '...' : ''; ?></td>
                                                <td>
                                                    <?php if ($category['is_active'] == 1): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $item_count; ?> items</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-primary edit-category" data-bs-toggle="modal" data-bs-target="#editCategoryModal" 
                                                            data-id="<?php echo $category['id']; ?>"
                                                            data-name="<?php echo $category['name']; ?>"
                                                            data-description="<?php echo htmlspecialchars($category['description']); ?>"
                                                            data-active="<?php echo $category['is_active']; ?>"
                                                            data-image="<?php echo $category['image']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($item_count == 0): ?>
                                                            <a href="categories.php?delete=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this category?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-danger" disabled title="Cannot delete category with menu items">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No categories found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
<?php include 'includes/footer.php'; ?>
    
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Image <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                            <small class="text-muted">Recommended size: 500x500 pixels</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_image" class="form-label">Image</label>
                            <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
                            <div id="current_image_container" class="mt-2">
                                <label>Current Image:</label>
                                <img id="current_image" src="" alt="Current Image" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#categoriesTable').DataTable({
                "pageLength": 10,
                "order": [[1, "asc"]]
            });
            
            // Handle edit button click
            $('.edit-category').click(function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var description = $(this).data('description');
                var active = $(this).data('active');
                var image = $(this).data('image');
                
                // Set form values
                $('#edit_id').val(id);
                $('#edit_name').val(name);
                $('#edit_description').val(description);
                $('#edit_is_active').prop('checked', active == 1);
                
                // Set current image
                $('#current_image').attr('src', '../assets/images/categories/' + image);
            });
        });
    </script>
</body>
</html>
