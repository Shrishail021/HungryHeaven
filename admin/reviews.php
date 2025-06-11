<?php
// Start session
session_start();

// Include database connection
include '../includes/db_connection.php';
include '../includes/functions.php';

// Get all reviews
$sql = "SELECT r.*, u.name as user_name 
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $sql);
$reviews = array();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $reviews[] = $row;
    }
}

// Initialize messages
$success = '';
$error = '';

// Process approve/reject review
if (isset($_GET['action']) && isset($_GET['id'])) {
    $review_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        $status = 1; // Approved
        $success_message = 'Review approved successfully';
    } else if ($action == 'reject') {
        $status = 0; // Rejected
        $success_message = 'Review rejected successfully';
    } else if ($action == 'delete') {
        // Delete review
        $delete_sql = "DELETE FROM reviews WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($stmt, "i", $review_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Review deleted successfully';
            
            // Remove from the array
            foreach ($reviews as $key => $review) {
                if ($review['id'] == $review_id) {
                    unset($reviews[$key]);
                    break;
                }
            }
        } else {
            $error = 'Error deleting review: ' . mysqli_error($conn);
        }
    }
    
    if (isset($status)) {
        $update_sql = "UPDATE reviews SET is_approved = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "ii", $status, $review_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update the review status in the array
            foreach ($reviews as &$review) {
                if ($review['id'] == $review_id) {
                    $review['is_approved'] = $status;
                    break;
                }
            }
            $success = $success_message;
        } else {
            $error = 'Error updating review status: ' . mysqli_error($conn);
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Reviews</h1>
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
            <table class="table table-striped table-hover" id="reviewsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $index => $review): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                                <td>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <i class="fas fa-star text-warning"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-warning"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars(substr($review['review_text'], 0, 50)) . (strlen($review['review_text']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo date('d M Y', strtotime($review['created_at'])); ?></td>
                                <td>
                                    <?php if ($review['is_approved'] == 1): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="#" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewReviewModal<?php echo $review['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($review['is_approved'] == 0): ?>
                                            <a href="?action=approve&id=<?php echo $review['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this review?');">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=reject&id=<?php echo $review['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to reject this review?');">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="?action=delete&id=<?php echo $review['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this review? This action cannot be undone.');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                    
                                    <!-- View Review Modal -->
                                    <div class="modal fade" id="viewReviewModal<?php echo $review['id']; ?>" tabindex="-1" aria-labelledby="viewReviewModalLabel<?php echo $review['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewReviewModalLabel<?php echo $review['id']; ?>">Review Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <h6>Customer</h6>
                                                        <p><?php echo htmlspecialchars($review['user_name']); ?></p>
                                                    </div>
                                                    

                                                    
                                                    <div class="mb-3">
                                                        <h6>Rating</h6>
                                                        <div class="rating">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <?php if ($i <= $review['rating']): ?>
                                                                    <i class="fas fa-star text-warning"></i>
                                                                <?php else: ?>
                                                                    <i class="far fa-star text-warning"></i>
                                                                <?php endif; ?>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <h6>Review</h6>
                                                        <p><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <h6>Date</h6>
                                                        <p><?php echo date('d M Y h:i A', strtotime($review['created_at'])); ?></p>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <h6>Status</h6>
                                                        <?php if ($review['is_approved'] == 1): ?>
                                                            <span class="badge bg-success">Approved</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Pending</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <?php if ($review['is_approved'] == 0): ?>
                                                        <a href="?action=approve&id=<?php echo $review['id']; ?>" class="btn btn-success">Approve</a>
                                                    <?php else: ?>
                                                        <a href="?action=reject&id=<?php echo $review['id']; ?>" class="btn btn-warning">Reject</a>
                                                    <?php endif; ?>
                                                    <a href="?action=delete&id=<?php echo $review['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this review? This action cannot be undone.');">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No reviews found</td>
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

<script>
    $(document).ready(function() {
        $('#reviewsTable').DataTable({
            "pageLength": 25,
            "order": [[5, "desc"]]
        });
    });
</script>
