<?php
/**
 * Configuration File - CORRECTED (No Syntax Errors)
 */

// ============================================
// STEP 1: Performance Settings (FIRST!)
// ============================================
define('ENABLE_CACHE', true);
define('CACHE_TIME', 300);

// ============================================
// STEP 2: Database Configuration
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'thesis_management');

// Application Configuration
define('SITE_NAME', 'Academic Thesis Management System');
define('SITE_URL', 'http://localhost/thesis_management');
define('UPLOAD_PATH', __DIR__ . '/uploads/theses/');
define('PROFILE_UPLOAD_PATH', __DIR__ . '/uploads/profiles/');
define('MAX_FILE_SIZE', 52428800);
define('ALLOWED_FILE_TYPES', ['pdf', 'docx', 'doc']);

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'reymart.xuang@gmail.com');
define('SMTP_PASS', 'wfxp mycd aijz iolr');
define('FROM_EMAIL', 'reymart.xuang@gmail.com');
define('FROM_NAME', 'Thesis Management System');

// Activation Settings
define('ACTIVATION_EXPIRY_HOURS', 24);

// Session Configuration
// Only change session settings when a session is not already active to avoid warnings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 7200);
    session_set_cookie_params(7200);
}

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// STEP 3: Database Connection
// ============================================
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

   // $conn->query("SET SESSION query_cache_type = ON");
   // $conn->query("SET SESSION query_cache_size = 1048576");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// ============================================
// STEP 4: Create upload directories
// ============================================
$directories = [UPLOAD_PATH, PROFILE_UPLOAD_PATH];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ============================================
// STEP 5: Load PHPMailer
// ============================================
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ============================================
// STEP 6: Initialize Cache System
// ============================================
if (ENABLE_CACHE) {
    require_once __DIR__ . '/includes/cache.php';
    $cache = new SimpleCache();
} else {
    // Dummy cache object
    $cache = new stdClass();
}

// ============================================
// Email Functions (existing code remains)
// ============================================
function sendActivationEmail($to, $firstName, $activationToken) {
    // PHPMailer classes already loaded above
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to, $firstName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Activate Your Account - ' . SITE_NAME;
        
        $activationLink = SITE_URL . "/activate.php?token=" . $activationToken;
        
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    color: white; 
                    padding: 30px; 
                    text-align: center; 
                    border-radius: 10px 10px 0 0; 
                }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { 
                    display: inline-block; 
                    padding: 15px 30px; 
                    background: #3498db; 
                    color: white !important; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 20px 0; 
                }
                .link-box {
                    background: #e9ecef;
                    padding: 15px;
                    word-break: break-all;
                    font-size: 12px;
                    border-radius: 5px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎓 Welcome to ' . SITE_NAME . '!</h1>
                </div>
                <div class="content">
                    <h2>Hello, ' . htmlspecialchars($firstName) . '!</h2>
                    <p>Your account has been created. Click the button below to activate your account and set your password:</p>
                    
                    <div style="text-align: center;">
                        <a href="' . $activationLink . '" class="button">✅ Activate My Account</a>
                    </div>
                    
                    <p><strong>⏰ This link expires in 24 hours.</strong></p>
                    
                    <p>If the button doesn\'t work, copy this link:</p>
                    <div class="link-box">' . $activationLink . '</div>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send Password Change Email
 */
function sendPasswordChangeEmail($to, $firstName) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to, $firstName);
        
        $mail->isHTML(true);
        $mail->Subject = 'Password Changed - ' . SITE_NAME;
        $mail->Body = '
        <html>
        <body style="font-family: Arial, sans-serif;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2>🔐 Password Changed</h2>
                <p>Hello, ' . htmlspecialchars($firstName) . '!</p>
                <p>Your password has been successfully changed.</p>
                <p><strong>Date:</strong> ' . date('F d, Y g:i A') . '</p>
                <p>If you did not make this change, contact the administrator immediately.</p>
            </div>
        </body>
        </html>
        ';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }

// Initialize Cache
    if (ENABLE_CACHE) {
        require_once __DIR__ . '/includes/cache.php';
        $cache = new SimpleCache();
    } else {
        // Dummy cache object if disabled
        $cache = new stdClass();
        $cache->get = function($key) { return null; };
        $cache->set = function($key, $content, $time = null) { return false; };
        $cache->delete = function($key) { return false; };
        $cache->clear = function() { return false; };
    }
}

/**
 * Send Feedback Notification Email
 */
function sendFeedbackEmail($to, $studentName, $thesisTitle, $reviewerName) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to, $studentName);
        
        $mail->isHTML(true);
        $mail->Subject = 'New Feedback on Your Thesis - ' . SITE_NAME;
        
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    color: white; 
                    padding: 30px; 
                    text-align: center; 
                    border-radius: 10px 10px 0 0; 
                }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { 
                    display: inline-block; 
                    padding: 15px 30px; 
                    background: #3498db; 
                    color: white !important; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 20px 0; 
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📝 New Feedback Received!</h1>
                </div>
                <div class="content">
                    <h2>Hello, ' . htmlspecialchars($studentName) . '!</h2>
                    <p>Your thesis titled <strong>"' . htmlspecialchars($thesisTitle) . '"</strong> has received new feedback from <strong>' . htmlspecialchars($reviewerName) . '</strong>.</p>
                    
                    <div style="text-align: center;">
                        <a href="' . SITE_URL . '/index.php?page=revisions" class="button">📖 View Feedback</a>
                    </div>
                    
                    <p><strong>What to do next:</strong></p>
                    <ol>
                        <li>Login to your account</li>
                        <li>Go to "My Revisions" page</li>
                        <li>Review the feedback carefully</li>
                        <li>Submit a revision if needed</li>
                    </ol>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send Welcome Email to New Google Users
 */
function sendWelcomeEmail($to, $firstName) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to, $firstName);
        
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to ' . SITE_NAME . '!';
        
        $loginLink = SITE_URL . '/index.php?page=login';
        
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    color: white; 
                    padding: 30px; 
                    text-align: center; 
                    border-radius: 10px 10px 0 0; 
                }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { 
                    display: inline-block; 
                    padding: 15px 30px; 
                    background: #3498db; 
                    color: white !important; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 20px 0; 
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎓 Welcome to Thesis Management System!</h1>
                </div>
                <div class="content">
                    <h2>Hello, ' . htmlspecialchars($firstName) . '!</h2>
                    <p>Your account has been successfully created! You can now access all features of our thesis management system.</p>
                    
                    <div style="text-align: center;">
                        <a href="' . $loginLink . '" class="button">🚀 Login Now</a>
                    </div>
                    
                    <p><strong>Features available to you:</strong></p>
                    <ul>
                        <li>📄 Submit and manage your thesis</li>
                        <li>💬 Receive feedback from advisors</li>
                        <li>📊 Track your progress</li>
                        <li>🔔 Get notifications on updates</li>
                    </ul>
                    
                    <p>If you have any questions, feel free to contact your advisor or system administrator.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>