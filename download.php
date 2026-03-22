<?php

/**

 * File Download Handler

 * Handles secure file downloads for thesis documents

 */


require_once 'config.php';
require_once 'functions.php';

session_start();
requireLogin();

// Performance: Enable output buffering
ob_start();

// Set cache headers for files
header('Cache-Control: public, max-age=86400');
header('Pragma: public');

if (isset($_GET['id'])) {

    $submissionId = intval($_GET['id']);

    

    // Get file info from database
    $stmt = $conn->prepare("SELECT file_path, file_name, student_id, advisor_id FROM thesis_submissions WHERE submission_id = ?");
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $result = $stmt->get_result();

    

    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        

        // Check permissions
        $canDownload = false;

        

        if ($userRole === 'admin') {
            $canDownload = true;
        } elseif ($userRole === 'student' && $file['student_id'] == $userId) {
            $canDownload = true;
        } elseif ($userRole === 'advisor' && $file['advisor_id'] == $userId) {
            $canDownload = true;
        } elseif ($userRole === 'panelist') {

            // Check if panelist is assigned
            $panelistStmt = $conn->prepare("SELECT * FROM panelist_assignments WHERE submission_id = ? AND panelist_id = ?");
            $panelistStmt->bind_param("ii", $submissionId, $userId);
            $panelistStmt->execute();

            if ($panelistStmt->get_result()->num_rows > 0) {
                $canDownload = true;

            }

        }

        

        if ($canDownload) {
            $filePath = $file['file_path'];

            

            if (file_exists($filePath)) {

                // Log download activity
                logActivity($conn, $userId, 'download_thesis', 'thesis_submission', $submissionId, $file['file_name']);

                

                // Force download
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($file['file_name']) . '"');
                header('Content-Length: ' . filesize($filePath));
                header('Cache-Control: must-revalidate');
                header('Pragma: public');

                

                // Clean output buffer
                ob_clean();
                flush();

                

                // Read and output file
                readfile($filePath);
                exit();

            } else {
                die('Error: File not found on server. Please contact administrator.');
            }

        } else {
            die('Error: You do not have permission to download this file.');
        }

    } else {
        die('Error: File not found in database.');
    }

} else {
    die('Error: Invalid request. No file ID provided.');
}
?>