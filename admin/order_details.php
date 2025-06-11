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

// Process update order status
$success = '';
$error = '';

if (isset($_POST['update_status'])) {
    $new_status = intval($_POST['order_status']);
    
    // Update order status
    $sql = "UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $new_status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Order status updated successfully';
        $order['order_status'] = $new_status; // Update status in current view
    } else {
        $error = 'Error updating order status: ' . mysqli_error($conn);
    }
}

// Process assign delivery person
if (isset($_POST['assign_delivery'])) {
    $delivery_person = clean_input($_POST['delivery_person']);
    
    // Update delivery person
    $sql = "UPDATE orders SET delivery_person = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $delivery_person, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Delivery person assigned successfully';
        $order['delivery_person'] = $delivery_person; // Update in current view
    } else {
        $error = 'Error assigning delivery person: ' . mysqli_error($conn);
    }
}

// Process add admin note
if (isset($_POST['add_note'])) {
    $admin_note = clean_input($_POST['admin_note']);
    
    // Update admin note
    $sql = "UPDATE orders SET admin_notes = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $admin_note, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Admin note added successfully';
        $order['admin_notes'] = $admin_note; // Update in current view
    } else {
        $error = 'Error adding admin note: ' . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Admin - Hungry Heaven</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/admin.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-3">
                    <h1 class="h2">Order Details</h1>
                    <a href="orders.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Orders
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
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Order #<?php echo $order['reference_number']; ?></h5>
                                <span class="badge <?php echo get_order_status_badge($order['order_status']); ?>">
                                    <?php echo get_order_status_text($order['order_status']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <p class="text-muted mb-1">Customer</p>
                                        <p class="fw-bold"><?php echo $order['customer_name']; ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="text-muted mb-1">Contact</p>
                                        <p class="fw-bold">
                                            <a href="mailto:<?php echo $order['email']; ?>"><?php echo $order['email']; ?></a><br>
                                            <a href="tel:<?php echo $order['phone']; ?>"><?php echo $order['phone']; ?></a>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="text-muted mb-1">Order Date</p>
                                        <p class="fw-bold"><?php echo date('d M Y h:i A', strtotime($order['order_date'])); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="text-muted mb-1">Total Amount</p>
                                        <p class="fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></p>
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
                                                <?php if (!empty($order['transaction_id'])): ?>
                                                    <small class="d-block"><?php echo $order['transaction_id']; ?></small>
                                                <?php endif; ?>
                                            <?php elseif ($order['payment_method'] == 'cod'): ?>
                                                <span class="badge bg-warning text-dark">Cash on Delivery</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Cash</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    
                                    <?php if ($order['delivery_type'] == 'delivery'): ?>
                                        <div class="col-12 mb-3">
                                            <p class="text-muted mb-1">Delivery Address</p>
                                            <p class="fw-bold"><?php echo $order['address']; ?></p>
                                        </div>
                                        <?php if (!empty($order['delivery_person'])): ?>
                                            <div class="col-12 mb-3">
                                                <p class="text-muted mb-1">Delivery Person</p>
                                                <p class="fw-bold"><?php echo $order['delivery_person']; ?></p>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="col-12 mb-3">
                                            <p class="text-muted mb-1">Table Number</p>
                                            <p class="fw-bold"><?php echo str_replace('Table: ', '', $order['address']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($order['notes'])): ?>
                                        <div class="col-12 mb-3">
                                            <p class="text-muted mb-1">Customer Notes</p>
                                            <p class="fw-bold"><?php echo $order['notes']; ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($order['admin_notes'])): ?>
                                        <div class="col-12 mb-3">
                                            <p class="text-muted mb-1">Admin Notes</p>
                                            <p class="fw-bold"><?php echo $order['admin_notes']; ?></p>
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
                                        <tfoot>
                                            <tr>
                                                <th colspan="4" class="text-end">Subtotal:</th>
                                                <th class="text-end">
                                                    <?php 
                                                    $subtotal = 0;
                                                    foreach ($order_items as $item) {
                                                        $subtotal += ($item['price'] * $item['quantity']);
                                                    }
                                                    echo '₹' . number_format($subtotal, 2);
                                                    ?>
                                                </th>
                                            </tr>
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
                                            <tr>
                                                <th colspan="4" class="text-end">Tax (<?php echo $tax_percentage; ?>%):</th>
                                                <th class="text-end">₹<?php echo number_format($tax, 2); ?></th>
                                            </tr>
                                            <?php if ($order['delivery_type'] == 'delivery'): ?>
                                                <tr>
                                                    <th colspan="4" class="text-end">Delivery Charge:</th>
                                                    <th class="text-end"><?php echo $delivery_charge > 0 ? '₹' . number_format($delivery_charge, 2) : 'Free'; ?></th>
                                                </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <th colspan="4" class="text-end">Total:</th>
                                                <th class="text-end">₹<?php echo number_format($order['total_amount'], 2); ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Print Invoice Button -->
                        <div class="text-end mb-4">
                            <a href="print_invoice.php?id=<?php echo $order_id; ?>" target="_blank" class="btn btn-primary">
                                <i class="fas fa-print me-2"></i>Print Invoice
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Update Order Status -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Update Order Status</h5>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $order_id; ?>" method="post">
                                    <div class="mb-3">
                                        <label for="order_status" class="form-label">Order Status</label>
                                        <select class="form-select" id="order_status" name="order_status" required>
                                            <option value="0" <?php echo $order['order_status'] == 0 ? 'selected' : ''; ?>>Pending</option>
                                            <option value="1" <?php echo $order['order_status'] == 1 ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="2" <?php echo $order['order_status'] == 2 ? 'selected' : ''; ?>>Preparing</option>
                                            <option value="3" <?php echo $order['order_status'] == 3 ? 'selected' : ''; ?>>
                                                <?php echo $order['delivery_type'] == 'delivery' ? 'Ready for Delivery' : 'Ready for Pickup'; ?>
                                            </option>
                                            <option value="4" <?php echo $order['order_status'] == 4 ? 'selected' : ''; ?>>
                                                <?php echo $order['delivery_type'] == 'delivery' ? 'Delivered' : 'Completed'; ?>
                                            </option>
                                            <option value="5" <?php echo $order['order_status'] == 5 ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                                </form>
                            </div>
                        </div>
                        
                        <?php if ($order['delivery_type'] == 'delivery'): ?>
                            <!-- Assign Delivery Person -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Assign Delivery Person</h5>
                                </div>
                                <div class="card-body">
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $order_id; ?>" method="post">
                                        <div class="mb-3">
                                            <label for="delivery_person" class="form-label">Delivery Person Name</label>
                                            <input type="text" class="form-control" id="delivery_person" name="delivery_person" value="<?php echo isset($order['delivery_person']) ? $order['delivery_person'] : ''; ?>" required>
                                        </div>
                                        <button type="submit" name="assign_delivery" class="btn btn-success w-100">Assign Delivery</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Add Admin Note -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Add Admin Note</h5>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $order_id; ?>" method="post">
                                    <div class="mb-3">
                                        <label for="admin_note" class="form-label">Note</label>
                                        <textarea class="form-control" id="admin_note" name="admin_note" rows="3" required><?php echo isset($order['admin_notes']) ? $order['admin_notes'] : ''; ?></textarea>
                                        <div class="form-text">This note is only visible to administrators.</div>
                                    </div>
                                    <button type="submit" name="add_note" class="btn btn-secondary w-100">Save Note</button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Order Timeline -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Order Timeline</h5>
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
                                                <p class="text-muted mb-0">Order has been confirmed</p>
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
                                                <p class="text-muted mb-0">Food is being prepared</p>
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
                                                <p class="text-muted mb-0">Order is ready</p>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">Order is being prepared</p>
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
                                                <p class="text-muted mb-0">Order has been completed</p>
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
                                                <p class="text-muted mb-0">Order has been cancelled</p>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Admin JS -->
    <script src="../assets/js/admin.js"></script>
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
