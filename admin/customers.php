<?php
// Start session
session_start();

// Include database connection
include '../includes/db_connection.php';
include '../includes/functions.php';

// Get all customers (users with role 'customer')
$sql = "SELECT * FROM users WHERE role = 'customer' ORDER BY name ASC";
$result = mysqli_query($conn, $sql);
$customers = array();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $customers[] = $row;
    }
}

// Initialize messages
$success = '';
$error = '';

// Include header
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Customers</h1>
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

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="customersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Registered On</th>
                        <th>Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($customers) > 0): ?>
                        <?php foreach ($customers as $index => $customer): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                <td><?php echo date('d M Y', strtotime($customer['created_at'])); ?></td>
                                <td>
                                    <?php
                                    // Get order count for this customer
                                    $order_sql = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?";
                                    $stmt = mysqli_prepare($conn, $order_sql);
                                    mysqli_stmt_bind_param($stmt, "i", $customer['id']);
                                    mysqli_stmt_execute($stmt);
                                    $order_result = mysqli_stmt_get_result($stmt);
                                    $order_count = 0;
                                    
                                    if ($order_row = mysqli_fetch_assoc($order_result)) {
                                        $order_count = $order_row['order_count'];
                                    }
                                    ?>
                                    <a href="orders.php?user_id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <?php echo $order_count; ?> Orders
                                    </a>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="#" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewCustomerModal<?php echo $customer['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                    
                                    <!-- View Customer Modal -->
                                    <div class="modal fade" id="viewCustomerModal<?php echo $customer['id']; ?>" tabindex="-1" aria-labelledby="viewCustomerModalLabel<?php echo $customer['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewCustomerModalLabel<?php echo $customer['id']; ?>">Customer Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3 text-center">
                                                        <div class="avatar-circle">
                                                            <span class="avatar-text"><?php echo substr($customer['name'], 0, 1); ?></span>
                                                        </div>
                                                    </div>
                                                    
                                                    <table class="table">
                                                        <tr>
                                                            <th>Name:</th>
                                                            <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Email:</th>
                                                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Phone:</th>
                                                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Registered On:</th>
                                                            <td><?php echo date('d M Y h:i A', strtotime($customer['created_at'])); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Total Orders:</th>
                                                            <td><?php echo $order_count; ?></td>
                                                        </tr>
                                                    </table>
                                                    
                                                    <?php
                                                    // Get customer addresses
                                                    $address_sql = "SELECT * FROM user_addresses WHERE user_id = ?";
                                                    $stmt = mysqli_prepare($conn, $address_sql);
                                                    mysqli_stmt_bind_param($stmt, "i", $customer['id']);
                                                    mysqli_stmt_execute($stmt);
                                                    $address_result = mysqli_stmt_get_result($stmt);
                                                    $addresses = array();
                                                    
                                                    while ($address = mysqli_fetch_assoc($address_result)) {
                                                        $addresses[] = $address;
                                                    }
                                                    
                                                    if (count($addresses) > 0):
                                                    ?>
                                                        <h6 class="mt-4 mb-3">Saved Addresses</h6>
                                                        <div class="list-group">
                                                            <?php foreach ($addresses as $address): ?>
                                                                <div class="list-group-item">
                                                                    <p class="mb-1"><?php echo htmlspecialchars($address['address']); ?></p>
                                                                    <?php if ($address['is_default']): ?>
                                                                        <span class="badge bg-primary">Default</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <a href="orders.php?user_id=<?php echo $customer['id']; ?>" class="btn btn-primary">View Orders</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No customers found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- DataTables JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<style>
    .avatar-circle {
        width: 80px;
        height: 80px;
        background-color: #007bff;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto;
    }
    
    .avatar-text {
        color: white;
        font-size: 32px;
        font-weight: bold;
        text-transform: uppercase;
    }
</style>

<script>
    $(document).ready(function() {
        $('#customersTable').DataTable({
            "pageLength": 25,
            "order": [[1, "asc"]]
        });
    });
</script>