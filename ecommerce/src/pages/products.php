<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();

// Get category filter
$category = isset($_GET['category']) ? $_GET['category'] : null;

// Get sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Get price range
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;

// Build query
$query = "SELECT p.*, 
          (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
          (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
          FROM products p
          WHERE 1=1";

if ($category) {
    $query .= " AND category = '" . $db->getConnection()->escapeString($category) . "'";
}

if ($min_price !== null) {
    $query .= " AND price >= " . $min_price;
}

if ($max_price !== null) {
    $query .= " AND price <= " . $max_price;
}

switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'rating':
        $query .= " ORDER BY avg_rating DESC";
        break;
    default:
        $query .= " ORDER BY created_at DESC";
}

$products = $db->query($query);

// Get all categories
$categories = $db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL");
?>

<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">All Products</h1>
        
        <!-- Sort Dropdown -->
        <div class="relative">
            <select onchange="window.location.href=this.value" 
                    class="appearance-none bg-white border border-gray-300 rounded-md py-2 pl-3 pr-10 text-gray-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="?sort=newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="?sort=price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="?sort=price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="?sort=rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </div>
    
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Filters Sidebar -->
        <div class="w-full md:w-64 space-y-6">
            <!-- Categories -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Categories</h3>
                <div class="space-y-2">
                    <?php while ($cat = $categories->fetchArray(SQLITE3_ASSOC)): ?>
                    <a href="?category=<?php echo urlencode($cat['category']); ?>" 
                       class="block text-gray-600 hover:text-indigo-600 <?php echo $category === $cat['category'] ? 'text-indigo-600 font-medium' : ''; ?>">
                        <?php echo htmlspecialchars($cat['category']); ?>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <!-- Price Range -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Price Range</h3>
                <form action="" method="GET" class="space-y-4">
                    <div>
                        <label for="min_price" class="block text-sm text-gray-600">Min Price</label>
                        <input type="number" id="min_price" name="min_price" 
                               value="<?php echo $min_price; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               min="0" step="0.01">
                    </div>
                    <div>
                        <label for="max_price" class="block text-sm text-gray-600">Max Price</label>
                        <input type="number" id="max_price" name="max_price" 
                               value="<?php echo $max_price; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               min="0" step="0.01">
                    </div>
                    <button type="submit" 
                            class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Apply Filter
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="flex-1">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php 
                $loop_index = 0;
                while ($product = $products->fetchArray(SQLITE3_ASSOC)): 
                ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden product-card animate-fade-in-up" 
                     style="animation-delay: <?php echo $loop_index * 150; ?>ms">
                    <?php if ($product['image_url']): ?>
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="w-full h-48 object-cover">
                        <div class="product-overlay"></div>
                        <?php if ($product['stock'] < 10 && $product['stock'] > 0): ?>
                            <span class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded text-xs">
                                Only <?php echo $product['stock']; ?> left
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                        <i class="fas fa-image text-gray-400 text-4xl"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h3>
                        
                        <p class="mt-1 text-sm text-gray-500">
                            <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                        </p>
                        
                        <div class="mt-2 flex items-center">
                            <?php
                            $rating = round($product['avg_rating'] ?? 0);
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fas fa-star text-yellow-400"></i>';
                                } else {
                                    echo '<i class="far fa-star text-yellow-400"></i>';
                                }
                            }
                            ?>
                            <span class="ml-1 text-sm text-gray-500">
                                (<?php echo $product['review_count'] ?? 0; ?> reviews)
                            </span>
                        </div>
                        
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-lg font-bold text-gray-900">
                                $<?php echo number_format($product['price'], 2); ?>
                            </p>
                            
                            <?php if ($product['stock'] > 0): ?>
                                <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-shopping-cart mr-2"></i>
                                    <span id="btn-text-<?php echo $product['id']; ?>">Add to Cart</span>
                                </button>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-700">
                                    Out of Stock
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php 
                $loop_index++;
                endwhile; 
                ?>
            </div>
        </div>
    </div>
</div>

<script>
function addToCart(productId) {
    // Get the button text element
    const btnText = document.getElementById(`btn-text-${productId}`);
    const originalText = btnText.textContent;
    
    // Show loading state
    btnText.textContent = 'Adding...';
    
    // Send AJAX request
    fetch('/api/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                cartBadge.textContent = data.cart_count;
                cartBadge.style.display = 'flex';
            } else {
                // Create new badge if it doesn't exist
                const cartIcon = document.querySelector('.fa-shopping-cart').parentElement;
                const badge = document.createElement('span');
                badge.className = 'cart-badge absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs';
                badge.textContent = data.cart_count;
                cartIcon.appendChild(badge);
            }
            
            // Show success state
            btnText.textContent = 'Added!';
            setTimeout(() => {
                btnText.textContent = originalText;
            }, 2000);
        } else {
            // Show error state
            btnText.textContent = 'Error';
            setTimeout(() => {
                btnText.textContent = originalText;
            }, 2000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error state
        btnText.textContent = 'Error';
        setTimeout(() => {
            btnText.textContent = originalText;
        }, 2000);
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
