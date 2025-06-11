<?php
// Start session
session_start();

// Include database connection
include '../../includes/db_connection.php';
include '../../includes/functions.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'cart_count' => 0
);

try {
    // Check if item_id and quantity are set
    if (isset($_POST['item_id']) && isset($_POST['quantity'])) {
        $item_id = intval($_POST['item_id']);
        $quantity = intval($_POST['quantity']);
        
        // Validate quantity
        if ($quantity < 1) {
            $quantity = 1;
        } else if ($quantity > 10) {
            $quantity = 10;
        }
        
        // Check if item exists
        $item = get_menu_item($item_id);
        if (!$item) {
            throw new Exception('Menu item not found');
        }
        
        // Add item to cart
        if (add_to_cart($item_id, $quantity)) {
            $response['success'] = true;
            $response['message'] = 'Item added to cart successfully!';
            $response['cart_count'] = get_cart_count();
        } else {
            $response['message'] = 'Error adding item to cart';
        }
    } else {
        $response['message'] = 'Invalid request';
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
