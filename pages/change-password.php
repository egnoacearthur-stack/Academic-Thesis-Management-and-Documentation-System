<?php
/**
 * Change Password Page
 */

requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All fields are required';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect';
        } else {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $updateStmt->bind_param("si", $hashedPassword, $userId);
            
            if ($updateStmt->execute()) {
                $success = 'Password changed successfully';
                logActivity($conn, $userId, 'change_password');
            } else {
                $error = 'Failed to change password';
            }
        }
    }
}
?>

<div class="page-header">
    <h1><i class="fas fa-key"></i> Change Password</h1>
</div>

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

<div class="row">
    <div class="col-md-8" style="margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-lock"></i> Update Your Password</h2>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Password Requirements:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <li>At least 6 characters long</li>
                        <li>Use a mix of letters, numbers, and symbols</li>
                        <li>Avoid using personal information</li>
                        <li>Don't reuse old passwords</li>
                    </ul>
                </div>
                
                <form method="POST" action="" id="passwordForm">
                    <div class="form-group">
                        <label for="current_password">Current Password <span class="required">*</span></label>
                        <div class="password-input-group">
                            <input type="password" id="current_password" name="current_password" 
                                   class="form-control" required>
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password <span class="required">*</span></label>
                        <div class="password-input-group">
                            <input type="password" id="new_password" name="new_password" 
                                   class="form-control" required minlength="6"
                                   oninput="checkPasswordStrength(this.value)">
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="passwordStrength" class="password-strength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                        <div class="password-input-group">
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" required minlength="6"
                                   oninput="checkPasswordMatch()">
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="passwordMatch"></div>
                    </div>
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                        <a href="index.php?page=profile" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Security Tips -->
        <div class="card mt-4">
            <div class="card-header">
                <h2><i class="fas fa-shield-alt"></i> Security Tips</h2>
            </div>
            <div class="card-body">
                <ul style="line-height: 2;">
                    <li><i class="fas fa-check text-success"></i> Change your password regularly</li>
                    <li><i class="fas fa-check text-success"></i> Never share your password with anyone</li>
                    <li><i class="fas fa-check text-success"></i> Use different passwords for different accounts</li>
                    <li><i class="fas fa-check text-success"></i> Avoid using public computers for sensitive operations</li>
                    <li><i class="fas fa-check text-success"></i> Log out after using the system</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.password-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.password-input-group input {
    padding-right: 45px;
}

.toggle-password {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    color: #7f8c8d;
    cursor: pointer;
    padding: 5px 10px;
}

.toggle-password:hover {
    color: #2c3e50;
}

.password-strength {
    margin-top: 5px;
    height: 5px;
    border-radius: 3px;
    transition: all 0.3s;
}

.password-strength.weak {
    background: #e74c3c;
    width: 33%;
}

.password-strength.medium {
    background: #f39c12;
    width: 66%;
}

.password-strength.strong {
    background: #27ae60;
    width: 100%;
}

.password-match {
    margin-top: 5px;
    font-size: 0.85rem;
}

.password-match.match {
    color: #27ae60;
}

.password-match.no-match {
    color: #e74c3c;
}
</style>

<script>
// Toggle password visibility
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Check password strength
function checkPasswordStrength(password) {
    const strengthDiv = document.getElementById('passwordStrength');
    
    if (password.length === 0) {
        strengthDiv.className = 'password-strength';
        return;
    }
    
    let strength = 0;
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    if (strength <= 2) {
        strengthDiv.className = 'password-strength weak';
    } else if (strength <= 3) {
        strengthDiv.className = 'password-strength medium';
    } else {
        strengthDiv.className = 'password-strength strong';
    }
}

// Check if passwords match
function checkPasswordMatch() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirmPassword.length === 0) {
        matchDiv.innerHTML = '';
        return;
    }
    
    if (newPassword === confirmPassword) {
        matchDiv.innerHTML = '<span class="password-match match"><i class="fas fa-check"></i> Passwords match</span>';
    } else {
        matchDiv.innerHTML = '<span class="password-match no-match"><i class="fas fa-times"></i> Passwords do not match</span>';
    }
}

// Form validation
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
});
</script>