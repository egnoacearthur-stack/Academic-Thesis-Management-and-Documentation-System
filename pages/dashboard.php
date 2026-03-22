<?php
/**
 * Dashboard Page - FIXED FILTERS + UN-SUBMIT LOGIC
 */

requireLogin();

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$userName = $_SESSION['user_name'];

// Get statistics
$stats = getDashboardStats($conn, $userId, $userRole);
?>

<div class="dashboard-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <p>Welcome back, <strong><?= htmlspecialchars($userName) ?></strong>! Here's your overview.</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <?php if ($userRole === 'student'): ?>
        <!-- Student stats remain the same -->
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content">
                <h3><?= $stats['total_submissions'] ?? 0 ?></h3>
                <p>Total Submissions</p>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <h3><?= $stats['pending_feedback'] ?? 0 ?></h3>
                <p>Pending Feedback</p>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <h3><?= $stats['approved'] ?? 0 ?></h3>
                <p>Approved</p>
            </div>
        </div>
        
        <div class="stat-card stat-danger">
            <div class="stat-icon"><i class="fas fa-edit"></i></div>
            <div class="stat-content">
                <h3><?= $stats['revision_requested'] ?? 0 ?></h3>
                <p>Needs Revision</p>
            </div>
        </div>
        
    <?php elseif ($userRole === 'advisor'): ?>
        <!-- Advisor stats remain the same -->
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <h3><?= $stats['total_advisees'] ?? 0 ?></h3>
                <p>Total Advisees</p>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-tasks"></i></div>
            <div class="stat-content">
                <h3><?= $stats['pending_reviews'] ?? 0 ?></h3>
                <p>Pending Reviews</p>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-check-double"></i></div>
            <div class="stat-content">
                <h3><?= $stats['approved_theses'] ?? 0 ?></h3>
                <p>Approved Thesis</p>
            </div>
        </div>
        
    <?php elseif ($userRole === 'panelist'): ?>
        <!-- Panelist stats remain the same -->
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-content">
                <h3><?= $stats['total_assignments'] ?? 0 ?></h3>
                <p>Total Assignments</p>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-content">
                <h3><?= $stats['pending_reviews'] ?? 0 ?></h3>
                <p>Pending Reviews</p>
            </div>
        </div>
        
    <?php elseif ($userRole === 'admin'): ?>
        <!-- Admin stats remain the same -->
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <h3><?= $stats['total_users'] ?? 0 ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content">
                <h3><?= $stats['total_submissions'] ?? 0 ?></h3>
                <p>Total Submissions</p>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <h3><?= $stats['approved_theses'] ?? 0 ?></h3>
                <p>Approved Theses</p>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <h3><?= $stats['pending_approvals'] ?? 0 ?></h3>
                <p>Pending Approvals</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Activity -->
<div class="dashboard-content">
    <div class="card">
        <?php
        // Dashboard Recent Activity filters
        $dashFilterStatus = sanitize($_GET['dash_filter_status'] ?? '');
        $dashFilterProgram = sanitize($_GET['dash_filter_program'] ?? '');
        ?>
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <h2><i class="fas fa-history"></i> Recent Activity</h2>
            <?php if ($userRole === 'advisor' || $userRole === 'panelist'): ?>
                <form method="GET" action="index.php" style="display:flex;gap:8px;align-items:center;margin:0;">
                    <input type="hidden" name="page" value="dashboard">
                    <select name="dash_filter_program" class="form-control" style="min-width:200px;">
                        <option value="">All Programs</option>
                        <?php
                        // ✅ FIXED: Updated program list
                        $programs = [
                            'BS Computer Engineering' => 'BS Computer Engineering',
                            'BS Computer Science' => 'BS Computer Science',
                            'BS Information Technology' => 'BS Information Technology',
                            'BS Information System' => 'BS Information System'
                        ];
                        foreach ($programs as $value => $label):
                            $selected = ($dashFilterProgram === $value) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($value) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="dash_filter_status" class="form-control" style="min-width:160px;">
                        <option value="">All Statuses</option>
                        <option value="submitted" <?= ($dashFilterStatus === 'submitted') ? 'selected' : '' ?>>Submitted</option>
                        <option value="under_review" <?= ($dashFilterStatus === 'under_review') ? 'selected' : '' ?>>Under Review</option>
                        <option value="revision_requested" <?= ($dashFilterStatus === 'revision_requested') ? 'selected' : '' ?>>Revision Requested</option>
                        <option value="approved" <?= ($dashFilterStatus === 'approved') ? 'selected' : '' ?>>Approved</option>
                    </select>
                    <div style="display:flex;gap:6px;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="index.php?page=dashboard" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            <?php endif; ?>
            <?php if ($userRole === 'student'):
                $dashFilterStatus = sanitize($_GET['dash_filter_status'] ?? '');
                $dashFilterDept = sanitize($_GET['dash_filter_department'] ?? '');
            ?>
                <form method="GET" action="index.php" style="display:flex;gap:8px;align-items:center;margin:0;">
                    <input type="hidden" name="page" value="dashboard">
                    <select name="dash_filter_department" class="form-control" style="min-width:180px;">
                        <option value="">All Departments</option>
                        <?php
                        $deptRs = $conn->query("SELECT DISTINCT department FROM thesis_submissions WHERE student_id = " . intval($userId) . " AND department <> '' ORDER BY department");
                        while ($d = $deptRs->fetch_assoc()):
                            $selDept = ($dashFilterDept === $d['department']) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($d['department']) ?>" <?= $selDept ?>>
                                <?= htmlspecialchars($d['department']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <select name="dash_filter_status" class="form-control" style="min-width:160px;">
                        <option value="">All Statuses</option>
                        <option value="submitted" <?= ($dashFilterStatus === 'submitted') ? 'selected' : '' ?>>Submitted</option>
                        <option value="under_review" <?= ($dashFilterStatus === 'under_review') ? 'selected' : '' ?>>Under Review</option>
                        <option value="revision_requested" <?= ($dashFilterStatus === 'revision_requested') ? 'selected' : '' ?>>Revision Requested</option>
                        <option value="approved" <?= ($dashFilterStatus === 'approved') ? 'selected' : '' ?>>Approved</option>
                    </select>
                    <div style="display:flex;gap:6px;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="index.php?page=dashboard" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php
            if ($userRole === 'student') {
                // Student submissions
                $types = 'i'; $params = [$userId];
                $sql = "SELECT ts.*, 
                        CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as advisor_name 
                        FROM thesis_submissions ts 
                        LEFT JOIN users u ON ts.advisor_id = u.user_id 
                        WHERE ts.student_id = ?";
                if ($dashFilterStatus !== '') {
                    $sql .= " AND ts.status = ?";
                    $types .= 's'; $params[] = $dashFilterStatus;
                }
                if ($dashFilterDept !== '') {
                    $sql .= " AND ts.department = ?";
                    $types .= 's'; $params[] = $dashFilterDept;
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

                if ($submissions && $submissions->num_rows > 0):
            ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Thesis Title</th>
                            <th>Status</th>
                            <th>Submission Date</th>
                            <th>Advisor</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sub = $submissions->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($sub['title']) ?></td>
                                <td><span class="badge <?= getStatusBadge($sub['status']) ?>"><?= ucwords(str_replace('_', ' ', $sub['status'])) ?></span></td>
                                <td><?= formatDate($sub['submission_date']) ?></td>
                                <td><?= htmlspecialchars($sub['advisor_name'] ?? 'Not assigned') ?></td>
                                <td>
                                    <a href="index.php?page=revisions&id=<?= $sub['submission_id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; display: block; margin-bottom: 15px;"></i>
                    No submissions yet. <a href="index.php?page=submission">Submit your thesis</a> to get started.
                </p>
            <?php 
                endif;
            } elseif ($userRole === 'advisor' || $userRole === 'panelist') {
                $types = 'i'; $params = [$userId];
                $sql = "SELECT ts.*, 
                        CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as student_name 
                        FROM thesis_submissions ts 
                        JOIN users u ON ts.student_id = u.user_id 
                        WHERE ";
                if ($userRole === 'advisor') {
                    $sql .= "ts.advisor_id = ?";
                } else {
                    $sql .= "ts.submission_id IN (SELECT submission_id FROM panelist_assignments WHERE panelist_id = ?)";
                }
                
                // ✅ Exclude draft status (un-submitted theses)
                $sql .= " AND ts.status != 'draft'";
                
                if ($dashFilterStatus !== '') {
                    $sql .= " AND ts.status = ?";
                    $types .= 's'; $params[] = $dashFilterStatus;
                }
                if ($dashFilterProgram !== '') {
                    $sql .= " AND ts.program = ?";
                    $types .= 's'; $params[] = $dashFilterProgram;
                }
                $sql .= " ORDER BY ts.submission_date DESC LIMIT 10";
                
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
                
                if ($submissions && $submissions->num_rows > 0):
            ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Thesis Title</th>
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
            <?php else: ?>
                <p class="no-data">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; display: block; margin-bottom: 15px;"></i>
                    No submissions to review.
                </p>
            <?php 
                endif;
            } elseif ($userRole === 'admin') {
                // Admin recent submissions
                $stmt = $conn->prepare("
                    SELECT ts.*, 
                    CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as student_name 
                    FROM thesis_submissions ts 
                    JOIN users u ON ts.student_id = u.user_id 
                    WHERE ts.status != 'draft'
                    ORDER BY ts.submission_date DESC 
                    LIMIT 10
                ");
                $stmt->execute();
                $recentSubs = $stmt->get_result();
                
                if ($recentSubs && $recentSubs->num_rows > 0):
            ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Thesis Title</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Submission Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sub = $recentSubs->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($sub['student_name']) ?></td>
                                <td><?= htmlspecialchars($sub['title']) ?></td>
                                <td><?= htmlspecialchars($sub['department']) ?></td>
                                <td><span class="badge <?= getStatusBadge($sub['status']) ?>"><?= ucwords(str_replace('_', ' ', $sub['status'])) ?></span></td>
                                <td><?= formatDate($sub['submission_date']) ?></td>
                                <td>
                                    <a href="index.php?page=approval&id=<?= $sub['submission_id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; display: block; margin-bottom: 15px;"></i>
                    No submissions in the system yet.
                </p>
            <?php 
                endif;
            }
            ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <?php if ($userRole === 'student'): ?>
                <a href="index.php?page=submission" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Submit New Thesis
                </a>
                <a href="index.php?page=revisions" class="btn btn-info">
                    <i class="fas fa-history"></i> View Revisions
                </a>
            <?php elseif ($userRole === 'advisor'): ?>
                <a href="index.php?page=reviews" class="btn btn-primary">
                    <i class="fas fa-tasks"></i> Pending Reviews
                </a>
                <a href="index.php?page=feedback" class="btn btn-info">
                    <i class="fas fa-comments"></i> My Feedback
                </a>
            <?php elseif ($userRole === 'panelist'): ?>
                <a href="index.php?page=reviews" class="btn btn-primary">
                    <i class="fas fa-clipboard-list"></i> My Assignments
                </a>
                <a href="index.php?page=feedback" class="btn btn-info">
                    <i class="fas fa-comments"></i> My Feedback
                </a>
            <?php elseif ($userRole === 'admin'): ?>
                <a href="index.php?page=users" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Manage Users
                </a>
                <a href="index.php?page=approval" class="btn btn-success">
                    <i class="fas fa-gavel"></i> Manage Approvals
                </a>
                <a href="index.php?page=repository" class="btn btn-info">
                    <i class="fas fa-archive"></i> View Repository
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>