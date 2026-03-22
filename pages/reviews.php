<?php
/**
 * Reviews Page - COMPLETE VERSION
 * For Advisors and Panelists to review theses
 */

if (!hasRole('advisor') && !hasRole('panelist')) {
    die('<div class="alert alert-danger">Access denied. This page is only for advisors and panelists.</div>');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$error = '';
$success = '';

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $submissionId = intval($_POST['submission_id']);
    $feedbackText = sanitize($_POST['feedback_text']);
    $section = sanitize($_POST['section'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $feedbackType = sanitize($_POST['feedback_type']);
    
    if (empty($feedbackText)) {
        $error = 'Please provide feedback text';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO feedback (submission_id, reviewer_id, reviewer_role, feedback_text, section, rating, feedback_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iissiis", $submissionId, $userId, $userRole, $feedbackText, $section, $rating, $feedbackType);
        
        if ($stmt->execute()) {
            // Update thesis status
            $conn->query("UPDATE thesis_submissions SET status = 'under_review' WHERE submission_id = $submissionId");
            
            // Get student ID for notification
            $subStmt = $conn->prepare("SELECT student_id, title FROM thesis_submissions WHERE submission_id = ?");
            $subStmt->bind_param("i", $submissionId);
            $subStmt->execute();
            $subResult = $subStmt->get_result()->fetch_assoc();
            
            // Notify student
            createNotification($conn, $subResult['student_id'], 'New Feedback Received', 
                'Your thesis "' . $subResult['title'] . '" has received new feedback from ' . $_SESSION['user_name'], 
                'feedback', $submissionId);
            // ✅ SEND EMAIL TO STUDENT
            $studentStmt = $conn->prepare("SELECT email, first_name FROM users WHERE user_id = ?");
            $studentStmt->bind_param("i", $subResult['student_id']);
            $studentStmt->execute();
            $studentData = $studentStmt->get_result()->fetch_assoc();
            
            if ($studentData) {
                sendFeedbackEmail($studentData['email'], $studentData['first_name'], $subResult['title'], $_SESSION['user_name']);
            }
            $success = 'Feedback submitted successfully';
        } else {
            $error = 'Failed to submit feedback';
        }
    }
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_decision'])) {
    $submissionId = intval($_POST['submission_id']);
    $decision = sanitize($_POST['decision']);
    $comments = sanitize($_POST['decision_comments'] ?? '');
    
    $stmt = $conn->prepare("
        INSERT INTO approval_workflow (submission_id, approver_id, approver_role, decision, decision_date, comments) 
        VALUES (?, ?, ?, ?, NOW(), ?)
    ");
    $stmt->bind_param("iisss", $submissionId, $userId, $userRole, $decision, $comments);
    
    if ($stmt->execute()) {
        // Update thesis status based on decision
        $newStatus = ($decision === 'approved') ? 'approved' : (($decision === 'revision_required') ? 'revision_requested' : 'under_review');
        $conn->query("UPDATE thesis_submissions SET status = '$newStatus' WHERE submission_id = $submissionId");
        
        // Get student info
        $subStmt = $conn->prepare("SELECT student_id, title FROM thesis_submissions WHERE submission_id = ?");
        $subStmt->bind_param("i", $submissionId);
        $subStmt->execute();
        $subResult = $subStmt->get_result()->fetch_assoc();
        
        // Notify student
        $notifTitle = ($decision === 'approved') ? 'Thesis Approved!' : 'Thesis Review Status';
        createNotification($conn, $subResult['student_id'], $notifTitle, 
            'Your thesis "' . $subResult['title'] . '" has been reviewed. Status: ' . ucwords(str_replace('_', ' ', $decision)), 
            'approval', $submissionId);
        
        $success = 'Status submitted successfully';
    } else {
        $error = 'Failed to submit status';
    }
}

// Get specific submission if ID provided
$viewingSubmission = null;
if (isset($_GET['id'])) {
    $submissionId = intval($_GET['id']);
    $stmt = $conn->prepare("
        SELECT ts.*, 
        CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as student_name, 
        u.email as student_email,
               adv.full_name as advisor_name 
        FROM thesis_submissions ts 
        JOIN users u ON ts.student_id = u.user_id 
        LEFT JOIN users adv ON ts.advisor_id = adv.user_id 
        WHERE ts.submission_id = ?
    ");
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $viewingSubmission = $stmt->get_result()->fetch_assoc();
    
    if ($viewingSubmission) {
        // Get feedback history (load into array so we can reuse it)
        $fbRes = getFeedback($conn, $submissionId);
        $feedbackHistory = [];
        if ($fbRes) {
            while ($f = $fbRes->fetch_assoc()) $feedbackHistory[] = $f;
        }

        // Get revision history (load into array and sort by uploaded_at desc)
        $revRes = getRevisionHistory($conn, $submissionId);
        $revisionHistory = [];
        if ($revRes) {
            while ($r = $revRes->fetch_assoc()) $revisionHistory[] = $r;
        }
        usort($revisionHistory, function($a, $b){
            return strtotime($b['uploaded_at']) <=> strtotime($a['uploaded_at']);
        });
    }
}

// Get list of submissions to review with optional filters (department, status)
$filterProgram = sanitize($_GET['filter_program'] ?? '');
$filterStatus = sanitize($_GET['filter_status'] ?? '');

$params = [];
$types = '';
if ($userRole === 'advisor') {
    $sql = "SELECT ts.*, 
            CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as student_name, 
            u.email as student_email 
            FROM thesis_submissions ts 
            JOIN users u ON ts.student_id = u.user_id 
            WHERE ts.advisor_id = ?";
    $types .= 'i'; 
    $params[] = $userId;
} else {
    $sql = "SELECT ts.*, 
            CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as student_name, 
            pa.assigned_date, 
            pa.status as assignment_status 
            FROM thesis_submissions ts 
            JOIN panelist_assignments pa ON ts.submission_id = pa.submission_id 
            JOIN users u ON ts.student_id = u.user_id 
            WHERE pa.panelist_id = ?";
    $types .= 'i'; 
    $params[] = $userId;
}

if ($filterProgram !== '') {
    $sql .= " AND ts.program = ?";
    $types .= 's'; $params[] = $filterProgram;
}

if ($filterStatus !== '') {
    $sql .= " AND ts.status = ?";
    $types .= 's'; $params[] = $filterStatus;
}

$sql .= " ORDER BY ts.submission_date DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $submissions = false;
} else {
    if ($types !== '') {
        $bindArr = array_merge([$types], $params);
        $refs = [];
        foreach ($bindArr as $k => $v) $refs[$k] = &$bindArr[$k];
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    $submissions = $stmt->get_result();
}
?>

<div class="page-header">
    <h1><i class="fas fa-tasks"></i> Thesis Reviews</h1>
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
            <span class="badge <?= getStatusBadge($viewingSubmission['status']) ?>"><?= ucwords(str_replace('_', ' ', $viewingSubmission['status'])) ?></span>
        </div>
        <div class="card-body">
            <div class="thesis-details">
                <div class="detail-row">
                    <strong>Student:</strong> <?= htmlspecialchars($viewingSubmission['student_name']) ?> (<?= htmlspecialchars($viewingSubmission['student_email']) ?>)
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
                <div class="detail-row">
                    <strong>Advisor:</strong> <?= htmlspecialchars($viewingSubmission['advisor_name'] ?? 'Not assigned') ?>
                </div>
            </div>
            
            <div class="mt-3">
                <h4>Abstract</h4>
                <p class="abstract-text"><?= nl2br(htmlspecialchars($viewingSubmission['abstract'])) ?></p>
            </div>
            
            <?php if ($viewingSubmission['keywords']): ?>
                <div class="mt-3">
                    <h4>Keywords</h4>
                    <p><?= htmlspecialchars($viewingSubmission['keywords']) ?></p>
                </div>
            <?php endif; ?>
            
            <div class="mt-3">
                <h4>Thesis File</h4>
                <?php if (file_exists($viewingSubmission['file_path'])): ?>
                    <a href="download.php?id=<?= $viewingSubmission['submission_id'] ?>" 
                        class="btn btn-success">
                        <i class="fas fa-download"></i> Download Thesis
                    </a>
                <?php else: ?>
                    <p class="text-danger">File not found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Version History -->
    <div class="card mt-4">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Version History</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($revisionHistory)): ?>
                <?php
                    // revisionHistory is sorted desc (newest first)
                    for ($i = 0; $i < count($revisionHistory); $i++):
                        $rev = $revisionHistory[$i];
                        $nextRev = $revisionHistory[$i-1] ?? null; // because sorted desc, previous index is newer
                        $start = strtotime($rev['uploaded_at']);
                        $end = $nextRev ? strtotime($nextRev['uploaded_at']) : PHP_INT_MAX;
                        // check if there is feedback for this version
                        $hasFeedbackForVersion = false;
                        foreach ($feedbackHistory as $fbchk) {
                            $tchk = strtotime($fbchk['created_at']);
                            if ($tchk >= $start && $tchk < $end) { $hasFeedbackForVersion = true; break; }
                        }
                        $vfId = 'vf_' . $viewingSubmission['submission_id'] . '_' . intval($rev['version_number']);
                ?>
                    <div class="revision-item" style="background:#f8f9fa;padding:20px;border-radius:8px;margin-bottom:15px;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                            <div>
                                <strong style="background:#2d6cdf;color:#fff;padding:8px 12px;border-radius:6px;font-size:1rem;display:inline-block;">Version v<?= intval($rev['version_number']) ?></strong>
                                <div style="height:10px;"></div>
                                <div style="color:#7f8c8d;font-size:0.95rem;"><strong>Submission Date:</strong> <?= formatDateTime($rev['uploaded_at']) ?></div>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
                                <?php if (file_exists($rev['file_path'])): ?>
                                    <a href="<?= $rev['file_path'] ?>" class="btn btn-success btn-sm" download>
                                        <i class="fas fa-download"></i> Download v<?= intval($rev['version_number']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-danger">File not available</span>
                                <?php endif; ?>
                                <?php if ($hasFeedbackForVersion): ?>
                                    <button type="button" class="btn btn-light btn-sm view-feedback-btn" data-target="<?= $vfId ?>">
                                        <i class="fas fa-eye"></i> View Feedback
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="margin-top:14px;">
                            <div style="font-weight:600;margin-bottom:6px;">Abstract</div>
                            <div style="color:#7f8c8d;margin-bottom:12px;"><?= nl2br(htmlspecialchars($viewingSubmission['abstract'])) ?></div>
                            <?php if ($viewingSubmission['keywords']): ?>
                                <div style="font-weight:600;margin-bottom:6px;">Keywords</div>
                                <div style="color:#7f8c8d;margin-bottom:6px;"><?= htmlspecialchars($viewingSubmission['keywords']) ?></div>
                            <?php endif; ?>

                            <?php if ($hasFeedbackForVersion): ?>
                                <div id="<?= $vfId ?>" class="version-feedback" style="display:none;margin-top:12px;">
                                    <?php
                                        foreach ($feedbackHistory as $fb) {
                                            $t = strtotime($fb['created_at']);
                                            if ($t >= $start && $t < $end) {
                                                ?>
                                                <div style="background:#fff;padding:12px;border-radius:6px;margin-bottom:10px;border-left:4px solid #3498db;">
                                                    <div style="display:flex;align-items:center;gap:10px;">
                                                        <strong><?= htmlspecialchars($fb['reviewer_name']) ?></strong>
                                                        <span class="badge badge-info"><?= ucfirst($fb['reviewer_role']) ?></span>
                                                        <span style="margin-left:auto;color:#7f8c8d;"><?= timeAgo($fb['created_at']) ?></span>
                                                    </div>
                                                    <div style="margin-top:8px;color:#7f8c8d;">
                                                        <?php if ($fb['section']): ?><div><strong>Section:</strong> <?= htmlspecialchars($fb['section']) ?></div><?php endif; ?>
                                                        <?php if ($fb['rating']): ?><div><strong>Rating:</strong> <?= str_repeat('⭐',$fb['rating']) ?></div><?php endif; ?>
                                                        <div><strong>Type:</strong> <?= ucwords(str_replace('_',' ',$fb['feedback_type'])) ?></div>
                                                        <div style="margin-top:8px;"><?= nl2br(htmlspecialchars($fb['feedback_text'])) ?></div>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                        }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php else: ?>
                <p class="no-data">No versions available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feedback History -->
    <div class="card mt-4">
        <div class="card-header">
            <h2><i class="fas fa-comments"></i> Feedback History</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($feedbackHistory)): ?>
                <?php foreach ($feedbackHistory as $feedback): ?>
                    <?php
                        // Determine which revision/version this feedback pertains to by comparing timestamps
                        $versionForFeedback = null;
                        if (!empty($revisionHistory)) {
                            foreach ($revisionHistory as $rev) {
                                if (strtotime($rev['uploaded_at']) <= strtotime($feedback['created_at'])) {
                                    $versionForFeedback = $rev['version_number'];
                                    break;
                                }
                            }
                        }
                        if ($versionForFeedback === null) {
                            // fallback to current version
                            $versionForFeedback = $viewingSubmission['current_version'] ?? 1;
                        }
                    ?>
                    <div class="feedback-item">
                        <div class="feedback-header">
                            <strong><?= htmlspecialchars($feedback['reviewer_name']) ?></strong>
                            <span class="badge badge-info"><?= ucfirst($feedback['reviewer_role']) ?></span>
                            <span class="feedback-date"><?= timeAgo($feedback['created_at']) ?></span>
                        </div>
                        <div class="feedback-meta">
                            <?php if ($feedback['section']): ?>
                                <span><strong>Section:</strong> <?= htmlspecialchars($feedback['section']) ?></span>
                            <?php endif; ?>
                            <?php if ($feedback['rating']): ?>
                                <span><strong>Rating:</strong> <?= str_repeat('⭐', $feedback['rating']) ?></span>
                            <?php endif; ?>
                            <span><strong>Type:</strong> <?= ucwords(str_replace('_', ' ', $feedback['feedback_type'])) ?></span>
                            <span><strong>Version:</strong> v<?= intval($versionForFeedback) ?></span>
                        </div>
                        <div class="feedback-text">
                            <?= nl2br(htmlspecialchars($feedback['feedback_text'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data">No feedback yet</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Provide Feedback Form -->
    <div class="card mt-4">
        <div class="card-header">
            <h2><i class="fas fa-comment-dots"></i> Provide Feedback</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="submission_id" value="<?= $viewingSubmission['submission_id'] ?>">
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="section">Section/Chapter</label>
                        <input type="text" id="section" name="section" class="form-control" placeholder="e.g., Chapter 3, Methodology">
                    </div>
                    
                    <div class="form-group col-md-3">
                        <label for="rating">Rating (1-5)</label>
                        <select id="rating" name="rating" class="form-control">
                            <option value="">Select rating</option>
                            <option value="1">1 - Needs Major Revision</option>
                            <option value="2">2 - Needs Improvement</option>
                            <option value="3">3 - Satisfactory</option>
                            <option value="4">4 - Good</option>
                            <option value="5">5 - Excellent</option>
                        </select>
                    </div>
                    
                    <div class="form-group col-md-3">
                        <label for="feedback_type">Feedback Type</label>
                        <select id="feedback_type" name="feedback_type" class="form-control" required>
                            <option value="general">General</option>
                            <option value="methodology">Methodology</option>
                            <option value="writing">Writing Style</option>
                            <option value="structure">Structure</option>
                            <option value="content">Content</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="feedback_text">Feedback/Comments <span class="required">*</span></label>
                    <textarea id="feedback_text" name="feedback_text" class="form-control" rows="6" required 
                              placeholder="Provide detailed feedback for the student..."></textarea>
                </div>
                
                <button type="submit" name="submit_feedback" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
            </form>
        </div>
    </div>
    
    <!-- Approval Decision -->
    <div class="card mt-4">
        <div class="card-header">
            <h2><i class="fas fa-gavel"></i> Status Review</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="submission_id" value="<?= $viewingSubmission['submission_id'] ?>">
                
                <div class="form-group">
                    <label for="decision">Status <span class="required">*</span></label>
                    <select id="decision" name="decision" class="form-control" required>
                        <option value="">Select Status</option>
                        <option value="approved">✓ Approve Thesis</option>
                        <option value="revision_required">↻ Request Revision</option>
                        <option value="pending">⏳ Keep Under Review</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="decision_comments">Comments</label>
                    <textarea id="decision_comments" name="decision_comments" class="form-control" rows="4"
                              placeholder="Optional: Add comments about your decision..."></textarea>
                </div>
                
                <button type="submit" name="submit_decision" class="btn btn-success">
                    <i class="fas fa-check"></i> Submit Status
                </button>
            </form>
        </div>
    </div>
    
    <div class="mt-3">
        <a href="index.php?page=reviews" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
    

<?php else: ?>
    <!-- List View -->
    <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <h2><i class="fas fa-list"></i> Theses to Review</h2>
            <form method="GET" action="index.php" style="display:flex;gap:8px;align-items:center;margin:0;">
                <input type="hidden" name="page" value="reviews">
                <select name="filter_program" class="form-control" style="min-width:180px;">
                    <option value="">All Programs</option>
                    <?php
                        $progRs = $conn->query("SELECT DISTINCT program FROM thesis_submissions WHERE program <> '' ORDER BY program");
                        while ($d = $progRs->fetch_assoc()):
                            $sel = ($filterProgram === $d['program']) ? 'selected' : '';
                            $displayProg = formatProgramName($d['program']);
                            if ($displayProg === 'BS in Computer Science') continue; // remove from filters
                    ?>
                        <option value="<?= htmlspecialchars($d['program']) ?>" <?= $sel ?>><?= htmlspecialchars($displayProg) ?></option>
                    <?php endwhile; ?>
                </select>
                <select name="filter_status" class="form-control" style="min-width:160px;">
                    <option value="">All Statuses</option>
                    <option value="submitted" <?= ($filterStatus === 'submitted') ? 'selected' : '' ?>>Submitted</option>
                    <option value="under_review" <?= ($filterStatus === 'under_review') ? 'selected' : '' ?>>Under Review</option>
                    <option value="revision_requested" <?= ($filterStatus === 'revision_requested') ? 'selected' : '' ?>>Revision Requested</option>
                    <option value="approved" <?= ($filterStatus === 'approved') ? 'selected' : '' ?>>Approved</option>
                </select>
                <div style="display:flex;gap:6px;align-items:center;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="index.php?page=reviews" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <?php if ($submissions && $submissions->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Thesis Title</th>
                                <th>Program</th>
                                <th>Status</th>
                                <th>Submission Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($sub = $submissions->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sub['student_name']) ?></td>
                                    <td><?= htmlspecialchars($sub['title']) ?></td>
                                    <td><?= htmlspecialchars(formatProgramName($sub['program'])) ?></td>
                                    <td><span class="badge <?= getStatusBadge($sub['status']) ?>"><?= ucwords(str_replace('_', ' ', $sub['status'])) ?></span></td>
                                    <td><?= formatDate($sub['submission_date']) ?></td>
                                    <td>
                                        <a href="index.php?page=reviews&id=<?= $sub['submission_id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-clipboard-check"></i> Review
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; display: block; margin-bottom: 15px;"></i>
                    No theses assigned for review
                </p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<style>
/* ✅ DARK THEME - Advisor/Panelist Reviews Page */

/* Abstract text box */
body.dark-theme .abstract-text {
    background: #0f3460 !important;
    color: #ecf0f1 !important;
}

/* Revision items */
body.dark-theme .revision-item {
    background: #0f3460 !important;
    color: #ecf0f1 !important;
}

/* Version feedback sections */
body.dark-theme .version-feedback {
    background: #1a2942 !important;
    color: #ecf0f1 !important;
}

body.dark-theme .version-feedback > div {
    background: #0f3460 !important;
    color: #ecf0f1 !important;
    border-left-color: #3498db !important;
}

/* Feedback metadata */
body.dark-theme .feedback-meta {
    color: #b0bec5 !important;
}

/* All text in cards */
body.dark-theme .card-body p,
body.dark-theme .card-body div,
body.dark-theme .card-body span {
    color: #ecf0f1 !important;
}

/* Form labels in dark mode */
body.dark-theme .form-group label {
    color: #ecf0f1 !important;
}

/* Select dropdowns */
body.dark-theme select.form-control {
    background: #0f3460 !important;
    color: #ecf0f1 !important;
    border-color: #3a4f63 !important;
}

body.dark-theme select.form-control option {
    background: #0f3460 !important;
    color: #ecf0f1 !important;
}

/* Text in buttons */
body.dark-theme .btn {
    color: #ffffff !important;
}

/* Specific text elements */
body.dark-theme strong,
body.dark-theme .detail-row strong {
    color: #ecf0f1 !important;
}

/* Dates and timestamps */
body.dark-theme .timeline-date,
body.dark-theme .feedback-date,
body.dark-theme .notification-time {
    color: #95a5a6 !important;
}

/* No data messages */
body.dark-theme .no-data {
    color: #7f8c8d !important;
}

/* Repository cards */
body.dark-theme .thesis-card {
    background: #16213e !important;
    color: #ecf0f1 !important;
    border-color: #0f3460 !important;
}

body.dark-theme .thesis-card-header {
    background: linear-gradient(135deg, #0f3460, #16213e) !important;
    color: #ecf0f1 !important;
}

body.dark-theme .thesis-card-body {
    color: #ecf0f1 !important;
}

body.dark-theme .thesis-meta {
    color: #b0bec5 !important;
}

body.dark-theme .thesis-abstract p {
    color: #b0bec5 !important;
}

/* Modal in dark mode */
body.dark-theme .modal-content {
    background: #16213e !important;
    color: #ecf0f1 !important;
}

body.dark-theme .modal-content h2,
body.dark-theme .modal-content h3,
body.dark-theme .modal-content p,
body.dark-theme .modal-content strong {
    color: #ecf0f1 !important;
}

/* Keyword tags */
body.dark-theme .keyword-tag {
    background: #3498db !important;
    color: #ffffff !important;
}

/* Ensure all paragraph text is visible */
body.dark-theme p {
    color: #ecf0f1 !important;
}

/* Feedback form textareas */
body.dark-theme textarea.form-control {
    background: #0f3460 !important;
    color: #ecf0f1 !important;
    border-color: #3a4f63 !important;
}

body.dark-theme textarea.form-control:focus {
    background: #1a2942 !important;
    border-color: #3498db !important;
    color: #ecf0f1 !important;
}
</style>

<script>
// Toggle version feedback sections
document.addEventListener('click', function(e){
    if (e.target && (e.target.classList.contains('view-feedback-btn') || e.target.closest('.view-feedback-btn'))) {
        var btn = e.target.classList.contains('view-feedback-btn') ? e.target : e.target.closest('.view-feedback-btn');
        var target = btn.getAttribute('data-target');
        var el = document.getElementById(target);
        if (el) {
            el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
        }
    }
});
</script>