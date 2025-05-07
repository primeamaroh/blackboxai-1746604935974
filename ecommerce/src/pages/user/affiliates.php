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

// Get recent referrals
$recentReferrals = $db->query("
    SELECT o.*, at.commission_amount, u.email as customer_email
    FROM orders o
    LEFT JOIN affiliate_transactions at ON o.id = at.order_id
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.affiliate_code = '" . $db->getConnection()->escapeString($user['affiliate_code']) . "'
    ORDER BY o.created_at DESC
    LIMIT 5
");
?>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Affiliate Stats -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Your Affiliate Dashboard</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Total Sales -->
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-75">Total Sales</p>
                                <p class="text-2xl font-bold mt-1">
                                    $<?php echo number_format($affiliateStats['total_sales'] ?? 0, 2); ?>
                                </p>
                            </div>
                            <div class="text-3xl opacity-75">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Commission -->
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-75">Total Commission</p>
                                <p class="text-2xl font-bold mt-1">
                                    $<?php echo number_format($affiliateStats['total_commission'] ?? 0, 2); ?>
                                </p>
                            </div>
                            <div class="text-3xl opacity-75">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Referred Orders -->
                    <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-75">Referred Orders</p>
                                <p class="text-2xl font-bold mt-1">
                                    <?php echo $affiliateStats['referred_orders'] ?? 0; ?>
                                </p>
                            </div>
                            <div class="text-3xl opacity-75">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Referrals -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Referrals</h3>
                    <?php if ($recentReferrals && $recentReferrals->fetchArray()): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($referral = $recentReferrals->fetchArray(SQLITE3_ASSOC)): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#<?php echo $referral['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($referral['customer_email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($referral['total_amount'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">$<?php echo number_format($referral['commission_amount'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M j, Y', strtotime($referral['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-500">No referrals yet. Start sharing your affiliate code to earn commissions!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Affiliate Info -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Your Affiliate Code</h3>
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <code class="text-lg font-mono text-indigo-600"><?php echo htmlspecialchars($user['affiliate_code']); ?></code>
                        <button onclick="copyToClipboard('<?php echo htmlspecialchars($user['affiliate_code']); ?>')" 
                                class="ml-2 p-2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-gift text-indigo-600 mt-1"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-gray-900">Customer Discount</h4>
                            <p class="text-sm text-gray-500">Your referrals get <?php echo CUSTOMER_DISCOUNT; ?>% off their purchase</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-dollar-sign text-indigo-600 mt-1"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-gray-900">Your Commission</h4>
                            <p class="text-sm text-gray-500">You earn <?php echo AFFILIATE_COMMISSION; ?>% commission on each sale</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-indigo-600 mt-1"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-gray-900">How It Works</h4>
                            <p class="text-sm text-gray-500">Share your code with friends and earn commission when they make a purchase using your code.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Share Links -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-4">Share Your Code</h4>
                    <div class="space-y-3">
                        <a href="#" onclick="shareOnFacebook()" class="flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fab fa-facebook text-blue-600 mr-2"></i>
                            Share on Facebook
                        </a>
                        <a href="#" onclick="shareOnTwitter()" class="flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fab fa-twitter text-blue-400 mr-2"></i>
                            Share on Twitter
                        </a>
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

function shareOnFacebook() {
    const url = encodeURIComponent(window.location.origin);
    const text = encodeURIComponent('Use my affiliate code <?php echo $user['affiliate_code']; ?> to get <?php echo CUSTOMER_DISCOUNT; ?>% off your purchase!');
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`, '_blank');
}

function shareOnTwitter() {
    const url = encodeURIComponent(window.location.origin);
    const text = encodeURIComponent('Use my affiliate code <?php echo $user['affiliate_code']; ?> to get <?php echo CUSTOMER_DISCOUNT; ?>% off your purchase!');
    window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
