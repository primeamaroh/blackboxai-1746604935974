<?php
// Start session for all requests
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Initialize database connection
$db = Database::getInstance();

// Get the request path
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base directory from path if needed
$request_path = str_replace('/ecommerce/public', '', $request_path);

// Router
switch ($request_path) {
    case '/':
        require '../src/pages/home.php';
        break;
    
    // Auth routes
    case '/login':
        require '../src/pages/auth/login.php';
        break;
    
    case '/register':
        require '../src/pages/auth/register.php';
        break;
    
    case '/verify-email':
        require '../src/pages/auth/verify-email.php';
        break;
    
    case '/logout':
        require '../src/pages/auth/logout.php';
        break;
    
    // Admin routes
    case '/admin/dashboard':
        require '../src/pages/admin/dashboard.php';
        break;
    
    // User routes
    case '/profile':
        require '../src/pages/user/profile.php';
        break;
    
    case '/affiliates':
        require '../src/pages/user/affiliates.php';
        break;
    
    // Shop routes
    case '/products':
        require '../src/pages/products.php';
        break;
    
    case '/cart':
        require '../src/pages/cart.php';
        break;
    
    case '/checkout':
        require '../src/pages/checkout.php';
        break;
    
    // API routes
    case '/api/cart/add':
        require '../src/api/cart.php';
        break;
    
    // 404 handler
    default:
        http_response_code(404);
        require '../src/pages/404.php';
        break;
}
