<?php
// Start session
session_start();

// Include database connection
include '../../includes/db_connection.php';
include '../../includes/functions.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

// Clear cart
if (clear_cart()) {
    $response['success'] = true;
    $response['message'] = 'Cart cleared successfully!';
} else {
    $response['message'] = 'Error clearing cart';
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
