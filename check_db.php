<?php
// Include database connection
include 'includes/db_connection.php';

// Check if the orders table has the payment_id, payment_order_id, and payment_signature columns
$sql = "SHOW COLUMNS FROM orders LIKE 'payment_id'";
$result = mysqli_query($conn, $sql);
$payment_id_exists = mysqli_num_rows($result) > 0;

$sql = "SHOW COLUMNS FROM orders LIKE 'payment_order_id'";
$result = mysqli_query($conn, $sql);
$payment_order_id_exists = mysqli_num_rows($result) > 0;

$sql = "SHOW COLUMNS FROM orders LIKE 'payment_signature'";
$result = mysqli_query($conn, $sql);
$payment_signature_exists = mysqli_num_rows($result) > 0;

// Output the results
echo "payment_id column exists: " . ($payment_id_exists ? 'Yes' : 'No') . "<br>";
echo "payment_order_id column exists: " . ($payment_order_id_exists ? 'Yes' : 'No') . "<br>";
echo "payment_signature column exists: " . ($payment_signature_exists ? 'Yes' : 'No') . "<br>";

// If any of the columns don't exist, add them
if (!$payment_id_exists || !$payment_order_id_exists || !$payment_signature_exists) {
    echo "Adding missing columns...<br>";
    
    if (!$payment_id_exists) {
        $sql = "ALTER TABLE orders ADD COLUMN payment_id VARCHAR(255) DEFAULT NULL";
        if (mysqli_query($conn, $sql)) {
            echo "payment_id column added successfully<br>";
        } else {
            echo "Error adding payment_id column: " . mysqli_error($conn) . "<br>";
        }
    }
    
    if (!$payment_order_id_exists) {
        $sql = "ALTER TABLE orders ADD COLUMN payment_order_id VARCHAR(255) DEFAULT NULL";
        if (mysqli_query($conn, $sql)) {
            echo "payment_order_id column added successfully<br>";
        } else {
            echo "Error adding payment_order_id column: " . mysqli_error($conn) . "<br>";
        }
    }
    
    if (!$payment_signature_exists) {
        $sql = "ALTER TABLE orders ADD COLUMN payment_signature VARCHAR(255) DEFAULT NULL";
        if (mysqli_query($conn, $sql)) {
            echo "payment_signature column added successfully<br>";
        } else {
            echo "Error adding payment_signature column: " . mysqli_error($conn) . "<br>";
        }
    }
}
?>
