<?php
// Start session
session_start();

// Include database connection
include '../includes/db_connection.php';
include '../includes/functions.php';

// Get categories
$categories = get_categories();

// Get selected category
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Get search term
$search_term = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Get sort option
$sort_option = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'name_asc';

// Get veg filter
$veg_filter = isset($_GET['veg_filter']) ? clean_input($_GET['veg_filter']) : 'all';

// Pagination settings
$items_per_page = 24; // Number of items to display per page
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $items_per_page;

// Determine the ORDER BY clause based on sort option
$order_by = 'name ASC'; // Default sorting
switch ($sort_option) {
    case 'price_asc':
        $order_by = 'price ASC';
        break;
    case 'price_desc':
        $order_by = 'price DESC';
        break;
    case 'name_desc':
        $order_by = 'name DESC';
        break;
    case 'name_asc':
    default:
        $order_by = 'name ASC';
        break;
}

// Get menu items with pagination
$menu_items = array();
$total_items = 0;

// Build the SQL query based on filters
if (!empty($search_term)) {
    // Count total items for pagination
    if ($selected_category > 0) {
        // Add veg filter condition if not 'all'
        if ($veg_filter == 'veg') {
            $count_sql = "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1 AND category_id = ? AND is_veg = 1 AND (name LIKE ? OR description LIKE ?)";
        } elseif ($veg_filter == 'non_veg') {
            $count_sql = "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1 AND category_id = ? AND is_veg = 0 AND (name LIKE ? OR description LIKE ?)";
        } else {
            $count_sql = "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1 AND category_id = ? AND (name LIKE ? OR description LIKE ?)";
        }
        $count_stmt = mysqli_prepare($conn, $count_sql);
        $search_param = "%$search_term%";
        mysqli_stmt_bind_param($count_stmt, "iss", $selected_category, $search_param, $search_param);
    } else {
        // Add veg filter condition if not 'all'
        if ($veg_filter == 'veg') {
            $count_sql = "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1 AND is_veg = 1 AND (name LIKE ? OR description LIKE ?)";
        } elseif ($veg_filter == 'non_veg') {
            $count_sql = "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1 AND is_veg = 0 AND (name LIKE ? OR description LIKE ?)";
        } else {
            $count_sql = "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1 AND (name LIKE ? OR description LIKE ?)";
        }
        $count_stmt = mysqli_prepare($conn, $count_sql);
        $search_param = "%$search_term%";
        mysqli_stmt_bind_param($count_stmt, "ss", $search_param, $search_param);
    }
    
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_items = $count_row['total'];
    mysqli_stmt_close($count_stmt);
    
    // Get paginated results
    if ($selected_category > 0) {
        // Add veg filter condition if not 'all'
        if ($veg_filter == 'veg') {
            $sql = "SELECT * FROM menu_items WHERE is_available = 1 AND category_id = ? AND is_veg = 1 AND (name LIKE ? OR description LIKE ?) ORDER BY $order_by LIMIT ? OFFSET ?";
        } elseif ($veg_filter == 'non_veg') {
            $sql = "SELECT * FROM menu_items WHERE is_available = 1 AND category_id = ? AND is_veg = 0 AND (name LIKE ? OR description LIKE ?) ORDER BY $order_by LIMIT ? OFFSET ?";
        } else {
            $sql = "SELECT * FROM menu_items WHERE is_available = 1 AND category_id = ? AND (name LIKE ? OR description LIKE ?) ORDER BY $order_by LIMIT ? OFFSET ?";
        }
        $stmt = mysqli_prepare($conn, $sql);
        $search_param = "%$search_term%";
        mysqli_stmt_bind_param($stmt, "issii", $selected_category, $search_param, $search_param, $items_per_page, $offset);
    } else {
        // Add veg filter condition if not 'all'
        if ($veg_filter == 'veg') {
            $sql = "SELECT * FROM menu_items WHERE is_available = 1 AND is_veg = 1 AND (name LIKE ? OR description LIKE ?) ORDER BY $order_by LIMIT ? OFFSET ?";
        } elseif ($veg_filter == 'non_veg') {
            $sql = "SELECT * FROM menu_items WHERE is_available = 1 AND is_veg = 0 AND (name LIKE ? OR description LIKE ?) ORDER BY $order_by LIMIT ? OFFSET ?";
        } else {
            $sql = "SELECT * FROM menu_items WHERE is_available = 1 AND (name LIKE ? OR description LIKE ?) ORDER BY $order_by LIMIT ? OFFSET ?";
        }
        $stmt = mysqli_prepare($conn, $sql);
        $search_param = "%$search_term%";
        mysqli_stmt_bind_param($stmt, "ssii", $search_param, $search_param, $items_per_page, $offset);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $menu_items[] = $row;
        }
    }
} else {
    // No search term, just filter by category if selected
    if ($selected_category > 0) {
        // Count total items for pagination with veg filter
        if ($veg_filter == 'veg') {
            $count_sql = "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1 AND category_id = ? AND is_veg = 1";
        } elseif ($veg_filter == 'non_veg') {
            $count_sql = "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1 AND category_id = ? AND is_veg = 0";
        } else {
            $count_sql = "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1 AND category_id = ?";
        }
        $count_stmt = mysqli_prepare($conn, $count_sql);
        mysqli_stmt_bind_param($count_stmt, "i", $selected_category);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count_row = mysqli_fetch_assoc($count_result);
        $total_items = $count_row['total'];
        mysqli_stmt_close($count_stmt);
        
        // Get paginated results with veg filter and sorting
        if ($veg_filter == 'veg') {
            $sql = "SELECT * FROM menu_items WHERE is_available = 1 AND category_id = ? AND is_veg = 1 ORDER BY $order_by LIMIT ? OFFSET ?";
        } elseif ($veg_filter == 'non_veg') {
            $sql = "SELECT * FROM menu_items WHERE is_available = 1 AND category_id = ? AND is_veg = 0 ORDER BY $order_by LIMIT ? OFFSET ?";
        } else {
            $sql = "SELECT * FROM menu_items WHERE is_available = 1 AND category_id = ? ORDER BY $order_by LIMIT ? OFFSET ?";
        }
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $selected_category, $items_per_page, $offset);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $menu_items[] = $row;
            }
        }
    } else {
        // Count total items for pagination with veg filter
        if ($veg_filter == 'veg') {
            $count_sql = "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1 AND is_veg = 1";
        } elseif ($veg_filter == 'non_veg') {
            $count_sql = "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1 AND is_veg = 0";
        } else {
            $count_sql = "SELECT COUNT(*) as total FROM menu_items WHERE is_available = 1";
        }
        $count_stmt = mysqli_prepare($conn, $count_sql);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count_row = mysqli_fetch_assoc($count_result);
        $total_items = $count_row['total'];
        mysqli_stmt_close($count_stmt);
        
        // Get all menu items with pagination with veg filter
        if ($veg_filter == 'veg') {
            $sql = "SELECT * FROM menu_items WHERE is_available = 1 AND is_veg = 1 ORDER BY $order_by LIMIT ? OFFSET ?";
        } elseif ($veg_filter == 'non_veg') {
            $sql = "SELECT * FROM menu_items WHERE is_available = 1 AND is_veg = 0 ORDER BY $order_by LIMIT ? OFFSET ?";
        } else {
            $sql = "SELECT * FROM menu_items WHERE is_available = 1 ORDER BY $order_by LIMIT ? OFFSET ?";
        }
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $items_per_page, $offset);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $menu_items[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Hungry Heaven</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/custom.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Search suggestions styles */
        .search-form {
            position: relative;
        }
        
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 0 0 4px 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            max-height: 350px;
            overflow-y: auto;
            display: none;
        }
        
        .suggestion-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .suggestion-item:hover {
            background-color: #f8f9fa;
        }
        
        .suggestion-item:last-child {
            border-bottom: none;
        }
        
        .suggestion-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }
        
        .suggestion-details {
            flex: 1;
        }
        
        .suggestion-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .suggestion-description {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .suggestion-price {
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-5">
        <h1 class="text-center mb-5">Our Menu</h1>
        
        <!-- Search and Filter Section -->
        <div class="row mb-4">
            <div class="col-md-12 mb-3">
                <form action="menu.php" method="GET" class="search-form">
                    <div class="input-group">
                        <input type="text" class="form-control" id="menuSearchInput" placeholder="Search for dishes..." name="search" value="<?php echo htmlspecialchars($search_term); ?>" autocomplete="off">
                        
                        <!-- Preserve filters and sort options in search form -->
                        <?php if ($selected_category > 0): ?>
                            <input type="hidden" name="category" value="<?php echo $selected_category; ?>">
                        <?php endif; ?>
                        
                        <?php if ($veg_filter != 'all'): ?>
                            <input type="hidden" name="veg_filter" value="<?php echo $veg_filter; ?>">
                        <?php endif; ?>
                        
                        <?php if ($sort_option != 'name_asc'): ?>
                            <input type="hidden" name="sort" value="<?php echo $sort_option; ?>">
                        <?php endif; ?>
                        
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($search_term)): ?>
                            <a href="<?php echo $selected_category > 0 ? 'menu.php?category=' . $selected_category : 'menu.php'; ?><?php echo ($veg_filter != 'all') ? '&veg_filter=' . $veg_filter : ''; ?><?php echo ($sort_option != 'name_asc') ? '&sort=' . $sort_option : ''; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                    <div id="searchSuggestions" class="search-suggestions"></div>
                </form>
            </div>
            
            <!-- Category Filter -->
            <div class="col-md-12 mb-3">
                <div class="category-filter d-flex justify-content-center flex-wrap">
                    <a href="<?php echo !empty($search_term) ? 'menu.php?search=' . urlencode($search_term) : 'menu.php'; ?><?php echo ($veg_filter != 'all') ? '&veg_filter=' . $veg_filter : ''; ?><?php echo ($sort_option != 'name_asc') ? '&sort=' . $sort_option : ''; ?>" class="btn <?php echo $selected_category == 0 ? 'btn-primary' : 'btn-outline-primary'; ?> m-1">All</a>
                    <?php foreach ($categories as $category): ?>
                        <a href="menu.php?category=<?php echo $category['id']; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo ($veg_filter != 'all') ? '&veg_filter=' . $veg_filter : ''; ?><?php echo ($sort_option != 'name_asc') ? '&sort=' . $sort_option : ''; ?>" class="btn <?php echo $selected_category == $category['id'] ? 'btn-primary' : 'btn-outline-primary'; ?> m-1"><?php echo $category['name']; ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Veg/Non-Veg Filter Toggle -->
            <div class="col-md-6 mb-3">
                <div class="veg-filter d-flex justify-content-center">
                    <div class="btn-group" role="group" aria-label="Veg Filter">
                        <a href="menu.php?<?php echo $selected_category > 0 ? 'category='.$selected_category.'&' : ''; ?><?php echo !empty($search_term) ? 'search='.urlencode($search_term).'&' : ''; ?><?php echo ($sort_option != 'name_asc') ? 'sort='.$sort_option.'&' : ''; ?>veg_filter=all" class="btn <?php echo $veg_filter == 'all' ? 'btn-success' : 'btn-outline-success'; ?>">All</a>
                        <a href="menu.php?<?php echo $selected_category > 0 ? 'category='.$selected_category.'&' : ''; ?><?php echo !empty($search_term) ? 'search='.urlencode($search_term).'&' : ''; ?><?php echo ($sort_option != 'name_asc') ? 'sort='.$sort_option.'&' : ''; ?>veg_filter=veg" class="btn <?php echo $veg_filter == 'veg' ? 'btn-success' : 'btn-outline-success'; ?>"><i class="fas fa-leaf"></i> Veg Only</a>
                        <a href="menu.php?<?php echo $selected_category > 0 ? 'category='.$selected_category.'&' : ''; ?><?php echo !empty($search_term) ? 'search='.urlencode($search_term).'&' : ''; ?><?php echo ($sort_option != 'name_asc') ? 'sort='.$sort_option.'&' : ''; ?>veg_filter=non_veg" class="btn <?php echo $veg_filter == 'non_veg' ? 'btn-danger' : 'btn-outline-danger'; ?>"><i class="fas fa-drumstick-bite"></i> Non-Veg Only</a>
                    </div>
                </div>
            </div>
            
            <!-- Sort Options -->
            <div class="col-md-6 mb-3">
                <div class="sort-options d-flex justify-content-center">
                    <div class="btn-group" role="group" aria-label="Sort Options">
                        <a href="menu.php?<?php echo $selected_category > 0 ? 'category='.$selected_category.'&' : ''; ?><?php echo !empty($search_term) ? 'search='.urlencode($search_term).'&' : ''; ?><?php echo ($veg_filter != 'all') ? 'veg_filter='.$veg_filter.'&' : ''; ?>sort=name_asc" class="btn <?php echo $sort_option == 'name_asc' ? 'btn-primary' : 'btn-outline-primary'; ?>"><i class="fas fa-sort-alpha-down"></i> Name A-Z</a>
                        <a href="menu.php?<?php echo $selected_category > 0 ? 'category='.$selected_category.'&' : ''; ?><?php echo !empty($search_term) ? 'search='.urlencode($search_term).'&' : ''; ?><?php echo ($veg_filter != 'all') ? 'veg_filter='.$veg_filter.'&' : ''; ?>sort=name_desc" class="btn <?php echo $sort_option == 'name_desc' ? 'btn-primary' : 'btn-outline-primary'; ?>"><i class="fas fa-sort-alpha-up"></i> Name Z-A</a>
                        <a href="menu.php?<?php echo $selected_category > 0 ? 'category='.$selected_category.'&' : ''; ?><?php echo !empty($search_term) ? 'search='.urlencode($search_term).'&' : ''; ?><?php echo ($veg_filter != 'all') ? 'veg_filter='.$veg_filter.'&' : ''; ?>sort=price_asc" class="btn <?php echo $sort_option == 'price_asc' ? 'btn-primary' : 'btn-outline-primary'; ?>"><i class="fas fa-sort-numeric-down"></i> Price Low-High</a>
                        <a href="menu.php?<?php echo $selected_category > 0 ? 'category='.$selected_category.'&' : ''; ?><?php echo !empty($search_term) ? 'search='.urlencode($search_term).'&' : ''; ?><?php echo ($veg_filter != 'all') ? 'veg_filter='.$veg_filter.'&' : ''; ?>sort=price_desc" class="btn <?php echo $sort_option == 'price_desc' ? 'btn-primary' : 'btn-outline-primary'; ?>"><i class="fas fa-sort-numeric-up"></i> Price High-Low</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Menu Items -->
        <div class="row">
            <?php if (count($menu_items) > 0): ?>
                <?php foreach ($menu_items as $item): ?>
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card food-card h-100">
                            <img src="../assets/images/food/<?php echo $item['image']; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="card-title mb-0"><?php echo $item['name']; ?></h5>
                                    <?php if ($item['is_veg'] == 1): ?>
                                        <span class="badge bg-success">Veg</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Non-Veg</span>
                                    <?php endif; ?>
                                </div>
                                <p class="card-text small"><?php echo substr($item['description'], 0, 80); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="price">₹<?php echo $item['price']; ?></span>
                                    <button class="btn btn-sm btn-primary add-to-cart" data-id="<?php echo $item['id']; ?>">
                                        <i class="fas fa-cart-plus"></i> Add
                                    </button>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#foodModal<?php echo $item['id']; ?>">
                                    Quick View
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Food Item Modal -->
                    <div class="modal fade food-modal" id="foodModal<?php echo $item['id']; ?>" tabindex="-1" aria-labelledby="foodModalLabel<?php echo $item['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="foodModalLabel<?php echo $item['id']; ?>"><?php echo $item['name']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <div class="row g-0">
                                        <div class="col-md-6">
                                            <img src="../assets/images/food/<?php echo $item['image']; ?>" class="img-fluid food-image" alt="<?php echo $item['name']; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <div class="food-details">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h4><?php echo $item['name']; ?></h4>
                                                    <?php if ($item['is_veg'] == 1): ?>
                                                        <span class="badge bg-success">Veg</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Non-Veg</span>
                                                    <?php endif; ?>
                                                </div>
                                                <p><?php echo $item['description']; ?></p>
                                                <h5 class="price mb-3">₹<?php echo $item['price']; ?></h5>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="input-group input-group-sm" style="max-width: 150px;">
                                                        <button class="btn btn-outline-secondary decrease-quantity" type="button">-</button>
                                                        <input type="number" class="form-control text-center item-quantity" value="1" min="1" max="10">
                                                        <button class="btn btn-outline-secondary increase-quantity" type="button">+</button>
                                                    </div>
                                                    <button class="btn btn-primary add-to-cart-modal" data-id="<?php echo $item['id']; ?>">
                                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination Controls -->
                <?php if ($total_items > $items_per_page): ?>
                <div class="col-12">
                    <nav aria-label="Menu pagination">
                        <?php 
                        $total_pages = ceil($total_items / $items_per_page);
                        $pagination_url = '?';
                        
                        // Preserve other parameters
                        if ($selected_category > 0) {
                            $pagination_url .= 'category=' . $selected_category . '&';
                        }
                        if (!empty($search_term)) {
                            $pagination_url .= 'search=' . urlencode($search_term) . '&';
                        }
                        // Preserve veg filter
                        if ($veg_filter != 'all') {
                            $pagination_url .= 'veg_filter=' . $veg_filter . '&';
                        }
                        // Preserve sort option
                        if ($sort_option != 'name_asc') {
                            $pagination_url .= 'sort=' . $sort_option . '&';
                        }
                        ?>
                        <ul class="pagination justify-content-center mt-4">
                            <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $pagination_url . 'page=' . ($current_page - 1); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $pagination_url . 'page=' . $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $pagination_url . 'page=' . ($current_page + 1); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>No menu items found. Try a different search or category.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Handle quantity buttons in modal
        document.querySelectorAll('.decrease-quantity').forEach(button => {
            button.addEventListener('click', function() {
                let input = this.parentNode.querySelector('.item-quantity');
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                }
            });
        });
        
        document.querySelectorAll('.increase-quantity').forEach(button => {
            button.addEventListener('click', function() {
                let input = this.parentNode.querySelector('.item-quantity');
                let value = parseInt(input.value);
                if (value < 10) {
                    input.value = value + 1;
                }
            });
        });
        
        // Real-time search suggestions
        const searchInput = document.getElementById('menuSearchInput');
        const suggestionsContainer = document.getElementById('searchSuggestions');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // Hide suggestions if search term is empty
            if (searchTerm.length === 0) {
                suggestionsContainer.style.display = 'none';
                return;
            }
            
            // Set a small delay to avoid too many requests while typing
            searchTimeout = setTimeout(() => {
                // Get category ID if any
                const categoryInput = document.querySelector('input[name="category"]');
                const categoryId = categoryInput ? categoryInput.value : 0;
                
                // Fetch suggestions
                fetch(`ajax/search_suggestions.php?term=${encodeURIComponent(searchTerm)}&category=${categoryId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Clear previous suggestions
                        suggestionsContainer.innerHTML = '';
                        
                        if (data.length > 0) {
                            // Create suggestion items
                            data.forEach(item => {
                                const suggestionItem = document.createElement('div');
                                suggestionItem.className = 'suggestion-item';
                                suggestionItem.innerHTML = `
                                    <img src="../assets/images/food/${item.image}" alt="${item.name}" class="suggestion-image">
                                    <div class="suggestion-details">
                                        <div class="suggestion-name">${item.name}</div>
                                        <div class="suggestion-description">${item.description}</div>
                                        <div class="suggestion-price">₹${item.price}</div>
                                    </div>
                                `;
                                
                                // Add click event to suggestion item
                                suggestionItem.addEventListener('click', function() {
                                    // Close suggestions
                                    suggestionsContainer.style.display = 'none';
                                    
                                    // If we're already on the menu page, just open the modal
                                    const modal = document.getElementById(`foodModal${item.id}`);
                                    if (modal) {
                                        const modalInstance = new bootstrap.Modal(modal);
                                        modalInstance.show();
                                    } else {
                                        // If modal not found, navigate to the page with the anchor
                                        window.location.href = `menu.php${categoryId ? '?category=' + categoryId : ''}#foodModal${item.id}`;
                                    }
                                });
                                
                                suggestionsContainer.appendChild(suggestionItem);
                            });
                            
                            // Show suggestions container
                            suggestionsContainer.style.display = 'block';
                        } else {
                            // Hide suggestions if no results
                            suggestionsContainer.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching suggestions:', error);
                        suggestionsContainer.style.display = 'none';
                    });
            }, 300); // 300ms delay
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(event) {
            if (!searchInput.contains(event.target) && !suggestionsContainer.contains(event.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });
        
        // Handle add to cart directly from menu cards
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const itemId = this.getAttribute('data-id');
                const quantity = 1; // Default quantity is 1 for direct add
                
                // AJAX request to add item to cart
                fetch('../customer/ajax/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'item_id=' + itemId + '&quantity=' + quantity
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count
                        const cartBadge = document.querySelector('.fa-shopping-cart').nextElementSibling;
                        if (cartBadge) {
                            cartBadge.textContent = data.cart_count;
                        } else {
                            // Create badge if it doesn't exist
                            const badge = document.createElement('span');
                            badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                            badge.textContent = data.cart_count;
                            document.querySelector('.fa-shopping-cart').parentNode.appendChild(badge);
                        }
                        
                        // Show success message using toast or alert
                        alert(data.message);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding item to cart');
                });
            });
        });
        
        // Handle add to cart from modal
        document.querySelectorAll('.add-to-cart-modal').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-id');
                const quantity = this.closest('.food-details').querySelector('.item-quantity').value;
                
                // AJAX request to add item to cart
                fetch('../customer/ajax/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'item_id=' + itemId + '&quantity=' + quantity
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        
                        // Update cart count
                        const cartBadge = document.querySelector('.fa-shopping-cart').nextElementSibling;
                        if (cartBadge) {
                            cartBadge.textContent = data.cart_count;
                        } else {
                            // Create badge if it doesn't exist
                            const badge = document.createElement('span');
                            badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                            badge.textContent = data.cart_count;
                            document.querySelector('.fa-shopping-cart').parentNode.appendChild(badge);
                        }
                        
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                        modal.hide();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding item to cart');
                });
            });
        });
    </script>
</body>
</html>
