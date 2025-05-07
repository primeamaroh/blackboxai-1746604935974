<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <script>
        // Tailwind Configuration
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center">
                        <span class="text-xl font-bold text-indigo-600"><?php echo SITE_NAME; ?></span>
                    </a>
                    <div class="hidden md:flex md:items-center md:ml-10 space-x-4">
                        <a href="/" class="text-gray-600 hover:text-gray-900 px-3 py-2">Home</a>
                        <a href="/products" class="text-gray-600 hover:text-gray-900 px-3 py-2">Products</a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="/admin/dashboard" class="text-gray-600 hover:text-gray-900 px-3 py-2">Dashboard</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="relative group">
                            <button class="flex items-center text-gray-600 hover:text-gray-900 px-3 py-2">
                                <i class="fas fa-user mr-2"></i>
                                Account
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            <div class="absolute right-0 w-48 mt-2 py-2 bg-white rounded-md shadow-xl z-50 hidden group-hover:block">
                                <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                <a href="/orders" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Orders</a>
                                <a href="/affiliates" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Affiliates</a>
                                <div class="border-t border-gray-100"></div>
                                <a href="/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/login" class="text-gray-600 hover:text-gray-900 px-3 py-2">Login</a>
                        <a href="/register" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Register</a>
                    <?php endif; ?>
                    
                    <a href="/cart" class="relative group">
                        <i class="fas fa-shopping-cart text-gray-600 hover:text-gray-900 text-xl"></i>
                        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs cart-badge">
                                <?php echo count($_SESSION['cart']); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <main class="flex-grow">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="max-w-7xl mx-auto px-4 py-4">
                <div class="flash-message rounded-lg p-4 <?php echo $_SESSION['flash_type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                    <?php 
                    echo $_SESSION['flash_message'];
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                </div>
            </div>
        <?php endif; ?>
