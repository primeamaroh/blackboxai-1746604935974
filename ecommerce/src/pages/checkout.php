<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'Please login to proceed with checkout';
    $_SESSION['flash_type'] = 'error';
    header('Location: /login');
    exit;
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: /cart');
    exit;
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Get user details
$stmt = $db->query("SELECT * FROM users WHERE id = " . $userId);
$user = $stmt->fetchArray(SQLITE3_ASSOC);

// Calculate cart totals
$cartItems = [];
$subtotal = 0;
$discount = 0;
$affiliate_code = filter_input(INPUT_POST, 'affiliate_code', FILTER_SANITIZE_STRING);

foreach ($_SESSION['cart'] as $productId => $quantity) {
    $stmt = $db->query("SELECT * FROM products WHERE id = " . $productId);
    $product = $stmt->fetchArray(SQLITE3_ASSOC);
    
    if ($product) {
        $item_total = $product['price'] * $quantity;
        $subtotal += $item_total;
        
        $cartItems[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'total' => $item_total
        ];
    }
}

// Apply affiliate discount if code is valid
if ($affiliate_code) {
    $stmt = $db->query("SELECT * FROM users WHERE affiliate_code = '" . 
        $db->getConnection()->escapeString($affiliate_code) . "'");
    $affiliate = $stmt->fetchArray(SQLITE3_ASSOC);
    
    if ($affiliate) {
        $discount = $subtotal * (CUSTOMER_DISCOUNT / 100);
    }
}

$total = $subtotal - $discount;

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_checkout'])) {
    // Validate stock availability
    $stock_error = false;
    foreach ($cartItems as $item) {
        $stmt = $db->query("SELECT stock FROM products WHERE id = " . $item['id']);
        $product = $stmt->fetchArray(SQLITE3_ASSOC);
        
        if ($product['stock'] < $item['quantity']) {
            $stock_error = true;
            $_SESSION['flash_message'] = 'Some items in your cart are no longer available in the requested quantity.';
            $_SESSION['flash_type'] = 'error';
            break;
        }
    }
    
    if (!$stock_error) {
        // Start transaction
        $db->query('BEGIN TRANSACTION');
        
        try {
            // Create order
            $db->query("INSERT INTO orders (user_id, total_amount, status, affiliate_code, shipping_address) VALUES (
                " . $userId . ",
                " . $total . ",
                'pending',
                '" . ($affiliate_code ? $db->getConnection()->escapeString($affiliate_code) : '') . "',
                '" . $db->getConnection()->escapeString($_POST['shipping_address']) . "'
            )");
            
            $orderId = $db->getConnection()->lastInsertRowID();
            
            // Create order items and update stock
            foreach ($cartItems as $item) {
                $db->query("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (
                    " . $orderId . ",
                    " . $item['id'] . ",
                    " . $item['quantity'] . ",
                    " . $item['price'] . "
                )");
                
                $db->query("UPDATE products SET stock = stock - " . $item['quantity'] . " WHERE id = " . $item['id']);
            }
            
            // Create affiliate transaction if applicable
            if ($affiliate_code && $affiliate) {
                $commission = $total * (AFFILIATE_COMMISSION / 100);
                $db->query("INSERT INTO affiliate_transactions (affiliate_user_id, order_id, commission_amount) VALUES (
                    " . $affiliate['id'] . ",
                    " . $orderId . ",
                    " . $commission . "
                )");
            }
            
            // Commit transaction
            $db->query('COMMIT');
            
            // Clear cart
            unset($_SESSION['cart']);
            
            $_SESSION['flash_message'] = 'Order placed successfully!';
            $_SESSION['flash_type'] = 'success';
            header('Location: /orders');
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->query('ROLLBACK');
            $_SESSION['flash_message'] = 'Error processing your order. Please try again.';
            $_SESSION['flash_type'] = 'error';
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Order Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
                    <div class="space-y-4">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="flex justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="text-sm text-gray-500">Quantity: <?php echo $item['quantity']; ?></p>
                            </div>
                            <p class="text-sm font-medium text-gray-900">$<?php echo number_format($item['total'], 2); ?></p>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between">
                                <p class="text-sm text-gray-600">Subtotal</p>
                                <p class="text-sm font-medium text-gray-900">$<?php echo number_format($subtotal, 2); ?></p>
                            </div>
                            
                            <?php if ($discount > 0): ?>
                            <div class="flex justify-between mt-2">
                                <p class="text-sm text-gray-600">Discount (<?php echo CUSTOMER_DISCOUNT; ?>%)</p>
                                <p class="text-sm font-medium text-red-600">-$<?php echo number_format($discount, 2); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex justify-between mt-2">
                                <p class="text-base font-medium text-gray-900">Total</p>
                                <p class="text-base font-medium text-gray-900">$<?php echo number_format($total, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Checkout Form -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Checkout Information</h2>
                    <form action="" method="POST">
                        <div class="space-y-4">
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 text-gray-700 sm:text-sm" 
                                       readonly>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 text-gray-700 sm:text-sm" 
                                       readonly>
                            </div>
                            
                            <div>
                                <label for="shipping_address" class="block text-sm font-medium text-gray-700">Shipping Address</label>
                                <textarea name="shipping_address" id="shipping_address" rows="3" required
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                            </div>
                            
                            <div>
                                <label for="affiliate_code" class="block text-sm font-medium text-gray-700">Affiliate Code (Optional)</label>
                                <input type="text" name="affiliate_code" id="affiliate_code" 
                                       value="<?php echo htmlspecialchars($affiliate_code ?? ''); ?>"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            
                            <!-- Payment Information -->
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Information</h3>
                                
                                <div>
                                    <label for="card_number" class="block text-sm font-medium text-gray-700">Card Number</label>
                                    <input type="text" id="card_number" required
                                           pattern="[0-9]{16}" maxlength="16"
                                           placeholder="1234 5678 9012 3456"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="expiry" class="block text-sm font-medium text-gray-700">Expiry Date</label>
                                        <input type="text" id="expiry" required
                                               pattern="(0[1-9]|1[0-2])\/[0-9]{2}" maxlength="5"
                                               placeholder="MM/YY"
                                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    
                                    <div>
                                        <label for="cvv" class="block text-sm font-medium text-gray-700">CVV</label>
                                        <input type="text" id="cvv" required
                                               pattern="[0-9]{3,4}" maxlength="4"
                                               placeholder="123"
                                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" name="process_checkout"
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Place Order
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Format card number input
document.getElementById('card_number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    e.target.value = value;
});

// Format expiry date input
document.getElementById('expiry').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 2) {
        value = value.substr(0, 2) + '/' + value.substr(2);
    }
    e.target.value = value;
});

// Format CVV input
document.getElementById('cvv').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    e.target.value = value;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
