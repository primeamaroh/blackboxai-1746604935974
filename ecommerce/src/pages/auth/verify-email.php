<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
$verified = false;
$message = '';

if ($token) {
    $db = Database::getInstance();
    
    // Find user with this verification token
    $stmt = $db->query("SELECT * FROM users WHERE verification_token = '" . 
        $db->getConnection()->escapeString($token) . "' AND email_verified = 0");
    $user = $stmt->fetchArray(SQLITE3_ASSOC);
    
    if ($user) {
        // Update user as verified
        $update = $db->query("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = " . $user['id']);
        
        if ($update) {
            $verified = true;
            $_SESSION['flash_message'] = 'Your email has been verified successfully! You can now log in.';
            $_SESSION['flash_type'] = 'success';
            
            // Auto-login the user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
        } else {
            $_SESSION['flash_message'] = 'Failed to verify email. Please try again or contact support.';
            $_SESSION['flash_type'] = 'error';
        }
    } else {
        $_SESSION['flash_message'] = 'Invalid verification token or email already verified.';
        $_SESSION['flash_type'] = 'error';
    }
} else {
    $_SESSION['flash_message'] = 'No verification token provided.';
    $_SESSION['flash_type'] = 'error';
}
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <?php if ($verified): ?>
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <i class="fas fa-check text-green-600 text-xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Email Verified Successfully!
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Your email has been verified. You can now access all features of your account.
                </p>
                <div class="mt-6">
                    <a href="/" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Continue to Homepage
                    </a>
                </div>
            <?php else: ?>
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-times text-red-600 text-xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Verification Failed
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    <?php echo $_SESSION['flash_message']; ?>
                </p>
                <div class="mt-6">
                    <a href="/login" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Return to Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
