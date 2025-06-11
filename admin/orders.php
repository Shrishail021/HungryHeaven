<?php
// Start session
session_start();

// Include database connection
include '../includes/db_connection.php';
include '../includes/functions.php';

// Set default filter values
$status_filter = isset($_GET['status']) ? intval($_GET['status']) : -1; // -1 means all
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Build query based on filters
$sql = "SELECT o.*, u.name as customer_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE 1=1";

if ($status_filter >= 0) {
    $sql .= " AND o.order_status = $status_filter";
}

if (!empty($date_filter)) {
    $sql .= " AND DATE(o.order_date) = '$date_filter'";
}

if (!empty($search)) {
    $sql .= " AND (o.reference_number LIKE '%$search%' OR u.name LIKE '%$search%' OR u.email LIKE '%$search%')";
}

$sql .= " ORDER BY o.order_date DESC";

// Execute query
$result = mysqli_query($conn, $sql);
$orders = array();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
}

// Process order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = intval($_POST['status']);
    
    $update_sql = "UPDATE orders SET order_status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "ii", $status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Redirect to refresh the page
        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
        exit();
    }
}

// Include header
include 'includes/header.php';
?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Orders</h1>
                </div>
                
                <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Order status updated successfully.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" class="row g-3">
                                    <div class="col-md-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="-1" <?php echo $status_filter == -1 ? 'selected' : ''; ?>>All</option>
                                            <option value="0" <?php echo $status_filter == 0 ? 'selected' : ''; ?>>Pending</option>
                                            <option value="1" <?php echo $status_filter == 1 ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="2" <?php echo $status_filter == 2 ? 'selected' : ''; ?>>Preparing</option>
                                            <option value="3" <?php echo $status_filter == 3 ? 'selected' : ''; ?>>Ready</option>
                                            <option value="4" <?php echo $status_filter == 4 ? 'selected' : ''; ?>>Completed</option>
                                            <option value="5" <?php echo $status_filter == 5 ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search" name="search" placeholder="Order #, Customer" value="<?php echo $search; ?>">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                                        <a href="orders.php" class="btn btn-secondary">Reset</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="ordersTable">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Date & Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($orders) > 0): ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><?php echo $order['reference_number']; ?></td>
                                                <td><?php echo $order['customer_name']; ?></td>
                                                <td>
                                                    <?php if ($order['delivery_type'] == 'delivery'): ?>
                                                        <span class="badge bg-info">Delivery</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Table Order</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <?php if ($order['payment_method'] == 'razorpay'): ?>
                                                        <span class="badge bg-primary">Razorpay</span>
                                                    <?php elseif ($order['payment_method'] == 'cod'): ?>
                                                        <span class="badge bg-warning text-dark">COD</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Cash</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo get_order_status_badge($order['order_status']); ?>">
                                                        <?php echo get_order_status_text($order['order_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $order['id']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="print_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
                                                            <i class="fas fa-print"></i>
                                                        </a>
                                                    </div>
                                                    
                                                    <!-- Update Status Modal -->
                                                    <div class="modal fade" id="updateStatusModal<?php echo $order['id']; ?>" tabindex="-1" aria-labelledby="updateStatusModalLabel<?php echo $order['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="updateStatusModalLabel<?php echo $order['id']; ?>">Update Order Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>&search=<?php echo $search; ?>" method="post">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="status<?php echo $order['id']; ?>" class="form-label">Order Status</label>
                                                                            <select class="form-select" id="status<?php echo $order['id']; ?>" name="status">
                                                                                <option value="0" <?php echo $order['order_status'] == 0 ? 'selected' : ''; ?>>Pending</option>
                                                                                <option value="1" <?php echo $order['order_status'] == 1 ? 'selected' : ''; ?>>Confirmed</option>
                                                                                <option value="2" <?php echo $order['order_status'] == 2 ? 'selected' : ''; ?>>Preparing</option>
                                                                                <option value="3" <?php echo $order['order_status'] == 3 ? 'selected' : ''; ?>>Ready for Pickup/Delivery</option>
                                                                                <option value="4" <?php echo $order['order_status'] == 4 ? 'selected' : ''; ?>>Delivered/Completed</option>
                                                                                <option value="5" <?php echo $order['order_status'] == 5 ? 'selected' : ''; ?>>Cancelled</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        <button type="submit" class="btn btn-primary">Update Status</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No orders found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
<?php include 'includes/footer.php'; ?>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#ordersTable').DataTable({
            "order": [[ 3, "desc" ]],
            "pageLength": 25
        });
    });
</script>
