<?php
// Include database connection
include 'includes/db_connection.php';

// Add payment columns to orders table
$sql = "ALTER TABLE orders 
        ADD COLUMN payment_id VARCHAR(255) DEFAULT NULL,
        ADD COLUMN payment_order_id VARCHAR(255) DEFAULT NULL,
        ADD COLUMN payment_signature VARCHAR(255) DEFAULT NULL";

if (mysqli_query($conn, $sql)) {
    echo "Payment columns added successfully to orders table.";
} else {
    echo "Error adding payment columns: " . mysqli_error($conn);
}
?>
