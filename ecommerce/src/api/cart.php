<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Handle cart actions
switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if (isset($input['product_id'])) {
            $product_id = (int)$input['product_id'];
            $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
            
            // Initialize cart if not exists
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Add or update quantity
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
            
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Product added to cart',
                'cart_count' => count($_SESSION['cart'])
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Product ID is required'
            ]);
        }
        break;
        
    case 'DELETE':
        if (isset($input['product_id'])) {
            $product_id = (int)$input['product_id'];
            
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Product removed from cart',
                    'cart_count' => count($_SESSION['cart'])
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Product not found in cart'
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Product ID is required'
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}
