<?php
/**
 * Google Authentication Handler 
 */

// Start output buffering to prevent any accidental output
ob_start();
// Suppress display of errors in responses and enable logging
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Ensure response will be JSON even on fatal errors
header('Content-Type: application/json');
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Clean any partial output and return a JSON error
        if (ob_get_length()) ob_clean();
        // Write to project log file as well for easier debugging
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/google_auth.log';
        $entry = date('[Y-m-d H:i:s]') . " FATAL: " . print_r($err, true) . PHP_EOL;
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        error_log("Fatal error in google_auth.php: " . print_r($err, true));
        echo json_encode(['success' => false, 'message' => 'Server error (fatal). Please check server logs.']);
        // Ensure output is sent
        flush();
    }
});

session_start();
require_once 'config.php';
require_once 'functions.php';

// Clear any buffered output from included files before sending JSON
if (ob_get_length()) ob_clean();
ini_set('log_errors', 1);

// Log function for debugging
function logDebug($message, $data = null) {
    $logMessage = date('[Y-m-d H:i:s] ') . $message;
    if ($data !== null) {
        $logMessage .= ': ' . print_r($data, true);
    }
    error_log($logMessage);
}

logDebug('Google Auth Request Received');

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    logDebug('Raw Input', $input);
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logDebug('JSON Parse Error', json_last_error_msg());
        echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit();
    }
    
    if (!$data || !isset($data['email'])) {
        logDebug('Missing required data', $data);
        echo json_encode(['success' => false, 'message' => 'Missing email in request']);
        exit();
    }
    
    $email = $data['email'];
    $name = $data['name'] ?? '';
    $photoURL = $data['photoURL'] ?? '';
    $uid = $data['uid'] ?? '';
    
    logDebug('Processing Google Auth', [
        'email' => $email,
        'name' => $name,
        'uid' => $uid
    ]);
    
    // Check database connection
    if (!$conn) {
        logDebug('Database connection failed');
        echo json_encode(['success' => false, 'message' => 'Database connection error']);
        exit();
    }
    
    // First, get the actual columns in the users table
    $columnsResult = $conn->query("SHOW COLUMNS FROM users");
    $availableColumns = [];
    while ($row = $columnsResult->fetch_assoc()) {
        $availableColumns[] = $row['Field'];
    }
    logDebug('Available columns', $availableColumns);
    
    // Build SELECT query based on available columns
    $selectColumns = ['user_id', 'full_name', 'role', 'status'];
    $selectQuery = "SELECT " . implode(', ', $selectColumns) . " FROM users WHERE email = ?";
    
    // Check if user exists
    $stmt = $conn->prepare($selectQuery);
    if (!$stmt) {
        logDebug('Prepare failed', $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database query error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Existing user
        $user = $result->fetch_assoc();
        logDebug('Existing user found', ['user_id' => $user['user_id'], 'role' => $user['role']]);
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            logDebug('User account deactivated', $user['user_id']);
            echo json_encode(['success' => false, 'message' => 'Your account has been deactivated']);
            exit();
        }
        
        // Check if user has a role (or has temporary 'pending' role)
        if (empty($user['role']) || $user['role'] === null || $user['role'] === 'pending') {
            // User exists but needs to choose role
            logDebug('User needs role', $user['user_id']);
            $_SESSION['temp_user_id'] = $user['user_id'];
            $_SESSION['temp_email'] = $email;
            $_SESSION['temp_name'] = $name;
            echo json_encode(['success' => true, 'needsRole' => true]);
            exit();
        }
        
        // Login existing user
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        
        logDebug('User logged in successfully', [
            'user_id' => $user['user_id'],
            'role' => $user['role']
        ]);
        
        // Update last login
        $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $updateStmt->bind_param("i", $user['user_id']);
        $updateStmt->execute();
        
        // Log activity
        logActivity($conn, $user['user_id'], 'google_login');
        
        echo json_encode(['success' => true, 'needsRole' => false]);
        
    } else {
        // New user - create account
        logDebug('Creating new user', ['email' => $email]);
        
        // Generate unique username from email
        $username = explode('@', $email)[0] . '_' . substr(md5($uid), 0, 4);
        
        logDebug('Generated username', $username);
        
        // Check which columns are available for INSERT
        $hasGoogleUid = in_array('google_uid', $availableColumns);
        $hasProfilePicture = in_array('profile_picture', $availableColumns);
        
        $nameParts = explode(' ', trim($name));
        $firstName = $nameParts[0] ?? '';
        $lastName = isset($nameParts[1]) ? end($nameParts) : '';
        $middleName = count($nameParts) > 2 ? $nameParts[1] : '';

        $insertColumns = ['username', 'email', 'first_name', 'middle_name', 'last_name', 'full_name', 'role', 'status'];
        $insertValues = ['?', '?', '?', '?', '?', '?', '?', "'active'"];
        $bindTypes = 'sssssss';
        $tempRole = 'pending';
        $bindParams = [$username, $email, $firstName, $middleName, $lastName, $name, $tempRole];
        
        if ($hasGoogleUid) {
            $insertColumns[] = 'google_uid';
            $insertValues[] = '?';
            $bindTypes .= 's';
            $bindParams[] = $uid;
        }
        
        if ($hasProfilePicture) {
            $insertColumns[] = 'profile_picture';
            $insertValues[] = '?';
            $bindTypes .= 's';
            $bindParams[] = $photoURL;
        }
        
        $insertQuery = "INSERT INTO users (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
        logDebug('Insert query', $insertQuery);
        
        $stmt = $conn->prepare($insertQuery);
        
        if (!$stmt) {
            logDebug('Prepare insert failed', $conn->error);
            echo json_encode(['success' => false, 'message' => 'Failed to prepare user creation: ' . $conn->error]);
            exit();
        }
        
        // Bind parameters dynamically
        $stmt->bind_param($bindTypes, ...$bindParams);
        
        if ($stmt->execute()) {
            $newUserId = $conn->insert_id;
            logDebug('New user created', ['user_id' => $newUserId]);
            
            // Store temporary session data
            $_SESSION['temp_user_id'] = $newUserId;
            $_SESSION['temp_email'] = $email;
            $_SESSION['temp_name'] = $name;
            
            // Log activity
            logActivity($conn, $newUserId, 'google_register');
            sendWelcomeEmail($email, explode(' ', $name)[0]);
            sendWelcomeEmail($email, $firstName);
            
            echo json_encode(['success' => true, 'needsRole' => true]);
        } else {
            logDebug('Failed to create user', $stmt->error);
            
            // Check if it's a duplicate entry error
            if (strpos($stmt->error, 'Duplicate entry') !== false) {
                echo json_encode(['success' => false, 'message' => 'An account with this email already exists. Please use traditional login.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create account: ' . $stmt->error]);
            }
        }
    }
    
} catch (Exception $e) {
    // Log to both PHP error log and project log file
    logDebug('Exception caught', $e->getMessage());
    error_log("Google Auth Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {@mkdir($logDir, 0755, true);} 
    $logFile = $logDir . '/google_auth.log';
    $entry = date('[Y-m-d H:i:s]') . " EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . PHP_EOL;
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    echo json_encode(['success' => false, 'message' => 'Server error (fatal). Please check server logs.']);
}

// End output buffering and send response
ob_end_flush();
?>