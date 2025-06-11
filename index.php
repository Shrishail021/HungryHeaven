<?php
// Start session
session_start();

// Include database connection
include 'includes/db_connection.php';
include 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hungry Heaven - Food Ordering Website</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section with Parallax -->
    <section class="hero-section">
        <div class="parallax-bg">
            <div class="container">
                <div class="row min-vh-100 align-items-center">
                    <div class="col-md-6">
                        <div class="hero-content text-white p-4 glass-card">
                            <h1 class="display-4 fw-bold">Hungry Heaven</h1>
                            <p class="lead">Delicious food delivered to your doorstep</p>
                            <a href="customer/menu.php" class="btn btn-lg btn-primary">Order Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Categories -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Explore Our Menu</h2>
            <div class="row">
                <?php
                // Fetch categories from database
                $sql = "SELECT * FROM categories LIMIT 6";
                $result = mysqli_query($conn, $sql);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card category-card h-100">
                                <img src="assets/images/categories/<?php echo $row['image']; ?>" class="card-img-top" alt="<?php echo $row['name']; ?>">
                                <div class="card-body text-center">
                                    <h5 class="card-title"><?php echo $row['name']; ?></h5>
                                    <a href="customer/menu.php?category=<?php echo $row['id']; ?>" class="btn btn-outline-primary">View Items</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="col-12 text-center"><p>No categories found</p></div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Popular Items -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">Popular Items</h2>
            <div class="row">
                <?php
                // Fetch popular items from database
                $sql = "SELECT * FROM menu_items WHERE is_popular = 1 LIMIT 8";
                $result = mysqli_query($conn, $sql);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <div class="col-md-3 mb-4">
                            <div class="card food-card h-100">
                                <img src="assets/images/food/<?php echo $row['image']; ?>" class="card-img-top" alt="<?php echo $row['name']; ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $row['name']; ?></h5>
                                    <p class="card-text"><?php echo substr($row['description'], 0, 80); ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price">₹<?php echo $row['price']; ?></span>
                                        <button class="btn btn-sm btn-primary add-to-cart" data-id="<?php echo $row['id']; ?>">Add to Cart</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="col-12 text-center"><p>No popular items found</p></div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">What Our Customers Say</h2>
            <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="testimonial-item text-center p-4">
                            <img src="assets/images/testimonials/user1.jpg" class="rounded-circle mb-3" alt="Customer">
                            <p class="mb-3">"The food was amazing and delivery was super quick. Definitely ordering again!"</p>
                            <h5>John Doe</h5>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="testimonial-item text-center p-4">
                            <img src="assets/images/testimonials/user2.jpg" class="rounded-circle mb-3" alt="Customer">
                            <p class="mb-3">"Best biryani I've ever had. The taste was authentic and portions were generous."</p>
                            <h5>Jane Smith</h5>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Stats Counter -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 mb-3">
                    <div class="counter">
                        <i class="fas fa-utensils fa-3x mb-3"></i>
                        <h2 class="counter-number" data-count="5000">0</h2>
                        <p>Orders Served</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="counter">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h2 class="counter-number" data-count="1000">0</h2>
                        <p>Happy Customers</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="counter">
                        <i class="fas fa-hamburger fa-3x mb-3"></i>
                        <h2 class="counter-number" data-count="50">0</h2>
                        <p>Menu Items</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="counter">
                        <i class="fas fa-motorcycle fa-3x mb-3"></i>
                        <h2 class="counter-number" data-count="20">0</h2>
                        <p>Delivery Partners</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
