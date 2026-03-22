/**
 * Academic Thesis Management System - JavaScript
 * UPDATED: No animations, instant loading
 */

document.addEventListener('DOMContentLoaded', function() {
    initFormValidation();
    initFileUpload();
    initConfirmDialogs();
    autoHideAlerts();
});

//New notification check
function checkNewNotifications() {
    fetch('index.php?ajax=notifications')
        .then(response => response.json())
        .then(data => {
            if (data.unread_count > 0) {
                // Update notification badge
                document.querySelector('.dropdown-badge').textContent = data.unread_count;
            }
        });
}

// Check every 30 seconds
setInterval(checkNewNotifications, 30000);

// Form validation
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    field.addEventListener('input', function() {
                        this.classList.remove('is-invalid');
                    });
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showAlert('Please fill in all required fields', 'danger');
            }
        });
    });
}

// File upload preview and validation
function initFileUpload() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 52428800; // 50MB
                if (file.size > maxSize) {
                    showAlert('File size exceeds 50MB limit', 'danger');
                    input.value = '';
                    return;
                }
                
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!allowedTypes.includes(file.type)) {
                    showAlert('Invalid file type. Only PDF, DOC, and DOCX are allowed', 'danger');
                    input.value = '';
                    return;
                }
                
                const fileInfo = document.createElement('div');
                fileInfo.className = 'file-info';
                fileInfo.innerHTML = `<i class="fas fa-file"></i> <span>${file.name}</span> <span class="file-size">(${formatFileSize(file.size)})</span>`;
                
                const existingInfo = input.parentElement.querySelector('.file-info');
                if (existingInfo) existingInfo.remove();
                
                input.parentElement.appendChild(fileInfo);
            }
        });
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Confirm dialogs
function initConfirmDialogs() {
    const confirmLinks = document.querySelectorAll('[data-confirm]');
    confirmLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `<i class="fas fa-${getAlertIcon(type)}"></i> ${message}`;
    
    const container = document.querySelector('.main-content .container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        setTimeout(() => {
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 300);
        }, 5000);
    }
}

function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Auto-hide alerts
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

// NO ANIMATIONS - Elements appear instantly
// Removed: setupAnimations, animateOnScroll functions

console.log('Thesis Management System JavaScript Loaded Successfully');

// Add styles for file info and invalid fields
const style = document.createElement('style');
style.textContent = `
    .file-info {
        margin-top: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .file-info i {
        color: #3498db;
        font-size: 1.2rem;
    }
    .file-size {
        color: #7f8c8d;
        font-size: 0.9rem;
    }
    .is-invalid {
        border-color: #e74c3c !important;
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1) !important;
    }
`;
document.head.appendChild(style);


// ✅ FIXED: User Dropdown - Opens on LEFT side of avatar (inside viewport)
(function() {
    const avatar = document.querySelector('.user-avatar');
    const dropdown = document.querySelector('.user-dropdown');

    if (!avatar || !dropdown) return;

    function placeDropdown() {
        const rect = avatar.getBoundingClientRect();
        const ddWidth = Math.max(dropdown.offsetWidth, 250);
        
        // ✅ Calculate LEFT position: dropdown RIGHT edge aligns with avatar LEFT edge
        // This makes dropdown appear to the LEFT of the avatar
        let leftPos = rect.left - ddWidth + rect.width;
        
        // Ensure dropdown doesn't go off left edge of viewport
        if (leftPos < 8) {
            leftPos = 8; // Minimum 8px padding from left edge
        }
        
        const topPos = rect.bottom + 8; // 8px gap below avatar

        dropdown.style.left = leftPos + 'px';
        dropdown.style.right = 'auto'; // Clear any right positioning
        dropdown.style.top = topPos + 'px';
    }

    // toggle and position
    avatar.addEventListener('click', function(e) {
        e.stopPropagation();
        const opening = !dropdown.classList.contains('show');
        if (opening) {
            dropdown.classList.add('show');
            placeDropdown();
            // close when clicking outside
            setTimeout(() => {
                document.addEventListener('click', outsideClick);
            }, 0);
        } else {
            dropdown.classList.remove('show');
            document.removeEventListener('click', outsideClick);
        }
    });

    function outsideClick(e) {
        if (!dropdown.contains(e.target) && !avatar.contains(e.target)) {
            dropdown.classList.remove('show');
            document.removeEventListener('click', outsideClick);
        }
    }

    // reposition on resize/scroll when open
    window.addEventListener('resize', () => { if (dropdown.classList.contains('show')) placeDropdown(); });
    window.addEventListener('scroll', () => { if (dropdown.classList.contains('show')) placeDropdown(); }, true);
})();