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

// Get all categories
$categories = get_categories();

// Get menu items with their categories
$sql = "SELECT m.*, c.name as category_name 
        FROM menu_items m 
        JOIN categories c ON m.category_id = c.id 
        ORDER BY m.category_id, m.name";
$result = mysqli_query($conn, $sql);
$menu_items = array();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $menu_items[] = $row;
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
    $category_id = intval($_POST['category_id']);
    $description = clean_input($_POST['description']);
    $price = floatval($_POST['price']);
    $is_veg = isset($_POST['is_veg']) ? 1 : 0;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;
    
    // Validate form data
    if (empty($name) || $category_id <= 0 || empty($description) || $price <= 0) {
        $error = 'Please fill in all required fields';
    } else {
        // Handle image upload
        $image_name = '';
        $upload_success = true;
        
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "../assets/images/food/";
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
            // Image is required for new items
            $error = 'Please upload an image';
            $upload_success = false;
        }
        
        if ($upload_success) {
            if ($is_edit) {
                // Update existing menu item
                if (!empty($image_name)) {
                    // With new image
                    $sql = "UPDATE menu_items SET 
                            category_id = ?, 
                            name = ?, 
                            description = ?, 
                            price = ?, 
                            image = ?, 
                            is_veg = ?, 
                            is_available = ?, 
                            is_popular = ?, 
                            updated_at = NOW() 
                            WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "issdsiiii", $category_id, $name, $description, $price, $image_name, $is_veg, $is_available, $is_popular, $edit_id);
                } else {
                    // Without new image
                    $sql = "UPDATE menu_items SET 
                            category_id = ?, 
                            name = ?, 
                            description = ?, 
                            price = ?, 
                            is_veg = ?, 
                            is_available = ?, 
                            is_popular = ?, 
                            updated_at = NOW() 
                            WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "issdiiii", $category_id, $name, $description, $price, $is_veg, $is_available, $is_popular, $edit_id);
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Menu item updated successfully';
                } else {
                    $error = 'Error updating menu item: ' . mysqli_error($conn);
                }
            } else {
                // Add new menu item
                $sql = "INSERT INTO menu_items (category_id, name, description, price, image, is_veg, is_available, is_popular, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "issdsiii", $category_id, $name, $description, $price, $image_name, $is_veg, $is_available, $is_popular);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Menu item added successfully';
                } else {
                    $error = 'Error adding menu item: ' . mysqli_error($conn);
                }
            }
            
            // Reload menu items
            $result = mysqli_query($conn, "SELECT m.*, c.name as category_name FROM menu_items m JOIN categories c ON m.category_id = c.id ORDER BY m.category_id, m.name");
            $menu_items = array();
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $menu_items[] = $row;
                }
            }
        }
    }
}

// Process delete request
if (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
    $delete_id = intval($_GET['delete']);
    
    // Get item image to delete
    $sql = "SELECT image FROM menu_items WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $image_path = "../assets/images/food/" . $row['image'];
        
        // Delete image file if exists
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Delete menu item
    $sql = "DELETE FROM menu_items WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Menu item deleted successfully';
        
        // Reload menu items
        $result = mysqli_query($conn, "SELECT m.*, c.name as category_name FROM menu_items m JOIN categories c ON m.category_id = c.id ORDER BY m.category_id, m.name");
        $menu_items = array();
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $menu_items[] = $row;
            }
        }
    } else {
        $error = 'Error deleting menu item: ' . mysqli_error($conn);
    }
}

// Include header
include 'includes/header.php';
?>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Menu Items</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus me-2"></i>Add New Item
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
                
                <!-- Menu Items Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="menuItemsTable">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Popular</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($menu_items) > 0): ?>
                                        <?php foreach ($menu_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <img src="../assets/images/food/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                </td>
                                                <td><?php echo $item['name']; ?></td>
                                                <td><?php echo $item['category_name']; ?></td>
                                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                                <td>
                                                    <?php if ($item['is_veg'] == 1): ?>
                                                        <span class="badge bg-success">Veg</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Non-Veg</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($item['is_available'] == 1): ?>
                                                        <span class="badge bg-success">Available</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Not Available</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($item['is_popular'] == 1): ?>
                                                        <span class="badge bg-primary">Popular</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Regular</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-primary edit-item" data-bs-toggle="modal" data-bs-target="#editItemModal" 
                                                            data-id="<?php echo $item['id']; ?>"
                                                            data-name="<?php echo $item['name']; ?>"
                                                            data-category="<?php echo $item['category_id']; ?>"
                                                            data-description="<?php echo htmlspecialchars($item['description']); ?>"
                                                            data-price="<?php echo $item['price']; ?>"
                                                            data-veg="<?php echo $item['is_veg']; ?>"
                                                            data-available="<?php echo $item['is_available']; ?>"
                                                            data-popular="<?php echo $item['is_popular']; ?>"
                                                            data-image="<?php echo $item['image']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="menu_items.php?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No menu items found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
<?php include 'includes/footer.php'; ?>
    
    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">Add New Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price (₹) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="image" class="form-label">Image <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                    <small class="text-muted">Recommended size: 500x500 pixels</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_veg" name="is_veg">
                                        <label class="form-check-label" for="is_veg">Vegetarian</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_available" name="is_available" checked>
                                        <label class="form-check-label" for="is_available">Available</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_popular" name="is_popular">
                                        <label class="form-check-label" for="is_popular">Popular Item</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">Edit Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="edit_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_price" class="form-label">Price (₹) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="edit_price" name="price" min="0" step="0.01" required>
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
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="4" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="edit_is_veg" name="is_veg">
                                        <label class="form-check-label" for="edit_is_veg">Vegetarian</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="edit_is_available" name="is_available">
                                        <label class="form-check-label" for="edit_is_available">Available</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="edit_is_popular" name="is_popular">
                                        <label class="form-check-label" for="edit_is_popular">Popular Item</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Item</button>
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
            $('#menuItemsTable').DataTable({
                "pageLength": 25,
                "order": [[1, "asc"]]
            });
            
            // Handle edit button click
            $('.edit-item').click(function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var category = $(this).data('category');
                var description = $(this).data('description');
                var price = $(this).data('price');
                var veg = $(this).data('veg');
                var available = $(this).data('available');
                var popular = $(this).data('popular');
                var image = $(this).data('image');
                
                // Set form values
                $('#edit_id').val(id);
                $('#edit_name').val(name);
                $('#edit_category_id').val(category);
                $('#edit_description').val(description);
                $('#edit_price').val(price);
                $('#edit_is_veg').prop('checked', veg == 1);
                $('#edit_is_available').prop('checked', available == 1);
                $('#edit_is_popular').prop('checked', popular == 1);
                
                // Set current image
                $('#current_image').attr('src', '../assets/images/food/' + image);
            });
        });
    </script>
</body>
</html>
