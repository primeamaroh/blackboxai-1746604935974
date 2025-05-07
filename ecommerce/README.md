# E-commerce Website

A PHP-based e-commerce website with features including:
- User authentication with email verification
- Product management
- Shopping cart
- Checkout system
- Admin dashboard
- Affiliate system
- Reviews system

## Setup Instructions

1. Make sure you have PHP installed on your system (version 7.4 or higher recommended)

2. Clone this repository to your local machine

3. Navigate to the project directory:
```bash
cd ecommerce
```

4. Initialize the database and sample data:
```bash
php database/init_data.php
```

5. Start the PHP development server:
```bash
php -S localhost:8000 -t public
```

6. Visit http://localhost:8000 in your web browser

## Default Login Credentials

### Admin User
- Email: admin@example.com
- Password: admin123

### Customer User
- Email: customer@example.com
- Password: customer123

## Features

### Customer Features
- Browse products
- Add items to cart
- Manage shopping cart
- Checkout process
- View order history
- Write product reviews
- Affiliate program participation
- Profile management

### Admin Features
- Dashboard with sales overview
- Product management
- Order management
- Customer management
- Review moderation
- Affiliate settings
- Stock management

### Affiliate System
- Users can share their affiliate code
- Customers get 10% discount using affiliate codes
- Affiliates earn 7% commission on sales
- Configurable commission and discount rates
- Track affiliate performance

## Database

The project supports both SQLite (for development) and MySQL (for production) databases. 
The default configuration uses SQLite for easy setup and testing.

To switch to MySQL:
1. Update the database configuration in `config/config.php`
2. Use the MySQL schema provided in `database/schema.sql`

## Security Features

- Password hashing
- Email verification
- Session management
- Input sanitization
- CSRF protection
- Secure password reset

## Directory Structure

```
ecommerce/
├── config/             # Configuration files
├── database/          # Database files and migrations
├── public/            # Public files
├── src/               # Source files
│   ├── includes/      # Reusable components
│   └── pages/         # Page controllers
└── README.md         # This file
```

## Contributing

Feel free to submit issues and enhancement requests!
