<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$cart_total = 0;
$cart_items = [];

// Get cart items
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $db->query("SELECT * FROM products WHERE id = " . $product_id);
        $product = $stmt->fetchArray(SQLITE3_ASSOC);
        
        if ($product) {
            $item_total = $product['price'] * $quantity;
            $cart_total += $item_total;
            
            $cart_items[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'total' => $item_total,
                'image_url' => $product['image_url'],
                'stock' => $product['stock']
            ];
        }
    }
}

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }
        $_SESSION['flash_message'] = 'Cart updated successfully!';
        $_SESSION['flash_type'] = 'success';
        header('Location: /cart');
        exit;
    } elseif (isset($_POST['remove_item'])) {
        $product_id = (int)$_POST['remove_item'];
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['flash_message'] = 'Item removed from cart!';
        $_SESSION['flash_type'] = 'success';
        header('Location: /cart');
        exit;
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">Shopping Cart</h1>
    
    <?php if (empty($cart_items)): ?>
    <div class="bg-white rounded-lg shadow-lg p-6 text-center">
        <div class="mb-4">
            <i class="fas fa-shopping-cart text-gray-400 text-5xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Your cart is empty</h2>
        <p class="text-gray-600 mb-4">Looks like you haven't added any items to your cart yet.</p>
        <a href="/products" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
            Continue Shopping
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cart Items -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <form method="POST" class="divide-y divide-gray-200">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="p-6 flex items-center animate-fade-in">
                        <div class="flex-shrink-0 w-24 h-24">
                            <?php if ($item['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 class="w-full h-full object-cover rounded">
                            <?php else: ?>
                            <div class="w-full h-full bg-gray-200 rounded flex items-center justify-center">
                                <i class="fas fa-image text-gray-400 text-2xl"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ml-6 flex-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </h3>
                                <p class="text-lg font-medium text-gray-900">
                                    $<?php echo number_format($item['total'], 2); ?>
                                </p>
                            </div>
                            
                            <p class="mt-1 text-sm text-gray-500">
                                Price: $<?php echo number_format($item['price'], 2); ?> each
                            </p>
                            
                            <div class="mt-4 flex items-center justify-between">
                                <div class="flex items-center">
                                    <label for="quantity-<?php echo $item['id']; ?>" class="sr-only">Quantity</label>
                                    <input type="number" id="quantity-<?php echo $item['id']; ?>" 
                                           name="quantity[<?php echo $item['id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>"
                                           min="0" max="<?php echo $item['stock']; ?>"
                                           class="max-w-[80px] rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    
                                    <?php if ($item['quantity'] > $item['stock']): ?>
                                    <p class="ml-2 text-sm text-red-600">
                                        Only <?php echo $item['stock']; ?> available
                                    </p>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" name="remove_item" value="<?php echo $item['id']; ?>" 
                                        class="text-red-600 hover:text-red-500">
                                    <i class="fas fa-trash"></i>
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="p-6 bg-gray-50">
                        <button type="submit" name="update_cart" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Update Cart
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Order Summary</h2>
                
                <div class="flow-root">
                    <dl class="-my-4 text-sm divide-y divide-gray-200">
                        <div class="py-4 flex items-center justify-between">
                            <dt class="text-gray-600">Subtotal</dt>
                            <dd class="font-medium text-gray-900">$<?php echo number_format($cart_total, 2); ?></dd>
                        </div>
                        
                        <?php if (isset($_SESSION['affiliate_discount'])): ?>
                        <div class="py-4 flex items-center justify-between">
                            <dt class="text-gray-600">Affiliate Discount (<?php echo CUSTOMER_DISCOUNT; ?>%)</dt>
                            <dd class="font-medium text-red-600">-$<?php echo number_format($cart_total * (CUSTOMER_DISCOUNT / 100), 2); ?></dd>
                        </div>
                        <?php endif; ?>
                        
                        <div class="py-4 flex items-center justify-between">
                            <dt class="text-base font-medium text-gray-900">Order Total</dt>
                            <dd class="text-base font-medium text-gray-900">
                                $<?php echo number_format(isset($_SESSION['affiliate_discount']) ? 
                                    $cart_total * (1 - CUSTOMER_DISCOUNT / 100) : $cart_total, 2); ?>
                            </dd>
                        </div>
                    </dl>
                </div>
                
                <div class="mt-6">
                    <a href="/checkout" 
                       class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        Proceed to Checkout
                    </a>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="/products" class="text-sm text-indigo-600 hover:text-indigo-500">
                        Continue Shopping
                    </a>
                </div>
            </div>
            
            <!-- Affiliate Code Input -->
            <?php if (!isset($_SESSION['affiliate_discount'])): ?>
            <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Have an Affiliate Code?</h3>
                <form action="/apply-affiliate" method="POST" class="space-y-4">
                    <div>
                        <label for="affiliate_code" class="sr-only">Affiliate Code</label>
                        <input type="text" id="affiliate_code" name="affiliate_code" 
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               placeholder="Enter affiliate code">
                    </div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        Apply Code
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
