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

// Process status update
if (isset($_POST['update_status'])) {
    $reservation_id = intval($_POST['reservation_id']);
    $status = clean_input($_POST['status']);
    
    $sql = "UPDATE reservations SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $reservation_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Reservation status updated successfully.";
    } else {
        $error = "Error updating reservation status: " . mysqli_error($conn);
    }
}

// Process reservation deletion
if (isset($_POST['delete_reservation'])) {
    $reservation_id = intval($_POST['reservation_id']);
    
    $sql = "DELETE FROM reservations WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $reservation_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Reservation deleted successfully.";
    } else {
        $error = "Error deleting reservation: " . mysqli_error($conn);
    }
}

// Check if reservations table exists
$table_exists = false;
$check_table_sql = "SHOW TABLES LIKE 'reservations'";
$check_table_result = mysqli_query($conn, $check_table_sql);

if (mysqli_num_rows($check_table_result) > 0) {
    $table_exists = true;
} else {
    // Create reservations table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS reservations (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) DEFAULT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        date DATE NOT NULL,
        time TIME NOT NULL,
        guests INT(11) NOT NULL,
        special_request TEXT DEFAULT NULL,
        status ENUM('pending', 'confirmed', 'canceled', 'completed') NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        CONSTRAINT fk_reservations_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_table_sql)) {
        $table_exists = true;
        $success = "Reservations table created successfully.";
    } else {
        $error = "Error creating reservations table: " . mysqli_error($conn);
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? clean_input($_GET['date']) : '';

// Build query based on filters
$sql = "SELECT r.*, u.name as user_name 
        FROM reservations r 
        LEFT JOIN users u ON r.user_id = u.id";

$where_clauses = array();
$params = array();
$param_types = "";

if (!empty($status_filter)) {
    $where_clauses[] = "r.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($date_filter)) {
    $where_clauses[] = "r.date = ?";
    $params[] = $date_filter;
    $param_types .= "s";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY r.date ASC, r.time ASC";

$reservations = array();

// Only execute the query if the table exists
if ($table_exists) {
    // Prepare and execute the query
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $reservations = mysqli_fetch_all($result, MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - Admin Panel</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/admin.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Reservations</h1>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
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
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Filter Reservations</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="get" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="canceled" <?php echo $status_filter == 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                                <a href="reservations.php" class="btn btn-secondary">
                                    <i class="fas fa-redo me-2"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Reservations Table -->
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Reservations</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                <li><a class="dropdown-item" href="#" id="export-csv">CSV</a></li>
                                <li><a class="dropdown-item" href="#" id="export-excel">Excel</a></li>
                                <li><a class="dropdown-item" href="#" id="export-pdf">PDF</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="reservationsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Guests</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $reservation): ?>
                                        <tr>
                                            <td><?php echo $reservation['id']; ?></td>
                                            <td><?php echo htmlspecialchars($reservation['name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($reservation['date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($reservation['time'])); ?></td>
                                            <td><?php echo $reservation['guests']; ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $reservation['status'] == 'confirmed' ? 'bg-success' : 
                                                        ($reservation['status'] == 'pending' ? 'bg-warning text-dark' : 
                                                            ($reservation['status'] == 'canceled' ? 'bg-danger' : 'bg-info')); 
                                                ?>">
                                                    <?php echo ucfirst($reservation['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($reservation['created_at'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info view-reservation" data-bs-toggle="modal" data-bs-target="#viewReservationModal" data-id="<?php echo $reservation['id']; ?>" data-name="<?php echo htmlspecialchars($reservation['name']); ?>" data-email="<?php echo htmlspecialchars($reservation['email']); ?>" data-phone="<?php echo htmlspecialchars($reservation['phone']); ?>" data-date="<?php echo $reservation['date']; ?>" data-time="<?php echo $reservation['time']; ?>" data-guests="<?php echo $reservation['guests']; ?>" data-request="<?php echo htmlspecialchars($reservation['special_request']); ?>" data-status="<?php echo $reservation['status']; ?>" data-created="<?php echo $reservation['created_at']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary update-status" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-id="<?php echo $reservation['id']; ?>" data-status="<?php echo $reservation['status']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-reservation" data-bs-toggle="modal" data-bs-target="#deleteReservationModal" data-id="<?php echo $reservation['id']; ?>" data-name="<?php echo htmlspecialchars($reservation['name']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- View Reservation Modal -->
    <div class="modal fade" id="viewReservationModal" tabindex="-1" aria-labelledby="viewReservationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewReservationModalLabel">Reservation Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6>Name</h6>
                            <p id="view-name" class="border-bottom pb-2"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6>Email</h6>
                            <p id="view-email" class="border-bottom pb-2"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6>Phone</h6>
                            <p id="view-phone" class="border-bottom pb-2"></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6>Status</h6>
                            <p id="view-status" class="border-bottom pb-2"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <h6>Date</h6>
                            <p id="view-date" class="border-bottom pb-2"></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6>Time</h6>
                            <p id="view-time" class="border-bottom pb-2"></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6>Guests</h6>
                            <p id="view-guests" class="border-bottom pb-2"></p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6>Special Request</h6>
                        <p id="view-request" class="border-bottom pb-2"></p>
                    </div>
                    <div class="mb-3">
                        <h6>Created At</h6>
                        <p id="view-created" class="border-bottom pb-2"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Reservation Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="reservation_id" id="status-reservation-id">
                        <div class="mb-3">
                            <label for="status-select" class="form-label">Status</label>
                            <select class="form-select" id="status-select" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="canceled">Canceled</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Reservation Modal -->
    <div class="modal fade" id="deleteReservationModal" tabindex="-1" aria-labelledby="deleteReservationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteReservationModalLabel">Delete Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the reservation for <span id="delete-reservation-name" class="fw-bold"></span>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="post">
                        <input type="hidden" name="reservation_id" id="delete-reservation-id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_reservation" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#reservationsTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'csv',
                        text: 'Export to CSV',
                        className: 'd-none',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'excel',
                        text: 'Export to Excel',
                        className: 'd-none',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'pdf',
                        text: 'Export to PDF',
                        className: 'd-none',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        }
                    }
                ]
            });
            
            // Custom export buttons
            $('#export-csv').on('click', function() {
                table.button('.buttons-csv').trigger();
            });
            
            $('#export-excel').on('click', function() {
                table.button('.buttons-excel').trigger();
            });
            
            $('#export-pdf').on('click', function() {
                table.button('.buttons-pdf').trigger();
            });
            
            // View reservation details
            $('.view-reservation').on('click', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var email = $(this).data('email');
                var phone = $(this).data('phone');
                var date = $(this).data('date');
                var time = $(this).data('time');
                var guests = $(this).data('guests');
                var request = $(this).data('request');
                var status = $(this).data('status');
                var created = $(this).data('created');
                
                // Format date and time
                var formattedDate = new Date(date).toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
                
                var formattedTime = new Date('2000-01-01T' + time).toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
                
                var formattedCreated = new Date(created).toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Set modal values
                $('#view-name').text(name);
                $('#view-email').text(email);
                $('#view-phone').text(phone);
                $('#view-date').text(formattedDate);
                $('#view-time').text(formattedTime);
                $('#view-guests').text(guests + (guests == 1 ? ' person' : ' people'));
                $('#view-request').text(request || 'None');
                $('#view-status').html('<span class="badge ' + 
                    (status == 'confirmed' ? 'bg-success' : 
                        (status == 'pending' ? 'bg-warning text-dark' : 
                            (status == 'canceled' ? 'bg-danger' : 'bg-info'))) + 
                    '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>');
                $('#view-created').text(formattedCreated);
            });
            
            // Update status
            $('.update-status').on('click', function() {
                var id = $(this).data('id');
                var status = $(this).data('status');
                
                $('#status-reservation-id').val(id);
                $('#status-select').val(status);
            });
            
            // Delete reservation
            $('.delete-reservation').on('click', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                
                $('#delete-reservation-id').val(id);
                $('#delete-reservation-name').text(name);
            });
        });
    </script>
</body>
</html>
