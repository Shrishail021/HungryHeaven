<?php
// Start session
session_start();

// Include database connection
include '../includes/db_connection.php';
include '../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Hungry Heaven</title>
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
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="text-center mb-5">About Hungry Heaven</h1>
                
                <div class="card mb-5 border-0 shadow-sm">
                    <div class="row g-0">
                        <div class="col-md-6">
                            <img src="../assets/images/food/loginback3.jpg" class="img-fluid rounded-start h-100 object-fit-cover" alt="Restaurant Interior">
                        </div>
                        <div class="col-md-6">
                            <div class="card-body">
                                <h3 class="card-title">Our Story</h3>
                                <p class="card-text">
                                    Founded in 2010, Hungry Heaven began as a small family-owned restaurant with a passion for authentic cuisine and exceptional service. What started as a modest eatery has now grown into one of the most beloved dining destinations in the city.
                                </p>
                                <p class="card-text">
                                    Our journey has been guided by a simple philosophy: serve delicious, high-quality food made from the freshest ingredients, in a warm and welcoming atmosphere.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-5 border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Our Mission</h3>
                        <p class="card-text">
                            At Hungry Heaven, our mission is to create memorable dining experiences that delight all the senses. We believe that great food brings people together, and we're committed to being a place where friends and family can gather to share meals and create lasting memories.
                        </p>
                        <p class="card-text">
                            We strive to:
                        </p>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success me-2"></i> Source the freshest, highest-quality ingredients from local suppliers whenever possible</li>
                            <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success me-2"></i> Prepare every dish with care and attention to detail</li>
                            <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success me-2"></i> Provide warm, attentive service that makes every guest feel special</li>
                            <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success me-2"></i> Create a welcoming atmosphere for diners of all ages</li>
                            <li class="list-group-item bg-transparent"><i class="fas fa-check-circle text-success me-2"></i> Contribute positively to our community through sustainable practices</li>
                        </ul>
                    </div>
                </div>
                
                <div class="row mb-5">
                    <div class="col-md-4 mb-4 mb-md-0">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="display-4 text-primary mb-3">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <h4 class="card-title">Quality Food</h4>
                                <p class="card-text">We use only the freshest ingredients to prepare our dishes, ensuring every bite is delicious.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4 mb-md-0">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="display-4 text-primary mb-3">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h4 class="card-title">Expert Team</h4>
                                <p class="card-text">Our team of experienced chefs and friendly staff work together to create an exceptional dining experience.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="display-4 text-primary mb-3">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <h4 class="card-title">Fast Delivery</h4>
                                <p class="card-text">Enjoy our delicious meals in the comfort of your home with our prompt and reliable delivery service.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-5 border-0 shadow-sm">
                    <div class="row g-0">
                        <div class="col-md-6">
                            <div class="card-body">
                                <h3 class="card-title">Meet Our Chef</h3>
                                <p class="card-text">
                                    Chef Rahul Sharma brings over 15 years of culinary expertise to Hungry Heaven. Trained at the prestigious Culinary Institute of India, Chef Rahul has worked in renowned restaurants across the country before joining our team.
                                </p>
                                <p class="card-text">
                                    His passion for innovative cooking techniques and commitment to using locally-sourced ingredients has elevated our menu to new heights. Under his leadership, our kitchen team creates dishes that blend traditional flavors with modern presentation.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <img src="../assets/images/food/chef1.jpg" class="img-fluid rounded-end h-100 object-fit-cover" alt="Our Chef">
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <h3 class="mb-4">Visit Us Today</h3>
                    <p class="lead">
                        Experience the Hungry Heaven difference for yourself. Whether you're joining us for a quick lunch, a family dinner, or ordering for delivery, we look forward to serving you.
                    </p>
                    <a href="menu.php" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-utensils me-2"></i>Explore Our Menu
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
