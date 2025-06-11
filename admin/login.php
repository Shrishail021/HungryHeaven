<?php
// Start session
session_start();

// Redirect if admin is already logged in
if (isset($_SESSION['admin_user_id']) && $_SESSION['admin_user_role'] == 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Include database connection
include '../includes/db_connection.php';
include '../includes/functions.php';

$error = '';
$success = '';
$register_mode = isset($_GET['register']) && $_GET['register'] == 'true';

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$register_mode) {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    
    // Validate form input
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check if admin exists
        $sql = "SELECT * FROM users WHERE email = ? AND role = 'admin'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set admin session variables
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['name'];
                $_SESSION['admin_user_email'] = $user['email'];
                $_SESSION['admin_user_role'] = $user['role'];
                
                // Redirect to admin dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Admin not found';
        }
    }
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $register_mode) {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_key = clean_input($_POST['admin_key']);
    
    // Validate form input
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password) || empty($admin_key)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif ($password != $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($admin_key != 'HungryHeaven2025') { // Simple security key - in a real app, use a more secure method
        $error = 'Invalid admin security key';
    } else {
        // Check if email already exists
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = 'Email already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new admin
            $sql = "INSERT INTO users (name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, 'admin', NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $phone, $hashed_password);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'New admin account created successfully. You can now login.';
                $register_mode = false; // Switch back to login mode
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $register_mode ? 'Register Admin' : 'Admin Login'; ?> - Hungry Heaven</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #212529;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-login-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 450px;
            max-width: 100%;
        }
        .admin-header {
            background-color: #dc3545;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .admin-header h2 {
            margin: 0;
            font-weight: 600;
        }
        .admin-body {
            padding: 30px;
        }
        .admin-input {
            margin-bottom: 20px;
        }
        .admin-input label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .admin-input input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .admin-input input:focus {
            border-color: #dc3545;
            outline: none;
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25);
        }
        .admin-btn {
            background-color: #dc3545;
            border: none;
            color: white;
            padding: 12px 0;
            width: 100%;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .admin-btn:hover {
            background-color: #c82333;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover {
            color: #dc3545;
        }
        .admin-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .admin-logo i {
            font-size: 40px;
            color: #dc3545;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .remember-me input {
            margin-right: 10px;
        }
        .toggle-form {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .toggle-form a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
        }
        .toggle-form a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-header">
            <h2><i class="fas fa-user-shield me-2"></i> <?php echo $register_mode ? 'Register New Admin' : 'Admin Portal'; ?></h2>
        </div>
        <div class="admin-body">
            <div class="admin-logo">
                <i class="fas fa-utensils"></i>
                <h4>Hungry Heaven</h4>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$register_mode): ?>
                <!-- Login Form -->
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <div class="admin-input">
                        <label for="email">Admin Email</label>
                        <input type="email" id="email" name="email" required placeholder="Enter your admin email">
                    </div>
                    <div class="admin-input">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                    </div>
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="admin-btn">Login to Admin Panel</button>
                </form>
                
                <div class="toggle-form">
                    <p>Need to create a new admin account? <a href="?register=true">Register here</a></p>
                </div>
            <?php else: ?>
                <!-- Registration Form -->
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?register=true" method="post">
                    <div class="admin-input">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required placeholder="Enter admin name" value="<?php echo isset($name) ? $name : ''; ?>">
                    </div>
                    <div class="admin-input">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="Enter admin email" value="<?php echo isset($email) ? $email : ''; ?>">
                    </div>
                    <div class="admin-input">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" required placeholder="Enter phone number" value="<?php echo isset($phone) ? $phone : ''; ?>">
                    </div>
                    <div class="admin-input">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Enter password">
                        <small class="text-muted">Password must be at least 6 characters long</small>
                    </div>
                    <div class="admin-input">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm password">
                    </div>
                    <div class="admin-input">
                        <label for="admin_key">Admin Security Key</label>
                        <input type="password" id="admin_key" name="admin_key" required placeholder="Enter admin security key">
                        <small class="text-muted">Required for security verification</small>
                    </div>
                    <button type="submit" class="admin-btn">Register New Admin</button>
                </form>
                
                <div class="toggle-form">
                    <p>Already have an admin account? <a href="login.php">Login here</a></p>
                </div>
            <?php endif; ?>
            
            <a href="../index.php" class="back-link"><i class="fas fa-arrow-left me-1"></i> Back to Website</a>
        </div>
    </div>
</body>
</html>
