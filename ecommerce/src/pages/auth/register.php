<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    
    // Validation
    $errors = [];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($errors)) {
        $db = Database::getInstance();
        
        // Check if email already exists
        $stmt = $db->query("SELECT id FROM users WHERE email = '" . $db->getConnection()->escapeString($email) . "'");
        if ($stmt->fetchArray()) {
            $_SESSION['flash_message'] = 'Email already registered';
            $_SESSION['flash_type'] = 'error';
        } else {
            // Generate verification token
            $verification_token = bin2hex(random_bytes(32));
            
            // Generate unique affiliate code
            $affiliate_code = strtoupper(substr(md5(uniqid()), 0, 8));
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $query = "INSERT INTO users (email, password, full_name, verification_token, affiliate_code) 
                     VALUES (
                        '" . $db->getConnection()->escapeString($email) . "',
                        '" . $db->getConnection()->escapeString($hashed_password) . "',
                        '" . $db->getConnection()->escapeString($full_name) . "',
                        '" . $db->getConnection()->escapeString($verification_token) . "',
                        '" . $db->getConnection()->escapeString($affiliate_code) . "'
                     )";
            
            $result = $db->query($query);
            
            if ($result) {
                // Send verification email
                $verification_link = BASE_URL . '/verify-email?token=' . $verification_token;
                $to = $email;
                $subject = "Verify your email address";
                $message = "Hi $full_name,\n\n";
                $message .= "Thank you for registering! Please click the link below to verify your email address:\n\n";
                $message .= $verification_link . "\n\n";
                $message .= "If you didn't create this account, please ignore this email.\n\n";
                $message .= "Best regards,\n";
                $message .= SITE_NAME;
                
                $headers = "From: " . SMTP_USER . "\r\n";
                $headers .= "Reply-To: " . SMTP_USER . "\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                if (mail($to, $subject, $message, $headers)) {
                    $_SESSION['flash_message'] = 'Registration successful! Please check your email to verify your account.';
                    $_SESSION['flash_type'] = 'success';
                    header('Location: /login');
                    exit;
                } else {
                    $_SESSION['flash_message'] = 'Registration successful but failed to send verification email. Please contact support.';
                    $_SESSION['flash_type'] = 'error';
                }
            } else {
                $_SESSION['flash_message'] = 'Registration failed. Please try again.';
                $_SESSION['flash_type'] = 'error';
            }
        }
    } else {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'error';
    }
}
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Create your account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Or
                <a href="/login" class="font-medium text-indigo-600 hover:text-indigo-500">
                    sign in to your existing account
                </a>
            </p>
        </div>
        <form class="mt-8 space-y-6" action="/register" method="POST">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="full_name" class="sr-only">Full Name</label>
                    <input id="full_name" name="full_name" type="text" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                           placeholder="Full Name">
                </div>
                <div>
                    <label for="email" class="sr-only">Email address</label>
                    <input id="email" name="email" type="email" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                           placeholder="Email address">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                           placeholder="Password">
                </div>
                <div>
                    <label for="confirm_password" class="sr-only">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                           placeholder="Confirm Password">
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-plus"></i>
                    </span>
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
