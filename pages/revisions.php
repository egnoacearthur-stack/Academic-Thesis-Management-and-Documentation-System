<?php
/**
 * Revisions Page - FIXED: Removed duplicate code block
 */

requireRole('student');

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle un-submit action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unsubmit_thesis'])) {
    $submissionId = intval($_POST['submission_id']);
    
    $checkStmt = $conn->prepare("SELECT status FROM thesis_submissions WHERE submission_id = ? AND student_id = ?");
    $checkStmt->bind_param("ii", $submissionId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $subData = $result->fetch_assoc();
        
        if ($subData['status'] === 'approved') {
            $error = 'Cannot un-submit an approved thesis. It has been finalized by your advisor.';
        } elseif ($subData['status'] === 'submitted') {
            $updateStmt = $conn->prepare("UPDATE thesis_submissions SET status = 'draft' WHERE submission_id = ?");
            $updateStmt->bind_param("i", $submissionId);
            
            if ($updateStmt->execute()) {
                logActivity($conn, $userId, 'unsubmit_thesis', 'thesis_submission', $submissionId);
                $success = 'Thesis un-submitted successfully. Status changed to Draft.';
            } else {
                $error = 'Failed to un-submit thesis';
            }
        } else {
            $error = 'Can only un-submit theses with "Submitted" status';
        }
    } else {
        $error = 'Submission not found or access denied';
    }
}    

// Handle revision upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_revision'])) {
    $submissionId = intval($_POST['submission_id']);
    $revisionNotes = sanitize($_POST['revision_notes'] ?? '');
    $changesSummary = sanitize($_POST['changes_summary'] ?? '');
    $revisionType = $_POST['revision_type'] ?? 'reuse';
    
    $checkStmt = $conn->prepare("SELECT current_version, file_path, file_name FROM thesis_submissions WHERE submission_id = ? AND student_id = ?");
    
    if (!$checkStmt) {
        $error = 'Database error: ' . $conn->error;
    } else {
        $checkStmt->bind_param("ii", $submissionId, $userId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
    
        if ($result && $result->num_rows > 0) {
            $currentData = $result->fetch_assoc();
            $newVersion = $currentData['current_version'] + 1;
            
            // Check if reusing file or uploading new
            if ($revisionType === 'reuse') {
                // REUSE EXISTING FILE
                $uploadResult = [
                    'success' => true,
                    'file_path' => $currentData['file_path'],
                    'file_name' => $currentData['file_name'],
                    'file_size' => filesize($currentData['file_path'])
                ];
            } else {
                // UPLOAD NEW FILE
                if (isset($_FILES['revision_file']) && $_FILES['revision_file']['error'] === 0) {
                    $uploadResult = uploadFile($_FILES['revision_file'], $submissionId);
                } else {
                    $error = 'Please select a file to upload';
                    $uploadResult = ['success' => false];
                }
            }
            
            if ($uploadResult['success']) {
                $stmt = $conn->prepare("
                    INSERT INTO revision_history (submission_id, version_number, file_path, file_name, revision_notes, uploaded_by, changes_summary) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iisssis", 
                    $submissionId,
                    $newVersion,
                    $uploadResult['file_path'],
                    $uploadResult['file_name'],
                    $revisionNotes,
                    $userId,
                    $changesSummary
                );
                
                if ($stmt->execute()) {
                    // Only update file path if new file was uploaded
                    if ($revisionType === 'new') {
                        $updateStmt = $conn->prepare("
                            UPDATE thesis_submissions 
                            SET current_version = ?, file_path = ?, file_name = ?, status = 'submitted' 
                            WHERE submission_id = ?
                        ");
                        $updateStmt->bind_param("issi", $newVersion, $uploadResult['file_path'], $uploadResult['file_name'], $submissionId);
                    } else {
                        $updateStmt = $conn->prepare("
                            UPDATE thesis_submissions 
                            SET current_version = ?, status = 'submitted' 
                            WHERE submission_id = ?
                        ");
                        $updateStmt->bind_param("ii", $newVersion, $submissionId);
                    }
                    $updateStmt->execute();
                    
                    // Notify advisor
                    $advisorStmt = $conn->prepare("SELECT advisor_id, title FROM thesis_submissions WHERE submission_id = ?");
                    $advisorStmt->bind_param("i", $submissionId);
                    $advisorStmt->execute();
                    $thesisData = $advisorStmt->get_result()->fetch_assoc();
                    
                    if ($thesisData && $thesisData['advisor_id']) {
                        createNotification($conn, $thesisData['advisor_id'], 'Thesis Revision Uploaded', 
                            'A new revision (v' . $newVersion . ') has been uploaded for "' . $thesisData['title'] . '"', 
                            'revision', $submissionId);
                    }
                    
                    logActivity($conn, $userId, 'upload_revision', 'thesis_submission', $submissionId, "Version $newVersion ($revisionType)");
                    
                    $success = 'Revision submitted successfully (Version ' . $newVersion . ')';
                } else {
                    $error = 'Failed to save revision: ' . $stmt->error;
                }
            } else {
                $error = $uploadResult['message'] ?? 'Failed to process file';
            }
        } else {
            $error = 'Submission not found or access denied';
        }
    }
}

// Get submission details if ID provided
$viewingSubmission = null;
$revisionHistory = null;
$feedbackHistory = null;

if (isset($_GET['id'])) {
    $submissionId = intval($_GET['id']);
    
    $stmt = $conn->prepare("
        SELECT ts.*, u.full_name as advisor_name 
        FROM thesis_submissions ts 
        LEFT JOIN users u ON ts.advisor_id = u.user_id 
        WHERE ts.submission_id = ? AND ts.student_id = ?
    ");
    $stmt->bind_param("ii", $submissionId, $userId);
    $stmt->execute();
    $viewingSubmission = $stmt->get_result()->fetch_assoc();
    
    if ($viewingSubmission) {
        $revisionHistory = getRevisionHistory($conn, $submissionId);
        $feedbackHistory = getFeedback($conn, $submissionId);
    }
}

// Get all student submissions with filters
$revFilterDept = sanitize($_GET['rev_filter_department'] ?? '');
$revFilterStatus = sanitize($_GET['rev_filter_status'] ?? '');

$types = 'i'; $params = [$userId];
$sql = "SELECT ts.*, u.full_name as advisor_name FROM thesis_submissions ts LEFT JOIN users u ON ts.advisor_id = u.user_id WHERE ts.student_id = ?";
if ($revFilterStatus !== '') {
    $sql .= " AND ts.status = ?";
    $types .= 's'; $params[] = $revFilterStatus;
}
if ($revFilterDept !== '') {
    $sql .= " AND ts.department = ?";
    $types .= 's'; $params[] = $revFilterDept;
}
$sql .= " ORDER BY ts.submission_date DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (count($params) === 1) {
        $stmt->bind_param($types, $params[0]);
    } else {
        $bindArr = array_merge([$types], $params);
        $refs = [];
        foreach ($bindArr as $k => $v) $refs[$k] = &$bindArr[$k];
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    $submissions = $stmt->get_result();
} else {
    $submissions = false;
}
?>

<div class="page-header">
    <h1><i class="fas fa-history"></i> Revision History</h1>
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

<?php if ($viewingSubmission): ?>
    <!-- Thesis Details -->
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
                    <strong>Current Version:</strong> v<?= $viewingSubmission['current_version'] ?>
                </div>
                <div class="detail-row">
                    <strong>Program:</strong> <?= htmlspecialchars(formatProgramName($viewingSubmission['program'])) ?>
                </div>
                <div class="detail-row">
                    <strong>Advisor:</strong> <?= htmlspecialchars($viewingSubmission['advisor_name'] ?? 'Not assigned') ?>
                </div>
            </div>
            
            <?php if ($viewingSubmission['status'] === 'submitted'): ?>
                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
                    <p style="margin: 0 0 10px 0;"><strong><i class="fas fa-info-circle"></i> Want to make changes?</strong></p>
                    <p style="margin: 0 0 15px 0;">You can un-submit this thesis to edit it before review.</p>
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to un-submit this thesis? This will change its status to Draft.');">
                        <input type="hidden" name="submission_id" value="<?= $viewingSubmission['submission_id'] ?>">
                        <button type="submit" name="unsubmit_thesis" class="btn btn-warning">
                            <i class="fas fa-undo"></i> Un-submit Thesis
                        </button>
                    </form>
                </div>
            <?php elseif ($viewingSubmission['status'] === 'approved'): ?>
                <div style="margin-top: 20px; padding: 15px; background: #d4edda; border-left: 4px solid #28a745; border-radius: 5px;">
                    <p style="margin: 0;"><strong><i class="fas fa-check-circle"></i> Thesis Approved!</strong></p>
                    <p style="margin: 10px 0 0 0;">This thesis has been approved and finalized. No further changes can be made.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Feedback Received -->
    <?php if ($feedbackHistory && $feedbackHistory->num_rows > 0): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h2><i class="fas fa-comments"></i> Feedback from Reviewers</h2>
            </div>
            <div class="card-body">
                <?php while ($feedback = $feedbackHistory->fetch_assoc()): ?>
                    <div class="feedback-item">
                        <div class="feedback-header">
                            <strong><?= htmlspecialchars($feedback['reviewer_name']) ?></strong>
                            <span class="badge badge-info"><?= ucfirst($feedback['reviewer_role']) ?></span>
                            <span class="feedback-date"><?= timeAgo($feedback['created_at']) ?></span>
                        </div>
                        <div class="feedback-text">
                            <?= nl2br(htmlspecialchars($feedback['feedback_text'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Upload New Revision -->
    <?php if ($viewingSubmission['status'] === 'revision_requested' || $viewingSubmission['status'] === 'under_review'): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h2><i class="fas fa-upload"></i> Submit New Revision</h2>
        </div>
        <div class="card-body">
            <div class="revision-options" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <p style="margin-bottom: 15px;"><strong>Choose revision method:</strong></p>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <label style="flex: 1; min-width: 200px; cursor: pointer;">
                        <input type="radio" name="revision_type" value="reuse" checked onchange="toggleFileUpload(false)">
                        <strong>📄 Reuse Current File</strong>
                        <small style="display: block; color: #7f8c8d;">Use existing file with new notes</small>
                    </label>
                    <label style="flex: 1; min-width: 200px; cursor: pointer;">
                        <input type="radio" name="revision_type" value="new" onchange="toggleFileUpload(true)">
                        <strong>📤 Upload New File</strong>
                        <small style="display: block; color: #7f8c8d;">Submit updated document</small>
                    </label>
                </div>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data" id="revisionForm">
                <input type="hidden" name="submission_id" value="<?= $viewingSubmission['submission_id'] ?>">
                
                <div class="form-group">
                    <label for="changes_summary">Summary of Changes <span class="required">*</span></label>
                    <textarea id="changes_summary" name="changes_summary" class="form-control" rows="4" required 
                              placeholder="Describe what changes you made based on feedback"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="revision_notes">Additional Notes</label>
                    <textarea id="revision_notes" name="revision_notes" class="form-control" rows="3" 
                              placeholder="Any additional information about this revision"></textarea>
                </div>
                
                <div class="form-group" id="fileUploadSection" style="display: none;">
                    <label for="revision_file">Upload Revised File (PDF, DOC, DOCX) <span class="required">*</span></label>
                    <input type="file" id="revision_file" name="revision_file" class="form-control-file" 
                           accept=".pdf,.doc,.docx">
                    <small class="form-text">This will be version <?= $viewingSubmission['current_version'] + 1 ?></small>
                </div>
                
                <button type="submit" name="upload_revision" class="btn btn-primary">
                    <i class="fas fa-check"></i> Submit Revision (Version <?= $viewingSubmission['current_version'] + 1 ?>)
                </button>
            </form>
            
            <script>
            function toggleFileUpload(show) {
                const fileSection = document.getElementById('fileUploadSection');
                const fileInput = document.getElementById('revision_file');
                
                if (show) {
                    fileSection.style.display = 'block';
                    fileInput.required = true;
                } else {
                    fileSection.style.display = 'none';
                    fileInput.required = false;
                    fileInput.value = '';
                }
            }
            
            toggleFileUpload(false);
            </script>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Revision History Timeline -->
    <div class="card mt-4">
        <div class="card-header">
            <h2><i class="fas fa-clock"></i> Version History</h2>
        </div>
        <div class="card-body">
            <?php if ($revisionHistory && $revisionHistory->num_rows > 0): ?>
                <div class="timeline">
                    <?php while ($revision = $revisionHistory->fetch_assoc()): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h4>Version <?= $revision['version_number'] ?></h4>
                                    <span class="timeline-date"><?= formatDateTime($revision['uploaded_at']) ?></span>
                                </div>
                                <?php if ($revision['changes_summary']): ?>
                                    <p><strong>Changes:</strong> <?= nl2br(htmlspecialchars($revision['changes_summary'])) ?></p>
                                <?php endif; ?>
                                <p><strong>File:</strong> <?= htmlspecialchars($revision['file_name']) ?></p>
                                <?php if (file_exists($revision['file_path'])): ?>
                                    <a href="<?= $revision['file_path'] ?>" class="btn btn-sm btn-success" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-data">No revision history available</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-3">
        <a href="index.php?page=revisions" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
    
<?php else: ?>
    <!-- List of Submissions -->
    <div class="card">
        <div class="card-header">
            <div style="display:flex;align-items:center;justify-content:space-between; width:100%;">
                <h2><i class="fas fa-list"></i> My Thesis Submissions</h2>
                <form method="GET" action="index.php" style="display:flex;gap:8px;align-items:center;margin:0;">
                    <input type="hidden" name="page" value="revisions">
                    <select name="rev_filter_department" class="form-control" style="min-width:160px;">
                        <option value="">All Departments</option>
                        <?php
                            $myDepts = $conn->query("SELECT DISTINCT department FROM thesis_submissions WHERE student_id = " . intval($userId) . " AND department <> '' ORDER BY department");
                            if ($myDepts):
                                while ($md = $myDepts->fetch_assoc()):
                                    $sel = ($revFilterDept === $md['department']) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($md['department']) ?>" <?= $sel ?>><?= htmlspecialchars($md['department']) ?></option>
                        <?php 
                                endwhile;
                            endif;
                        ?>
                    </select>
                    <select name="rev_filter_status" class="form-control" style="min-width:160px;">
                        <option value="">All Statuses</option>
                        <option value="submitted" <?= ($revFilterStatus === 'submitted') ? 'selected' : '' ?>>Submitted</option>
                        <option value="under_review" <?= ($revFilterStatus === 'under_review') ? 'selected' : '' ?>>Under Review</option>
                        <option value="revision_requested" <?= ($revFilterStatus === 'revision_requested') ? 'selected' : '' ?>>Revision Requested</option>
                        <option value="approved" <?= ($revFilterStatus === 'approved') ? 'selected' : '' ?>>Approved</option>
                    </select>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="index.php?page=revisions" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body">
            <?php if ($submissions && $submissions->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Current Version</th>
                            <th>Last Update</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sub = $submissions->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($sub['title']) ?></td>
                                <td><span class="badge <?= getStatusBadge($sub['status']) ?>"><?= ucwords(str_replace('_', ' ', $sub['status'])) ?></span></td>
                                <td>v<?= $sub['current_version'] ?></td>
                                <td><?= formatDateTime($sub['submission_date']) ?></td>
                                <td>
                                    <a href="index.php?page=revisions&id=<?= $sub['submission_id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-history"></i> View History
                                    </a>
                                    <?php if ($sub['status'] === 'submitted'): ?>
                                        <form method="POST" action="" style="display:inline-block;" onsubmit="return confirm('Un-submit this thesis?');">
                                            <input type="hidden" name="submission_id" value="<?= $sub['submission_id'] ?>">
                                            <button type="submit" name="unsubmit_thesis" class="btn btn-sm btn-warning">
                                                <i class="fas fa-undo"></i> Un-submit
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No submissions yet. <a href="index.php?page=submission">Submit your thesis</a> to get started.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<style>
.timeline {
    position: relative;
    padding-left: 50px;
}

.timeline-item {
    position: relative;
    padding-bottom: 30px;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: -35px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dfe6e9;
}

.timeline-item:last-child:before {
    display: none;
}

.timeline-marker {
    position: absolute;
    left: -47px;
    top: 0;
    width: 24px;
    height: 24px;
    background: #3498db;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}

.timeline-content {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 3px solid #3498db;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.timeline-header h4 {
    margin: 0;
    color: #2c3e50;
}

.timeline-date {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.feedback-item {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #3498db;
}

.feedback-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.feedback-date {
    margin-left: auto;
    color: #7f8c8d;
    font-size: 0.85rem;
}

.feedback-text {
    line-height: 1.6;
    color: #2c3e50;
}

/* DARK MODE */
body.dark-theme .thesis-details {
    background: #0f3460 !important;
    color: #ecf0f1 !important;
}

body.dark-theme .detail-row {
    color: #ecf0f1 !important;
    border-bottom-color: #1a2942 !important;
}

body.dark-theme .detail-row strong {
    color: #ecf0f1 !important;
}

body.dark-theme .timeline-content {
    background: #0f3460 !important;
    color: #ecf0f1 !important;
}

body.dark-theme .timeline-header h4 {
    color: #ecf0f1 !important;
}

body.dark-theme .timeline-content p {
    color: #b0bec5 !important;
}

body.dark-theme .timeline-date {
    color: #95a5a6 !important;
}

body.dark-theme .feedback-item {
    background: #0f3460 !important;
    color: #ecf0f1 !important;
}

body.dark-theme .feedback-header strong {
    color: #ecf0f1 !important;
}

body.dark-theme .feedback-text {
    color: #b0bec5 !important;
}

body.dark-theme .feedback-date {
    color: #95a5a6 !important;
}

body.dark-theme .form-group label {
    color: #ecf0f1 !important;
}

body.dark-theme .form-control {
    background: #0f3460 !important;
    color: #ecf0f1 !important;
    border-color: #3a4f63 !important;
}

body.dark-theme .form-control:focus {
    background: #1a2942 !important;
    border-color: #3498db !important;
}

body.dark-theme textarea.form-control {
    background: #0f3460 !important;
    color: #ecf0f1 !important;
}

body.dark-theme .form-text {
    color: #95a5a6 !important;
}
</style>