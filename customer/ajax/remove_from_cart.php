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
    'cart_count' => 0,
    'total' => 0
);

// Check if item_id is set
if (isset($_POST['item_id'])) {
    $item_id = intval($_POST['item_id']);
    
    // Remove item from cart
    if (remove_from_cart($item_id)) {
        // Calculate cart total
        $cart_total = calculate_cart_total($_SESSION['cart']);
        
        // Get delivery charge and free delivery minimum
        $sql = "SELECT setting_value FROM settings WHERE setting_key = 'delivery_charge'";
        $result = mysqli_query($conn, $sql);
        $delivery_charge = 0;
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $delivery_charge = floatval($row['setting_value']);
        }

        $sql = "SELECT setting_value FROM settings WHERE setting_key = 'min_order_free_delivery'";
        $result = mysqli_query($conn, $sql);
        $min_order_free_delivery = 0;
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $min_order_free_delivery = floatval($row['setting_value']);
        }

        // Check if eligible for free delivery
        $is_free_delivery = $cart_total >= $min_order_free_delivery;
        
        // Calculate final total
        $final_total = $is_free_delivery ? $cart_total : $cart_total + $delivery_charge;
        
        $response['success'] = true;
        $response['message'] = 'Item removed from cart successfully!';
        $response['cart_count'] = get_cart_count();
        $response['total'] = number_format($final_total, 2);
    } else {
        $response['message'] = 'Error removing item from cart';
    }
} else {
    $response['message'] = 'Invalid request';
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
