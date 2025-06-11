<?php
// Start session
session_start();

// Include database connection
include '../includes/db_connection.php';
include '../includes/functions.php';

// Disable redirects - this is a standalone page
ob_start(); // Start output buffering to prevent headers already sent issues

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    die("Invalid order ID");
}

// Get order details
$sql = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Order not found");
}

$order = mysqli_fetch_assoc($result);

// Get order items
$items_sql = "SELECT oi.*, m.name as item_name, m.price as original_price 
              FROM order_items oi 
              JOIN menu_items m ON oi.item_id = m.id 
              WHERE oi.order_id = ?";

$items_stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

$order_items = array();
if ($items_result && mysqli_num_rows($items_result) > 0) {
    while ($row = mysqli_fetch_assoc($items_result)) {
        $order_items[] = $row;
    }
}

// Order status labels
$status_labels = array(
    0 => 'Pending',
    1 => 'Confirmed',
    2 => 'Preparing',
    3 => 'Ready for Pickup/Delivery',
    4 => 'Delivered/Completed',
    5 => 'Cancelled'
);

// Payment method labels
$payment_labels = array(
    'cod' => 'Cash on Delivery',
    'razorpay' => 'Online Payment (Razorpay)'
);

// Format date
$order_date = date('d M Y h:i A', strtotime($order['order_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill #<?php echo $order_id; ?> - HungryHeaven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            line-height: 1.4;
            font-size: 12px;
        }
        .bill-box {
            max-width: 400px;
            margin: auto;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #fff;
        }
        .bill-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px dashed #ddd;
            padding-bottom: 10px;
        }
        .bill-header h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .bill-header p {
            font-size: 12px;
            margin-bottom: 5px;
        }
        .order-info {
            margin-bottom: 15px;
            font-size: 12px;
        }
        .customer-info {
            margin-bottom: 15px;
            font-size: 12px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            border-bottom: 1px solid #ddd;
            padding: 5px;
            text-align: left;
            font-size: 12px;
        }
        .items-table td {
            padding: 5px;
            border-bottom: 1px dotted #eee;
            font-size: 12px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 5px;
            font-size: 12px;
        }
        .bill-footer {
            margin-top: 15px;
            text-align: center;
            color: #777;
            border-top: 1px dashed #ddd;
            padding-top: 10px;
            font-size: 11px;
        }
        .text-right {
            text-align: right;
        }
        @media print {
            body {
                background-color: #fff;
            }
            .bill-box {
                box-shadow: none;
                border: none;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container mb-4">
        <div class="no-print text-end mb-3">
            <button id="printButton" class="btn btn-primary">Print Bill</button>
            <a id="backButton" href="orders.php" class="btn btn-secondary ms-2">Back to Orders</a>
        </div>
    </div>

    <div class="bill-box">
        <div class="bill-header">
            <h2>HungryHeaven</h2>
            <p>Your Delicious Food Destination</p>
            <p>Tel: +91 9876543210</p>
            <p>---------------------------------</p>
            <p>BILL</p>
            <p>---------------------------------</p>
        </div>

        <div class="order-info">
            <p>Order #: <?php echo $order['reference_number']; ?></p>
            <p>Date: <?php echo $order_date; ?></p>
            <p>Status: <?php echo $status_labels[$order['order_status']]; ?></p>
            <p>Payment: <?php echo $payment_labels[$order['payment_method']]; ?></p>
            <?php if ($order['payment_method'] == 'razorpay' && !empty($order['razorpay_payment_id'])): ?>
            <p>Payment ID: <?php echo htmlspecialchars($order['razorpay_payment_id']); ?></p>
            <?php endif; ?>
        </div>

        <div class="customer-info">
            <p>Customer: <?php echo $order['customer_name']; ?></p>
            <p>Phone: <?php echo $order['customer_phone']; ?></p>
            <?php if ($order['delivery_type'] == 'delivery'): ?>
            <p>Delivery Address: <?php echo $order['address']; ?></p>
            <?php else: ?>
            <p>Pickup Order</p>
            <?php endif; ?>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($order_items as $item): 
                    $item_total = $item['price'] * $item['quantity'];
                ?>
                <tr>
                    <td><?php echo $item['item_name']; ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td class="text-right">₹<?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-right">₹<?php echo number_format($item_total, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        // Set default delivery fee if not set
        $delivery_fee = isset($order['delivery_fee']) ? $order['delivery_fee'] : 0;
        $subtotal = $order['total_amount'] - $delivery_fee;
        ?>
        <table class="totals-table">
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td class="text-right">₹<?php echo number_format($subtotal, 2); ?></td>
            </tr>
            <?php if ($order['delivery_type'] == 'delivery'): ?>
            <tr>
                <td><strong>Delivery Fee:</strong></td>
                <td class="text-right">₹<?php echo number_format($delivery_fee, 2); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td><strong>Total:</strong></td>
                <td class="text-right"><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
            </tr>
        </table>

        <div class="bill-footer">
            <p>Thank you for your order!</p>
            <p>Visit again soon!</p>
            <p>www.hungryheaven.com</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent any automatic redirects
        window.onload = function() {
            // Focus on this window to ensure it's active
            window.focus();
            
            // Add event listener to the print button
            document.getElementById('printButton').addEventListener('click', function(e) {
                e.preventDefault();
                window.print();
            });
            
            // Add event listener to the back button to ensure it works
            document.getElementById('backButton').addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = 'orders.php';
            });
        };
    </script>
</body>
</html>
<?php ob_end_flush(); // End output buffering ?>
