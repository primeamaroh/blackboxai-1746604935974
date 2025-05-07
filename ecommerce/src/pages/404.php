<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
        <div>
            <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600 text-4xl"></i>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                404 - Page Not Found
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                The page you're looking for doesn't exist or has been moved.
            </p>
        </div>
        <div class="mt-8">
            <a href="/" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                <i class="fas fa-home mr-2"></i>
                Return to Homepage
            </a>
        </div>
        <div class="mt-6">
            <p class="text-sm text-gray-500">
                If you believe this is an error, please contact our support team.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
