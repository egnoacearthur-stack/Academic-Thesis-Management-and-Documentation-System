-- Academic Thesis Management System Database Schema
-- MySQL Database

CREATE DATABASE IF NOT EXISTS thesis_management;
USE thesis_management;

-- Users Table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR (50) NOT NULL,
    middle_name VARCHAR (50) NOT NULL,
    last_name VARCHAR (50) NOT NULL,
    suffix VARCHAR (50) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'advisor', 'panelist', 'admin') NOT NULL,
    department VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    INDEX idx_role (role),
    INDEX idx_email (email)
);

-- Thesis Submissions Table
CREATE TABLE thesis_submissions (
    submission_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    abstract TEXT,
    keywords VARCHAR(255),
    department VARCHAR(100),
    program VARCHAR(100),
    thesis_type ENUM('masters', 'phd', 'undergraduate') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'submitted', 'under_review', 'revision_requested', 'approved', 'rejected') DEFAULT 'submitted',
    current_version INT DEFAULT 1,
    advisor_id INT,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (advisor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_advisor (advisor_id)
);

-- Revision History Table
CREATE TABLE revision_history (
    revision_id INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    version_number INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    revision_notes TEXT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changes_summary TEXT,
    FOREIGN KEY (submission_id) REFERENCES thesis_submissions(submission_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_submission (submission_id),
    INDEX idx_version (version_number)
);

-- Feedback Table
CREATE TABLE feedback (
    feedback_id INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewer_role ENUM('advisor', 'panelist') NOT NULL,
    feedback_text TEXT NOT NULL,
    section VARCHAR(100),
    page_number INT,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    feedback_type ENUM('general', 'methodology', 'writing', 'structure', 'content') DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (submission_id) REFERENCES thesis_submissions(submission_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_submission (submission_id),
    INDEX idx_reviewer (reviewer_id)
);

-- Approval Workflow Table
CREATE TABLE approval_workflow (
    approval_id INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    approver_id INT NOT NULL,
    approver_role ENUM('advisor', 'panelist', 'admin') NOT NULL,
    decision ENUM('pending', 'approved', 'revision_required', 'rejected') DEFAULT 'pending',
    decision_date TIMESTAMP NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES thesis_submissions(submission_id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_submission (submission_id),
    INDEX idx_approver (approver_id),
    INDEX idx_decision (decision)
);

-- Notifications Table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('submission', 'feedback', 'approval', 'revision', 'system') NOT NULL,
    related_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_type (notification_type)
);

-- Panelist Assignments Table
CREATE TABLE panelist_assignments (
    assignment_id INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    panelist_id INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('assigned', 'completed', 'declined') DEFAULT 'assigned',
    FOREIGN KEY (submission_id) REFERENCES thesis_submissions(submission_id) ON DELETE CASCADE,
    FOREIGN KEY (panelist_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_submission (submission_id),
    INDEX idx_panelist (panelist_id)
);

-- Repository Archive Table
CREATE TABLE repository_archive (
    archive_id INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    archived_by INT NOT NULL,
    archive_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    access_level ENUM('public', 'restricted', 'private') DEFAULT 'public',
    download_count INT DEFAULT 0,
    FOREIGN KEY (submission_id) REFERENCES thesis_submissions(submission_id) ON DELETE CASCADE,
    FOREIGN KEY (archived_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_submission (submission_id),
    INDEX idx_access (access_level)
);

-- Activity Log Table
CREATE TABLE activity_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- Insert Default Admin User
INSERT INTO users (username, password, email, full_name, role, department) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'admin@thesis.edu', 'System Administrator', 'admin', 'IT Department');
-- Default password is 'password' - 

-- Insert Sample Data for Testing

-- Sample Students
INSERT INTO users (username, password, email, full_name, role, department) VALUES
('reymart.g', 'LunaCakes20', 'reymart.g@student.edu', 'Reymart G', 'student', 'Computer Science'),
('sunwoo.han', 'password', 'sunwoo.han@student.edu', 'Sunwoo Han', 'student', 'Computer Science');

-- Sample Advisors
INSERT INTO users (username, password, email, full_name, role, department) VALUES
('dr.byrne', 'password', 'dr.byrne@faculty.edu', 'Dr. Liam Byrne', 'advisor', 'Computer Science'),

-- Sample Panelists
INSERT INTO users (username, password, email, full_name, role, department) VALUES
('prof.callas', 'password', 'prof.callas@faculty.edu', 'Prof. Sabine Callas', 'panelist', 'Information Technology');,
