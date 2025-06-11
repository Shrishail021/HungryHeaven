<?php
// Start session
session_start();

// Unset only admin session variables
unset($_SESSION['admin_user_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_user_email']);
unset($_SESSION['admin_user_role']);

// Redirect to admin login page
header('Location: ../admin/login.php');
exit();
?>
