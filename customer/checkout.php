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

// Check if cart is empty
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header('Location: cart.php');
    exit();
}

// Get user details
$user = get_user_details($_SESSION['user_id']);

// Get cart items
$cart_items = $_SESSION['cart'];
$cart_total = calculate_cart_total($cart_items);

// Get delivery charge and free delivery minimum
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'delivery_charge'";
$result = mysqli_query($conn, $sql);
$delivery_charge = 0;
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $delivery_charge = floatval($row['setting_value']);
}

$sql = "SELECT setting_value FROM settings WHERE setting_key = 'min_order_free_delivery'";
$result = mysqli_query($conn, $sql);
$min_order_free_delivery = 0;
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $min_order_free_delivery = floatval($row['setting_value']);
}

// Calculate if eligible for free delivery
$is_free_delivery = $cart_total >= $min_order_free_delivery;

// Get tax percentage
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'tax_percentage'";
$result = mysqli_query($conn, $sql);
$tax_percentage = 0;
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $tax_percentage = floatval($row['setting_value']);
}

// Calculate tax amount
$tax_amount = ($cart_total * $tax_percentage) / 100;

// Get Razorpay API keys
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('razorpay_key_id', 'razorpay_key_secret')";
$result = mysqli_query($conn, $sql);
$razorpay_key_id = '';
$razorpay_key_secret = '';

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['setting_key'] == 'razorpay_key_id') {
            $razorpay_key_id = $row['setting_value'];
        } else if ($row['setting_key'] == 'razorpay_key_secret') {
            $razorpay_key_secret = $row['setting_value'];
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

$error = '';
$success = '';

// Process checkout form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $delivery_type = clean_input($_POST['delivery_type']);
    $table_number = isset($_POST['table_number']) ? clean_input($_POST['table_number']) : '';
    $address = isset($_POST['address']) ? clean_input($_POST['address']) : '';
    $latitude = isset($_POST['latitude']) ? clean_input($_POST['latitude']) : '';
    $longitude = isset($_POST['longitude']) ? clean_input($_POST['longitude']) : '';
    $payment_method = isset($_POST['payment_method']) ? clean_input($_POST['payment_method']) : '';
    $notes = isset($_POST['notes']) ? clean_input($_POST['notes']) : '';
    
    // Get Razorpay payment details if payment method is razorpay
    $razorpay_payment_id = isset($_POST['razorpay_payment_id']) ? clean_input($_POST['razorpay_payment_id']) : '';
    $razorpay_order_id = isset($_POST['razorpay_order_id']) ? clean_input($_POST['razorpay_order_id']) : '';
    $razorpay_signature = isset($_POST['razorpay_signature']) ? clean_input($_POST['razorpay_signature']) : '';
    
    // Validate input
    if ($delivery_type == 'delivery' && (empty($address) || empty($payment_method))) {
        $error = 'Please provide delivery address and payment method';
    } else if ($delivery_type == 'table' && empty($table_number)) {
        $error = 'Please provide table number';
    } else if ($payment_method == 'razorpay' && empty($razorpay_payment_id)) {
        $error = 'Payment verification failed. Please try again or choose a different payment method.';
    } else {
        // Generate reference number
        $reference_number = generate_reference_number();
        
        // Calculate total amount
        $total_amount = $cart_total + $tax_amount;
        if ($delivery_type == 'delivery' && !$is_free_delivery) {
            $total_amount += $delivery_charge;
        }
        
        // For Razorpay payment, verify the signature if we're not in test mode
        $payment_verified = true;
        if ($payment_method == 'razorpay' && !empty($razorpay_payment_id) && !empty($razorpay_order_id) && !empty($razorpay_signature)) {
            if (!empty($razorpay_key_secret) && strpos($razorpay_payment_id, 'test_') !== 0) {
                // This is a real payment, verify signature
                $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $razorpay_key_secret);
                
                if ($generated_signature != $razorpay_signature) {
                    $error = 'Payment verification failed. Invalid signature.';
                    $payment_verified = false;
                }
            }
            // For test payments (starting with 'test_'), we skip verification
        }
        
        if ($payment_verified) {
            // Sanitize input
            if ($delivery_type == 'table') {
                $address = 'Table #' . $table_number;
                $payment_method = 'cash';
            }
            
            // Store payment ID for Razorpay
            $payment_id = ($payment_method == 'razorpay') ? $razorpay_payment_id : '';
            
            // First check if the payment_id column exists in the orders table
            $check_column_sql = "SHOW COLUMNS FROM orders LIKE 'payment_id'";
            $column_result = mysqli_query($conn, $check_column_sql);
            
            // If payment_id column doesn't exist, use the query without it
            if (mysqli_num_rows($column_result) == 0) {
                $sql = "INSERT INTO orders (user_id, reference_number, total_amount, delivery_type, payment_method, address, latitude, longitude, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "isdssssss", 
                    $_SESSION['user_id'], 
                    $reference_number, 
                    $total_amount, 
                    $delivery_type, 
                    $payment_method, 
                    $address, 
                    $latitude, 
                    $longitude, 
                    $notes
                );
            } else {
                // Use the query with payment_id
                $sql = "INSERT INTO orders (user_id, reference_number, total_amount, delivery_type, payment_method, address, latitude, longitude, notes, payment_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "isdsssssss", 
                    $_SESSION['user_id'], 
                    $reference_number, 
                    $total_amount, 
                    $delivery_type, 
                    $payment_method, 
                    $address, 
                    $latitude, 
                    $longitude, 
                    $notes, 
                    $payment_id
                );
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $order_id = mysqli_insert_id($conn);
                
                // Add order items
                $sql = "INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                
                foreach ($cart_items as $item) {
                    mysqli_stmt_bind_param($stmt, "iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
                    mysqli_stmt_execute($stmt);
                }
                
                // Order created successfully
                
                // Save address if it's a new one
                if ($delivery_type == 'delivery' && !empty($address) && !empty($latitude) && !empty($longitude)) {
                    // Check if address already exists
                    $address_exists = false;
                    foreach ($addresses as $addr) {
                        if ($addr['latitude'] == $latitude && $addr['longitude'] == $longitude) {
                            $address_exists = true;
                            break;
                        }
                    }
                    
                    if (!$address_exists) {
                        $sql = "INSERT INTO user_addresses (user_id, address, latitude, longitude, is_default) VALUES (?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        $is_default = count($addresses) == 0 ? 1 : 0;
                        mysqli_stmt_bind_param($stmt, "isssi", $_SESSION['user_id'], $address, $latitude, $longitude, $is_default);
                        mysqli_stmt_execute($stmt);
                    }
                }
                
                // Clear cart
                clear_cart();
                
                // Set success message
                $success = 'Order placed successfully! Your order reference number is ' . $reference_number;
                
                // Redirect to order confirmation page
                header('Location: order_confirmation.php?order_id=' . $order_id);
                exit();
            } else {
                $error = 'Error placing order: ' . mysqli_error($conn);
            }
        } else {
            $error = 'Error placing order: ' . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Hungry Heaven</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/custom.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Razorpay -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-5">
        <h1 class="text-center mb-5">Checkout</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Order Details</h5>
                        </div>
                        <div class="card-body">
                            <form id="checkout-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                <!-- Delivery Type -->
                                <div class="mb-4">
                                    <h6 class="mb-3">Delivery Type</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="delivery_type" id="table_order" value="table" checked>
                                        <label class="form-check-label" for="table_order">
                                            <i class="fas fa-utensils me-2"></i>Table Order
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="delivery_type" id="home_delivery" value="delivery">
                                        <label class="form-check-label" for="home_delivery">
                                            <i class="fas fa-motorcycle me-2"></i>Home Delivery
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Table Number (for table order) -->
                                <div id="table-section" class="mb-4">
                                    <h6 class="mb-3">Table Number</h6>
                                    <div class="mb-3">
                                        <input type="text" class="form-control" id="table_number" name="table_number" placeholder="Enter table number">
                                    </div>
                                </div>
                                
                                <!-- Delivery Address (for home delivery) -->
                                <div id="address-section" class="mb-4 d-none">
                                    <h6 class="mb-3">Delivery Address</h6>
                                    
                                    <?php if (count($addresses) > 0): ?>
                                        <div class="mb-3">
                                            <select class="form-select" id="saved_address" name="saved_address">
                                                <option value="">Select a saved address</option>
                                                <?php foreach ($addresses as $addr): ?>
                                                    <option value="<?php echo $addr['id']; ?>" data-address="<?php echo $addr['address']; ?>" data-lat="<?php echo $addr['latitude']; ?>" data-lng="<?php echo $addr['longitude']; ?>">
                                                        <?php echo $addr['address']; ?>
                                                        <?php if ($addr['is_default']): ?> (Default)<?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                                <option value="new">Add new address</option>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div id="new-address-form" class="<?php echo count($addresses) > 0 ? 'd-none' : ''; ?>">
                                        <div class="mb-3">
                                            <textarea class="form-control" id="address" name="address" rows="2" placeholder="Enter your full address"></textarea>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="latitude" class="form-label">Latitude</label>
                                                <input type="text" class="form-control" id="latitude" name="latitude" placeholder="e.g., 20.5937">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="longitude" class="form-label">Longitude</label>
                                                <input type="text" class="form-control" id="longitude" name="longitude" placeholder="e.g., 78.9629">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <button type="button" id="get-location-btn" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-map-marker-alt me-2"></i>Get My Current Location
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Payment Method (for home delivery) -->
                                <div id="payment-section" class="mb-4 d-none">
                                    <h6 class="mb-3">Payment Method</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
                                        <label class="form-check-label" for="cod">
                                            <i class="fas fa-money-bill me-2"></i>Cash on Delivery
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="razorpay" value="razorpay">
                                        <label class="form-check-label" for="razorpay">
                                            <i class="fas fa-credit-card me-2"></i>Razorpay (Credit/Debit Card, UPI, Net Banking)
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Special Instructions -->
                                <div class="mb-4">
                                    <h6 class="mb-3">Special Instructions</h6>
                                    <div class="mb-3">
                                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any special instructions for your order?"></textarea>
                                    </div>
                                </div>
                                
                                <!-- Razorpay hidden fields -->
                                <input type="hidden" id="razorpay_payment_id" name="razorpay_payment_id">
                                <input type="hidden" id="razorpay_order_id" name="razorpay_order_id">
                                <input type="hidden" id="razorpay_signature" name="razorpay_signature">
                                
                                <!-- Submit button (only for table order and COD) -->
                                <div id="cod-section">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-check-circle me-2"></i>Place Order
                                    </button>
                                </div>
                                
                                <!-- Razorpay button -->
                                <div id="razorpay-section" class="d-none">
                                    <button type="button" id="razorpay-button" class="btn btn-primary w-100">
                                        <i class="fas fa-credit-card me-2"></i>Pay Now
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Items (<?php echo count($cart_items); ?>):</span>
                                <span>₹<?php echo number_format($cart_total, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (<?php echo $tax_percentage; ?>%):</span>
                                <span>₹<?php echo number_format($tax_amount, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 delivery-charge">
                                <span>Delivery Charge:</span>
                                <span><?php echo $is_free_delivery ? 'Free' : '₹' . number_format($delivery_charge, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-bold">Total:</span>
                                <span class="fw-bold" id="order-total">₹<?php echo number_format($cart_total + $tax_amount, 2); ?></span>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Order Items</h6>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($cart_items as $item): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="fw-bold"><?php echo $item['name']; ?></span>
                                                    <br>
                                                    <small class="text-muted">₹<?php echo $item['price']; ?> x <?php echo $item['quantity']; ?></small>
                                                </div>
                                                <span>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Razorpay SDK -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    
    <script>
        // Handle payment method selection
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'razorpay') {
                    document.getElementById('razorpay-section').classList.remove('d-none');
                    document.getElementById('cod-section').classList.add('d-none');
                } else {
                    document.getElementById('razorpay-section').classList.add('d-none');
                    document.getElementById('cod-section').classList.remove('d-none');
                }
            });
        });
        
        // Handle delivery type selection
        document.querySelectorAll('input[name="delivery_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'delivery') {
                    document.getElementById('table-section').classList.add('d-none');
                    document.getElementById('address-section').classList.remove('d-none');
                    document.getElementById('payment-section').classList.remove('d-none');
                    
                    // Update total with delivery charge if applicable
                    const deliveryChargeElement = document.querySelector('.delivery-charge');
                    deliveryChargeElement.classList.remove('d-none');
                    
                    <?php if (!$is_free_delivery): ?>
                        // Add delivery charge to total
                        const currentTotal = parseFloat('<?php echo $cart_total + $tax_amount; ?>');
                        const deliveryCharge = parseFloat('<?php echo $delivery_charge; ?>');
                        document.getElementById('order-total').textContent = '₹' + (currentTotal + deliveryCharge).toFixed(2);
                    <?php endif; ?>
                } else {
                    document.getElementById('table-section').classList.remove('d-none');
                    document.getElementById('address-section').classList.add('d-none');
                    document.getElementById('payment-section').classList.add('d-none');
                    
                    // Hide delivery charge
                    const deliveryChargeElement = document.querySelector('.delivery-charge');
                    deliveryChargeElement.classList.add('d-none');
                    
                    // Reset total without delivery charge
                    const currentTotal = parseFloat('<?php echo $cart_total + $tax_amount; ?>');
                    document.getElementById('order-total').textContent = '₹' + currentTotal.toFixed(2);
                }
            });
        });
        
        // Handle payment method selection
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'razorpay') {
                    document.getElementById('razorpay-section').classList.remove('d-none');
                    document.getElementById('cod-section').classList.add('d-none');
                } else {
                    document.getElementById('razorpay-section').classList.add('d-none');
                    document.getElementById('cod-section').classList.remove('d-none');
                }
            });
        });
        
        // Handle saved address selection
        if (document.getElementById('saved_address')) {
            document.getElementById('saved_address').addEventListener('change', function() {
                if (this.value === 'new') {
                    document.getElementById('new-address-form').classList.remove('d-none');
                    document.getElementById('address').value = '';
                    document.getElementById('latitude').value = '';
                    document.getElementById('longitude').value = '';
                    
                    // Reset map
                    if (window.map && window.marker) {
                        const defaultLocation = { lat: 20.5937, lng: 78.9629 };
                        window.map.setCenter(defaultLocation);
                        window.marker.setPosition(defaultLocation);
                    }
                } else if (this.value !== '') {
                    document.getElementById('new-address-form').classList.add('d-none');
                    
                    // Get selected address details
                    const option = this.options[this.selectedIndex];
                    const address = option.getAttribute('data-address');
                    const lat = parseFloat(option.getAttribute('data-lat'));
                    const lng = parseFloat(option.getAttribute('data-lng'));
                    
                    // Set form values
                    document.getElementById('address').value = address;
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                }
            });
        }
        
        // Handle get location button
        document.getElementById('get-location-btn').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    document.getElementById('latitude').value = userLocation.lat;
                    document.getElementById('longitude').value = userLocation.lng;
                    
                    // You could implement reverse geocoding here with a fetch API call
                    // to get the address from coordinates, but for simplicity we'll skip that
                    // and let the user enter their address manually
                }, function(error) {
                    console.error('Error getting location:', error);
                    alert('Unable to get your location. Please enter your coordinates manually.');
                }, {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                });
            } else {
                alert('Geolocation is not supported by your browser. Please enter your coordinates manually.');
            }
        });
        
        // Handle saved address selection
        const savedAddressSelect = document.getElementById('saved_address');
        if (savedAddressSelect) {
            savedAddressSelect.addEventListener('change', function() {
                const newAddressForm = document.getElementById('new-address-form');
                
                if (this.value === 'new') {
                    newAddressForm.classList.remove('d-none');
                    document.getElementById('address').value = '';
                    document.getElementById('latitude').value = '';
                    document.getElementById('longitude').value = '';
                } else if (this.value !== '') {
                    newAddressForm.classList.add('d-none');
                    
                    const selectedOption = this.options[this.selectedIndex];
                    const address = selectedOption.getAttribute('data-address');
                    const lat = selectedOption.getAttribute('data-lat');
                    const lng = selectedOption.getAttribute('data-lng');
                    
                    document.getElementById('address').value = address;
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                }
            });
        }
        
        // Initialize Razorpay checkout
        document.getElementById('razorpay-button').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validate form
            const form = document.getElementById('checkout-form');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Get total amount
            let amount = parseFloat('<?php echo $cart_total + $tax_amount; ?>');
            
            // Add delivery charge if applicable
            if (document.querySelector('input[name="delivery_type"]:checked').value === 'delivery' && !<?php echo $is_free_delivery ? 'true' : 'false'; ?>) {
                amount += parseFloat('<?php echo $delivery_charge; ?>');
            }
            
            // Convert to paise for Razorpay
            amount = Math.round(amount * 100);
            
            const options = {
                // Use Razorpay key from database settings
                key: '<?php echo $razorpay_key_id; ?>',
                // Don't set order_id here, Razorpay will create it automatically
                amount: amount,
                currency: 'INR',
                name: 'Hungry Heaven',
                description: 'Food Order Payment',
                image: '../assets/images/logo.png',
                prefill: {
                    name: '<?php echo isset($_SESSION["username"]) ? $_SESSION["username"] : ""; ?>',
                    email: '<?php echo isset($_SESSION["user_email"]) ? $_SESSION["user_email"] : ""; ?>',
                    contact: ''
                },
                theme: {
                    color: '#3399cc'
                },
                notes: {
                    address: '<?php echo isset($address) ? htmlspecialchars($address) : ""; ?>'
                },
                handler: function(response) {
                    console.log('Payment response:', response);
                    
                    try {
                        // Set the Razorpay payment details in the form
                        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id || '';
                        document.getElementById('razorpay_order_id').value = response.razorpay_order_id || '';
                        document.getElementById('razorpay_signature').value = response.razorpay_signature || '';
                        
                        // Submit the form
                        document.getElementById('checkout-form').submit();
                    } catch (err) {
                        console.error('Error processing payment response:', err);
                        alert('Payment processing error. Please try again.');
                    }
                },
                // Enable this for testing - allows you to close payment without completing
                modal: {
                    ondismiss: function() {
                        alert('Payment cancelled. You can try again or choose a different payment method.');
                    }
                }
            };
            
            // Check if we're in test mode (localhost or test API key)
            const isTestMode = (window.location.hostname === 'localhost' || 
                              window.location.hostname === '127.0.0.1') &&
                              options.key.includes('test');
            
            // Check if Razorpay key is set
            if (!options.key || options.key.trim() === '') {
                console.error('Razorpay key is empty. Please set it in Admin > Settings > Payment tab');
                alert('Payment gateway not configured. Please contact the administrator.');
                return;
            }
            
            // Log payment details for debugging
            console.log('Razorpay initialization with key:', options.key);
            console.log('Test mode detection:', isTestMode ? 'Enabled' : 'Disabled');
            
            try {
                // Create instance of Razorpay
                const rzp = new Razorpay(options);
                
                // If test mode is enabled, add a test button
                if (isTestMode) {
                    console.log('Test mode available - Click test button to simulate payment');
                    
                    // Remove any existing test button first
                    const existingButton = document.getElementById('test-payment-button');
                    if (existingButton) {
                        existingButton.remove();
                    }
                    
                    // Add a test button for simulating payments in test mode
                    const testButton = document.createElement('button');
                    testButton.id = 'test-payment-button';
                    testButton.type = 'button';
                    testButton.className = 'btn btn-outline-secondary mt-2 w-100';
                    testButton.innerHTML = '<i class="fas fa-vial me-2"></i>Simulate Test Payment';
                    document.getElementById('razorpay-section').appendChild(testButton);
                    
                    testButton.addEventListener('click', function() {
                        // Generate dummy payment details with test_ prefix
                        const dummyPaymentId = 'test_' + Math.random().toString(36).substring(2, 15);
                        const dummyOrderId = 'order_' + Math.random().toString(36).substring(2, 15);
                        const dummySignature = 'test_signature_' + Math.random().toString(36).substring(2, 15);
                        
                        // Set the form fields
                        document.getElementById('razorpay_payment_id').value = dummyPaymentId;
                        document.getElementById('razorpay_order_id').value = dummyOrderId;
                        document.getElementById('razorpay_signature').value = dummySignature;
                        
                        // Submit the form
                        document.getElementById('checkout-form').submit();
                    });
                }
                
                // Set up the razorpay button click handler
                document.getElementById('razorpay-button').addEventListener('click', function() {
                    try {
                        rzp.open();
                    } catch (error) {
                        console.error('Razorpay error:', error);
                        alert('Payment gateway error. Please try Cash on Delivery instead.');
                        document.getElementById('cod').checked = true;
                    }
                });
            } catch (error) {
                console.error('Error creating Razorpay instance:', error);
                alert('Payment gateway initialization failed. Please try Cash on Delivery instead.');
                document.getElementById('cod').checked = true;
            }
        });
        
        // Handle payment method selection
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'razorpay') {
                    document.getElementById('razorpay-section').classList.remove('d-none');
                    document.getElementById('cod-section').classList.add('d-none');
                } else {
                    document.getElementById('razorpay-section').classList.add('d-none');
                    document.getElementById('cod-section').classList.remove('d-none');
                }
            });
        });
    </script>
</body>
</html>
