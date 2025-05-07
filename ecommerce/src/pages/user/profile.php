<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Get user details
$stmt = $db->query("SELECT * FROM users WHERE id = " . $userId);
$user = $stmt->fetchArray(SQLITE3_ASSOC);

// Get user's orders
$orders = $db->query("
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = " . $userId . " 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

// Get affiliate statistics
$affiliateStats = $db->query("
    SELECT 
        COUNT(o.id) as referred_orders,
        SUM(o.total_amount) as total_sales,
        SUM(at.commission_amount) as total_commission
    FROM orders o
    LEFT JOIN affiliate_transactions at ON o.id = at.order_id
    WHERE o.affiliate_code = '" . $db->getConnection()->escapeString($user['affiliate_code']) . "'
")->fetchArray(SQLITE3_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate current password if trying to change password
    if (!empty($new_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        }
        
        if (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters long';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        }
    }
    
    if (empty($errors)) {
        $updateQuery = "UPDATE users SET full_name = '" . $db->getConnection()->escapeString($full_name) . "'";
        
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $updateQuery .= ", password = '" . $db->getConnection()->escapeString($hashed_password) . "'";
        }
        
        $updateQuery .= " WHERE id = " . $userId;
        
        if ($db->query($updateQuery)) {
            $_SESSION['flash_message'] = 'Profile updated successfully!';
            $_SESSION['flash_type'] = 'success';
            header('Location: /profile');
            exit;
        } else {
            $_SESSION['flash_message'] = 'Failed to update profile';
            $_SESSION['flash_type'] = 'error';
        }
    } else {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'error';
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Profile Information -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Profile Information</h2>
                    <form action="" method="POST">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       class="mt-1 block w-full bg-gray-50 border border-gray-300 rounded-md shadow-sm py-2 px-3 text-gray-700 sm:text-sm" 
                                       disabled>
                            </div>
                            
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" name="full_name" id="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input type="password" name="current_password" id="current_password" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <input type="password" name="new_password" id="new_password" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            
                            <div>
                                <button type="submit" 
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Update Profile
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="mt-8 bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Recent Orders</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($order = $orders->fetchArray(SQLITE3_ASSOC)): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#<?php echo $order['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $order['item_count']; ?> items</td>
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
                        <a href="/orders" class="text-indigo-600 hover:text-indigo-900">View all orders →</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Affiliate Information -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Affiliate Program</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Your Affiliate Code</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="text" value="<?php echo htmlspecialchars($user['affiliate_code']); ?>" 
                                       class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300" 
                                       readonly>
                                <button onclick="copyToClipboard('<?php echo htmlspecialchars($user['affiliate_code']); ?>')" 
                                        class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm font-medium text-gray-500">Referred Orders</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $affiliateStats['referred_orders']; ?></p>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm font-medium text-gray-500">Total Sales</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">$<?php echo number_format($affiliateStats['total_sales'], 2); ?></p>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm font-medium text-gray-500">Total Commission</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">$<?php echo number_format($affiliateStats['total_commission'], 2); ?></p>
                            </div>
                        </div>
                        
                        <div class="rounded-md bg-blue-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">How it works</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>Share your affiliate code with friends. When they make a purchase:</p>
                                        <ul class="list-disc pl-5 mt-1">
                                            <li>They get <?php echo CUSTOMER_DISCOUNT; ?>% off their order</li>
                                            <li>You earn <?php echo AFFILIATE_COMMISSION; ?>% commission</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Affiliate code copied to clipboard!');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
