<?php
session_start();
// Include necessary files
include '../includes/db_connection.php';
include '../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - Hungry Heaven</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/custom.css" rel="stylesheet">
    <style>
        .terms-container {
            line-height: 1.8;
        }
        .terms-container h4 {
            color: var(--primary-color);
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="text-center mb-4">Terms and Conditions</h1>
                        <div class="terms-container">
                            <p><strong>Last updated:</strong> <?php echo date('F j, Y'); ?></p>

                            <h4>1. Introduction</h4>
                            <p>Welcome to Hungry Heaven. These terms and conditions outline the rules and regulations for the use of Hungry Heaven's Website and services.</p>
                            <p>By accessing this website and/or placing an order, we assume you accept these terms and conditions. Do not continue to use Hungry Heaven if you do not agree to all of the terms and conditions stated on this page.</p>

                            <h4>2. Use of Our Service</h4>
                            <p>Hungry Heaven provides an online platform for users to order food from our restaurant for delivery or pickup. You agree to use our service for lawful purposes only and in a way that does not infringe the rights of, restrict, or inhibit anyone else's use and enjoyment of the website.</p>

                            <h4>3. User Accounts</h4>
                            <p>To access certain features of the service, you may be required to create an account. You must provide accurate, complete, and current information at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account.</p>
                            <p>You are responsible for safeguarding the password that you use to access the Service and for any activities or actions under your password. You agree not to disclose your password to any third party.</p>

                            <h4>4. Orders, Payments, and Cancellations</h4>
                            <p>All orders are subject to availability and confirmation of the order price. We reserve the right to refuse any order you place with us.</p>
                            <p>Prices for our products are subject to change without notice. We accept various forms of payment as listed on our checkout page. By providing payment card details, you confirm that you are authorized to use the payment card.</p>
                            <p>Cancellation policies will be applicable as per the order status. Once an order is prepared, it cannot be canceled.</p>

                            <h4>5. Food Allergen Information</h4>
                            <p>While we take steps to minimize the risk of cross-contamination, we cannot guarantee that any of our products are safe to consume for people with specific allergies. Please contact us directly if you have any food allergies or dietary restrictions before placing an order.</p>

                            <h4>6. Intellectual Property</h4>
                            <p>The Service and its original content, features, and functionality are and will remain the exclusive property of Hungry Heaven and its licensors. The Service is protected by copyright, trademark, and other laws.</p>

                            <h4>7. Limitation of Liability</h4>
                            <p>In no event shall Hungry Heaven, nor its directors, employees, or partners, be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, or other intangible losses, resulting from your use of the service.</p>

                            <h4>8. Governing Law</h4>
                            <p>These Terms shall be governed and construed in accordance with the laws of India, without regard to its conflict of law provisions.</p>

                            <h4>9. Changes to Terms</h4>
                            <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. We will try to provide at least 30 days' notice prior to any new terms taking effect.</p>

                            <h4>10. Contact Us</h4>
                            <p>If you have any questions about these Terms, please <a href="contact.php">contact us</a>.</p>
                        </div>
                    </div>
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
