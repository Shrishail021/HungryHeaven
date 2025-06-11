<?php
// Common functions for Hungry Heaven website

// Clean user input data
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Get user details
function get_user_details($user_id) {
    global $conn;
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return false;
}

// Get categories
function get_categories() {
    global $conn;
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    
    $categories = array();
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

// Get menu items by category
function get_menu_items_by_category($category_id) {
    global $conn;
    $sql = "SELECT * FROM menu_items WHERE category_id = ? ORDER BY name ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $items = array();
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    
    return $items;
}

// Get popular menu items
function get_popular_items() {
    global $conn;
    $sql = "SELECT * FROM menu_items WHERE is_popular = 1 ORDER BY name ASC LIMIT 8";
    $result = mysqli_query($conn, $sql);
    
    $items = array();
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    
    return $items;
}

// Get menu item details
function get_menu_item($item_id) {
    global $conn;
    $sql = "SELECT * FROM menu_items WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return false;
}

// Get recommended items based on user's order history
function get_recommended_items($user_id) {
    global $conn;
    
    // Get categories from user's order history
    $sql = "SELECT DISTINCT m.category_id 
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN menu_items m ON oi.item_id = m.id
            WHERE o.user_id = ?
            ORDER BY o.order_date DESC
            LIMIT 3";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $categories = array();
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row['category_id'];
        }
    }
    
    // If user has no order history, return popular items
    if (empty($categories)) {
        return get_popular_items();
    }
    
    // Get items from those categories that user hasn't ordered yet
    $categories_str = implode(',', $categories);
    $sql = "SELECT DISTINCT m.* 
            FROM menu_items m
            WHERE m.category_id IN ($categories_str)
            AND m.id NOT IN (
                SELECT oi.item_id 
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.user_id = ?
            )
            ORDER BY m.is_popular DESC
            LIMIT 8";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $items = array();
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    
    // If not enough recommended items, fill with popular items
    if (count($items) < 8) {
        $popular_items = get_popular_items();
        $needed = 8 - count($items);
        
        foreach ($popular_items as $item) {
            $exists = false;
            foreach ($items as $existing_item) {
                if ($existing_item['id'] == $item['id']) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $items[] = $item;
                $needed--;
            }
            
            if ($needed <= 0) {
                break;
            }
        }
    }
    
    return $items;
}

// Generate random reference number for orders
function generate_reference_number() {
    return 'HH' . date('Ymd') . rand(1000, 9999);
}

// Format currency
function format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

// Calculate cart total
function calculate_cart_total($cart_items) {
    $total = 0;
    foreach ($cart_items as $item) {
        $total += ($item['price'] * $item['quantity']);
    }
    return $total;
}

// Add to cart
function add_to_cart($item_id, $quantity = 1) {
    // Get item details
    $item = get_menu_item($item_id);
    
    if (!$item) {
        return false;
    }
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    // Check if item already in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$cart_item) {
        if ($cart_item['id'] == $item_id) {
            $cart_item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    // Add item to cart if not already there
    if (!$found) {
        $_SESSION['cart'][] = array(
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'image' => $item['image'],
            'quantity' => $quantity
        );
    }
    
    return true;
}

// Remove from cart
function remove_from_cart($item_id) {
    if (!isset($_SESSION['cart'])) {
        return false;
    }
    
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $item_id) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            return true;
        }
    }
    
    return false;
}

// Update cart quantity
function update_cart_quantity($item_id, $quantity) {
    if (!isset($_SESSION['cart'])) {
        return false;
    }
    
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $item_id) {
            $item['quantity'] = $quantity;
            return true;
        }
    }
    
    return false;
}

// Clear cart
function clear_cart() {
    $_SESSION['cart'] = array();
    return true;
}

// Get cart items count
function get_cart_count() {
    if (!isset($_SESSION['cart'])) {
        return 0;
    }
    
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
    
    return $count;
}

// Get order status text
function get_order_status_text($status) {
    switch ($status) {
        case 0:
            return 'Pending';
        case 1:
            return 'Confirmed';
        case 2:
            return 'Preparing';
        case 3:
            return 'Ready for Pickup/Delivery';
        case 4:
            return 'Delivered/Completed';
        case 5:
            return 'Cancelled';
        default:
            return 'Unknown';
    }
}

// Get order status badge class
function get_order_status_badge($status) {
    switch ($status) {
        case 0:
            return 'bg-warning';
        case 1:
            return 'bg-info';
        case 2:
            return 'bg-primary';
        case 3:
            return 'bg-success';
        case 4:
            return 'bg-success';
        case 5:
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>
