<?php
/**
 * Export Functionality - CSV Export for Reports
 */

require_once 'config.php';
require_once 'functions.php';
session_start();

requireRole('admin');

if (!isset($_GET['type'])) {
    die('Export type not specified');
}

$type = $_GET['type'];
$filename = 'export_' . $type . '_' . date('Y-m-d_His') . '.csv';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Export based on type
switch ($type) {
    case 'submissions':
        // Header row
        fputcsv($output, [
            'ID', 
            'Student Name', 
            'Student Email',
            'Title', 
            'Department',
            'Program',
            'Thesis Type',
            'Status', 
            'Current Version',
            'Advisor',
            'Submission Date'
        ]);
        
        // Data rows
        $query = "
            SELECT 
                ts.submission_id,
                u.full_name as student_name,
                u.email as student_email,
                ts.title,
                ts.department,
                ts.program,
                ts.thesis_type,
                ts.status,
                ts.current_version,
                adv.full_name as advisor_name,
                ts.submission_date
            FROM thesis_submissions ts
            JOIN users u ON ts.student_id = u.user_id
            LEFT JOIN users adv ON ts.advisor_id = adv.user_id
            ORDER BY ts.submission_date DESC
        ";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['submission_id'],
                $row['student_name'],
                $row['student_email'],
                $row['title'],
                $row['department'],
                $row['program'],
                ucfirst($row['thesis_type']),
                ucwords(str_replace('_', ' ', $row['status'])),
                $row['current_version'],
                $row['advisor_name'] ?? 'Not assigned',
                date('Y-m-d H:i:s', strtotime($row['submission_date']))
            ]);
        }
        break;
        
    case 'users':
        // Header row
        fputcsv($output, [
            'ID',
            'Username',
            'Full Name',
            'Email',
            'Role',
            'Department',
            'Phone',
            'Status',
            'Created At',
            'Last Login'
        ]);
        
        // Data rows
        $query = "SELECT * FROM users ORDER BY created_at DESC";
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['user_id'],
                $row['username'],
                $row['full_name'],
                $row['email'],
                ucfirst($row['role']),
                $row['department'] ?? 'N/A',
                $row['phone'] ?? 'N/A',
                ucfirst($row['status']),
                date('Y-m-d H:i:s', strtotime($row['created_at'])),
                $row['last_login'] ? date('Y-m-d H:i:s', strtotime($row['last_login'])) : 'Never'
            ]);
        }
        break;
        
    case 'feedback':
        // Header row
        fputcsv($output, [
            'ID',
            'Thesis Title',
            'Student Name',
            'Reviewer Name',
            'Reviewer Role',
            'Feedback Type',
            'Section',
            'Rating',
            'Feedback Text',
            'Created At'
        ]);
        
        // Data rows
        $query = "
            SELECT 
                f.feedback_id,
                ts.title as thesis_title,
                u_student.full_name as student_name,
                u_reviewer.full_name as reviewer_name,
                f.reviewer_role,
                f.feedback_type,
                f.section,
                f.rating,
                f.feedback_text,
                f.created_at
            FROM feedback f
            JOIN thesis_submissions ts ON f.submission_id = ts.submission_id
            JOIN users u_student ON ts.student_id = u_student.user_id
            JOIN users u_reviewer ON f.reviewer_id = u_reviewer.user_id
            ORDER BY f.created_at DESC
        ";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['feedback_id'],
                $row['thesis_title'],
                $row['student_name'],
                $row['reviewer_name'],
                ucfirst($row['reviewer_role']),
                ucwords(str_replace('_', ' ', $row['feedback_type'])),
                $row['section'] ?? 'N/A',
                $row['rating'] ?? 'N/A',
                $row['feedback_text'],
                date('Y-m-d H:i:s', strtotime($row['created_at']))
            ]);
        }
        break;
        
    case 'activity':
        // Header row
        fputcsv($output, [
            'ID',
            'User Name',
            'Action',
            'Entity Type',
            'Entity ID',
            'Details',
            'IP Address',
            'Created At'
        ]);
        
        // Data rows
        $query = "
            SELECT 
                al.log_id,
                u.full_name as user_name,
                al.action,
                al.entity_type,
                al.entity_id,
                al.details,
                al.ip_address,
                al.created_at
            FROM activity_log al
            JOIN users u ON al.user_id = u.user_id
            ORDER BY al.created_at DESC
            LIMIT 1000
        ";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['log_id'],
                $row['user_name'],
                $row['action'],
                $row['entity_type'] ?? 'N/A',
                $row['entity_id'] ?? 'N/A',
                $row['details'] ?? 'N/A',
                $row['ip_address'],
                date('Y-m-d H:i:s', strtotime($row['created_at']))
            ]);
        }
        break;
        
    default:
        fputcsv($output, ['Error: Invalid export type']);
        break;
}

fclose($output);
exit(); 
?>