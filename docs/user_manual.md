# HungryHeaven User Manual

## Customer Guide

### Getting Started
1. **Homepage**: Visit the HungryHeaven website to view featured items and promotions.
2. **Registration**: Click on "Sign Up" to create a new customer account.
3. **Login**: If you already have an account, click on "Login" and enter your credentials.

### Browsing the Menu
1. Navigate to the "Menu" section from the main navigation bar.
2. Browse by category using the category tabs.
3. Click on any item to view details, including description, price, and options.

### Placing an Order
1. **Add to Cart**: Click the "Add to Cart" button for desired items.
2. **Adjust Quantity**: In the cart, you can increase or decrease item quantities.
3. **Checkout**: Click "Checkout" to proceed with your order.
4. **Select Delivery Type**: Choose between "Table Order" or "Home Delivery".
   - For Table Orders: Enter your table number.
   - For Delivery: Enter your delivery address or select a saved address.
5. **Payment Method**: Select "Cash on Delivery" or "Razorpay" (online payment).
6. **Complete Order**: Click "Place Order" to confirm.

### Managing Your Account
1. **Profile**: Update your personal information and change password.
2. **My Orders**: View your order history and current order status.
3. **Saved Addresses**: Manage your delivery addresses.

### Making Reservations
1. Navigate to the "Reservation" page from the main menu.
2. Select date, time, and number of guests.
3. Provide contact information and any special requests.
4. Submit your reservation request.

## Administrator Guide

### Accessing the Admin Panel
1. Navigate to `/admin/login.php`
2. Enter your administrative credentials.

### Dashboard
The dashboard provides an overview of:
- Recent orders and their status
- Sales statistics and charts
- Reservation requests
- Quick access to main administrative functions

### Managing Menu
1. **Categories**: Add, edit, or delete food categories from "Admin → Categories".
2. **Menu Items**: Manage individual menu items from "Admin → Menu Items".
   - Add new menu items with images, descriptions, and prices
   - Assign items to categories
   - Set item availability
   - Manage special offers or discounts

### Order Management
1. Navigate to "Admin → Orders".
2. View all orders with their details.
3. Update order status (new, processing, ready, delivered, cancelled).
4. Search for orders by reference number or customer name.

### Reservation Management
1. Navigate to "Admin → Reservations".
2. View pending, approved, and denied reservations.
3. Accept or reject reservation requests.
4. Contact customers regarding their reservations.

### System Settings
1. Navigate to "Admin → Settings".
2. **General**: Configure restaurant name, logo, contact information, etc.
3. **Delivery**: Set delivery charges and minimum order amount for free delivery.
4. **Payment**: Configure Razorpay API keys for payment integration.
5. **Tax**: Set tax percentage for orders.
6. **Social Media**: Add your restaurant's social media links.

### User Management
1. Navigate to "Admin → Users".
2. Add new administrative users or update existing ones.
3. Assign appropriate roles and permissions.

### Reports
1. Navigate to "Admin → Reports".
2. Generate sales reports for specific date ranges.
3. Export reports in PDF or Excel format for accounting purposes.

## Troubleshooting

### Common Issues for Customers
- **Payment Failed**: Check your internet connection or try an alternative payment method.
- **Unable to Place Order**: Ensure you're logged in and all required fields are completed.
- **Address Not Found**: Try entering your address manually with detailed instructions.

### Common Issues for Administrators
- **Image Upload Failed**: Check file size and format (JPG, PNG recommended, max 2MB).
- **Payment Gateway Error**: Verify Razorpay API keys in Settings.
- **Database Connection Error**: Check database credentials in configuration file.
- **Session Timeout**: Re-login to continue administrative tasks.

For technical support, please contact the system administrator.
