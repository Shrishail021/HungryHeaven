# HungryHeaven Installation Guide

This guide provides detailed instructions for setting up the HungryHeaven restaurant management system on a local or production environment.

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)
- PHP Extensions:
  - mysqli
  - gd (for image processing)
  - mbstring
  - curl (for payment gateway integration)
  - json
  - session

## Local Development Setup

### Using XAMPP (Windows/Mac/Linux)

1. **Install XAMPP**
   - Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Start Apache and MySQL services from the XAMPP control panel

2. **Clone/Download the Application**
   - Clone the repository or extract the zip archive to `c:\xampp\htdocs\HungryHeaven` (Windows)
   - Or `/Applications/XAMPP/htdocs/HungryHeaven` (Mac)
   - Or `/opt/lampp/htdocs/HungryHeaven` (Linux)

3. **Create the Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `hungryheaven`
   - Select the newly created database
   - Click on "Import" in the top menu
   - Select the SQL file from `database/hungryheaven.sql`
   - Click "Go" to import the database schema

4. **Configure Database Connection**
   - Open `includes/db_connection.php`
   - Update the database credentials if necessary:
   ```php
   $servername = "localhost";
   $username = "root";  // Your database username
   $password = "";      // Your database password
   $dbname = "hungryheaven";
   ```

5. **Configure File Permissions (for Linux/Mac)**
   - Set proper permissions for the uploads directory:
   ```
   chmod -R 755 uploads/
   ```

6. **Access the Application**
   - Customer Interface: http://localhost/HungryHeaven/
   - Admin Panel: http://localhost/HungryHeaven/admin/login.php
   - Default admin credentials:
     - Email: admin@hungryheaven.com
     - Password: password

## Production Deployment

### Using a Shared Hosting Service

1. **Register a Domain and Purchase Hosting**
   - Choose a hosting provider that supports PHP 7.4+ and MySQL 5.7+

2. **Upload Files**
   - Upload all application files to your hosting using FTP or the file manager
   - Typically, files should be placed in the `public_html` directory

3. **Create Database**
   - Create a new MySQL database using your hosting control panel
   - Import the SQL file from `database/hungryheaven.sql`
   - Note your database name, username, and password

4. **Configure Database Connection**
   - Update `includes/db_connection.php` with your production database credentials:
   ```php
   $servername = "your_database_host";
   $username = "your_database_username";
   $password = "your_database_password";
   $dbname = "your_database_name";
   ```

5. **Configure File Permissions**
   - Set proper permissions for directories and files:
     - Directories: 755 (drwxr-xr-x)
     - Files: 644 (rw-r--r--)
     - Set upload directories to be writable: 777 (temporarily)

6. **Set Up Razorpay for Production**
   - Create a Razorpay account at [https://razorpay.com/](https://razorpay.com/)
   - Generate live API keys from the Razorpay dashboard
   - Enter these keys in the admin panel (Admin → Settings → Payment)

7. **Secure the Application**
   - Change default admin credentials
   - Consider adding SSL (HTTPS) to your domain
   - Remove or protect the installation files
   - Consider implementing additional security measures:
     - IP restrictions for admin access
     - Regular backups
     - Security headers

## Customization

### Restaurant Details
1. Log in to the admin panel
2. Navigate to "Settings" → "General"
3. Update restaurant name, logo, address, and contact details

### Payment Settings
1. Navigate to "Settings" → "Payment"
2. Configure Razorpay API keys
3. Set payment methods availability

### Delivery Settings
1. Navigate to "Settings" → "Delivery"
2. Set delivery charges and minimum order amount for free delivery

## Troubleshooting

### Common Installation Issues

1. **Database Connection Error**
   - Verify database credentials in `includes/db_connection.php`
   - Check if MySQL service is running
   - Ensure the database user has proper permissions

2. **Upload Permission Errors**
   - Check folder permissions for upload directories
   - Temporarily set permissions to 777 for testing
   - After successful upload test, set to more restrictive 755

3. **Blank Page or PHP Errors**
   - Check PHP version compatibility
   - Enable error reporting for debugging:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
   - Check PHP error logs

4. **Payment Gateway Issues**
   - Verify API keys are entered correctly
   - Check if curl extension is enabled in PHP
   - Test with Razorpay test mode first

## Updating the Application

To update the application to a newer version:

1. Back up your entire application folder
2. Back up your database
3. Download the new version
4. Replace the files, keeping your customized configuration files
5. Run any database migration scripts if provided
6. Test the application thoroughly

## Support

For additional support, contact:
- Email: [your-email@example.com]
- Documentation: [your-documentation-url]
