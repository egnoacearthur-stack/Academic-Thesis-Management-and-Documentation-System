<?php
/**
 * Login Page - FIXED DARK MODE FONTS
 */

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $stmt = $conn->prepare("SELECT user_id, password, full_name, role, status FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['status'] !== 'active') {
                $error = 'Your account has been deactivated. Please contact the administrator.';
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                
                $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $updateStmt->bind_param("i", $user['user_id']);
                $updateStmt->execute();
                
                logActivity($conn, $user['user_id'], 'login');
                
                header('Location: index.php?page=dashboard');
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">
    <!-- Dotted Background Pattern -->
    <div class="bg-pattern-dots"></div>
    
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <?php 
                $customLogo = 'uploads/profiles/logo.png';
                if (file_exists($customLogo)): 
                ?>
                    <img src="<?= $customLogo ?>" alt="TMS Logo" class="login-logo-image">
                <?php else: ?>
                    <i class="fas fa-graduation-cap"></i>
                <?php endif; ?>
                <h1>Thesis Management System</h1>
                <p>Academic Documentation & Workflow</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" class="form-control" autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="password-input-wrapper">
                        <input type="password" id="password" name="password" class="form-control">
                        <button type="button" class="password-toggle" onclick="toggleLoginPassword()">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="divider">
                <span>OR</span>
            </div>
            
            <button type="button" id="google-login-btn" class="btn-google">
                <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google" class="google-icon">
                Login with Google
            </button>
            
            <div class="login-footer">
                <p class="copyright">&copy; <?= date('Y') ?> Academic Thesis Management System</p>
            </div>
        </div>
    </div>

    <style>
    /* ========================================
    DOTTED BACKGROUND PATTERN
    ======================================== */

    .bg-pattern-dots {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        background-image: radial-gradient(rgba(255, 255, 255, 0.15) 1px, transparent 1px);
        background-size: 24px 24px;
        mask-image: radial-gradient(ellipse at center, black 20%, transparent 70%);
        -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 70%);
        pointer-events: none;
    }

    /* Dark mode variant */
    body.dark-theme .bg-pattern-dots {
        background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
        mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
        -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
    }
    

    body.login-page {
        background: linear-gradient(135deg, #1a2332 0%, #2d3e50 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .login-container {
        width: 100%;
        max-width: 450px;
        padding: 20px;
    }
    
    .login-box {
        background: #ffffff;
        border-radius: 20px;
        padding: 50px 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    /* ✅ DARK MODE - Lighter fonts for login */
    body.dark-theme .login-box {
        background: linear-gradient(135deg, #434343 0%, #000000 100%);
        color: #ecf0f1;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    body.dark-theme .login-header h1 {
        color: #ecf0f1 !important;
    }

    body.dark-theme .login-header p {
        color: #b0bec5 !important;
    }

    body.dark-theme .form-group label {
        color: #ecf0f1 !important;
    }

    body.dark-theme .form-control {
        background: #2d3e50 !important;
        color: #ecf0f1 !important;
        border-color: #3a4f63 !important;
    }

    body.dark-theme .form-control::placeholder {
        color: #7f8c8d !important;
    }

    body.dark-theme .form-control:focus {
        background: #364a5f !important;
        border-color: #3498db !important;
    }

    body.dark-theme .password-toggle {
        color: #b0bec5 !important;
    }

    body.dark-theme .password-toggle:hover {
        color: #3498db !important;
    }

    body.dark-theme .divider span {
        background: linear-gradient(135deg, #434343 0%, #000000 100%);
        color: #b0bec5;
    }

    body.dark-theme .divider::before,
    body.dark-theme .divider::after {
        background: #3a4f63;
    }

    body.dark-theme .btn-google {
        background: #2d3e50;
        border-color: #3a4f63;
        color: #ecf0f1;
    }

    body.dark-theme .btn-google:hover {
        background: #364a5f;
        border-color: #3498db;
    }

    body.dark-theme .copyright {
        color: #b0bec5 !important;
    }

    body.dark-theme .alert-danger {
        background: #5a1a1a !important;
        color: #ffcccc !important;
        border-left-color: #e74c3c !important;
    }

    body.dark-theme .alert-success {
        background: #1a4d2e !important;
        color: #ccffcc !important;
        border-left-color: #27ae60 !important;
    }
    
    body.dark-theme .login-header h1 {
        color: #ecf0f1 !important;
    }
    
    body.dark-theme .login-header p {
        color: #b0bec5 !important;
    }
    
    body.dark-theme .form-group label {
        color: #ecf0f1 !important;
    }
    
    body.dark-theme .form-control {
        background: #2d3e50 !important;
        color: #ecf0f1 !important;
        border-color: #3a4f63 !important;
    }
    
    body.dark-theme .form-control:focus {
        background: #364a5f !important;
        border-color: #3498db !important;
    }
    
    body.dark-theme .password-toggle {
        color: #b0bec5 !important;
    }
    
    body.dark-theme .password-toggle:hover {
        color: #3498db !important;
    }
    
    body.dark-theme .divider span {
        background: #1e2936;
        color: #b0bec5;
    }
    
    body.dark-theme .divider::before,
    body.dark-theme .divider::after {
        background: #3a4f63;
    }
    
    body.dark-theme .btn-google {
        background: #2d3e50;
        border-color: #3a4f63;
        color: #ecf0f1;
    }
    
    body.dark-theme .btn-google:hover {
        background: #364a5f;
        border-color: #3498db;
    }
    
    body.dark-theme .copyright {
        color: #b0bec5 !important;
    }
    
    .login-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .login-logo-image {
        width: 150px;
        height: auto;
        max-height: 140px;
        object-fit: contain;
        margin: 0 auto 30px;
        display: block;
    }
    
    .login-header i {
        font-size: 80px;
        color: #3498db;
        margin-bottom: 20px;
        display: block;
    }
    
    .login-header h1 {
        color: #1a2332;
        font-size: 1.8rem;
        margin: 15px 0 10px 0;
        font-weight: 700;
    }
    
    .login-header p {
        color: #7f8c8d;
        font-size: 1rem;
        margin: 0;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
    }
    
    .alert-danger {
        background: #fee;
        color: #c33;
        border-left: 4px solid #e74c3c;
    }
    
    .alert-success {
        background: #efe;
        color: #393;
        border-left: 4px solid #27ae60;
    }
    
    .login-form {
        margin-bottom: 30px;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-group label {
        display: block;
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 10px;
        font-size: 0.95rem;
    }
    
    .form-group label i {
        color: #3498db;
        margin-right: 8px;
        width: 18px;
    }
    
    .form-control {
        width: 100%;
        padding: 15px;
        border: 2px solid #e0e6ed;
        border-radius: 10px;
        font-size: 1rem;
        color: #2c3e50;
        background: #f8f9fa;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #3498db;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }
    
    .password-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .password-input-wrapper input {
        padding-right: 50px;
    }
    
    .password-toggle {
        position: absolute;
        right: 15px;
        background: none;
        border: none;
        color: #95a5a6;
        cursor: pointer;
        padding: 8px;
    }
    
    .password-toggle:hover {
        color: #3498db;
    }
    
    .btn {
        padding: 15px 30px;
        border: none;
        border-radius: 10px;
        font-size: 1.05rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: #fff;
        width: 100%;
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #2980b9 0%, #2574a9 100%);
        box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        transform: translateY(-2px);
    }
    
    .divider {
        text-align: center;
        margin: 30px 0;
        position: relative;
    }
    
    .divider::before,
    .divider::after {
        content: '';
        position: absolute;
        top: 50%;
        width: 45%;
        height: 1px;
        background: #e0e6ed;
    }
    
    .divider::before {
        left: 0;
    }
    
    .divider::after {
        right: 0;
    }
    
    .divider span {
        background: #fff;
        padding: 0 15px;
        color: #95a5a6;
        font-size: 0.9rem;
        font-weight: 500;
        position: relative;
        z-index: 1;
    }
    
    .btn-google {
        width: 100%;
        padding: 15px;
        background: #fff;
        border: 2px solid #e0e6ed;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        color: #5f6368;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }
    
    .btn-google:hover {
        background: #f8f9fa;
        border-color: #d0d0d0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .btn-google:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .google-icon {
        width: 22px;
        height: 22px;
    }
    
    .login-footer {
        margin-top: 30px;
        text-align: center;
    }
    
    .copyright {
        color: #95a5a6;
        font-size: 0.85rem;
        margin: 0;
    }
    
    @media (max-width: 500px) {
    .login-box {
        padding: 40px 25px;
    }
    
    .login-header h1 {
        font-size: 1.5rem;
    }
    
    .login-logo-image {
        width: 220px;
        height: auto;
        max-height: 150px;
    }
}
    </style>

    <script>
    function toggleLoginPassword() {
        const passwordField = document.getElementById('password');
        const icon = document.getElementById('passwordIcon');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    // Load saved theme
    window.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-theme');
        }
    });
    </script>
    
    <script type="module" src="js/main.js"></script>
</body>
</html>