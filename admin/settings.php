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

// Get current settings
$sql = "SELECT * FROM settings";
$result = mysqli_query($conn, $sql);
$settings = array();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Initialize messages
$success = '';
$error = '';

// Process settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // General Settings
    $site_name = clean_input($_POST['site_name']);
    $site_tagline = clean_input($_POST['site_tagline']);
    $contact_email = clean_input($_POST['contact_email']);
    $contact_phone = clean_input($_POST['contact_phone']);
    $address = clean_input($_POST['address']);
    
    // Order Settings
    $min_order_amount = floatval($_POST['min_order_amount']);
    $delivery_fee = floatval($_POST['delivery_fee']);
    $tax_percentage = floatval($_POST['tax_percentage']);
    
    // Social Media Settings
    $facebook_url = clean_input($_POST['facebook_url']);
    $instagram_url = clean_input($_POST['instagram_url']);
    $twitter_url = clean_input($_POST['twitter_url']);
    
    // Payment Settings
    $razorpay_key_id = clean_input($_POST['razorpay_key_id']);
    $razorpay_key_secret = clean_input($_POST['razorpay_key_secret']);
    
    // Update settings
    $update_settings = array(
        'site_name' => $site_name,
        'site_tagline' => $site_tagline,
        'contact_email' => $contact_email,
        'contact_phone' => $contact_phone,
        'address' => $address,
        'min_order_amount' => $min_order_amount,
        'delivery_fee' => $delivery_fee,
        'tax_percentage' => $tax_percentage,
        'facebook_url' => $facebook_url,
        'instagram_url' => $instagram_url,
        'twitter_url' => $twitter_url,
        'razorpay_key_id' => $razorpay_key_id,
        'razorpay_key_secret' => $razorpay_key_secret
    );
    
    $update_success = true;
    
    foreach ($update_settings as $key => $value) {
        // Check if setting exists
        $check_sql = "SELECT * FROM settings WHERE setting_key = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "s", $key);
        mysqli_stmt_execute($stmt);
        $check_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing setting
            $update_sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "ss", $value, $key);
        } else {
            // Insert new setting
            $update_sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "ss", $key, $value);
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            $update_success = false;
            $error = 'Error updating settings: ' . mysqli_error($conn);
            break;
        }
        
        // Update local array
        $settings[$key] = $value;
    }
    
    // Handle logo upload
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['size'] > 0) {
        $target_dir = "../assets/images/";
        $file_extension = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
        $new_filename = 'logo.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES['site_logo']['tmp_name']);
        if ($check !== false) {
            // Check file size (max 2MB)
            if ($_FILES['site_logo']['size'] < 2000000) {
                // Allow certain file formats
                if (in_array($file_extension, array('jpg', 'jpeg', 'png', 'gif'))) {
                    if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $target_file)) {
                        // Update logo setting
                        $logo_path = 'assets/images/' . $new_filename;
                        
                        $update_sql = "UPDATE settings SET setting_value = ? WHERE setting_key = 'site_logo'";
                        $stmt = mysqli_prepare($conn, $update_sql);
                        mysqli_stmt_bind_param($stmt, "s", $logo_path);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $settings['site_logo'] = $logo_path;
                        } else {
                            $error = 'Error updating logo setting: ' . mysqli_error($conn);
                            $update_success = false;
                        }
                    } else {
                        $error = 'Error uploading logo file.';
                        $update_success = false;
                    }
                } else {
                    $error = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.';
                    $update_success = false;
                }
            } else {
                $error = 'Sorry, your file is too large. Maximum size is 2MB.';
                $update_success = false;
            }
        } else {
            $error = 'File is not an image.';
            $update_success = false;
        }
    }
    
    if ($update_success) {
        $success = 'Settings updated successfully.';
    }
}

// Include header
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Site Settings</h1>
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
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
            <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">General</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="order-tab" data-bs-toggle="tab" data-bs-target="#order" type="button" role="tab" aria-controls="order" aria-selected="false">Order</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab" aria-controls="social" aria-selected="false">Social Media</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab" aria-controls="payment" aria-selected="false">Payment</button>
                </li>
            </ul>
            
            <div class="tab-content p-4" id="settingsTabsContent">
                <!-- General Settings -->
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_name" class="form-label">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo isset($settings['site_name']) ? htmlspecialchars($settings['site_name']) : 'Hungry Heaven'; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="site_tagline" class="form-label">Site Tagline</label>
                                <input type="text" class="form-control" id="site_tagline" name="site_tagline" value="<?php echo isset($settings['site_tagline']) ? htmlspecialchars($settings['site_tagline']) : 'Delicious Food Delivered'; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact_email" class="form-label">Contact Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo isset($settings['contact_email']) ? htmlspecialchars($settings['contact_email']) : 'contact@hungryheaven.com'; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact_phone" class="form-label">Contact Phone</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo isset($settings['contact_phone']) ? htmlspecialchars($settings['contact_phone']) : '+1 123-456-7890'; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($settings['address']) ? htmlspecialchars($settings['address']) : '123 Food Street, Cuisine City, CC 12345'; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="site_logo" class="form-label">Site Logo</label>
                                <?php if (isset($settings['site_logo'])): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo '../' . $settings['site_logo']; ?>" alt="Current Logo" class="img-thumbnail" style="max-height: 100px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="site_logo" name="site_logo">
                                <div class="form-text">Recommended size: 200x60 pixels. Max file size: 2MB.</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Settings -->
                <div class="tab-pane fade" id="order" role="tabpanel" aria-labelledby="order-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="min_order_amount" class="form-label">Minimum Order Amount (₹)</label>
                                <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" step="0.01" value="<?php echo isset($settings['min_order_amount']) ? htmlspecialchars($settings['min_order_amount']) : '100.00'; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="delivery_fee" class="form-label">Delivery Fee (₹)</label>
                                <input type="number" class="form-control" id="delivery_fee" name="delivery_fee" step="0.01" value="<?php echo isset($settings['delivery_fee']) ? htmlspecialchars($settings['delivery_fee']) : '30.00'; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="tax_percentage" class="form-label">Tax Percentage (%)</label>
                                <input type="number" class="form-control" id="tax_percentage" name="tax_percentage" step="0.01" value="<?php echo isset($settings['tax_percentage']) ? htmlspecialchars($settings['tax_percentage']) : '5.00'; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media Settings -->
                <div class="tab-pane fade" id="social" role="tabpanel" aria-labelledby="social-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="facebook_url" class="form-label">Facebook URL</label>
                                <input type="url" class="form-control" id="facebook_url" name="facebook_url" value="<?php echo isset($settings['facebook_url']) ? htmlspecialchars($settings['facebook_url']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="instagram_url" class="form-label">Instagram URL</label>
                                <input type="url" class="form-control" id="instagram_url" name="instagram_url" value="<?php echo isset($settings['instagram_url']) ? htmlspecialchars($settings['instagram_url']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="twitter_url" class="form-label">Twitter URL</label>
                                <input type="url" class="form-control" id="twitter_url" name="twitter_url" value="<?php echo isset($settings['twitter_url']) ? htmlspecialchars($settings['twitter_url']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Settings -->
                <div class="tab-pane fade" id="payment" role="tabpanel" aria-labelledby="payment-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="mb-3">Razorpay Settings</h4>
                            <div class="mb-3">
                                <label for="razorpay_key_id" class="form-label">Razorpay Key ID</label>
                                <input type="text" class="form-control" id="razorpay_key_id" name="razorpay_key_id" value="<?php echo isset($settings['razorpay_key_id']) ? htmlspecialchars($settings['razorpay_key_id']) : ''; ?>">
                                <small class="text-muted">Enter your Razorpay Key ID (test or live)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="razorpay_key_secret" class="form-label">Razorpay Key Secret</label>
                                <input type="password" class="form-control" id="razorpay_key_secret" name="razorpay_key_secret" value="<?php echo isset($settings['razorpay_key_secret']) ? htmlspecialchars($settings['razorpay_key_secret']) : ''; ?>">
                                <small class="text-muted">Enter your Razorpay Key Secret (test or live)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    // Activate tabs
    document.addEventListener('DOMContentLoaded', function() {
        const triggerTabList = [].slice.call(document.querySelectorAll('#settingsTabs button'));
        triggerTabList.forEach(function(triggerEl) {
            const tabTrigger = new bootstrap.Tab(triggerEl);
            
            triggerEl.addEventListener('click', function(event) {
                event.preventDefault();
                tabTrigger.show();
            });
        });
    });
</script>
