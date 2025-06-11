<?php
// Start session
session_start();

// Include database connection
include '../includes/db_connection.php';
include '../includes/functions.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Hungry Heaven</title>
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
        <h1 class="text-center mb-5">Your Cart</h1>
        
        <?php if (count($cart_items) > 0): ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Cart Items (<?php echo count($cart_items); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover cart-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px;">Item</th>
                                            <th>Name</th>
                                            <th style="width: 100px;">Price</th>
                                            <th style="width: 120px;">Quantity</th>
                                            <th style="width: 100px;">Subtotal</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <img src="../assets/images/food/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="img-fluid rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                                </td>
                                                <td>
                                                    <h6 class="mb-0"><?php echo $item['name']; ?></h6>
                                                </td>
                                                <td>₹<?php echo $item['price']; ?></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <button class="btn btn-outline-secondary decrease-quantity" type="button" data-id="<?php echo $item['id']; ?>">-</button>
                                                        <input type="number" class="form-control text-center item-quantity" data-id="<?php echo $item['id']; ?>" value="<?php echo $item['quantity']; ?>" min="1" max="10">
                                                        <button class="btn btn-outline-secondary increase-quantity" type="button" data-id="<?php echo $item['id']; ?>">+</button>
                                                    </div>
                                                </td>
                                                <td id="subtotal-<?php echo $item['id']; ?>">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger remove-from-cart" data-id="<?php echo $item['id']; ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-between">
                                <a href="menu.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                                </a>
                                <button class="btn btn-outline-danger" id="clear-cart">
                                    <i class="fas fa-trash me-2"></i>Clear Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>₹<?php echo number_format($cart_total, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery Charge:</span>
                                <span><?php echo $is_free_delivery ? 'Free' : '₹' . number_format($delivery_charge, 2); ?></span>
                            </div>
                            <?php if (!$is_free_delivery): ?>
                                <div class="alert alert-info small">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Add ₹<?php echo number_format($min_order_free_delivery - $cart_total, 2); ?> more to get free delivery!
                                </div>
                            <?php endif; ?>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-bold">Total:</span>
                                <span class="fw-bold" id="cart-total">₹<?php echo number_format($is_free_delivery ? $cart_total : $cart_total + $delivery_charge, 2); ?></span>
                            </div>
                            
                            <?php if (is_logged_in()): ?>
                                <a href="checkout.php" class="btn btn-primary w-100">
                                    <i class="fas fa-shopping-cart me-2"></i>Proceed to Checkout
                                </a>
                            <?php else: ?>
                                <div class="alert alert-warning small mb-3">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Please login to proceed with checkout.
                                </div>
                                <a href="login.php" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Checkout
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-4x mb-3 text-muted"></i>
                <h3>Your cart is empty</h3>
                <p class="mb-4">Looks like you haven't added anything to your cart yet.</p>
                <a href="menu.php" class="btn btn-primary">
                    <i class="fas fa-utensils me-2"></i>Browse Menu
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Handle quantity decrease
        document.querySelectorAll('.decrease-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-id');
                const input = this.parentNode.querySelector('.item-quantity');
                let value = parseInt(input.value);
                
                if (value > 1) {
                    value--;
                    input.value = value;
                    updateCartQuantity(itemId, value);
                }
            });
        });
        
        // Handle quantity increase
        document.querySelectorAll('.increase-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-id');
                const input = this.parentNode.querySelector('.item-quantity');
                let value = parseInt(input.value);
                
                if (value < 10) {
                    value++;
                    input.value = value;
                    updateCartQuantity(itemId, value);
                }
            });
        });
        
        // Handle quantity manual input
        document.querySelectorAll('.item-quantity').forEach(input => {
            input.addEventListener('change', function() {
                const itemId = this.getAttribute('data-id');
                let value = parseInt(this.value);
                
                if (value < 1) {
                    value = 1;
                    this.value = value;
                } else if (value > 10) {
                    value = 10;
                    this.value = value;
                }
                
                updateCartQuantity(itemId, value);
            });
        });
        
        // Update cart quantity via AJAX
        function updateCartQuantity(itemId, quantity) {
            fetch('ajax/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'item_id=' + itemId + '&quantity=' + quantity
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update subtotal
                    document.getElementById('subtotal-' + itemId).textContent = '₹' + data.subtotal;
                    
                    // Update cart total
                    document.getElementById('cart-total').textContent = '₹' + data.total;
                    
                    // Update delivery info if needed
                    if (data.is_free_delivery) {
                        const deliveryInfo = document.querySelector('.alert-info');
                        if (deliveryInfo) {
                            deliveryInfo.remove();
                        }
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating cart');
            });
        }
        
        // Handle remove from cart
        document.querySelectorAll('.remove-from-cart').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-id');
                
                fetch('ajax/remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'item_id=' + itemId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove item row from table
                        this.closest('tr').remove();
                        
                        // Update cart total
                        document.getElementById('cart-total').textContent = '₹' + data.total;
                        
                        // Update cart count in navbar
                        const cartBadge = document.querySelector('.fa-shopping-cart').nextElementSibling;
                        if (cartBadge) {
                            if (data.cart_count > 0) {
                                cartBadge.textContent = data.cart_count;
                            } else {
                                cartBadge.remove();
                            }
                        }
                        
                        // Show empty cart message if cart is empty
                        if (data.cart_count === 0) {
                            const cartContainer = document.querySelector('.row');
                            cartContainer.innerHTML = `
                                <div class="text-center py-5">
                                    <i class="fas fa-shopping-cart fa-4x mb-3 text-muted"></i>
                                    <h3>Your cart is empty</h3>
                                    <p class="mb-4">Looks like you haven't added anything to your cart yet.</p>
                                    <a href="menu.php" class="btn btn-primary">
                                        <i class="fas fa-utensils me-2"></i>Browse Menu
                                    </a>
                                </div>
                            `;
                        }
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item from cart');
                });
            });
        });
        
        // Handle clear cart
        document.getElementById('clear-cart').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear your cart?')) {
                fetch('ajax/clear_cart.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove cart badge
                        const cartBadge = document.querySelector('.fa-shopping-cart').nextElementSibling;
                        if (cartBadge) {
                            cartBadge.remove();
                        }
                        
                        // Show empty cart message
                        const cartContainer = document.querySelector('.row');
                        cartContainer.innerHTML = `
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-4x mb-3 text-muted"></i>
                                <h3>Your cart is empty</h3>
                                <p class="mb-4">Looks like you haven't added anything to your cart yet.</p>
                                <a href="menu.php" class="btn btn-primary">
                                    <i class="fas fa-utensils me-2"></i>Browse Menu
                                </a>
                            </div>
                        `;
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error clearing cart');
                });
            }
        });
    </script>
</body>
</html>
