<?php
ob_start();
/**
 * Main Index File - FINAL VERSION
 */

require_once 'config.php';
require_once 'functions.php';
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? null;
$userName = $_SESSION['user_name'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

$profilePicture = null;
if ($isLoggedIn) {
    $userStmt = $conn->prepare("SELECT profile_picture, full_name FROM users WHERE user_id = ?");
    
    if ($userStmt) {
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $result = $userStmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $userData = $result->fetch_assoc();
            $profilePicture = $userData['profile_picture'] ?? null;
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit();
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'notifications' && $isLoggedIn) {
    header('Content-Type: application/json');
    $notifications = getRecentNotifications($conn, $userId, 10);
    $notifArray = [];
    
    if ($notifications) {
        while ($notif = $notifications->fetch_assoc()) {
            $notifArray[] = $notif;
        }
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifArray,
        'unread_count' => getUnreadNotificationCount($conn, $userId)
    ]);
    exit();
}

if (isset($_GET['mark_read']) && $isLoggedIn) {
    $notifId = intval($_GET['mark_read']);
    markNotificationRead($conn, $notifId, $userId);
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

$page = $_GET['page'] ?? ($isLoggedIn ? 'dashboard' : 'login');

if (!$isLoggedIn && $page !== 'login') {
    $page = 'login';
}

$userInitials = $userName ? getUserInitials($userName) : 'U';
$avatarColor = $userName ? getAvatarColor($userName) : '#3498db';

$unreadNotifCount = 0;
if ($isLoggedIn) {
    $unreadNotifCount = getUnreadNotificationCount($conn, $userId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#667eea">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <title><?= $page === 'login' ? 'Login - ' : '' ?><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body<?= $page === 'login' ? ' class="login-page"' : '' ?>>
    <?php if ($isLoggedIn && $page !== 'login'): ?>
        <header class="main-header">
            <div class="container">
                <div class="header-content">
                    <div class="logo">
                        <?php 
                        $customLogo = 'uploads/profiles/logo.png';
                        if (file_exists($customLogo)): 
                        ?>
                            <img src="<?= $customLogo ?>" alt="TMS Logo" class="logo-image">
                        <?php else: ?>
                            <div class="logo-icon">TMS</div>
                        <?php endif; ?>
                        <div class="logo-text">
                            <h1>Thesis Management System</h1>
                        </div>
                    </div>
                    
                    <nav class="main-nav">
                        <a href="index.php?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        
                        <?php if ($userRole === 'student'): ?>
                            <a href="index.php?page=submission" class="<?= $page === 'submission' ? 'active' : '' ?>">
                                <i class="fas fa-upload"></i> Submit Thesis
                            </a>
                            <a href="index.php?page=revisions" class="<?= $page === 'revisions' ? 'active' : '' ?>">
                                <i class="fas fa-history"></i> My Revisions
                            </a>
                            <a href="index.php?page=repository" class="<?= $page === 'repository' ? 'active' : '' ?>">
                                <i class="fas fa-book"></i> Approved Theses
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($userRole === 'advisor' || $userRole === 'panelist'): ?>
                            <a href="index.php?page=reviews" class="<?= $page === 'reviews' ? 'active' : '' ?>">
                                <i class="fas fa-tasks"></i> Reviews
                            </a>
                            <a href="index.php?page=feedback" class="<?= $page === 'feedback' ? 'active' : '' ?>">
                                <i class="fas fa-comments"></i> Feedback
                            </a>
                            <a href="index.php?page=repository" class="<?= $page === 'repository' ? 'active' : '' ?>">
                                <i class="fas fa-book"></i> Approved Theses
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($userRole === 'admin'): ?>
                            <a href="index.php?page=users" class="<?= $page === 'users' ? 'active' : '' ?>">
                                <i class="fas fa-users"></i> Users
                            </a>
                            <a href="index.php?page=repository" class="<?= $page === 'repository' ? 'active' : '' ?>">
                                <i class="fas fa-archive"></i> Repository
                            </a>
                        <?php endif; ?>
                        <?php if ($userRole === 'admin' || $userRole === 'advisor'): ?>
                            <a href="index.php?page=analytics" class="<?= $page === 'analytics' ? 'active' : '' ?>">
                                <i class="fas fa-chart-line"></i> Analytics
                            </a>
                        <?php endif; ?>
                    </nav>
                    
                    <div class="user-menu-container">
                        <div class="user-profile-menu">
                            <div class="user-avatar-wrapper">
                                <?php if ($profilePicture && file_exists($profilePicture)): ?>
                                    <img src="<?= $profilePicture ?>?v=<?= time() ?>" class="user-avatar" alt="Profile">
                                <?php else: ?>
                                    <div class="user-avatar" style="background-color: <?= $avatarColor ?>">
                                        <?= $userInitials ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($unreadNotifCount > 0): ?>
                                    <span class="avatar-notification-dot"></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="user-dropdown" id="userDropdown">
                                <div class="user-dropdown-header">
                                    <?php if ($profilePicture && file_exists($profilePicture)): ?>
                                        <img src="<?= $profilePicture ?>?v=<?= time() ?>" class="user-avatar-large" alt="Profile">
                                    <?php else: ?>
                                        <div class="user-avatar-large" style="background-color: <?= $avatarColor ?>">
                                            <?= $userInitials ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="user-info">
                                        <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                                        <div class="user-role"><?= ucfirst($userRole) ?></div>
                                    </div>
                                </div>
                                
                                <div class="user-dropdown-menu">
                                    <a href="index.php?page=profile" class="dropdown-item">
                                        <i class="fas fa-user"></i> Profile
                                    </a>
                                    <a href="index.php?page=settings" class="dropdown-item">
                                        <i class="fas fa-cog"></i> Settings
                                    </a>
                                    <a href="index.php?page=notifications" class="dropdown-item">
                                        <i class="fas fa-bell"></i> Notifications
                                        <?php if ($unreadNotifCount > 0): ?>
                                            <span class="dropdown-badge"><?= $unreadNotifCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="javascript:void(0);" class="dropdown-item" id="themeToggleBtn">
                                        <i class="fas fa-moon" id="themeIcon"></i> <span id="themeText">Dark Mode</span>
                                    </a>
                                    <a href="index.php?page=change-password" class="dropdown-item">
                                        <i class="fas fa-key"></i> Change Password
                                    </a>
                                    <hr class="dropdown-divider">
                                    <a href="index.php?action=logout" class="dropdown-item logout-item">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="container">
                <?php
                $pageFile = 'pages/' . $page . '.php';
                if (file_exists($pageFile)) {
                    include $pageFile;
                } else {
                    echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Page not found.</div>';
                }
                ?>
            </div>
        </main>

        <footer class="main-footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> Academic Thesis Management System. All rights reserved.</p>
            </div>
        </footer>

    <?php else: ?>
        <?php include 'pages/login.php'; ?>
    <?php endif; ?>    
    <script src="js/script.js"></script>
    <script src="js/performance.js"></script>
    <script>
        // ✅ FIXED: Add to index.php <script> section
        document.addEventListener('DOMContentLoaded', function() {
            const avatarWrapper = document.querySelector('.user-avatar-wrapper');
            const dropdown = document.getElementById('userDropdown');
            const notificationDot = document.querySelector('.avatar-notification-dot');
            const notificationLink = document.querySelector('a[href*="page=notifications"]');
            const themeToggle = document.getElementById('themeToggleBtn');
            
            // Handle dropdown toggle
            if (avatarWrapper && dropdown) {
                avatarWrapper.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdown.classList.toggle('show');
                    
                    // ✅ Hide red dot when dropdown opens (like Facebook Messenger)
                    if (dropdown.classList.contains('show') && notificationDot) {
                        notificationDot.classList.add('hidden');
                    }
                });
                
                dropdown.addEventListener('click', function(e) {
                    if (e.target.tagName === 'A' && e.target.id !== 'themeToggleBtn') {
                        return;
                    }
                    e.stopPropagation();
                });
                
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.user-profile-menu')) {
                        dropdown.classList.remove('show');
                    }
                });
            }
            
            // ✅ Mark all notifications as seen when clicking notification link
            if (notificationLink && notificationDot) {
                notificationLink.addEventListener('click', function() {
                    notificationDot.classList.add('hidden');
                    
                    // Mark as read via AJAX
                    fetch('index.php?mark_all_seen=1', {
                        method: 'GET',
                        credentials: 'same-origin'
                    });
                });
            }
            
            // Theme toggle
            if (themeToggle) {
                themeToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleTheme();
                });
            }
            
            // Load saved theme
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
                if (document.getElementById('themeIcon')) {
                    document.getElementById('themeIcon').className = 'fas fa-sun';
                    document.getElementById('themeText').textContent = 'Light Mode';
                }
            }
        });

        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            const icon = document.getElementById('themeIcon');
            const text = document.getElementById('themeText');
            
            if (document.body.classList.contains('dark-theme')) {
                icon.className = 'fas fa-sun';
                text.textContent = 'Light Mode';
                localStorage.setItem('theme', 'dark');
            } else {
                icon.className = 'fas fa-moon';
                text.textContent = 'Dark Mode';
                localStorage.setItem('theme', 'light');
            }
        }
    </script>
</body>
</html>