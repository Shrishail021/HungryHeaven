<?php
// Include database connection
include '../../includes/db_connection.php';
include '../../includes/functions.php';

// Get search term
$search_term = isset($_GET['term']) ? clean_input($_GET['term']) : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Prepare response array
$suggestions = array();

if (!empty($search_term)) {
    // Build query based on category filter
    if ($category_id > 0) {
        $sql = "SELECT id, name, description, price, image FROM menu_items 
                WHERE is_available = 1 AND category_id = ? AND (name LIKE ? OR description LIKE ?) 
                ORDER BY name ASC LIMIT 5";
        $stmt = mysqli_prepare($conn, $sql);
        $search_param = "%$search_term%";
        mysqli_stmt_bind_param($stmt, "iss", $category_id, $search_param, $search_param);
    } else {
        $sql = "SELECT id, name, description, price, image FROM menu_items 
                WHERE is_available = 1 AND (name LIKE ? OR description LIKE ?) 
                ORDER BY name ASC LIMIT 5";
        $stmt = mysqli_prepare($conn, $sql);
        $search_param = "%$search_term%";
        mysqli_stmt_bind_param($stmt, "ss", $search_param, $search_param);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Format price
            $price = number_format($row['price'], 2);
            
            // Create suggestion item
            $suggestion = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => substr($row['description'], 0, 60) . (strlen($row['description']) > 60 ? '...' : ''),
                'price' => $price,
                'image' => $row['image']
            );
            
            $suggestions[] = $suggestion;
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($suggestions);
?>
