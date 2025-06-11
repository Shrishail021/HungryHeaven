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

// Get user details
$user = get_user_details($_SESSION['user_id']);

// Initialize messages
$success = '';
$error = '';

// Process profile update form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $address = clean_input($_POST['address']);
    
    // Validate form data
    if (empty($name) || empty($email) || empty($phone)) {
        $error = 'Please fill in all required fields';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error = 'Please enter a valid 10-digit phone number';
    } else {
        // Check if email already exists for other users
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $email, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = 'Email already exists';
        } else {
            // Update user profile
            $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $phone, $address, $_SESSION['user_id']);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Profile updated successfully';
                
                // Update session variables
                $_SESSION['username'] = $name;
                $_SESSION['user_email'] = $email;
                
                // Refresh user details
                $user = get_user_details($_SESSION['user_id']);
            } else {
                $error = 'Error updating profile: ' . mysqli_error($conn);
            }
        }
    }
}

// Process password change form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate form data
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields';
    } else if ($new_password != $confirm_password) {
        $error = 'New passwords do not match';
    } else if (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $_SESSION['user_id']);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Password changed successfully';
            } else {
                $error = 'Error changing password: ' . mysqli_error($conn);
            }
        } else {
            $error = 'Current password is incorrect';
        }
    }
}

// Get user's saved addresses
$sql = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$addresses = array();
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $addresses[] = $row;
    }
}

// Process add/edit address form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_address'])) {
    $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
    $address = clean_input($_POST['address_text']);
    $latitude = clean_input($_POST['latitude']);
    $longitude = clean_input($_POST['longitude']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // Validate form data
    if (empty($address) || empty($latitude) || empty($longitude)) {
        $error = 'Please fill in all address fields';
    } else {
        // If this is a new default address, unset other default addresses
        if ($is_default) {
            $sql = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
        }
        
        if ($address_id > 0) {
            // Update existing address
            $sql = "UPDATE user_addresses SET address = ?, latitude = ?, longitude = ?, is_default = ? WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssiii", $address, $latitude, $longitude, $is_default, $address_id, $_SESSION['user_id']);
        } else {
            // Add new address
            $sql = "INSERT INTO user_addresses (user_id, address, latitude, longitude, is_default, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isssi", $_SESSION['user_id'], $address, $latitude, $longitude, $is_default);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Address saved successfully';
            
            // Reload addresses
            $stmt = mysqli_prepare($conn, "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $addresses = array();
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $addresses[] = $row;
                }
            }
        } else {
            $error = 'Error saving address: ' . mysqli_error($conn);
        }
    }
}

// Process delete address
if (isset($_GET['delete_address']) && intval($_GET['delete_address']) > 0) {
    $address_id = intval($_GET['delete_address']);
    
    // Delete address
    $sql = "DELETE FROM user_addresses WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $address_id, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Address deleted successfully';
        
        // Reload addresses
        $stmt = mysqli_prepare($conn, "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $addresses = array();
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $addresses[] = $row;
            }
        }
    } else {
        $error = 'Error deleting address: ' . mysqli_error($conn);
    }
}

// Get order statistics
$sql = "SELECT COUNT(*) as total_orders FROM orders WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$total_orders = $row['total_orders'];

$sql = "SELECT SUM(total_amount) as total_spent FROM orders WHERE user_id = ? AND order_status != 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$total_spent = $row['total_spent'] ? $row['total_spent'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Hungry Heaven</title>
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
        <h1 class="text-center mb-5">My Profile</h1>
        
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
            <!-- Left Column - User Info -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">User Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="avatar mb-3">
                                <span class="avatar-text bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 2.5rem;">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </span>
                            </div>
                            <h5 class="mb-0"><?php echo $user['name']; ?></h5>
                            <p class="text-muted"><?php echo $user['email']; ?></p>
                        </div>
                        
                        <div class="row text-center mb-4">
                            <div class="col-6 border-end">
                                <h5><?php echo $total_orders; ?></h5>
                                <p class="text-muted mb-0">Orders</p>
                            </div>
                            <div class="col-6">
                                <h5>₹<?php echo number_format($total_spent, 2); ?></h5>
                                <p class="text-muted mb-0">Spent</p>
                            </div>
                        </div>
                        
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-phone me-2"></i> Phone</span>
                                <span><?php echo $user['phone']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-calendar-alt me-2"></i> Joined</span>
                                <span><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Tabs -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="edit-profile-tab" data-bs-toggle="tab" data-bs-target="#edit-profile" type="button" role="tab" aria-controls="edit-profile" aria-selected="true">Edit Profile</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="change-password-tab" data-bs-toggle="tab" data-bs-target="#change-password" type="button" role="tab" aria-controls="change-password" aria-selected="false">Change Password</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="addresses-tab" data-bs-toggle="tab" data-bs-target="#addresses" type="button" role="tab" aria-controls="addresses" aria-selected="false">Addresses</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="profileTabsContent">
                            <!-- Edit Profile Tab -->
                            <div class="tab-pane fade show active" id="edit-profile" role="tabpanel" aria-labelledby="edit-profile-tab">
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo $user['address']; ?></textarea>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                            
                            <!-- Change Password Tab -->
                            <div class="tab-pane fade" id="change-password" role="tabpanel" aria-labelledby="change-password-tab">
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                </form>
                            </div>
                            
                            <!-- Addresses Tab -->
                            <div class="tab-pane fade" id="addresses" role="tabpanel" aria-labelledby="addresses-tab">
                                <div class="mb-3">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                        <i class="fas fa-plus me-2"></i>Add New Address
                                    </button>
                                </div>
                                
                                <?php if (count($addresses) > 0): ?>
                                    <div class="list-group mb-4">
                                        <?php foreach ($addresses as $address): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0">
                                                        <?php if ($address['is_default']): ?>
                                                            <span class="badge bg-primary me-2">Default</span>
                                                        <?php endif; ?>
                                                        Address
                                                    </h6>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-primary edit-address" data-bs-toggle="modal" data-bs-target="#editAddressModal" 
                                                            data-id="<?php echo $address['id']; ?>"
                                                            data-address="<?php echo htmlspecialchars($address['address']); ?>"
                                                            data-lat="<?php echo $address['latitude']; ?>"
                                                            data-lng="<?php echo $address['longitude']; ?>"
                                                            data-default="<?php echo $address['is_default']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="profile.php?delete_address=<?php echo $address['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this address?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                <p class="mb-0"><?php echo $address['address']; ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        You haven't added any addresses yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAddressModalLabel">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="address_text" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="address_text" name="address_text" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="map" class="form-label">Location <span class="text-danger">*</span></label>
                            <div id="map" style="height: 300px;"></div>
                            <small class="text-muted">Drag the marker to adjust your exact location</small>
                            <input type="hidden" id="latitude" name="latitude" required>
                            <input type="hidden" id="longitude" name="longitude" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_default" name="is_default">
                            <label class="form-check-label" for="is_default">Set as default address</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_address" class="btn btn-primary">Save Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Address Modal -->
    <div class="modal fade" id="editAddressModal" tabindex="-1" aria-labelledby="editAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAddressModalLabel">Edit Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="edit_address_id" name="address_id">
                        
                        <div class="mb-3">
                            <label for="edit_address_text" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_address_text" name="address_text" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_map" class="form-label">Location <span class="text-danger">*</span></label>
                            <div id="edit_map" style="height: 300px;"></div>
                            <small class="text-muted">Drag the marker to adjust your exact location</small>
                            <input type="hidden" id="edit_latitude" name="latitude" required>
                            <input type="hidden" id="edit_longitude" name="longitude" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_default" name="is_default">
                            <label class="form-check-label" for="edit_is_default">Set as default address</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_address" class="btn btn-primary">Update Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap" async defer></script>
    
    <script>
        // Initialize Google Maps for Add Address
        let map, marker;
        let editMap, editMarker;
        
        function initMap() {
            const defaultLocation = { lat: 20.5937, lng: 78.9629 }; // Default to center of India
            
            // Add Address Map
            map = new google.maps.Map(document.getElementById('map'), {
                center: defaultLocation,
                zoom: 12
            });
            
            marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true
            });
            
            // Update coordinates when marker is dragged
            google.maps.event.addListener(marker, 'dragend', function() {
                const position = marker.getPosition();
                document.getElementById('latitude').value = position.lat();
                document.getElementById('longitude').value = position.lng();
                
                // Get address from coordinates using reverse geocoding
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: position }, function(results, status) {
                    if (status === 'OK') {
                        if (results[0]) {
                            document.getElementById('address_text').value = results[0].formatted_address;
                        }
                    }
                });
            });
            
            // Try to get user's current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    map.setCenter(userLocation);
                    marker.setPosition(userLocation);
                    
                    document.getElementById('latitude').value = userLocation.lat;
                    document.getElementById('longitude').value = userLocation.lng;
                    
                    // Get address from coordinates
                    const geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ location: userLocation }, function(results, status) {
                        if (status === 'OK') {
                            if (results[0]) {
                                document.getElementById('address_text').value = results[0].formatted_address;
                            }
                        }
                    });
                });
            }
            
            // Edit Address Map
            editMap = new google.maps.Map(document.getElementById('edit_map'), {
                center: defaultLocation,
                zoom: 12
            });
            
            editMarker = new google.maps.Marker({
                position: defaultLocation,
                map: editMap,
                draggable: true
            });
            
            // Update coordinates when marker is dragged
            google.maps.event.addListener(editMarker, 'dragend', function() {
                const position = editMarker.getPosition();
                document.getElementById('edit_latitude').value = position.lat();
                document.getElementById('edit_longitude').value = position.lng();
                
                // Get address from coordinates using reverse geocoding
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: position }, function(results, status) {
                    if (status === 'OK') {
                        if (results[0]) {
                            document.getElementById('edit_address_text').value = results[0].formatted_address;
                        }
                    }
                });
            });
        }
        
        // Handle edit address button click
        $(document).ready(function() {
            $('.edit-address').click(function() {
                const id = $(this).data('id');
                const address = $(this).data('address');
                const lat = parseFloat($(this).data('lat'));
                const lng = parseFloat($(this).data('lng'));
                const isDefault = $(this).data('default') == 1;
                
                // Set form values
                $('#edit_address_id').val(id);
                $('#edit_address_text').val(address);
                $('#edit_latitude').val(lat);
                $('#edit_longitude').val(lng);
                $('#edit_is_default').prop('checked', isDefault);
                
                // Set marker position
                if (editMap && editMarker) {
                    const position = { lat: lat, lng: lng };
                    editMap.setCenter(position);
                    editMarker.setPosition(position);
                }
            });
        });
    </script>
</body>
</html>
