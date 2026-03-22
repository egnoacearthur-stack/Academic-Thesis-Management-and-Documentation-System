<?php
/**
 * Notifications Page - AUTO-READ VERSION (No Delete)
 */

requireLogin();

$userId = $_SESSION['user_id'];

// Auto-mark as read when viewing notification
if (isset($_GET['view'])) {
    $notifId = intval($_GET['view']);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notifId, $userId);
    $stmt->execute();
    
    // Redirect based on notification type
    $notifStmt = $conn->prepare("SELECT notification_type, related_id FROM notifications WHERE notification_id = ? AND user_id = ?");
    $notifStmt->bind_param("ii", $notifId, $userId);
    $notifStmt->execute();
    $notifData = $notifStmt->get_result()->fetch_assoc();
    
    if ($notifData) {
        // Redirect to relevant page based on type
        switch($notifData['notification_type']) {
            case 'submission':
                header('Location: index.php?page=reviews&id=' . $notifData['related_id']);
                exit;
                break;
            case 'feedback':
                header('Location: index.php?page=revisions&id=' . $notifData['related_id']);
                exit;
                break;
            case 'approval':
                header('Location: index.php?page=revisions&id=' . $notifData['related_id']);
                exit;
                break;
            case 'revision':
                header('Location: index.php?page=reviews&id=' . $notifData['related_id']);
                exit;
                break;
            default:
                header('Location: index.php?page=notifications');
                exit;
                break;
        }
        exit();
    }
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    header('Location: index.php?page=notifications');
    exit();
}

// Get all notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifications = $stmt->get_result();

// Get unread count
$unreadCount = getUnreadNotificationCount($conn, $userId);
?>

<div class="page-header">
    <h1><i class="fas fa-bell"></i> Notifications</h1>
</div>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2><i class="fas fa-inbox"></i> All Notifications (<?= $unreadCount ?> unread)</h2>
        <?php if ($unreadCount > 0): ?>
            <a href="index.php?page=notifications&mark_all_read=1" class="btn btn-sm btn-primary">
                <i class="fas fa-check-double"></i> Mark All as Read
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($notifications->num_rows > 0): ?>
            <div class="notifications-list">
                <?php while ($notif = $notifications->fetch_assoc()): ?>
                    <a href="index.php?page=notifications&view=<?= $notif['notification_id'] ?>" 
                       class="notification-item-link">
                        <div class="notification-item <?= $notif['is_read'] ? 'read' : 'unread' ?>">
                            <div class="notification-icon notification-<?= $notif['notification_type'] ?>">
                                <?php
                                $icons = [
                                    'submission' => 'fa-upload',
                                    'feedback' => 'fa-comment',
                                    'approval' => 'fa-check-circle',
                                    'revision' => 'fa-edit',
                                    'system' => 'fa-info-circle'
                                ];
                                ?>
                                <i class="fas <?= $icons[$notif['notification_type']] ?? 'fa-bell' ?>"></i>
                            </div>
                            <div class="notification-content">
                                <h4><?= htmlspecialchars($notif['title']) ?></h4>
                                <p><?= htmlspecialchars($notif['message']) ?></p>
                                <span class="notification-time">
                                    <i class="far fa-clock"></i> <?= timeAgo($notif['created_at']) ?>
                                </span>
                            </div>
                            <?php if (!$notif['is_read']): ?>
                                <div class="notification-badge">
                                    <i class="fas fa-circle"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="no-data">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; display: block; margin-bottom: 15px;"></i>
                No notifications yet
            </p>
        <?php endif; ?>
    </div>
</div>

<style>
.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.notification-item-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.notification-item {
    display: flex;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #dfe6e9;
    transition: all 0.3s;
    position: relative;
}

.notification-item.unread {
    background: #e3f2fd;
    border-left-color: #3498db;
}

.notification-item.read {
    opacity: 0.7;
}

.notification-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateX(5px);
}

.notification-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.notification-submission {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
}

.notification-feedback {
    background: rgba(22, 160, 133, 0.1);
    color: #16a085;
}

.notification-approval {
    background: rgba(39, 174, 96, 0.1);
    color: #27ae60;
}

.notification-revision {
    background: rgba(243, 156, 18, 0.1);
    color: #f39c12;
}

.notification-system {
    background: rgba(149, 165, 166, 0.1);
    color: #95a5a6;
}

.notification-content {
    flex: 1;
}

.notification-content h4 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 1.1rem;
}

.notification-content p {
    margin: 0 0 8px 0;
    color: #7f8c8d;
    line-height: 1.5;
}

.notification-time {
    font-size: 0.85rem;
    color: #95a5a6;
}

.notification-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    color: #3498db;
    font-size: 0.8rem;
}

/* DARK MODE */
body.dark-theme .notification-item {
    background: #0f3460 !important;
}

body.dark-theme .notification-item.unread {
    background: #1a2942 !important;
    border-left-color: #3498db;
}

body.dark-theme .notification-content h4 {
    color: #ecf0f1 !important;
}

body.dark-theme .notification-content p {
    color: #b0bec5 !important;
}

body.dark-theme .notification-time {
    color: #95a5a6 !important;
}

body.dark-theme .no-data {
    color: #7f8c8d !important;
}
</style>