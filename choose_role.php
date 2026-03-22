<?php
/**
 * Choose Role Page
 * For new Google users to select their role
 */

session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user came from Google auth
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_email'])) {
    header('Location: index.php?page=login');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    $userId = $_SESSION['temp_user_id'];
    
    if (empty($role)) {
        $error = 'Please select a role';
    } elseif (!in_array($role, ['student', 'advisor', 'panelist'])) {
        $error = 'Invalid role selected';
    } else {
        // Update user role
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->bind_param("si", $role, $userId);
        
        if ($stmt->execute()) {
            // Set session variables
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $_SESSION['temp_name'];
            $_SESSION['user_role'] = $role;
            
            // Clear temporary session data
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_email']);
            unset($_SESSION['temp_name']);
            
            // Log activity
            logActivity($conn, $userId, 'role_selected', null, null, "Role: $role");
            
            // Redirect to dashboard
            header('Location: index.php?page=dashboard');
            exit();
        } else {
            $error = 'Failed to set role. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Role - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .role-selection-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .role-selection-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        
        .role-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .role-header h1 {
            color: #2c3e50;
            margin: 10px 0;
        }
        
        .role-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .role-card {
            background: #f8f9fa;
            border: 3px solid transparent;
            border-radius: 10px;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .role-card input[type="radio"] {
            display: none;
        }
        
        .role-card input[type="radio"]:checked + label {
            border-color: #3498db;
            background: #e3f2fd;
        }
        
        .role-card label {
            cursor: pointer;
            display: block;
            border: 3px solid transparent;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .role-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #3498db;
        }
        
        .role-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .role-description {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .submit-button {
            background: #3498db;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: background 0.3s ease;
        }
        
        .submit-button:hover {
            background: #2980b9;
        }
        
        .submit-button:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="role-selection-container">
        <div class="role-selection-box">
            <div class="role-header">
                <i class="fas fa-user-circle" style="font-size: 3rem; color: #3498db;"></i>
                <h1>Welcome, <?= htmlspecialchars($_SESSION['temp_name']) ?>!</h1>
                <p>Please select your role to continue</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="roleForm">
                <div class="roles-grid">
                    <div class="role-card">
                        <input type="radio" name="role" id="student" value="student" required>
                        <label for="student">
                            <div class="role-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="role-name">Student</div>
                            <div class="role-description">
                                Submit and manage your thesis documents
                            </div>
                        </label>
                    </div>
                    
                    <div class="role-card">
                        <input type="radio" name="role" id="advisor" value="advisor" required>
                        <label for="advisor">
                            <div class="role-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="role-name">Advisor</div>
                            <div class="role-description">
                                Guide and review student theses
                            </div>
                        </label>
                    </div>
                    
                    <div class="role-card">
                        <input type="radio" name="role" id="panelist" value="panelist" required>
                        <label for="panelist">
                            <div class="role-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="role-name">Panelist</div>
                            <div class="role-description">
                                Evaluate and provide feedback on theses
                            </div>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="submit-button">
                    <i class="fas fa-check"></i> Continue to Dashboard
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Add visual feedback when role is selected
        document.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.role-card label').forEach(label => {
                    label.style.borderColor = 'transparent';
                    label.style.background = '';
                });
                if (this.checked) {
                    const label = this.nextElementSibling;
                    label.style.borderColor = '#3498db';
                    label.style.background = '#e3f2fd';
                }
            });
        });
    </script>
</body>
</html>