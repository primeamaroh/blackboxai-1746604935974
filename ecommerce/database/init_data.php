<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

// Drop existing tables if they exist
$db->query("DROP TABLE IF EXISTS reviews");
$db->query("DROP TABLE IF EXISTS order_items");
$db->query("DROP TABLE IF EXISTS orders");
$db->query("DROP TABLE IF EXISTS products");
$db->query("DROP TABLE IF EXISTS users");
$db->query("DROP TABLE IF EXISTS affiliate_transactions");

// Create tables
echo "Creating tables...\n";

// Users table
$db->query("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'customer',
    email_verified INTEGER DEFAULT 0,
    verification_token VARCHAR(255),
    affiliate_code VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Products table
$db->query("CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INTEGER NOT NULL DEFAULT 0,
    category VARCHAR(50),
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Orders table
$db->query("CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    affiliate_code VARCHAR(50),
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

// Order items table
$db->query("CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    product_id INTEGER,
    quantity INTEGER NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)");

// Reviews table
$db->query("CREATE TABLE reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER,
    user_id INTEGER,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

// Affiliate transactions table
$db->query("CREATE TABLE affiliate_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    affiliate_user_id INTEGER,
    order_id INTEGER,
    commission_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (affiliate_user_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
)");

echo "Tables created successfully!\n";

// Insert sample admin user
echo "Creating sample users...\n";
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$db->query("INSERT INTO users (email, password, full_name, role, email_verified, affiliate_code) VALUES (
    'admin@example.com',
    '$admin_password',
    'Admin User',
    'admin',
    1,
    'ADMIN123'
)");

// Insert sample products
echo "Creating sample products...\n";
$products = [
    [
        'name' => 'Wireless Headphones',
        'description' => 'High-quality wireless headphones with noise cancellation',
        'price' => 199.99,
        'stock' => 50,
        'category' => 'Audio',
        'image_url' => 'https://images.pexels.com/photos/3587478/pexels-photo-3587478.jpeg'
    ],
    [
        'name' => 'Smart Watch',
        'description' => 'Feature-rich smartwatch with health monitoring',
        'price' => 299.99,
        'stock' => 30,
        'category' => 'Wearables',
        'image_url' => 'https://images.pexels.com/photos/437037/pexels-photo-437037.jpeg'
    ],
    [
        'name' => 'Laptop Backpack',
        'description' => 'Durable laptop backpack with multiple compartments',
        'price' => 79.99,
        'stock' => 100,
        'category' => 'Accessories',
        'image_url' => 'https://images.pexels.com/photos/2905238/pexels-photo-2905238.jpeg'
    ],
    [
        'name' => 'Mechanical Keyboard',
        'description' => 'RGB mechanical gaming keyboard with custom switches',
        'price' => 149.99,
        'stock' => 25,
        'category' => 'Peripherals',
        'image_url' => 'https://images.pexels.com/photos/1772123/pexels-photo-1772123.jpeg'
    ],
    [
        'name' => 'Wireless Mouse',
        'description' => 'Ergonomic wireless mouse with precision tracking',
        'price' => 49.99,
        'stock' => 75,
        'category' => 'Peripherals',
        'image_url' => 'https://images.pexels.com/photos/5082576/pexels-photo-5082576.jpeg'
    ],
    [
        'name' => 'USB-C Hub',
        'description' => 'Multi-port USB-C hub with power delivery',
        'price' => 69.99,
        'stock' => 40,
        'category' => 'Accessories',
        'image_url' => 'https://images.pexels.com/photos/4219862/pexels-photo-4219862.jpeg'
    ]
];

foreach ($products as $product) {
$db->query("INSERT INTO products (name, description, price, stock, category, image_url) VALUES (
        '" . $db->getConnection()->escapeString($product['name']) . "',
        '" . $db->getConnection()->escapeString($product['description']) . "',
        " . $product['price'] . ",
        " . $product['stock'] . ",
        '" . $db->getConnection()->escapeString($product['category']) . "',
        '" . $db->getConnection()->escapeString($product['image_url']) . "'
    )");
}

// Insert sample customer
echo "Creating sample customer...\n";
$customer_password = password_hash('customer123', PASSWORD_DEFAULT);
$db->query("INSERT INTO users (email, password, full_name, role, email_verified, affiliate_code) VALUES (
    'customer@example.com',
    '$customer_password',
    'John Doe',
    'customer',
    1,
    'CUST123'
)");

// Insert sample reviews
echo "Creating sample reviews...\n";
$reviews = [
    [
        'product_id' => 1,
        'user_id' => 2,
        'rating' => 5,
        'comment' => 'Excellent sound quality and comfortable to wear!'
    ],
    [
        'product_id' => 1,
        'user_id' => 2,
        'rating' => 4,
        'comment' => 'Great battery life, but could be more comfortable.'
    ],
    [
        'product_id' => 2,
        'user_id' => 2,
        'rating' => 5,
        'comment' => 'Amazing features and battery life!'
    ]
];

foreach ($reviews as $review) {
    $db->query("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (
        " . $review['product_id'] . ",
        " . $review['user_id'] . ",
        " . $review['rating'] . ",
        '" . $db->getConnection()->escapeString($review['comment']) . "'
    )");
}

echo "\nDatabase initialized successfully with sample data!\n";
echo "\nAdmin login:\n";
echo "Email: admin@example.com\n";
echo "Password: admin123\n";
echo "\nCustomer login:\n";
echo "Email: customer@example.com\n";
echo "Password: customer123\n";
?>
