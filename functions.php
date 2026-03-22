<?php
/**
 * Helper Functions - UPDATED
 * Academic Thesis Management System
 */

$stmtCache = [];

function getCachedStmt($conn, $sql) {
    global $stmtCache;
    
    if (!isset($stmtCache[$sql])) {
        $stmtCache[$sql] = $conn->prepare($sql);
    }
    
    return $stmtCache[$sql];
}

// Cross-Site Request Forgery attacks protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input data
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Get unread notification count
function getUnreadNotificationCount($conn, $userId) {
    if (!$conn || !$userId) return 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['count'];
    }
    return 0;
}

// Get recent notifications
function getRecentNotifications($conn, $userId, $limit = 5) {
    if (!$conn || !$userId) return null;
    
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Create notification
function createNotification($conn, $userId, $title, $message, $type, $relatedId = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, notification_type, related_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $userId, $title, $message, $type, $relatedId);
    return $stmt->execute();
}

// Mark notification as read
function markNotificationRead($conn, $notificationId, $userId) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notificationId, $userId);
    return $stmt->execute();
}

// Log activity
function logActivity($conn, $userId, $action, $entityType = null, $entityId = null, $details = null) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $userId, $action, $entityType, $entityId, $details, $ipAddress);
    return $stmt->execute();
}

// Upload file
function uploadFile($file, $submissionId) {
    $allowedTypes = ALLOWED_FILE_TYPES;
    $maxSize = MAX_FILE_SIZE;
    
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileError = $file['error'];
    
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if ($fileError !== 0) {
        return ['success' => false, 'message' => 'Error uploading file'];
    }
    
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, and DOCX are allowed'];
    }
    
    if ($fileSize > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit of 50MB'];
    }
    
    $newFileName = 'thesis_' . $submissionId . '_' . time() . '.' . $fileExt;
    $destination = UPLOAD_PATH . $newFileName;
    
    if (move_uploaded_file($fileTmp, $destination)) {
        return [
            'success' => true,
            'file_name' => $fileName,
            'file_path' => $destination,
            'file_size' => $fileSize
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

// Format date
function formatDate($date) {
    if (!$date) return 'N/A';
    return date('F d, Y', strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    if (!$datetime) return 'N/A';
    return date('F d, Y g:i A', strtotime($datetime));
}

// Time ago function
function timeAgo($datetime) {
    if (!$datetime) return 'N/A';
    
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return formatDate($datetime);
}

// Get status badge class
function getStatusBadge($status) {
    $badges = [
        'draft' => 'badge-secondary',
        'submitted' => 'badge-info',
        'under_review' => 'badge-warning',
        'revision_requested' => 'badge-danger',
        'approved' => 'badge-success',
        'rejected' => 'badge-dark'
    ];
    return $badges[$status] ?? 'badge-secondary';
}

// Get user submissions
function getUserSubmissions($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT ts.*, 
            CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as advisor_name
        FROM thesis_submissions ts 
        LEFT JOIN users u ON ts.advisor_id = u.user_id 
        WHERE ts.student_id = ? 
        ORDER BY ts.submission_date DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result();
}

// Get advisor submissions
function getAdvisorSubmissions($conn, $advisorId) {
    $stmt = $conn->prepare("
        SELECT ts.*, 
            CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as student_name, 
            u.email as student_email
        FROM thesis_submissions ts 
        JOIN users u ON ts.student_id = u.user_id 
        WHERE ts.advisor_id = ? 
        ORDER BY ts.submission_date DESC
    ");
    $stmt->bind_param("i", $advisorId);
    $stmt->execute();
    return $stmt->get_result();
}

// Get panelist assignments
function getPanelistAssignments($conn, $panelistId) {
    $stmt = $conn->prepare("
        SELECT ts.*, 
            CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as student_name, 
            pa.assigned_date, pa.status as assignment_status 
        FROM thesis_submissions ts 
        JOIN panelist_assignments pa ON ts.submission_id = pa.submission_id 
        JOIN users u ON ts.student_id = u.user_id 
        WHERE pa.panelist_id = ? 
        ORDER BY pa.assigned_date DESC
    ");
    $stmt->bind_param("i", $panelistId);
    $stmt->execute();
    return $stmt->get_result();
}

// Get feedback for submission
function getFeedback($conn, $submissionId) {
    $stmt = $conn->prepare("
        SELECT f.*, CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as reviewer_name
        FROM feedback f 
        JOIN users u ON f.reviewer_id = u.user_id 
        WHERE f.submission_id = ? 
        ORDER BY f.created_at DESC
    ");
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    return $stmt->get_result();
}

// Get revision history
function getRevisionHistory($conn, $submissionId) {
    $stmt = $conn->prepare("
        SELECT rh.*, CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as uploaded_by_name
        FROM revision_history rh 
        JOIN users u ON rh.uploaded_by = u.user_id 
        WHERE rh.submission_id = ? 
        ORDER BY rh.version_number DESC
    ");
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    return $stmt->get_result();
}

// Get dashboard statistics
function getDashboardStats($conn, $userId, $role) {
    global $cache;
    
    $cacheKey = "dashboard_stats_{$userId}_{$role}";

    $cacheEnabled = defined('ENABLE_CACHE') && ENABLE_CACHE && isset($cache);
    if ($cacheEnabled) {
        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    $stats = [];
    
    if ($role === 'student') {
        $stmt = getCachedStmt($conn, "SELECT COUNT(*) as count FROM thesis_submissions WHERE student_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['total_submissions'] = $stmt->get_result()->fetch_assoc()['count'];
        
        $stmt = getCachedStmt($conn, "SELECT COUNT(*) as count FROM thesis_submissions WHERE student_id = ? AND status IN ('submitted', 'under_review')");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['pending_feedback'] = $stmt->get_result()->fetch_assoc()['count'];
        
        $stmt = getCachedStmt($conn, "SELECT COUNT(*) as count FROM thesis_submissions WHERE student_id = ? AND status = 'approved'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['approved'] = $stmt->get_result()->fetch_assoc()['count'];
        
        $stmt = getCachedStmt($conn, "SELECT COUNT(*) as count FROM thesis_submissions WHERE student_id = ? AND status = 'revision_requested'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['revision_requested'] = $stmt->get_result()->fetch_assoc()['count'];
        
    } elseif ($role === 'advisor') {
        $stmt = getCachedStmt($conn, "SELECT COUNT(DISTINCT student_id) as count FROM thesis_submissions WHERE advisor_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['total_advisees'] = $stmt->get_result()->fetch_assoc()['count'];
        
        $stmt = getCachedStmt($conn, "SELECT COUNT(*) as count FROM thesis_submissions WHERE advisor_id = ? AND status IN ('submitted', 'under_review')");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['pending_reviews'] = $stmt->get_result()->fetch_assoc()['count'];
        
        $stmt = getCachedStmt($conn, "SELECT COUNT(*) as count FROM thesis_submissions WHERE advisor_id = ? AND status = 'approved'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['approved_theses'] = $stmt->get_result()->fetch_assoc()['count'];
        
    } elseif ($role === 'panelist') {
        $stmt = getCachedStmt($conn, "SELECT COUNT(*) as count FROM panelist_assignments WHERE panelist_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['total_assignments'] = $stmt->get_result()->fetch_assoc()['count'];
        
        $stmt = getCachedStmt($conn, "SELECT COUNT(*) as count FROM panelist_assignments WHERE panelist_id = ? AND status = 'assigned'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['pending_reviews'] = $stmt->get_result()->fetch_assoc()['count'];
        
    } elseif ($role === 'admin') {
        $stmt = getCachedStmt($conn, "SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $stats['total_users'] = $stmt->get_result()->fetch_assoc()['count'];
        
        $stmt = getCachedStmt($conn, "SELECT COUNT(*) as count FROM thesis_submissions");
        $stmt->execute();
        $stats['total_submissions'] = $stmt->get_result()->fetch_assoc()['count'];
        
        $stmt = getCachedStmt($conn, "SELECT COUNT(*) as count FROM thesis_submissions WHERE status = 'approved'");
        $stmt->execute();
        $stats['approved_theses'] = $stmt->get_result()->fetch_assoc()['count'];
        
        $stmt = getCachedStmt($conn, "SELECT COUNT(*) as count FROM thesis_submissions WHERE status IN ('submitted', 'under_review')");
        $stmt->execute();
        $stats['pending_approvals'] = $stmt->get_result()->fetch_assoc()['count'];
    }
    
    if ($cacheEnabled) {
        $cache->set($cacheKey, $stats, 60); // Cache for 1 minute
    }
    
    return $stats;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php?page=login');
        exit();
    }
}

// Require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        die('<div style="padding: 50px; text-align: center;"><h2>Access Denied</h2><p>You do not have permission to access this page.</p><a href="index.php">Go to Dashboard</a></div>');
    }
}

// Get user initials for avatar
function getUserInitials($name) {
    $parts = explode(' ', $name);
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

// Get avatar color based on name
function getAvatarColor($name) {
    $colors = ['#3498db', '#e74c3c', '#f39c12', '#27ae60', '#9b59b6', '#16a085', '#d35400', '#c0392b'];
    $index = ord(strtolower($name[0])) % count($colors);
    return $colors[$index];
}

// Build full name from parts
function buildFullName($firstName, $middleName, $lastName, $suffix) {
    return trim($firstName . ' ' . $middleName . ' ' . $lastName . ' ' . $suffix);
}

// Format program display names (use short form like "BS in Computer Science")
function formatProgramName($program) {
    if (!$program) return '';
    $p = trim($program);
    // If already contains 'BS' or 'Bachelor' or 'Master' leave as-is
    if (stripos($p, 'bs') !== false || stripos($p, 'bachelor') !== false || stripos($p, 'master') !== false || stripos($p, 'ms') !== false) {
        return $p;
    }

    $mappings = [
        'Computer Science' => 'BS in Computer Science',
        'Information Technology' => 'BS in Information Technology',
        'Computer Engineering' => 'BS in Computer Engineering',
        'Business Administration' => 'BS in Business Administration'
    ];

    foreach ($mappings as $key => $label) {
        if (stripos($p, $key) !== false) return $label;
    }

    // Fallback: prefix with BS in
    return 'BS in ' . $p;
}

// Handle un-submit action (already in revisions.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unsubmit_thesis'])) {
    $submissionId = intval($_POST['submission_id']);
    
    $checkStmt = $conn->prepare("SELECT status FROM thesis_submissions WHERE submission_id = ? AND student_id = ?");
    $checkStmt->bind_param("ii", $submissionId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $subData = $result->fetch_assoc();
        
        if ($subData['status'] === 'submitted') {
            $updateStmt = $conn->prepare("UPDATE thesis_submissions SET status = 'draft' WHERE submission_id = ?");
            $updateStmt->bind_param("i", $submissionId);
            
            if ($updateStmt->execute()) {
                logActivity($conn, $userId, 'unsubmit_thesis', 'thesis_submission', $submissionId);
                $success = 'Thesis un-submitted successfully. Status changed to Draft.';
            }
        }
    }
}
?>

