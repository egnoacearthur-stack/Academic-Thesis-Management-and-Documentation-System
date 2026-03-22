<?php
/**
 * Approval Management Page - For Admins
 */

requireRole('admin');

$error = '';
$success = '';

// Handle panelist assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_panelist'])) {
    $submissionId = intval($_POST['submission_id']);
    $panelistId = intval($_POST['panelist_id']);
    $adminId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("INSERT INTO panelist_assignments (submission_id, panelist_id, assigned_by) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $submissionId, $panelistId, $adminId);
    
    if ($stmt->execute()) {
        // Notify panelist
        $thesisStmt = $conn->prepare("SELECT title FROM thesis_submissions WHERE submission_id = ?");
        $thesisStmt->bind_param("i", $submissionId);
        $thesisStmt->execute();
        $thesisData = $thesisStmt->get_result()->fetch_assoc();
        
        createNotification($conn, $panelistId, 'New Review Assignment', 
            'You have been assigned to review the thesis: "' . $thesisData['title'] . '"', 
            'system', $submissionId);
        
        $success = 'Panelist assigned successfully';
    } else {
        $error = 'Failed to assign panelist or already assigned';
    }
}

// Handle final approval/archiving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_thesis'])) {
    $submissionId = intval($_POST['submission_id']);
    $accessLevel = sanitize($_POST['access_level']);
    $adminId = $_SESSION['user_id'];
    
    // Check if already archived
    $checkStmt = $conn->prepare("SELECT archive_id FROM repository_archive WHERE submission_id = ?");
    $checkStmt->bind_param("i", $submissionId);
    $checkStmt->execute();
    $existing = $checkStmt->get_result();
    
    if ($existing->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO repository_archive (submission_id, archived_by, access_level) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $submissionId, $adminId, $accessLevel);
        
        if ($stmt->execute()) {
            $success = 'Thesis archived to repository successfully';
        } else {
            $error = 'Failed to archive thesis';
        }
    } else {
        $error = 'Thesis already archived';
    }
}

// Get specific submission if ID provided
$viewingSubmission = null;
$panelistAssignments = null;
$approvalHistory = null;

if (isset($_GET['id'])) {
    $submissionId = intval($_GET['id']);
    
    $stmt = $conn->prepare("
        SELECT ts.*, 
            CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as student_name, 
            u.email as student_email,
            CONCAT_WS(' ', adv.first_name, adv.middle_name, adv.last_name, adv.suffix) as advisor_name
               ra.archive_id, ra.access_level as archive_access
        FROM thesis_submissions ts 
        JOIN users u ON ts.student_id = u.user_id 
        LEFT JOIN users adv ON ts.advisor_id = adv.user_id 
        LEFT JOIN repository_archive ra ON ts.submission_id = ra.submission_id
        WHERE ts.submission_id = ?
    ");
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $viewingSubmission = $stmt->get_result()->fetch_assoc();
    
    if ($viewingSubmission) {
        // Get panelist assignments
        $assignStmt = $conn->prepare("
            SELECT pa.*, u.full_name as panelist_name 
            FROM panelist_assignments pa 
            JOIN users u ON pa.panelist_id = u.user_id 
            WHERE pa.submission_id = ?
        ");
        $assignStmt->bind_param("i", $submissionId);
        $assignStmt->execute();
        $panelistAssignments = $assignStmt->get_result();
        
        // Get approval history
        $approvalStmt = $conn->prepare("
            SELECT aw.*, u.full_name as approver_name 
            FROM approval_workflow aw 
            JOIN users u ON aw.approver_id = u.user_id 
            WHERE aw.submission_id = ? 
            ORDER BY aw.created_at DESC
        ");
        $approvalStmt->bind_param("i", $submissionId);
        $approvalStmt->execute();
        $approvalHistory = $approvalStmt->get_result();
    }
}

// Get all submissions for list view
$submissions = $conn->query("
    SELECT ts.*, u.full_name as student_name, u.department as student_dept 
    FROM thesis_submissions ts 
    JOIN users u ON ts.student_id = u.user_id 
    ORDER BY ts.submission_date DESC
");

// Get available panelists
$panelists = $conn->query("SELECT user_id, full_name, department FROM users WHERE role = 'panelist' AND status = 'active'");
?>

<div class="page-header">
    <h1><i class="fas fa-gavel"></i> Approval Management</h1>
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

<?php if ($viewingSubmission): ?>
    <!-- Detailed View -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-file-alt"></i> <?= htmlspecialchars($viewingSubmission['title']) ?></h2>
            <span class="badge <?= getStatusBadge($viewingSubmission['status']) ?>">
                <?= ucwords(str_replace('_', ' ', $viewingSubmission['status'])) ?>
            </span>
        </div>
        <div class="card-body">
            <div class="thesis-details">
                <div class="detail-row">
                    <strong>Student:</strong> <?= htmlspecialchars($viewingSubmission['student_name']) ?> 
                    (<?= htmlspecialchars($viewingSubmission['student_email']) ?>)
                </div>
                <div class="detail-row">
                    <strong>Advisor:</strong> <?= htmlspecialchars($viewingSubmission['advisor_name'] ?? 'Not assigned') ?>
                </div>
                <div class="detail-row">
                    <strong>Program:</strong> <?= htmlspecialchars(formatProgramName($viewingSubmission['program'])) ?>
                </div>
                <div class="detail-row">
                    <strong>Thesis Type:</strong> <?= ucfirst($viewingSubmission['thesis_type']) ?>
                </div>
                <div class="detail-row">
                    <strong>Submission Date:</strong> <?= formatDateTime($viewingSubmission['submission_date']) ?>
                </div>
                <div class="detail-row">
                    <strong>Current Version:</strong> v<?= $viewingSubmission['current_version'] ?>
                </div>
                <?php if ($viewingSubmission['archive_id']): ?>
                    <div class="detail-row">
                        <strong>Archive Status:</strong> 
                        <span class="badge badge-success">Archived</span>
                        (Access: <?= ucfirst($viewingSubmission['archive_access']) ?>)
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mt-3">
                <?php if (file_exists($viewingSubmission['file_path'])): ?>
                    <a href="<?= $viewingSubmission['file_path'] ?>" class="btn btn-success" download>
                        <i class="fas fa-download"></i> Download Thesis
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Panelist Assignments -->
    <div class="card mt-4">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> Panelist Assignments</h2>
        </div>
        <div class="card-body">
            <?php if ($panelistAssignments && $panelistAssignments->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Panelist</th>
                            <th>Assigned Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($assign = $panelistAssignments->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($assign['panelist_name']) ?></td>
                                <td><?= formatDateTime($assign['assigned_date']) ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?= ucfirst($assign['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No panelists assigned yet</p>
            <?php endif; ?>
            
            <!-- Assign New Panelist -->
            <h4 class="mt-4">Assign New Panelist</h4>
            <form method="POST" action="" class="mt-3">
                <input type="hidden" name="submission_id" value="<?= $viewingSubmission['submission_id'] ?>">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <select name="panelist_id" class="form-control" required>
                            <option value="">Select Panelist</option>
                            <?php 
                            $panelists->data_seek(0); // Reset pointer
                            while ($panelist = $panelists->fetch_assoc()): 
                            ?>
                                <option value="<?= $panelist['user_id'] ?>">
                                    <?= htmlspecialchars($panelist['full_name']) ?> - 
                                    <?= htmlspecialchars($panelist['department']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <button type="submit" name="assign_panelist" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Assign Panelist
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Approval History -->
    <div class="card mt-4">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Approval History</h2>
        </div>
        <div class="card-body">
            <?php if ($approvalHistory && $approvalHistory->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Approver</th>
                            <th>Role</th>
                            <th>Decision</th>
                            <th>Date</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($approval = $approvalHistory->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($approval['approver_name']) ?></td>
                                <td><span class="badge badge-info"><?= ucfirst($approval['approver_role']) ?></span></td>
                                <td>
                                    <span class="badge <?= $approval['decision'] === 'approved' ? 'badge-success' : ($approval['decision'] === 'revision_required' ? 'badge-warning' : 'badge-secondary') ?>">
                                        <?= ucwords(str_replace('_', ' ', $approval['decision'])) ?>
                                    </span>
                                </td>
                                <td><?= $approval['decision_date'] ? formatDateTime($approval['decision_date']) : 'Pending' ?></td>
                                <td><?= htmlspecialchars($approval['comments']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No approval decisions yet</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Archive to Repository -->
    <?php if ($viewingSubmission['status'] === 'approved' && !$viewingSubmission['archive_id']): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h2><i class="fas fa-archive"></i> Archive to Repository</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="submission_id" value="<?= $viewingSubmission['submission_id'] ?>">
                    
                    <div class="form-group">
                        <label for="access_level">Access Level <span class="required">*</span></label>
                        <select id="access_level" name="access_level" class="form-control" required>
                            <option value="public">Public - Anyone can download</option>
                            <option value="restricted">Restricted - Requires approval</option>
                            <option value="private">Private - Admin only</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="archive_thesis" class="btn btn-success">
                        <i class="fas fa-archive"></i> Archive to Repository
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="mt-3">
        <a href="index.php?page=approval" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
    
<?php else: ?>
    <!-- List View -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-list"></i> All Thesis Submissions</h2>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Version</th>
                        <th>Submission Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($sub = $submissions->fetch_assoc()): ?>
                        <tr>
                            <td><?= $sub['submission_id'] ?></td>
                            <td><?= htmlspecialchars($sub['student_name']) ?></td>
                            <td><?= htmlspecialchars($sub['title']) ?></td>
                            <td><?= htmlspecialchars($sub['department']) ?></td>
                            <td><span class="badge <?= getStatusBadge($sub['status']) ?>"><?= ucwords(str_replace('_', ' ', $sub['status'])) ?></span></td>
                            <td>v<?= $sub['current_version'] ?></td>
                            <td><?= formatDate($sub['submission_date']) ?></td>
                            <td>
                                <a href="index.php?page=approval&id=<?= $sub['submission_id'] ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Manage
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>