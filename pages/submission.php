<?php
// ...existing code...
/**
 * Submission Page - COMPLETE FIXED VERSION
 * Shows form properly with all fields
 */

requireRole('student');

$error = '';
$success = '';
$userId = $_SESSION['user_id'];

// --- START: ADDED/CHANGED: load departments, programs and advisors list for dropdown/filtering ---
$departments = $conn->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department <> '' ORDER BY department");
$programs = $conn->query("SELECT DISTINCT program FROM thesis_submissions WHERE program IS NOT NULL AND program <> '' ORDER BY program");

// Fetch advisors into array so we can add data-department on each option for client-side filtering
$advisorsResult = $conn->query("SELECT user_id, full_name, department FROM users WHERE role = 'advisor' AND status = 'active' ORDER BY full_name");
$advisorsList = [];
if ($advisorsResult) {
    while ($r = $advisorsResult->fetch_assoc()) {
        $advisorsList[] = $r;
    }
}
// --- END: ADDED/CHANGED ---


// Handle new submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_thesis'])) {
    $title = sanitize($_POST['title'] ?? '');
    $abstract = sanitize($_POST['abstract'] ?? '');
    $keywords = sanitize($_POST['keywords'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $program = sanitize($_POST['program'] ?? '');
    $thesisType = sanitize($_POST['thesis_type'] ?? '');
    $advisorId = intval($_POST['advisor_id'] ?? 0);
    
    if (empty($title) || empty($abstract) || !isset($_FILES['thesis_file'])) {
        $error = 'Please fill in all required fields and upload a file';
    } elseif ($_FILES['thesis_file']['error'] !== 0) {
        $error = 'Error uploading file. Please try again.';
    } else {
        // Create temporary submission to get ID for file upload
        $stmt = $conn->prepare("
            INSERT INTO thesis_submissions (student_id, title, abstract, keywords, department, program, thesis_type, advisor_id, file_path, file_name, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', '', 'draft')
        ");
        $stmt->bind_param("issssssi", $userId, $title, $abstract, $keywords, $department, $program, $thesisType, $advisorId);
        
        if ($stmt->execute()) {
            $submissionId = $conn->insert_id;
            
            // Upload file
            $uploadResult = uploadFile($_FILES['thesis_file'], $submissionId);
            
            if ($uploadResult['success']) {
                // Update submission with file info
                $updateStmt = $conn->prepare("
                    UPDATE thesis_submissions 
                    SET file_path = ?, file_name = ?, file_size = ?, status = 'submitted' 
                    WHERE submission_id = ?
                ");
                $updateStmt->bind_param("ssii", 
                    $uploadResult['file_path'], 
                    $uploadResult['file_name'], 
                    $uploadResult['file_size'], 
                    $submissionId
                );
                $updateStmt->execute();
                
                // Create initial revision history
                $revStmt = $conn->prepare("
                    INSERT INTO revision_history (submission_id, version_number, file_path, file_name, uploaded_by, changes_summary) 
                    VALUES (?, 1, ?, ?, ?, 'Initial submission')
                ");
                $revStmt->bind_param("issi", $submissionId, $uploadResult['file_path'], $uploadResult['file_name'], $userId);
                $revStmt->execute();
                
                // Notify advisor
                if ($advisorId > 0) {
                    createNotification($conn, $advisorId, 'New Thesis Submission', 
                        "A new thesis titled '$title' has been submitted by " . $_SESSION['user_name'], 
                        'submission', $submissionId);
                }
                
                // Log activity
                logActivity($conn, $userId, 'submit_thesis', 'thesis_submission', $submissionId, $title);
                
                $success = 'Thesis submitted successfully!';
                
                // Refresh page to show new submission
                echo "<script>setTimeout(function(){ window.location.href = 'index.php?page=submission'; }, 2000);</script>";
            } else {
                // Delete the submission record if file upload failed
                $conn->query("DELETE FROM thesis_submissions WHERE submission_id = $submissionId");
                $error = $uploadResult['message'];
            }
        } else {
            $error = 'Failed to create submission: ' . $conn->error;
        }
    }
}

// Get advisors list (kept for backward compatibility in other parts)
$advisors = $conn->query("SELECT user_id, full_name, department FROM users WHERE role = 'advisor' AND status = 'active' ORDER BY full_name");

// Get user's submissions with optional filters
$subFilterDept = sanitize($_GET['sub_filter_department'] ?? '');
$subFilterStatus = sanitize($_GET['sub_filter_status'] ?? '');

$types = 'i'; $params = [$userId];
$sql = "SELECT ts.*, u.full_name as advisor_name FROM thesis_submissions ts LEFT JOIN users u ON ts.advisor_id = u.user_id WHERE ts.student_id = ?";
if ($subFilterStatus !== '') {
    $sql .= " AND ts.status = ?";
    $types .= 's'; $params[] = $subFilterStatus;
}
if ($subFilterDept !== '') {
    $sql .= " AND ts.department = ?";
    $types .= 's'; $params[] = $subFilterDept;
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
    <h1><i class="fas fa-upload"></i> Thesis Submission</h1>
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

<!-- Submission Form -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-file-upload"></i> Submit New Thesis</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data" id="submissionForm">
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="title">Thesis Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" class="form-control" required 
                           placeholder="Enter your complete thesis title">
                </div>
            </div>
            
            <div class="form-row">
                <!-- DEPARTMENT dropdown (changed) -->
                <div class="form-group col-md-6">
                    <label for="department">Department <span class="required">*</span></label>
                    <select id="department" name="department" class="form-control" required>
                        <option value="">Select Department</option>
                        <?php if ($departments && $departments->num_rows > 0): ?>
                            <?php while ($d = $departments->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($d['department']) ?>"><?= htmlspecialchars($d['department']) ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- PROGRAM dropdown (changed) -->
                <div class="form-group col-md-6">
                    <label for="program">Program <span class="required">*</span></label>
                    <select id="program" name="program" class="form-control" required>
                        <option value="">Select Program</option>
                         <!-- Static options: add/edit here -->
                        <option value="Bachelor of Science in Computer Engineering">Bachelor of Science in Computer Engineering</option>            
                        <option value="Bachelor of Science in Computer Science"> Bachelor of Science in Computer Science</option>
                        <option value="Bachelor of Science in Information Technology">Bachelor of Science in Information Technology</option>
                        <option value="Bachelor of Science in Information System">Bachelor of Science in Information System</option>     
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="thesis_type">Thesis Type <span class="required">*</span></label>
                    <select id="thesis_type" name="thesis_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="undergraduate">Undergraduate Thesis</option>
                        <option value="masters">Master's Thesis</option>
                        <option value="phd">PhD Dissertation</option>
                    </select>
                </div>
                
                <!-- ADVISOR dropdown now includes data-department attributes for filtering -->
                <div class="form-group col-md-6">
                    <label for="advisor_id">Advisor <span class="required">*</span></label>
                    <select id="advisor_id" name="advisor_id" class="form-control" required>
                        <option value="">Select Advisor</option>
                        <?php foreach ($advisorsList as $advisor): ?>
                            <option value="<?= intval($advisor['user_id']) ?>" data-dept="<?= htmlspecialchars($advisor['department']) ?>">
                                <?= htmlspecialchars($advisor['full_name']) ?>
                                <?php if (!empty($advisor['department'])): ?>
                                    - <?= htmlspecialchars($advisor['department']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- ...rest of form remains unchanged... -->
            <div class="form-group">
                <label for="abstract">Abstract <span class="required">*</span></label>
                <textarea id="abstract" name="abstract" class="form-control" rows="6" required
                          placeholder="Provide a brief summary of your research (recommended 150-300 words)"></textarea>
                <small class="form-text">A concise summary of your research objectives, methodology, and findings.</small>
            </div>
            
            <div class="form-group">
                <label for="keywords">Part of A Research Paper (Chapters 1-5)</label>
                <input type="text" id="keywords" name="keywords" class="form-control" 
                       placeholder="chapter1, chapter2, chapter3">
                <small class="form-text">Separate keywords with commas (e.g., introduction, significance of the study, literature review )</small>
            </div>
            
            <div class="form-group">
                <label for="thesis_file">Upload Thesis File <span class="required">*</span></label>
                <input type="file" id="thesis_file" name="thesis_file" class="form-control-file" 
                       accept=".pdf,.doc,.docx" required>
                <small class="form-text">
                    <strong>Accepted formats:</strong> PDF, DOC, DOCX | <strong>Maximum size:</strong> 50MB
                </small>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                <button type="submit" name="submit_thesis" class="btn btn-primary btn-lg">
                    <i class="fas fa-upload"></i> Submit Thesis
                </button>
                <button type="reset" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ...existing My Submissions block remains unchanged... -->
 <div class="card mt-4">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h2><i class="fas fa-list"></i> My Submissions</h2>
        <form method="GET" action="index.php" style="display:flex;gap:8px;align-items:center;margin:0;">
            <input type="hidden" name="page" value="submission">
            <select name="sub_filter_department" class="form-control" style="min-width:160px;">
                <option value="">All Departments</option>
                <?php
                    $myDepts = $conn->query("SELECT DISTINCT department FROM thesis_submissions WHERE student_id = " . intval($userId) . " AND department <> '' ORDER BY department");
                    while ($md = $myDepts->fetch_assoc()):
                        $sel = ($subFilterDept === $md['department']) ? 'selected' : '';
                ?>
                    <option value="<?= htmlspecialchars($md['department']) ?>" <?= $sel ?>><?= htmlspecialchars($md['department']) ?></option>
                <?php endwhile; ?>
            </select>
            <select name="sub_filter_status" class="form-control" style="min-width:160px;">
                <option value="">All Statuses</option>
                <option value="submitted" <?= ($subFilterStatus === 'submitted') ? 'selected' : '' ?>>Submitted</option>
                <option value="under_review" <?= ($subFilterStatus === 'under_review') ? 'selected' : '' ?>>Under Review</option>
                <option value="revision_requested" <?= ($subFilterStatus === 'revision_requested') ? 'selected' : '' ?>>Revision Requested</option>
                <option value="approved" <?= ($subFilterStatus === 'approved') ? 'selected' : '' ?>>Approved</option>
            </select>
            <div style="display:flex;gap:6px;align-items:center;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="index.php?page=submission" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body">
        <?php if ($submissions && $submissions->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Submission Date</th>
                            <th>Advisor</th>
                            <th>Version</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sub = $submissions->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($sub['title']) ?></td>
                                <td><?= htmlspecialchars($sub['department']) ?></td>
                                <td><span class="badge <?= getStatusBadge($sub['status']) ?>"><?= ucwords(str_replace('_', ' ', $sub['status'])) ?></span></td>
                                <td><?= formatDate($sub['submission_date']) ?></td>
                                <td><?= htmlspecialchars($sub['advisor_name'] ?? 'Not assigned') ?></td>
                                <td>v<?= $sub['current_version'] ?></td>
                                <td>
                                    <a href="index.php?page=revisions&id=<?= $sub['submission_id'] ?>" 
                                       class="btn btn-sm btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (file_exists($sub['file_path'])): ?>
                                        <a href="<?= $sub['file_path'] ?>" class="btn btn-sm btn-success" 
                                           title="Download" download>
                                            <i class="fas fa-download"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="no-data">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; display: block; margin-bottom: 15px;"></i>
                No submissions yet. Use the form above to submit your thesis.
            </p>
        <?php endif; ?>
    </div>
</div>

<script>
// Show loading on form submit
document.getElementById('submissionForm').addEventListener('submit', function() {
    const loading = document.getElementById('pageLoadingScreen');
    if (loading) {
        loading.classList.add('active');
    }
});

// FILTER ADVISORS BY DEPARTMENT (client-side)
(function() {
    const deptSelect = document.getElementById('department');
    const advisorSelect = document.getElementById('advisor_id');

    function filterAdvisors() {
        const selectedDept = deptSelect.value;
        // Remember current selected advisor to restore if still visible
        const currentAdvisor = advisorSelect.value;

        // Clear existing advisor options except the placeholder
        // We'll toggle hidden property on each option
        let restore = false;
        for (let i = 0; i < advisorSelect.options.length; i++) {
            const opt = advisorSelect.options[i];
            if (!opt.value) { // placeholder
                opt.hidden = false;
                opt.disabled = false;
                continue;
            }
            const optDept = opt.getAttribute('data-dept') || '';
            if (!selectedDept || selectedDept === optDept) {
                opt.hidden = false;
                opt.disabled = false;
                if (opt.value === currentAdvisor) restore = true;
            } else {
                opt.hidden = true;
                opt.disabled = true;
            }
        }

        // If previously selected advisor is no longer visible, reset to placeholder
        if (!restore) {
            advisorSelect.value = '';
        }
    }

    deptSelect.addEventListener('change', filterAdvisors);

    // Initialize on page load
    filterAdvisors();
})();
</script>