<?php
// Start session
session_start();

// Include database connection
include '../includes/db_connection.php';
include '../includes/functions.php';

// Initialize variables
$name = $email = $phone = $date = $time = $guests = $special_request = '';
$success = $error = '';

// Pre-fill form with user data if logged in
if (isset($_SESSION['user_id'])) {
    $user = get_user_details($_SESSION['user_id']);
    $name = $user['name'];
    $email = $user['email'];
    $phone = $user['phone'];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $date = clean_input($_POST['date']);
    $time = clean_input($_POST['time']);
    $guests = intval($_POST['guests']);
    $special_request = clean_input($_POST['special_request']);
    
    // Validate input
    if (empty($name) || empty($email) || empty($phone) || empty($date) || empty($time) || empty($guests)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($guests < 1 || $guests > 20) {
        $error = 'Number of guests must be between 1 and 20.';
    } else {
        // Validate date and time
        $current_date = date('Y-m-d');
        $reservation_date = date('Y-m-d', strtotime($date));
        
        if ($reservation_date < $current_date) {
            $error = 'Reservation date cannot be in the past.';
        } else {
            // Check if the selected time is within operating hours (11:00 AM to 10:00 PM)
            $reservation_time = strtotime($time);
            $opening_time = strtotime('11:00:00');
            $closing_time = strtotime('22:00:00');
            
            if ($reservation_time < $opening_time || $reservation_time > $closing_time) {
                $error = 'Reservation time must be between 11:00 AM and 10:00 PM.';
            } else {
                // Check if there are too many reservations for the selected time
                $check_sql = "SELECT COUNT(*) as count FROM reservations WHERE date = ? AND ABS(TIME_TO_SEC(TIMEDIFF(time, ?))) < 3600 AND status != 'canceled'";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "ss", $date, $time);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                $check_row = mysqli_fetch_assoc($check_result);
                
                if ($check_row['count'] >= 5) {
                    $error = 'Sorry, we are fully booked for the selected time. Please choose a different time.';
                } else {
                    // Insert into database
                    $sql = "INSERT INTO reservations (user_id, name, email, phone, date, time, guests, special_request) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    
                    if ($stmt) {
                        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
                        mysqli_stmt_bind_param($stmt, "issssiis", $user_id, $name, $email, $phone, $date, $time, $guests, $special_request);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $success = 'Your reservation request has been submitted successfully. We will confirm your reservation shortly.';
                            // Clear form fields after successful submission
                            if (!isset($_SESSION['user_id'])) {
                                $name = $email = $phone = '';
                            }
                            $date = $time = $special_request = '';
                            $guests = '';
                        } else {
                            $error = 'Error submitting reservation. Please try again later.';
                        }
                        
                        mysqli_stmt_close($stmt);
                    } else {
                        $error = 'Database error. Please try again later.';
                    }
                }
            }
        }
    }
}

// Get min date (today) and max date (6 months from now)
$min_date = date('Y-m-d');
$max_date = date('Y-m-d', strtotime('+6 months'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Reservation - Hungry Heaven</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/custom.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .reservation-bg {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('../assets/images/food/loginback3.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }
        .time-slot {
            cursor: pointer;
            transition: all 0.3s;
        }
        .time-slot:hover {
            background-color: #f8f9fa;
        }
        .time-slot.selected {
            background-color: #e9ecef;
            border-color: #0d6efd;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>
    
    <div class="reservation-bg">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Reserve Your Table</h1>
            <p class="lead">Enjoy a delightful dining experience at Hungry Heaven</p>
        </div>
    </div>
    
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
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
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4">Book Your Table</h3>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Your Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Your Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="guests" class="form-label">Number of Guests <span class="text-danger">*</span></label>
                                    <select class="form-select" id="guests" name="guests" required>
                                        <option value="" disabled <?php echo empty($guests) ? 'selected' : ''; ?>>Select number of guests</option>
                                        <?php for ($i = 1; $i <= 20; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $guests == $i ? 'selected' : ''; ?>><?php echo $i; ?> <?php echo $i == 1 ? 'person' : 'people'; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date" class="form-label">Reservation Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date" name="date" min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>" value="<?php echo htmlspecialchars($date); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="time" class="form-label">Reservation Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="time" name="time" min="11:00" max="22:00" value="<?php echo htmlspecialchars($time); ?>" required>
                                    <small class="text-muted">Our operating hours are from 11:00 AM to 10:00 PM</small>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="special_request" class="form-label">Special Requests</label>
                                <textarea class="form-control" id="special_request" name="special_request" rows="3" placeholder="Any special requests or dietary requirements?"><?php echo htmlspecialchars($special_request); ?></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-calendar-check me-2"></i>Reserve Table
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm mt-5">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4">Reservation Policy</h3>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item bg-transparent"><i class="fas fa-info-circle text-primary me-2"></i> Reservations can be made up to 6 months in advance.</li>
                            <li class="list-group-item bg-transparent"><i class="fas fa-info-circle text-primary me-2"></i> We hold reservations for 15 minutes past the reserved time.</li>
                            <li class="list-group-item bg-transparent"><i class="fas fa-info-circle text-primary me-2"></i> For parties of 10 or more, please call us directly at +91 98765 43210.</li>
                            <li class="list-group-item bg-transparent"><i class="fas fa-info-circle text-primary me-2"></i> Cancellations should be made at least 4 hours in advance.</li>
                            <li class="list-group-item bg-transparent"><i class="fas fa-info-circle text-primary me-2"></i> For any questions regarding your reservation, please contact us.</li>
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
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date picker
        flatpickr("#date", {
            minDate: "<?php echo $min_date; ?>",
            maxDate: "<?php echo $max_date; ?>",
            dateFormat: "Y-m-d",
            disable: [
                function(date) {
                    // Disable past dates
                    return date < new Date().setHours(0,0,0,0);
                }
            ]
        });
        
        // Initialize time picker
        flatpickr("#time", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            minTime: "11:00",
            maxTime: "22:00",
            time_24hr: true
        });
    </script>
</body>
</html>
