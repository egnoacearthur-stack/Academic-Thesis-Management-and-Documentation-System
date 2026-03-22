<?php
/**
 * Users Management Page - FIXED DATABASE QUERY ERROR
 */

requireRole('admin');

$error = '';
$success = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $firstName = sanitize($_POST['first_name'] ?? '');
    $middleName = sanitize($_POST['middle_name'] ?? '');
    $suffix = sanitize($_POST['suffix'] ?? '');
    $role = sanitize($_POST['role'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    
    $fullName = trim($lastName . ', ' . $firstName . ' ' . $middleName . ' ' . $suffix);
    
    if (empty($username) || empty($email) || empty($lastName) || empty($firstName) || empty($role)) {
        $error = 'Please fill in all required fields';
    } else {
        $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $checkStmt->bind_param("ss", $username, $email);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            $error = 'Username or email already exists';
        } else {
            $activationToken = bin2hex(random_bytes(32));
            $tokenExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $tempPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $isActivated = ($role === 'admin') ? 1 : 0;
            
            $stmt = $conn->prepare("
                INSERT INTO users (username, password, email, first_name, middle_name, last_name, suffix, full_name, role, department, phone, activation_token, token_expiry, is_activated, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->bind_param("sssssssssssssi", $username, $tempPassword, $email, $firstName, $middleName, $lastName, $suffix, $fullName, $role, $department, $phone, $activationToken, $tokenExpiry, $isActivated);
            
            if ($stmt->execute()) {
                $newUserId = $conn->insert_id;
                
                if ($role !== 'admin') {
                    $activationLink = SITE_URL . "/activate.php?token=" . $activationToken;
                    $success = "User created! Activation link: <a href='$activationLink' target='_blank'>Click here</a> (Valid for 24 hours)";
                } else {
                    $success = "Admin user created successfully! No activation required.";
                }
                
                logActivity($conn, $_SESSION['user_id'], 'create_user', 'users', $newUserId, $fullName);
            } else {
                $error = 'Failed to create user: ' . $conn->error;
            }
        }
    }
}

// Handle user status toggle
if (isset($_GET['toggle_status'])) {
    $userId = intval($_GET['toggle_status']);
    $stmt = $conn->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    logActivity($conn, $_SESSION['user_id'], 'toggle_user_status', 'users', $userId);
    
    echo '<script>window.location.href = "index.php?page=users";</script>';
    exit();
}

// Handle resend activation
if (isset($_GET['resend_activation'])) {
    $userId = intval($_GET['resend_activation']);
    
    $stmt = $conn->prepare("SELECT email, first_name FROM users WHERE user_id = ? AND is_activated = FALSE");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    
    if ($userData) {
        $newToken = bin2hex(random_bytes(32));
        $newExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $updateStmt = $conn->prepare("UPDATE users SET activation_token = ?, token_expiry = ? WHERE user_id = ?");
        $updateStmt->bind_param("ssi", $newToken, $newExpiry, $userId);
        $updateStmt->execute();
        
        $activationLink = SITE_URL . "/activate.php?token=" . $newToken;
        $success = "New activation link generated: <a href='$activationLink' target='_blank'>$activationLink</a>";
    } else {
        $error = 'User already activated or not found';
    }
    
    echo '<script>window.location.href = "index.php?page=users&msg=' . urlencode($success ?: $error) . '";</script>';
    exit();
}

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $userId = intval($_GET['delete_user']);
    
    if ($userId == $_SESSION['user_id']) {
        $error = 'You cannot delete your own account';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $success = 'User deleted successfully';
            logActivity($conn, $_SESSION['user_id'], 'delete_user', 'users', $userId);
        } else {
            $error = 'Failed to delete user';
        }
    }
}

// Get message from URL
if (isset($_GET['msg'])) {
    $success = sanitize($_GET['msg']);
}

// ✅ FIXED: Get all users with proper error handling
$usersQuery = "
    SELECT u.*, 
    (SELECT COUNT(*) FROM thesis_submissions WHERE student_id = u.user_id) as submission_count,
    CASE 
        WHEN u.is_activated = FALSE AND u.token_expiry < NOW() THEN 'expired'
        WHEN u.is_activated = FALSE THEN 'pending'
        ELSE 'activated'
    END as activation_status
    FROM users u 
    ORDER BY u.created_at DESC
";

$users = $conn->query($usersQuery);

// ✅ FIXED: Check if query failed
if ($users === false) {
    $error = 'Database error: ' . $conn->error;
    $users = []; // Empty array to prevent further errors
} else {
    // Convert to array for safe iteration
    $usersArray = [];
    while ($row = $users->fetch_assoc()) {
        $usersArray[] = $row;
    }
}

// Get statistics
$stats = [];
$statsQuery = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total'] = $statsQuery ? $statsQuery->fetch_assoc()['count'] : 0;

$statsQuery = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['students'] = $statsQuery ? $statsQuery->fetch_assoc()['count'] : 0;

$statsQuery = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'advisor'");
$stats['advisors'] = $statsQuery ? $statsQuery->fetch_assoc()['count'] : 0;

$statsQuery = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_activated = FALSE");
$stats['pending'] = $statsQuery ? $statsQuery->fetch_assoc()['count'] : 0;
?>

<div class="page-header">
    <h1><i class="fas fa-users"></i> User Management</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= $success ?>
    </div>
<?php endif; ?>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <h3><?= $stats['total'] ?></h3>
            <p>Total Users</p>
        </div>
    </div>
    
    <div class="stat-card stat-info">
        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-content">
            <h3><?= $stats['students'] ?></h3>
            <p>Students</p>
        </div>
    </div>
    
    <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-content">
            <h3><?= $stats['advisors'] ?></h3>
            <p>Advisors</p>
        </div>
    </div>
    
    <div class="stat-card stat-warning">
        <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
        <div class="stat-content">
            <h3><?= $stats['pending'] ?></h3>
            <p>Pending Activation</p>
        </div>
    </div>
</div>

<!-- Create New User -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-plus"></i> Create New User</h2>
    </div>
    <div class="card-body">
        <form method="POST" id="createUserForm">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group col-md-6">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required>
                </div>
                
                <div class="form-group col-md-4">
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required>
                </div>
                
                <div class="form-group col-md-3">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" class="form-control">
                </div>
                
                <div class="form-group col-md-1">
                    <label for="suffix">Suffix</label>
                    <input type="text" id="suffix" name="suffix" class="form-control" placeholder="Jr.">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="role">Role <span class="required">*</span></label>
                    <select id="role" name="role" class="form-control" required onchange="toggleDepartmentField()">
                        <option value="">Select Role</option>
                        <option value="student">Student</option>
                        <option value="advisor">Advisor</option>
                        <option value="panelist">Panelist</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                
                <div class="form-group col-md-4" id="departmentField">
                    <label for="department" id="departmentLabel">Department</label>
                    <input type="text" id="department" name="department" class="form-control" placeholder="e.g., Computer Science">
                </div>
                
                <div class="form-group col-md-4">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="form-control" placeholder="+63 XXX XXX XXXX">
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Activation link will be shown after creation. Admin accounts are activated automatically.
            </div>
            
            <button type="submit" name="create_user" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Create User
            </button>
        </form>
    </div>
</div>

<!-- Users List -->
<div class="card mt-4">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2><i class="fas fa-list"></i> All Users</h2>
        <a href="export.php?type=users" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Export
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department/Course</th>
                            <th>Status</th>
                            <th>Activation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersArray as $user): ?>
                            <tr>
                                <td><?= $user['user_id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($user['first_name']) ?> 
                                    <strong><?= htmlspecialchars($user['middle_name']) ?> 
                                    <strong><?= htmlspecialchars($user['last_name']) ?>
                                    <strong><?= htmlspecialchars($user['suffix']) ?>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><span class="badge badge-info"><?= ucfirst($user['role']) ?></span></td>
                                <td><?= htmlspecialchars($user['department'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge <?= $user['status'] === 'active' ? 'badge-success' : 'badge-secondary' ?>">
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge badge-success">✓ Admin</span>
                                    <?php elseif ($user['activation_status'] === 'activated'): ?>
                                        <span class="badge badge-success">✓ Activated</span>
                                    <?php elseif ($user['activation_status'] === 'expired'): ?>
                                        <span class="badge badge-danger">⚠ Expired</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">⏳ Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$user['is_activated'] && $user['role'] !== 'admin'): ?>
                                        <a href="index.php?page=users&resend_activation=<?= $user['user_id'] ?>" 
                                           class="btn btn-sm btn-info" title="Resend activation">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="index.php?page=users&toggle_status=<?= $user['user_id'] ?>" 
                                       class="btn btn-sm btn-warning" title="Toggle Status">
                                        <i class="fas fa-toggle-on"></i>
                                    </a>
                                    
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <a href="index.php?page=users&delete_user=<?= $user['user_id'] ?>" 
                                           class="btn btn-sm btn-danger" title="Delete User"
                                           onclick="return confirm('Delete this user?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>                            
        </div>
    </div>
</div>

<script>
function toggleDepartmentField() {
    const role = document.getElementById('role').value;
    const deptLabel = document.getElementById('departmentLabel');
    const deptInput = document.getElementById('department');
    
    if (role === 'student') {
        deptLabel.innerHTML = 'Course, Grade & Section <span class="required">*</span>';
        deptInput.placeholder = 'e.g., BSCS 4-A';
        deptInput.required = true;
    } else {
        deptLabel.textContent = 'Department';
        deptInput.placeholder = 'e.g., Computer Science';
        deptInput.required = false;
    }
}
</script>