<?php
/**
 * Settings Page - Display Preferences & Notifications
 */

requireLogin();

$userId = $_SESSION['user_id'];
$success = '';
?>

<div class="page-header">
    <h1><i class="fas fa-cog"></i> Settings</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= $success ?>
    </div>
<?php endif; ?>

<!-- Display Preferences -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-palette"></i> Display Preferences</h2>
    </div>
    <div class="card-body">
        <div class="settings-group">
            <div class="setting-item">
                <div class="setting-info">
                    <h4><i class="fas fa-moon"></i> Theme</h4>
                    <p>Choose between light and dark theme</p>
                </div>
                <div class="setting-control">
                    <button onclick="toggleTheme()" class="btn btn-secondary">
                        <i class="fas fa-adjust"></i> Toggle Theme
                    </button>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <h4><i class="fas fa-text-height"></i> Font Size</h4>
                    <p>Adjust the text size throughout the system</p>
                </div>
                <div class="setting-control">
                    <select id="fontSize" class="form-control" onchange="changeFontSize(this.value)">
                        <option value="small">Small</option>
                        <option value="medium" selected>Medium (Default)</option>
                        <option value="large">Large</option>
                    </select>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <h4><i class="fas fa-compress"></i> Compact Mode</h4>
                    <p>Reduce spacing for more content on screen</p>
                </div>
                <div class="setting-control">
                    <label class="switch">
                        <input type="checkbox" id="compactMode" onchange="toggleCompactMode()">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Settings -->
<div class="card mt-4">
    <div class="card-header">
        <h2><i class="fas fa-bell"></i> Notification Settings</h2>
    </div>
    <div class="card-body">
        <div class="settings-group">
            <div class="setting-item">
                <div class="setting-info">
                    <h4><i class="fas fa-envelope"></i> Email Notifications</h4>
                    <p>Receive email alerts for important updates</p>
                </div>
                <div class="setting-control">
                    <label class="switch">
                        <input type="checkbox" id="emailNotif" checked onchange="savePreference('email', this.checked)">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <h4><i class="fas fa-desktop"></i> Push Notifications</h4>
                    <p>Show browser notifications</p>
                </div>
                <div class="setting-control">
                    <label class="switch">
                        <input type="checkbox" id="pushNotif" checked onchange="savePreference('push', this.checked)">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <h4><i class="fas fa-volume-up"></i> Sound Alerts</h4>
                    <p>Play sound when receiving notifications</p>
                </div>
                <div class="setting-control">
                    <label class="switch">
                        <input type="checkbox" id="soundAlert" onchange="savePreference('sound', this.checked)">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Settings -->
<div class="card mt-4">
    <div class="card-header">
        <h2><i class="fas fa-shield-alt"></i> Privacy & Security</h2>
    </div>
    <div class="card-body">
        <div class="settings-group">
            <div class="setting-item">
                <div class="setting-info">
                    <h4><i class="fas fa-eye"></i> Profile Visibility</h4>
                    <p>Control who can see your profile information</p>
                </div>
                <div class="setting-control">
                    <select class="form-control" onchange="savePreference('visibility', this.value)">
                        <option value="public" selected>Public</option>
                        <option value="users">Registered Users Only</option>
                        <option value="private">Private</option>
                    </select>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <h4><i class="fas fa-history"></i> Activity Logging</h4>
                    <p>Log your activity for security purposes</p>
                </div>
                <div class="setting-control">
                    <label class="switch">
                        <input type="checkbox" id="activityLog" checked disabled>
                        <span class="slider"></span>
                    </label>
                    <small class="form-text">Required for security</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- About System -->
<div class="card mt-4">
    <div class="card-header">
        <h2><i class="fas fa-info-circle"></i> About System</h2>
    </div>
    <div class="card-body">
        <p><strong>System Version:</strong> 1.0.1</p>
        <p><strong>Your Role:</strong> <?= ucfirst($_SESSION['user_role']) ?></p>
        <p><strong>Account Created:</strong> <?php 
            $stmt = $conn->prepare("SELECT created_at FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userData = $stmt->get_result()->fetch_assoc();
            echo formatDate($userData['created_at']);
        ?></p>
        <p><strong>Browser:</strong> <span id="browserInfo"></span></p>
    </div>
</div>

<script>
// Font Size Control
function changeFontSize(size) {
    const body = document.body;
    body.classList.remove('font-small', 'font-medium', 'font-large');
    body.classList.add('font-' + size);
    localStorage.setItem('fontSize', size);
}

// Compact Mode
function toggleCompactMode() {
    document.body.classList.toggle('compact-mode');
    const isCompact = document.body.classList.contains('compact-mode');
    localStorage.setItem('compactMode', isCompact);
}

// Save Preferences
function savePreference(key, value) {
    localStorage.setItem('preference_' + key, value);
    showToast('Preference saved');
}

// Show Toast Message
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast-message';
    toast.innerHTML = '<i class="fas fa-check"></i> ' + message;
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #27ae60; color: white; padding: 15px 20px; border-radius: 5px; z-index: 9999; animation: slideIn 0.3s;';
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

// Load Saved Preferences
window.addEventListener('DOMContentLoaded', () => {
    // Load font size
    const fontSize = localStorage.getItem('fontSize') || 'medium';
    document.getElementById('fontSize').value = fontSize;
    changeFontSize(fontSize);
    
    // Load compact mode
    const compactMode = localStorage.getItem('compactMode') === 'true';
    document.getElementById('compactMode').checked = compactMode;
    if (compactMode) document.body.classList.add('compact-mode');
    
    // Load notification preferences
    const emailNotif = localStorage.getItem('preference_email') !== 'false';
    document.getElementById('emailNotif').checked = emailNotif;
    
    const pushNotif = localStorage.getItem('preference_push') !== 'false';
    document.getElementById('pushNotif').checked = pushNotif;
    
    const soundAlert = localStorage.getItem('preference_sound') === 'true';
    document.getElementById('soundAlert').checked = soundAlert;
    
    // Display browser info
    document.getElementById('browserInfo').textContent = navigator.userAgent.split(/[()]/)[1] || 'Unknown';
});

// Add CSS for font sizes and compact mode
const style = document.createElement('style');
style.textContent = `
    body.font-small { font-size: 14px; }
    body.font-medium { font-size: 16px; }
    body.font-large { font-size: 18px; }
    
    body.compact-mode .card { margin-bottom: 15px; }
    body.compact-mode .card-body { padding: 15px; }
    body.compact-mode .setting-item { padding: 15px; }
    
    @keyframes slideIn {
        from { transform: translateX(100%); }
        to { transform: translateX(0); }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); }
        to { transform: translateX(100%); }
    }
`;
document.head.appendChild(style);

// ✅ FIXED: Force correct dropdown positioning on settings page
(function() {
    const dropdown = document.querySelector('.user-dropdown');
    
    if (!dropdown) return;
    
    // Use MutationObserver to detect when dropdown gets 'show' class
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                if (dropdown.classList.contains('show')) {
                    // Dropdown just opened - reposition IMMEDIATELY
                    repositionDropdown();
                }
            }
        });
    });
    
    observer.observe(dropdown, { attributes: true });
    
    function repositionDropdown() {
        const avatar = document.querySelector('.user-avatar-wrapper') || document.querySelector('.user-avatar');
        if (!avatar) return;
        
        const rect = avatar.getBoundingClientRect();
        const ddWidth = dropdown.offsetWidth || 250;
        
        let leftPos = rect.left - ddWidth + rect.width;
        if (leftPos < 8) leftPos = 8;
        if (leftPos + ddWidth > window.innerWidth - 8) {
            leftPos = window.innerWidth - ddWidth - 8;
        }
        
        dropdown.style.left = leftPos + 'px';
        dropdown.style.right = 'auto';
        dropdown.style.top = (rect.bottom + 8) + 'px';
    }
})();
</script>