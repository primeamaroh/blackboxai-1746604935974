<?php
session_start();

define('BASE_URL', 'http://localhost:8000');
define('SITE_NAME', 'ECommerce Store');

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');

// Affiliate settings (default values)
define('AFFILIATE_COMMISSION', 7); // 7%
define('CUSTOMER_DISCOUNT', 10);   // 10%

// Database configuration
define('DB_TYPE', 'sqlite'); // Change to 'mysqli' for MySQL

// Security
define('HASH_SALT', 'your-random-salt-here');
?>
