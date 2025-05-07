<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();

// Get all products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

$total_products = $db->query("SELECT COUNT(*) as count FROM products")->fetchArray(SQLITE3_ASSOC)['count'];
$total_pages = ceil($total_products / $per_page);

$products = $db->query("
    SELECT p.*, 
           (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
    FROM products p
    ORDER BY p.created_at DESC
    LIMIT $per_page OFFSET $offset
");

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = 1; // Default quantity
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    $_SESSION['flash_message'] = 'Product added to cart!';
    $_SESSION['flash_type'] = 'success';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Hero Section -->
    <div class="relative hero-gradient rounded-lg shadow-xl mb-12 overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 sm:pb-16 md:pb-20 lg:w-full lg:pb-28 xl:pb-32">
                <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                <div class="sm:text-center lg:text-left hero-content animate-fade-in">
                        <h1 class="text-4xl tracking-tight font-extrabold text-white sm:text-5xl md:text-6xl animate-slide-up">
                            <span class="block">Welcome to</span>
                            <span class="block text-indigo-200"><?php echo SITE_NAME; ?></span>
                        </h1>
                        <p class="mt-3 text-base text-indigo-100 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0 animate-fade-in delay-300">
                            Discover amazing products at great prices. Shop with confidence and enjoy exclusive deals with our affiliate program.
                        </p>
                        <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <div class="rounded-md shadow animate-bounce-in delay-500">
                                <a href="#products" 
                                   class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-gray-50 hover:transform hover:scale-105 transition-all duration-300 md:py-4 md:text-lg md:px-10">
                                    Shop Now
                                </a>
                            </div>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <a href="/register" 
                                   class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-500 bg-opacity-60 hover:bg-opacity-70 md:py-4 md:text-lg md:px-10">
                                    Join Us
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>
                <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 hidden lg:block">
                    <div class="relative h-full">
                        <div class="absolute inset-0 bg-gradient-to-l from-indigo-600 to-transparent opacity-50"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-white text-9xl opacity-20"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Categories -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Shop by Category</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="/products?category=electronics" class="relative rounded-lg overflow-hidden hover:opacity-90 transition-opacity">
                <img src="https://images.pexels.com/photos/356056/pexels-photo-356056.jpeg" alt="Electronics" class="w-full h-48 object-cover">
                <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                    <span class="text-white text-xl font-bold">Electronics</span>
                </div>
            </a>
            <a href="/products?category=accessories" class="relative rounded-lg overflow-hidden hover:opacity-90 transition-opacity">
                <img src="https://images.pexels.com/photos/264591/pexels-photo-264591.jpeg" alt="Accessories" class="w-full h-48 object-cover">
                <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                    <span class="text-white text-xl font-bold">Accessories</span>
                </div>
            </a>
            <a href="/products?category=gadgets" class="relative rounded-lg overflow-hidden hover:opacity-90 transition-opacity">
                <img src="https://images.pexels.com/photos/3178938/pexels-photo-3178938.jpeg" alt="Gadgets" class="w-full h-48 object-cover">
                <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                    <span class="text-white text-xl font-bold">Gadgets</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Products Grid -->
    <h2 class="text-2xl font-bold text-gray-900 mb-6 animate-fade-in">Featured Products</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="products">
        <?php 
        $loop_index = 0;
        while ($product = $products->fetchArray(SQLITE3_ASSOC)): 
        ?>
        <div class="bg-white rounded-lg shadow-lg overflow-hidden product-card animate-fade-in-up" style="animation-delay: <?php echo ($loop_index ?? 0) * 150; ?>ms">
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
                        <form method="POST" class="inline">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" name="add_to_cart" 
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-shopping-cart mr-2"></i>
                                Add to Cart
                            </button>
                        </form>
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
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-8">
        <nav class="flex justify-center">
            <ul class="flex items-center">
                <?php if ($page > 1): ?>
                <li>
                    <a href="?page=<?php echo $page - 1; ?>" 
                       class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Previous
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li>
                    <a href="?page=<?php echo $i; ?>" 
                       class="px-3 py-2 rounded-md text-sm font-medium <?php echo $i === $page ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li>
                    <a href="?page=<?php echo $page + 1; ?>" 
                       class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Next
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
