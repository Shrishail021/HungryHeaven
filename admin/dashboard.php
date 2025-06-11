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

// Get dashboard statistics
// Total orders
$sql = "SELECT COUNT(*) as total_orders FROM orders";
$result = mysqli_query($conn, $sql);
$total_orders = 0;
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $total_orders = $row['total_orders'];
}

// Total revenue
$sql = "SELECT SUM(total_amount) as total_revenue FROM orders WHERE order_status != 5"; // Exclude cancelled orders
$result = mysqli_query($conn, $sql);
$total_revenue = 0;
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $total_revenue = $row['total_revenue'] ? $row['total_revenue'] : 0;
}

// Total customers
$sql = "SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'";
$result = mysqli_query($conn, $sql);
$total_customers = 0;
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $total_customers = $row['total_customers'];
}

// Total menu items
$sql = "SELECT COUNT(*) as total_items FROM menu_items";
$result = mysqli_query($conn, $sql);
$total_items = 0;
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $total_items = $row['total_items'];
}

// Get orders by status for pie chart
$sql = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
$result = mysqli_query($conn, $sql);
$order_status_data = array();
$order_status_labels = array('Pending', 'Confirmed', 'Preparing', 'Ready', 'Completed', 'Cancelled');
$order_status_counts = array_fill(0, 6, 0);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $order_status_counts[$row['order_status']] = $row['count'];
    }
}

// Get sales data for last 7 days for line chart
$sales_data = array();
$sales_labels = array();
$sales_values = array();

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $sales_labels[] = date('d M', strtotime($date));
    
    $sql = "SELECT SUM(total_amount) as daily_sales FROM orders WHERE DATE(order_date) = '$date' AND order_status != 5";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $sales_values[] = $row['daily_sales'] ? $row['daily_sales'] : 0;
    } else {
        $sales_values[] = 0;
    }
}

// Get top 5 selling items
$sql = "SELECT m.id, m.name, SUM(oi.quantity) as total_sold 
        FROM order_items oi 
        JOIN menu_items m ON oi.item_id = m.id 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.order_status != 5 
        GROUP BY m.id 
        ORDER BY total_sold DESC 
        LIMIT 5";
$result = mysqli_query($conn, $sql);
$top_items = array();

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $top_items[] = $row;
    }
}

// Get recent orders
$sql = "SELECT o.*, u.name as customer_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.order_date DESC 
        LIMIT 5";
$result = mysqli_query($conn, $sql);
$recent_orders = array();

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_orders[] = $row;
    }
}
?>

<?php include 'includes/header.php'; ?>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Print</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                            <i class="fas fa-calendar-alt"></i>
                            This week
                        </button>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Orders</h6>
                                        <h2 class="mb-0"><?php echo $total_orders; ?></h2>
                                    </div>
                                    <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="orders.php" class="text-white text-decoration-none small">View details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Revenue</h6>
                                        <h2 class="mb-0">₹<?php echo number_format($total_revenue, 2); ?></h2>
                                    </div>
                                    <i class="fas fa-rupee-sign fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="sales.php" class="text-white text-decoration-none small">View details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Customers</h6>
                                        <h2 class="mb-0"><?php echo $total_customers; ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="customers.php" class="text-white text-decoration-none small">View details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Menu Items</h6>
                                        <h2 class="mb-0"><?php echo $total_items; ?></h2>
                                    </div>
                                    <i class="fas fa-utensils fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="menu_items.php" class="text-white text-decoration-none small">View details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row"> 
                    <div class="col-md-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">Sales for Last 7 Days</h5>
                            </div>
                            <div class="card-body" style="height: 400px; max-height: 400px;">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">Orders by Status</h5>
                            </div>
                            <div class="card-body" style="height: 400px; max-height: 400px;">
                                <canvas id="orderStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders and Top Items Row -->
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">Recent Orders</h5>
                            </div>
                            <div class="card-body" style="height: 400px; max-height: 400px;">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td><?php echo $order['reference_number']; ?></td>
                                                    <td><?php echo $order['customer_name']; ?></td>
                                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo get_order_status_badge($order['order_status']); ?>">
                                                            <?php echo get_order_status_text($order['order_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></td>
                                                    <td>
                                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (count($recent_orders) == 0): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No recent orders found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">
                                    <a href="orders.php" class="btn btn-sm btn-outline-primary">View All Orders</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">Top Selling Items</h5>
                            </div>
                            <div class="card-body" style="height: 400px; max-height: 400px;">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($top_items as $index => $item): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-primary rounded-pill me-2"><?php echo $index + 1; ?></span>
                                                <?php echo $item['name']; ?>
                                            </div>
                                            <span class="badge bg-success rounded-pill"><?php echo $item['total_sold']; ?> sold</span>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (count($top_items) == 0): ?>
                                        <li class="list-group-item text-center">No data available</li>
                                    <?php endif; ?>
                                </ul>
                                <div class="mt-3">
                                    <a href="menu_items.php" class="btn btn-sm btn-outline-primary">View All Items</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

<?php include 'includes/footer.php'; ?>

<!-- Chart.js initialization -->
<script>
    // Sales Chart
    var salesCtx = document.getElementById('salesChart').getContext('2d');
        var salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($sales_labels); ?>,
                datasets: [{
                    label: 'Daily Sales (₹)',
                    data: <?php echo json_encode($sales_values); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value;
                            }
                        }
                    }
                }
            }
        });
        
        // Order Status Chart
        var statusCtx = document.getElementById('orderStatusChart').getContext('2d');
        var statusChart = new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($order_status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($order_status_counts); ?>,
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.8)',  // Pending
                        'rgba(23, 162, 184, 0.8)', // Confirmed
                        'rgba(0, 123, 255, 0.8)',  // Preparing
                        'rgba(40, 167, 69, 0.8)',  // Ready
                        'rgba(52, 58, 64, 0.8)',   // Completed
                        'rgba(220, 53, 69, 0.8)'   // Cancelled
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    </script>
</body>
</html>
