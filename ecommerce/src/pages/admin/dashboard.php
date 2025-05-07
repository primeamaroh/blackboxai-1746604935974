<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login');
    exit;
}

$db = Database::getInstance();

// Get counts for dashboard
$productCount = $db->query("SELECT COUNT(*) as count FROM products")->fetchArray(SQLITE3_ASSOC)['count'];
$orderCount = $db->query("SELECT COUNT(*) as count FROM orders")->fetchArray(SQLITE3_ASSOC)['count'];
$userCount = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetchArray(SQLITE3_ASSOC)['count'];
$reviewCount = $db->query("SELECT COUNT(*) as count FROM reviews")->fetchArray(SQLITE3_ASSOC)['count'];

// Get recent orders
$recentOrders = $db->query("
    SELECT o.*, u.email, u.full_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

// Get low stock products (less than 10 items)
$lowStockProducts = $db->query("
    SELECT * FROM products 
    WHERE stock < 10 
    ORDER BY stock ASC 
    LIMIT 5
");

// Update affiliate settings if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_affiliate'])) {
    $commission = filter_input(INPUT_POST, 'commission', FILTER_VALIDATE_FLOAT);
    $discount = filter_input(INPUT_POST, 'discount', FILTER_VALIDATE_FLOAT);
    
    if ($commission !== false && $discount !== false) {
        // In a real application, you would store these in a settings table
        // For now, we'll use PHP constants (defined in config.php)
        $_SESSION['flash_message'] = 'Affiliate settings updated successfully!';
        $_SESSION['flash_type'] = 'success';
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Admin Dashboard</h1>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Products -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-indigo-600 bg-opacity-75">
                    <i class="fas fa-box text-white text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="mb-2 text-sm font-medium text-gray-600">Total Products</p>
                    <p class="text-lg font-semibold text-gray-700"><?php echo $productCount; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Total Orders -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-600 bg-opacity-75">
                    <i class="fas fa-shopping-cart text-white text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="mb-2 text-sm font-medium text-gray-600">Total Orders</p>
                    <p class="text-lg font-semibold text-gray-700"><?php echo $orderCount; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Total Customers -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-600 bg-opacity-75">
                    <i class="fas fa-users text-white text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="mb-2 text-sm font-medium text-gray-600">Total Customers</p>
                    <p class="text-lg font-semibold text-gray-700"><?php echo $userCount; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Total Reviews -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-600 bg-opacity-75">
                    <i class="fas fa-star text-white text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="mb-2 text-sm font-medium text-gray-600">Total Reviews</p>
                    <p class="text-lg font-semibold text-gray-700"><?php echo $reviewCount; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h2 class="text-xl font-semibold mb-4">Recent Orders</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($order = $recentOrders->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#<?php echo $order['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($order['full_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $order['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                            ($order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <a href="/admin/orders" class="text-indigo-600 hover:text-indigo-900">View all orders →</a>
                </div>
            </div>
        </div>

        <!-- Low Stock Products -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h2 class="text-xl font-semibold mb-4">Low Stock Products</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($product = $lowStockProducts->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        <?php echo $product['stock']; ?> left
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="/admin/products/edit/<?php echo $product['id']; ?>" class="text-indigo-600 hover:text-indigo-900">Update Stock</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <a href="/admin/products" class="text-indigo-600 hover:text-indigo-900">Manage all products →</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Affiliate Settings -->
    <div class="mt-8 bg-white rounded-lg shadow">
        <div class="p-6">
            <h2 class="text-xl font-semibold mb-4">Affiliate Settings</h2>
            <form action="" method="POST" class="max-w-lg">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="commission" class="block text-sm font-medium text-gray-700">Affiliate Commission (%)</label>
                        <input type="number" name="commission" id="commission" step="0.1" min="0" max="100" 
                               value="<?php echo AFFILIATE_COMMISSION; ?>" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="discount" class="block text-sm font-medium text-gray-700">Customer Discount (%)</label>
                        <input type="number" name="discount" id="discount" step="0.1" min="0" max="100" 
                               value="<?php echo CUSTOMER_DISCOUNT; ?>" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <button type="submit" name="update_affiliate" 
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Update Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
