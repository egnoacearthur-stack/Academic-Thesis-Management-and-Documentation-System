<?php
/**
 * Profile Page - Modern Gradient Card Layout
 * LEFT: My Profile (Purple) + Statistics (Dark) | RIGHT: Edit Profile Form (White)
 */

requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 5242880; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Invalid file type. Only JPG, PNG, and GIF are allowed';
        } elseif ($file['size'] > $maxSize) {
            $error = 'File size exceeds 5MB limit';
        } else {
            $uploadDir = 'uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
            $destination = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                if ($user['profile_picture'] && file_exists($user['profile_picture'])) {
                    unlink($user['profile_picture']);
                }
                
                $updateStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $updateStmt->bind_param("si", $destination, $userId);
                $updateStmt->execute();
                
                $success = 'Profile picture updated successfully';
                $user['profile_picture'] = $destination;
                
                logActivity($conn, $userId, 'update_profile_picture');
            } else {
                $error = 'Failed to upload file';
            }
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $bio = sanitize($_POST['bio']);
    $courseOrDept = sanitize($_POST['course_or_dept']);
    
    $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $checkStmt->bind_param("si", $email, $userId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        $error = 'Email is already in use by another account';
    } else {
        $updateStmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, department = ?, phone = ?, bio = ? WHERE user_id = ?");
        $updateStmt->bind_param("sssssi", $fullName, $email, $courseOrDept, $phone, $bio, $userId);
        
        if ($updateStmt->execute()) {
            $_SESSION['user_name'] = $fullName;
            $success = 'Profile updated successfully';
            
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            logActivity($conn, $userId, 'update_profile');
        } else {
            $error = 'Failed to update profile';
        }
    }
}

// Get user statistics
$stats = [];
if ($user['role'] === 'student') {
    $stats['Submissions'] = $conn->query("SELECT COUNT(*) as count FROM thesis_submissions WHERE student_id = $userId")->fetch_assoc()['count'];
    $stats['Approved'] = $conn->query("SELECT COUNT(*) as count FROM thesis_submissions WHERE student_id = $userId AND status = 'approved'")->fetch_assoc()['count'];
} elseif ($user['role'] === 'advisor') {
    $stats['Advisees'] = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM thesis_submissions WHERE advisor_id = $userId")->fetch_assoc()['count'];
    $stats['Feedbacks'] = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE reviewer_id = $userId")->fetch_assoc()['count'];
} elseif ($user['role'] === 'panelist') {
    $stats['Assignments'] = $conn->query("SELECT COUNT(*) as count FROM panelist_assignments WHERE panelist_id = $userId")->fetch_assoc()['count'];
    $stats['Reviews'] = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE reviewer_id = $userId")->fetch_assoc()['count'];
}

$userInitials = getUserInitials($user['full_name']);
$avatarColor = getAvatarColor($user['full_name']);
$profilePicture = $user['profile_picture'] ?? null;
?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= $success ?>
    </div>
<?php endif; ?>

<div class="profile-container">
    <!-- LEFT COLUMN - My Profile & Statistics -->
    <div>
        <!-- My Profile Card - Purple Gradient -->
        <div class="profile-card-left">
            <div style="text-align: center;">
                <div class="profile-picture-container">
                    <?php if ($profilePicture && file_exists($profilePicture)): ?>
                        <img src="<?= $profilePicture ?>" alt="Profile Picture" class="profile-picture">
                    <?php else: ?>
                        <div class="profile-avatar-large" style="background-color: <?= $avatarColor ?>">
                            <?= $userInitials ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="pictureForm">
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display: none;" onchange="document.getElementById('pictureForm').submit()">
                        <label for="profile_picture" class="upload-picture-btn">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="hidden" name="upload_picture" value="1">
                    </form>
                </div>

                <!-- User Info Display -->
                <div class="user-info-display">
                    <div class="user-display-name"><?= htmlspecialchars($user['full_name']) ?></div>
                    <div class="user-display-email"><?= htmlspecialchars($user['email']) ?></div>
                    <span class="user-display-badge"><?= ucfirst($user['role']) ?></span>
                </div>
                
                <!-- ✅ NEW: Bio Section - BELOW user-display-badge -->
                <?php if (!empty($user['bio'])): ?>
                <div class="user-bio-section">
                    <div class="user-bio-label">
                        <i class="fas fa-quote-left"></i> About Me
                    </div>
                    <div class="user-bio-text">
                        "<?= htmlspecialchars($user['bio']) ?>"
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- ✅ COMPRESSED: User Details Grid -->
                <div class="user-details-grid">
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-user"></i> Username:
                        </div>
                        <div class="detail-value"><?= htmlspecialchars($user['username']) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-building"></i> Department:
                        </div>
                        <div class="detail-value"><?= htmlspecialchars($user['department'] ?? 'Not set') ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-phone"></i> Phone:
                        </div>
                        <div class="detail-value"><?= htmlspecialchars($user['phone'] ?? 'Not set') ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-calendar-alt"></i> Member Since:
                        </div>
                        <div class="detail-value"><?= $user['created_at'] ? date('F d, Y', strtotime($user['created_at'])) : 'N/A' ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-clock"></i> Last Login:
                        </div>
                        <div class="detail-value"><?= $user['last_login'] ? date('F d, Y g:i A', strtotime($user['last_login'])) : 'Never' ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- My Statistics Card - Dark Gradient -->
        <?php if (!empty($stats)): ?>
        <div class="profile-card-stats">
            <h2 class="profile-card-title white">
                <i class="fas fa-chart-bar"></i> My Statistics
            </h2>
            
            <?php foreach ($stats as $label => $value): ?>
                <div class="stats-row">
                    <span class="stats-label"><?= $label ?>:</span>
                    <span class="stats-value"><?= $value ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- RIGHT COLUMN - Edit Profile Form -->
    <div class="profile-card-red">
        <h1 class="profile-card-title large">
            <i class="fas fa-edit"></i> Edit Profile
        </h1>

        <div class="form-section-profile">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="course_or_dept">
                        <?= $user['role'] === 'student' ? 'Course' : 'Department' ?>
                    </label>
                    <input type="text" id="course_or_dept" name="course_or_dept" class="form-control" 
                           value="<?= htmlspecialchars($user['department'] ?? '') ?>"
                           placeholder="<?= $user['role'] === 'student' ? 'e.g., BS Computer Science' : 'e.g., Computer Science Department' ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="form-control" 
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                           placeholder="+63 XXX XXX XXXX">
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio / About Me</label>
                    <textarea id="bio" name="bio" class="form-control" rows="4"
                              placeholder="Tell us something about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    <small class="form-text">Brief description about yourself (optional)</small>
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                    <small class="form-text">Username cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label>Account Status</label>
                    <input type="text" class="form-control" 
                           value="<?= ucfirst($user['status']) ?>" disabled>
                </div>
                
                <div class="profile-button-group">
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <a href="index.php?page=change-password" class="btn btn-warning">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>