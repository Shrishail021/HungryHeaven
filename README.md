# HungryHeaven - Restaurant Food Ordering System

HungryHeaven is a comprehensive restaurant management system with integrated online ordering capabilities. It features both customer-facing interfaces and administrative tools to manage menu items, orders, reservations, and settings.

## Features

### Customer Features
- **Menu Browsing**: Browse through available menu items by category
- **Cart System**: Add items to cart with quantity selection
- **User Registration & Authentication**: Create accounts and login
- **Order History**: View past orders and their status
- **Multiple Payment Methods**: Cash on delivery and Razorpay integration
- **Table Reservations**: Book tables with date, time and guest count
- **Address Management**: Save and select delivery addresses
- **Geolocation**: Map-based address selection

### Admin Features
- **Dashboard**: Overview of sales, orders, and business metrics
- **Menu Management**: Add, edit, and delete menu items and categories
- **Order Management**: View and update order status
- **Reservation Management**: View and manage table reservations
- **Settings**: Configure store information, delivery charges, tax rates, payment gateways
- **User Management**: Add and manage administrative users

## Technical Stack
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Payment Gateway**: Razorpay
- **Maps**: Google Maps API for location selection
- **Icons**: Font Awesome

## Installation & Setup

### Prerequisites
- XAMPP, WAMP, MAMP or similar PHP development environment
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Installation Steps
1. Clone the repository to your web server directory (e.g., htdocs for XAMPP)
2. Import the database schema using the SQL file in the `database` folder
3. Configure database connection in `includes/db_connection.php`
4. Configure your Razorpay API keys in the admin settings
5. Set up your restaurant information in admin settings

### Configuration
- **Database**: Update database credentials in `includes/db_connection.php`
- **Payment Gateway**: Configure Razorpay API keys in Admin → Settings → Payment
- **Store Settings**: Configure store information, tax rates, and delivery charges in Admin → Settings

## Usage Instructions

### Admin Access
1. Navigate to `/admin/login.php`
2. Login with your admin credentials (default: admin@hungryheaven.com / password)
3. Use the dashboard to manage all aspects of the system

### Customer Access
1. Navigate to the homepage
2. Browse menu, add items to cart
3. Register/Login to place orders
4. Complete checkout with preferred payment method

## Development Notes

### Session Management
- Admin sessions use `admin_user_id` and related variables
- Customer sessions use `user_id` and related variables
- This separation prevents session conflicts

### Payment Integration
- Razorpay integration supports both test and production modes
- Test mode is automatically enabled on localhost with test API keys
- Payment verification is performed for security in production mode

## License
All rights reserved

## Author
SHRISHAIL MULAGUND

## Acknowledgments
- Bootstrap for the responsive UI components
- Font Awesome for the icons
- Razorpay for the payment gateway
- Google Maps for location services
