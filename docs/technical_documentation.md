# HungryHeaven Technical Documentation

## Database Schema

### Tables Structure

#### users
- **id** (int, PK, AUTO_INCREMENT): Unique user identifier
- **name** (varchar): User's full name
- **email** (varchar, UNIQUE): User's email address
- **phone** (varchar): User's phone number
- **password** (varchar): Hashed password
- **role** (varchar): User role (customer, admin)
- **created_at** (timestamp): Account creation timestamp
- **updated_at** (timestamp): Last update timestamp

#### categories
- **id** (int, PK, AUTO_INCREMENT): Unique category identifier
- **name** (varchar): Category name
- **description** (text): Category description
- **image** (varchar): Path to category image
- **status** (tinyint): Category status (active/inactive)
- **created_at** (timestamp): Creation timestamp
- **updated_at** (timestamp): Last update timestamp

#### menu_items
- **id** (int, PK, AUTO_INCREMENT): Unique menu item identifier
- **category_id** (int, FK): Foreign key to categories table
- **name** (varchar): Item name
- **description** (text): Item description
- **price** (decimal): Item price
- **image** (varchar): Path to item image
- **status** (tinyint): Item status (available/unavailable)
- **is_featured** (tinyint): Whether item is featured on homepage
- **created_at** (timestamp): Creation timestamp
- **updated_at** (timestamp): Last update timestamp

#### orders
- **id** (int, PK, AUTO_INCREMENT): Unique order identifier
- **user_id** (int, FK): Foreign key to users table
- **order_number** (varchar, UNIQUE): Human-readable order reference
- **total_amount** (decimal): Total order amount
- **tax_amount** (decimal): Tax amount
- **delivery_charge** (decimal): Delivery charge amount
- **payment_method** (varchar): Payment method (cod, razorpay)
- **payment_id** (varchar): External payment reference ID
- **status** (varchar): Order status (new, processing, completed, cancelled)
- **address** (text): Delivery address
- **phone** (varchar): Contact phone for delivery
- **notes** (text): Special instructions or notes
- **order_date** (timestamp): Order creation timestamp
- **delivery_date** (timestamp): Delivery completion timestamp

#### order_items
- **id** (int, PK, AUTO_INCREMENT): Unique order item identifier
- **order_id** (int, FK): Foreign key to orders table
- **menu_item_id** (int, FK): Foreign key to menu_items table
- **quantity** (int): Item quantity
- **price** (decimal): Item price at time of order
- **subtotal** (decimal): Line item subtotal (price × quantity)

#### reservations
- **id** (int, PK, AUTO_INCREMENT): Unique reservation identifier
- **user_id** (int, FK): Foreign key to users table
- **name** (varchar): Customer name
- **email** (varchar): Customer email
- **phone** (varchar): Customer phone
- **date** (date): Reservation date
- **time** (time): Reservation time
- **guests** (int): Number of guests
- **status** (varchar): Reservation status (pending, confirmed, cancelled)
- **special_request** (text): Special requests or notes
- **created_at** (timestamp): Creation timestamp
- **updated_at** (timestamp): Last update timestamp

#### addresses
- **id** (int, PK, AUTO_INCREMENT): Unique address identifier
- **user_id** (int, FK): Foreign key to users table
- **address_line1** (varchar): Address line 1
- **address_line2** (varchar): Address line 2 (optional)
- **city** (varchar): City
- **state** (varchar): State/Province
- **postal_code** (varchar): ZIP/Postal code
- **is_default** (tinyint): Whether this is the default address
- **created_at** (timestamp): Creation timestamp
- **updated_at** (timestamp): Last update timestamp

#### settings
- **id** (int, PK, AUTO_INCREMENT): Unique setting identifier
- **setting_key** (varchar, UNIQUE): Setting key name
- **setting_value** (text): Setting value
- **setting_type** (varchar): Type of setting (text, number, boolean, etc.)
- **created_at** (timestamp): Creation timestamp
- **updated_at** (timestamp): Last update timestamp

## API Integrations

### Razorpay Payment Gateway

#### Configuration
Razorpay integration requires API keys that can be set in the admin settings:
- `razorpay_key_id`: Public API key for client-side integration
- `razorpay_key_secret`: Secret API key for server-side verification

#### Implementation Details

**Client-side Integration (checkout.php)**
```javascript
// Create a new Razorpay instance with options
const rzp = new Razorpay({
    key: key_id,
    amount: amount * 100, // Amount in smallest currency unit (paise)
    currency: "INR",
    name: restaurant_name,
    description: "Order payment",
    prefill: {
        name: customer_name,
        email: customer_email,
        contact: customer_phone
    },
    theme: {
        color: "#3399cc"
    },
    handler: function(response) {
        // Set payment IDs in form and submit
        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
        document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
        document.getElementById('razorpay_signature').value = response.razorpay_signature;
        document.getElementById('checkout-form').submit();
    }
});
```

**Server-side Verification (checkout.php)**
```php
// Verify Razorpay payment signature
if ($payment_method == 'razorpay' && !empty($razorpay_payment_id)) {
    $key_id = get_setting('razorpay_key_id');
    $key_secret = get_setting('razorpay_key_secret');
    
    // Only verify in production (non-test) mode
    if (!is_test_mode($key_id)) {
        $generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, $key_secret);
        
        if ($generated_signature != $razorpay_signature) {
            // Payment verification failed
            echo "Payment verification failed!";
            exit();
        }
    }
}
```

**Test Mode Detection**
```php
function is_test_mode($key_id) {
    $is_localhost = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1');
    $is_test_key = (strpos($key_id, 'test') !== false);
    return $is_localhost && $is_test_key;
}
```

### Session Management System

The application uses two separate session systems to prevent conflicts between admin and customer sessions:

**Admin Sessions**
```php
// Admin login session variables
$_SESSION['admin_user_id'] = $user_id;
$_SESSION['admin_username'] = $user['name'];
$_SESSION['admin_user_email'] = $user['email'];
$_SESSION['admin_user_role'] = $user['role'];

// Admin session check
if (!isset($_SESSION['admin_user_id']) || $_SESSION['admin_user_role'] != 'admin') {
    header('Location: login.php');
    exit();
}
```

**Customer Sessions**
```php
// Customer login session variables
$_SESSION['user_id'] = $user_id;
$_SESSION['username'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];

// Customer session check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
```

## Security Implementations

### Input Sanitization
All user inputs are sanitized using the `clean_input()` function:

```php
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}
```

### Prepared Statements
All database queries use prepared statements to prevent SQL injection:

```php
$stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed_password);
mysqli_stmt_execute($stmt);
```

### Password Hashing
User passwords are hashed using PHP's built-in password_hash function:

```php
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
```

## File Structure

```
HungryHeaven/
├── admin/                  # Admin panel files
│   ├── categories.php      # Category management
│   ├── dashboard.php       # Admin dashboard
│   ├── login.php           # Admin login
│   ├── menu_items.php      # Menu item management
│   ├── orders.php          # Order management
│   ├── profile.php         # Admin profile
│   ├── register_admin.php  # Admin registration
│   ├── reservations.php    # Reservation management
│   ├── settings.php        # System settings
│   └── users.php           # User management
│
├── assets/                 # Static assets
│   ├── css/                # CSS files
│   ├── images/             # Image files
│   └── js/                 # JavaScript files
│
├── customer/               # Customer-facing pages
│   ├── cart.php            # Shopping cart
│   ├── checkout.php        # Order checkout
│   ├── login.php           # Customer login
│   ├── menu.php            # Restaurant menu
│   ├── orders.php          # Order history
│   ├── profile.php         # Customer profile
│   ├── register.php        # Customer registration
│   └── reservation.php     # Table reservations
│
├── includes/               # Shared PHP files
│   ├── db_connection.php   # Database connection
│   ├── footer.php          # Footer template
│   ├── functions.php       # Common functions
│   ├── header.php          # Header template
│   └── navbar.php          # Navigation template
│
├── uploads/                # Uploaded files
│   ├── categories/         # Category images
│   └── menu/               # Menu item images
│
├── docs/                   # Documentation
├── index.php               # Homepage
└── README.md               # Project overview
```

## Environment Configuration

The system is designed to detect local development environments for features like payment testing:

```php
// Check if running on localhost
$is_localhost = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1');

// Configure behavior based on environment
if ($is_localhost) {
    // Local development settings
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Production settings
    error_reporting(0);
    ini_set('display_errors', 0);
}
```
