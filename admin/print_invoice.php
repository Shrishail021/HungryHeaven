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

// Check if order_id is set
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$order_id = intval($_GET['id']);

// Get order details
$sql = "SELECT o.*, u.name as customer_name, u.email, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header('Location: orders.php');
    exit();
}

$order = mysqli_fetch_assoc($result);

// Get order items
$sql = "SELECT oi.*, m.name, m.image 
        FROM order_items oi 
        JOIN menu_items m ON oi.item_id = m.id 
        WHERE oi.order_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$order_items = array();
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $order_items[] = $row;
    }
}

// Get restaurant details
$sql = "SELECT * FROM settings WHERE setting_key = 'restaurant_details'";
$result = mysqli_query($conn, $sql);
$restaurant = array(
    'name' => 'Hungry Heaven',
    'address' => '123 Main Street, Foodville',
    'phone' => '+91 9876543210',
    'email' => 'info@hungryheaven.com',
    'gst' => 'GSTIN: 27ABCDE1234F1Z5'
);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $restaurant = json_decode($row['setting_value'], true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order['reference_number']; ?> - Hungry Heaven</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .invoice-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .invoice-header img {
            max-height: 70px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #ff6b6b;
        }
        .invoice-details {
            margin-bottom: 20px;
        }
        .invoice-details-title {
            font-weight: bold;
            color: #495057;
        }
        .table th {
            font-weight: 600;
            color: #495057;
        }
        .table-items th {
            background-color: #f8f9fa;
        }
        .table-total {
            border-top: 2px solid #e9ecef;
        }
        .footer-note {
            margin-top: 30px;
            font-size: 14px;
            color: #6c757d;
            text-align: center;
        }
        .signature-area {
            margin-top: 40px;
            border-top: 1px dashed #e9ecef;
            padding-top: 20px;
        }
        .status-paid {
            color: #28a745;
            font-weight: bold;
        }
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        @media print {
            body {
                background-color: #fff;
            }
            .invoice-container {
                box-shadow: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .table-striped tbody tr:nth-of-type(odd) {
                background-color: rgba(0, 0, 0, 0.05) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-4">
        <div class="row">
            <div class="col-12 text-end no-print mb-4">
                <button class="btn btn-primary me-2" onclick="window.print();">
                    <i class="fas fa-print me-2"></i>Print Invoice
                </button>
                <a href="order_details.php?id=<?php echo $order_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Order
                </a>
            </div>
        </div>
        
        <div class="invoice-container">
            <div class="invoice-header d-flex justify-content-between align-items-center">
                <div>
                    <div class="invoice-title">INVOICE</div>
                    <div>#<?php echo $order['reference_number']; ?></div>
                </div>
                <div class="text-end">
                    <div class="fw-bold mb-1"><?php echo $restaurant['name']; ?></div>
                    <div><?php echo $restaurant['address']; ?></div>
                    <div><?php echo $restaurant['phone']; ?></div>
                    <div><?php echo $restaurant['email']; ?></div>
                    <div><?php echo $restaurant['gst']; ?></div>
                </div>
            </div>
            
            <div class="row invoice-details">
                <div class="col-md-6">
                    <div class="invoice-details-title">Bill To:</div>
                    <div class="fw-bold"><?php echo $order['customer_name']; ?></div>
                    <div><?php echo $order['email']; ?></div>
                    <div><?php echo $order['phone']; ?></div>
                    <?php if ($order['delivery_type'] == 'delivery'): ?>
                        <div><?php echo $order['address']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="invoice-details-title">Invoice Details:</div>
                    <div>
                        <span class="fw-bold">Date:</span> 
                        <?php echo date('d M Y', strtotime($order['order_date'])); ?>
                    </div>
                    <div>
                        <span class="fw-bold">Status:</span> 
                        <?php if ($order['payment_method'] == 'razorpay'): ?>
                            <span class="status-paid">PAID</span>
                        <?php elseif ($order['payment_method'] == 'cod'): ?>
                            <span class="status-pending">PENDING</span>
                        <?php else: ?>
                            <span class="status-pending">CASH</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="fw-bold">Order Type:</span> 
                        <?php echo $order['delivery_type'] == 'delivery' ? 'Home Delivery' : 'Table Order'; ?>
                    </div>
                    <?php if ($order['delivery_type'] != 'delivery'): ?>
                        <div>
                            <span class="fw-bold">Table No:</span> 
                            <?php echo str_replace('Table: ', '', $order['address']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-items">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        foreach ($order_items as $index => $item): 
                            $item_total = $item['price'] * $item['quantity'];
                            $subtotal += $item_total;
                        ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $item['name']; ?></td>
                                <td class="text-end">₹<?php echo number_format($item['price'], 2); ?></td>
                                <td class="text-end"><?php echo $item['quantity']; ?></td>
                                <td class="text-end">₹<?php echo number_format($item_total, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php 
            // Calculate tax (assuming 5% tax)
            $tax_percentage = 5;
            $tax = ($subtotal * $tax_percentage) / 100;
            
            // Calculate delivery charge
            $delivery_charge = 0;
            if ($order['delivery_type'] == 'delivery') {
                // Check if delivery charge applies (assuming free delivery over ₹500)
                if ($subtotal < 500) {
                    $delivery_charge = 50;
                }
            }
            ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mt-4">
                        <div class="invoice-details-title">Payment Information:</div>
                        <div>
                            <span class="fw-bold">Payment Method:</span> 
                            <?php 
                            if ($order['payment_method'] == 'razorpay') {
                                echo 'Razorpay';
                                if (!empty($order['transaction_id'])) {
                                    echo ' (Txn ID: ' . $order['transaction_id'] . ')';
                                }
                            } elseif ($order['payment_method'] == 'cod') {
                                echo 'Cash on Delivery';
                            } else {
                                echo 'Cash';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['notes'])): ?>
                        <div class="mt-3">
                            <div class="invoice-details-title">Customer Notes:</div>
                            <div><?php echo $order['notes']; ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <table class="table table-total mt-4">
                        <tbody>
                            <tr>
                                <td class="text-end fw-bold">Subtotal:</td>
                                <td class="text-end">₹<?php echo number_format($subtotal, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="text-end fw-bold">Tax (<?php echo $tax_percentage; ?>%):</td>
                                <td class="text-end">₹<?php echo number_format($tax, 2); ?></td>
                            </tr>
                            <?php if ($order['delivery_type'] == 'delivery'): ?>
                                <tr>
                                    <td class="text-end fw-bold">Delivery Charge:</td>
                                    <td class="text-end">
                                        <?php echo $delivery_charge > 0 ? '₹' . number_format($delivery_charge, 2) : 'Free'; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="text-end fw-bold">Total:</td>
                                <td class="text-end fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="signature-area row">
                <div class="col-md-6">
                    <div class="text-center">
                        <div>_________________________</div>
                        <div class="mt-2">Authorized Signature</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-center">
                        <div>_________________________</div>
                        <div class="mt-2">Received By</div>
                    </div>
                </div>
            </div>
            
            <div class="footer-note">
                <p>Thank you for your business! We appreciate your trust in Hungry Heaven.</p>
                <p class="mb-0">For any inquiries regarding this invoice, please contact <?php echo $restaurant['email']; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
