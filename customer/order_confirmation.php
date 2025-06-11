<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
include '../includes/db_connection.php';
include '../includes/functions.php';

// Check if order_id is set
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}

$order_id = intval($_GET['order_id']);

// Get order details
$sql = "SELECT o.*, u.name as customer_name, u.email, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $_SESSION['user_id']);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Hungry Heaven</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/custom.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-white text-center py-4">
                        <h4 class="mb-0"><i class="fas fa-check-circle me-2"></i>Order Placed Successfully!</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                        
                            <h5>Thank you for your order, <?php echo $_SESSION['username']; ?>!</h5>
                            <p class="text-muted">Your order has been placed successfully and is being processed.</p>
                        </div>
                        
                        <div class="order-details mb-4">
                            <h5 class="border-bottom pb-2">Order Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Order Number:</strong> <?php echo $order['reference_number']; ?></p>
                                    <p><strong>Order Date:</strong> <?php echo date('d M Y h:i A', strtotime($order['order_date'])); ?></p>
                                    <p><strong>Payment Method:</strong> 
                                        <?php if ($order['payment_method'] == 'razorpay'): ?>
                                            Razorpay
                                        <?php elseif ($order['payment_method'] == 'cod'): ?>
                                            Cash on Delivery
                                        <?php else: ?>
                                            Cash
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Status:</strong> 
                                        <span class="badge <?php echo get_order_status_badge($order['order_status']); ?>">
                                            <?php echo get_order_status_text($order['order_status']); ?>
                                        </span>
                                    </p>
                                    <p><strong>Order Type:</strong> 
                                        <?php if ($order['delivery_type'] == 'delivery'): ?>
                                            Home Delivery
                                        <?php else: ?>
                                            Table Order
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($order['delivery_type'] == 'delivery'): ?>
                                        <p><strong>Delivery Address:</strong> <?php echo $order['address']; ?></p>
                                    <?php else: ?>
                                        <p><strong>Table Number:</strong> <?php echo str_replace('Table: ', '', $order['address']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-items mb-4">
                            <h5 class="border-bottom pb-2">Order Items</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">Item</th>
                                            <th>Name</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <img src="../assets/images/food/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="img-fluid rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                                </td>
                                                <td><?php echo $item['name']; ?></td>
                                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td class="text-end">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                            <td class="text-end"><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        
                        <?php if (!empty($order['notes'])): ?>
                            <div class="order-notes mb-4">
                                <h5 class="border-bottom pb-2">Special Instructions</h5>
                                <p><?php echo $order['notes']; ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="orders.php" class="btn btn-primary me-2">
                                <i class="fas fa-list me-2"></i>View All Orders
                            </a>
                            <a href="menu.php" class="btn btn-outline-primary">
                                <i class="fas fa-utensils me-2"></i>Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
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
</body>
</html>
