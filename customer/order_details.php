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
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$order_id = intval($_GET['id']);

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

// Process cancel order request
$success = '';
$error = '';

if (isset($_POST['cancel_order']) && $order['order_status'] == 0) {
    // Update order status to cancelled (5)
    $sql = "UPDATE orders SET order_status = 5, updated_at = NOW() WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Order cancelled successfully';
        $order['order_status'] = 5; // Update status in current view
    } else {
        $error = 'Error cancelling order: ' . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Hungry Heaven</title>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Order Details</h1>
            <a href="orders.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Order Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-1">Order Number</p>
                                <p class="fw-bold"><?php echo $order['reference_number']; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-1">Order Date</p>
                                <p class="fw-bold"><?php echo date('d M Y h:i A', strtotime($order['order_date'])); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-1">Order Type</p>
                                <p class="fw-bold">
                                    <?php if ($order['delivery_type'] == 'delivery'): ?>
                                        <span class="badge bg-info">Home Delivery</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Table Order</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-1">Payment Method</p>
                                <p class="fw-bold">
                                    <?php if ($order['payment_method'] == 'razorpay'): ?>
                                        <span class="badge bg-primary">Razorpay</span>
                                    <?php elseif ($order['payment_method'] == 'cod'): ?>
                                        <span class="badge bg-warning text-dark">Cash on Delivery</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Cash</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-1">Status</p>
                                <p class="fw-bold">
                                    <span class="badge <?php echo get_order_status_badge($order['order_status']); ?>">
                                        <?php echo get_order_status_text($order['order_status']); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-1">Total Amount</p>
                                <p class="fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                            
                            <?php if ($order['delivery_type'] == 'delivery'): ?>
                                <div class="col-12 mb-3">
                                    <p class="text-muted mb-1">Delivery Address</p>
                                    <p class="fw-bold"><?php echo $order['address']; ?></p>
                                </div>
                            <?php else: ?>
                                <div class="col-12 mb-3">
                                    <p class="text-muted mb-1">Table Number</p>
                                    <p class="fw-bold"><?php echo str_replace('Table: ', '', $order['address']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($order['notes'])): ?>
                                <div class="col-12 mb-3">
                                    <p class="text-muted mb-1">Special Instructions</p>
                                    <p class="fw-bold"><?php echo $order['notes']; ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($order['order_status'] == 0): ?>
                                <div class="col-12">
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $order_id; ?>" method="post" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                        <button type="submit" name="cancel_order" class="btn btn-danger">
                                            <i class="fas fa-times me-2"></i>Cancel Order
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">Item</th>
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
                                                <img src="../assets/images/food/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="img-fluid rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                            </td>
                                            <td><?php echo $item['name']; ?></td>
                                            <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td class="text-end">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>
                                <?php 
                                $subtotal = 0;
                                foreach ($order_items as $item) {
                                    $subtotal += ($item['price'] * $item['quantity']);
                                }
                                echo '₹' . number_format($subtotal, 2);
                                ?>
                            </span>
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
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (<?php echo $tax_percentage; ?>%):</span>
                            <span>₹<?php echo number_format($tax, 2); ?></span>
                        </div>
                        
                        <?php if ($order['delivery_type'] == 'delivery'): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery Charge:</span>
                                <span><?php echo $delivery_charge > 0 ? '₹' . number_format($delivery_charge, 2) : 'Free'; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        <div class="d-flex justify-content-between mb-0">
                            <span class="fw-bold">Total:</span>
                            <span class="fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Order Status Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Status</h5>
                    </div>
                    <div class="card-body">
                        <ul class="timeline">
                            <li class="timeline-item <?php echo $order['order_status'] >= 0 ? 'active' : ''; ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Order Placed</h6>
                                    <p class="text-muted mb-0"><?php echo date('d M Y h:i A', strtotime($order['order_date'])); ?></p>
                                </div>
                            </li>
                            <li class="timeline-item <?php echo $order['order_status'] >= 1 ? 'active' : ''; ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Order Confirmed</h6>
                                    <?php if ($order['order_status'] >= 1): ?>
                                        <p class="text-muted mb-0">Your order has been confirmed</p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">Waiting for confirmation</p>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li class="timeline-item <?php echo $order['order_status'] >= 2 ? 'active' : ''; ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Preparing</h6>
                                    <?php if ($order['order_status'] >= 2): ?>
                                        <p class="text-muted mb-0">Your food is being prepared</p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">Waiting to start preparation</p>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li class="timeline-item <?php echo $order['order_status'] >= 3 ? 'active' : ''; ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">
                                        <?php if ($order['delivery_type'] == 'delivery'): ?>
                                            Ready for Delivery
                                        <?php else: ?>
                                            Ready for Pickup
                                        <?php endif; ?>
                                    </h6>
                                    <?php if ($order['order_status'] >= 3): ?>
                                        <p class="text-muted mb-0">Your order is ready</p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">Your order is being prepared</p>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li class="timeline-item <?php echo $order['order_status'] >= 4 ? 'active' : ''; ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">
                                        <?php if ($order['delivery_type'] == 'delivery'): ?>
                                            Delivered
                                        <?php else: ?>
                                            Completed
                                        <?php endif; ?>
                                    </h6>
                                    <?php if ($order['order_status'] >= 4): ?>
                                        <p class="text-muted mb-0">Your order has been completed</p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">Waiting to be completed</p>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php if ($order['order_status'] == 5): ?>
                                <li class="timeline-item active cancelled">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-0">Cancelled</h6>
                                        <p class="text-muted mb-0">Your order has been cancelled</p>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
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

<style>
/* Timeline Styles */
.timeline {
    list-style: none;
    padding: 0;
    position: relative;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #ddd;
    left: 15px;
    margin-left: -1px;
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 20px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    border: 2px solid #ddd;
    background: #fff;
    left: 9px;
    top: 6px;
}

.timeline-item.active .timeline-marker {
    background: #28a745;
    border-color: #28a745;
}

.timeline-item.cancelled .timeline-marker {
    background: #dc3545;
    border-color: #dc3545;
}

.timeline-content {
    padding-bottom: 10px;
}
</style>
