<?php
/**
 * Analytics & Reporting Dashboard
 * For Admins and Advisors
 */

if (!hasRole('admin') && !hasRole('advisor')) {
    die('<div class="alert alert-danger">Access denied. Analytics is only available for Admins and Advisors.</div>');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Date range filter
$startDate = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-d');

// Cache key
$cacheKey = "analytics_{$userRole}_{$userId}_{$startDate}_{$endDate}";

// Get analytics data
function getAnalyticsData($conn, $userRole, $userId, $startDate, $endDate) {
    $data = [];
    
    if ($userRole === 'admin') {
        // Total statistics
        $data['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
        $data['total_students'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
        $data['total_advisors'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'advisor'")->fetch_assoc()['count'];
        $data['total_panelists'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'panelist'")->fetch_assoc()['count'];
        
        // Thesis statistics
        $data['total_submissions'] = $conn->query("SELECT COUNT(*) as count FROM thesis_submissions")->fetch_assoc()['count'];
        $data['approved_theses'] = $conn->query("SELECT COUNT(*) as count FROM thesis_submissions WHERE status = 'approved'")->fetch_assoc()['count'];
        $data['pending_review'] = $conn->query("SELECT COUNT(*) as count FROM thesis_submissions WHERE status IN ('submitted', 'under_review')")->fetch_assoc()['count'];
        $data['revision_requested'] = $conn->query("SELECT COUNT(*) as count FROM thesis_submissions WHERE status = 'revision_requested'")->fetch_assoc()['count'];
        
        // Department breakdown
        $data['by_department'] = [];
        $deptResult = $conn->query("
            SELECT department, COUNT(*) as count 
            FROM thesis_submissions 
            WHERE department IS NOT NULL AND department != ''
            GROUP BY department 
            ORDER BY count DESC
        ");
        while ($row = $deptResult->fetch_assoc()) {
            $data['by_department'][] = $row;
        }
        
        // Program breakdown
        $data['by_program'] = [];
        $progResult = $conn->query("
            SELECT program, COUNT(*) as count 
            FROM thesis_submissions 
            WHERE program IS NOT NULL AND program != ''
            GROUP BY program 
            ORDER BY count DESC
        ");
        while ($row = $progResult->fetch_assoc()) {
            $data['by_program'][] = $row;
        }
        
        // Status breakdown
        $data['by_status'] = [];
        $statusResult = $conn->query("
            SELECT status, COUNT(*) as count 
            FROM thesis_submissions 
            GROUP BY status 
            ORDER BY count DESC
        ");
        while ($row = $statusResult->fetch_assoc()) {
            $data['by_status'][] = $row;
        }
        
        // Monthly submissions trend
        $data['monthly_trend'] = [];
        $trendResult = $conn->query("
            SELECT 
                DATE_FORMAT(submission_date, '%Y-%m') as month,
                COUNT(*) as count 
            FROM thesis_submissions 
            WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month 
            ORDER BY month ASC
        ");
        while ($row = $trendResult->fetch_assoc()) {
            $data['monthly_trend'][] = $row;
        }
        
        // Top advisors
        $data['top_advisors'] = [];
        $advisorResult = $conn->query("
            SELECT 
                u.full_name,
                COUNT(*) as thesis_count,
                SUM(CASE WHEN ts.status = 'approved' THEN 1 ELSE 0 END) as approved_count
            FROM thesis_submissions ts
            JOIN users u ON ts.advisor_id = u.user_id
            WHERE ts.advisor_id IS NOT NULL
            GROUP BY ts.advisor_id, u.full_name
            ORDER BY thesis_count DESC
            LIMIT 10
        ");
        while ($row = $advisorResult->fetch_assoc()) {
            $data['top_advisors'][] = $row;
        }
        
        // Average review time
        $avgTime = $conn->query("
            SELECT 
                AVG(DATEDIFF(
                    (SELECT MIN(created_at) FROM feedback WHERE submission_id = ts.submission_id),
                    ts.submission_date
                )) as avg_days
            FROM thesis_submissions ts
            WHERE status IN ('under_review', 'approved', 'revision_requested')
        ")->fetch_assoc();
        $data['avg_review_time'] = round($avgTime['avg_days'] ?? 0, 1);
        
    } elseif ($userRole === 'advisor') {
        // Advisor-specific stats
        $data['my_advisees'] = $conn->query("
            SELECT COUNT(DISTINCT student_id) as count 
            FROM thesis_submissions 
            WHERE advisor_id = $userId
        ")->fetch_assoc()['count'];
        
        $data['my_submissions'] = $conn->query("
            SELECT COUNT(*) as count 
            FROM thesis_submissions 
            WHERE advisor_id = $userId
        ")->fetch_assoc()['count'];
        
        $data['my_approved'] = $conn->query("
            SELECT COUNT(*) as count 
            FROM thesis_submissions 
            WHERE advisor_id = $userId AND status = 'approved'
        ")->fetch_assoc()['count'];
        
        $data['my_pending'] = $conn->query("
            SELECT COUNT(*) as count 
            FROM thesis_submissions 
            WHERE advisor_id = $userId AND status IN ('submitted', 'under_review')
        ")->fetch_assoc()['count'];
        
        // My advisees' progress
        $data['advisee_progress'] = [];
        $progressResult = $conn->query("
            SELECT 
                CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as student_name,
                ts.title,
                ts.status,
                ts.current_version,
                ts.submission_date
            FROM thesis_submissions ts
            JOIN users u ON ts.student_id = u.user_id
            WHERE ts.advisor_id = $userId
            ORDER BY ts.submission_date DESC
        ");
        while ($row = $progressResult->fetch_assoc()) {
            $data['advisee_progress'][] = $row;
        }
    }
    
    return $data;
}

$analytics = getAnalyticsData($conn, $userRole, $userId, $startDate, $endDate);
?>

<div class="page-header">
    <h1><i class="fas fa-chart-line"></i> Analytics & Reports</h1>
</div>

<!-- Date Range Filter -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-calendar"></i> Date Range</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="date-filter-form">
            <input type="hidden" name="page" value="analytics">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" 
                           value="<?= $startDate ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group col-md-4">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" 
                           value="<?= $endDate ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> Apply Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($userRole === 'admin'): ?>
    
    <!-- Overview Statistics -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <h3><?= $analytics['total_users'] ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <h3><?= $analytics['approved_theses'] ?></h3>
                <p>Approved Theses</p>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <h3><?= $analytics['pending_review'] ?></h3>
                <p>Pending Review</p>
            </div>
        </div>
        
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-content">
                <h3><?= $analytics['avg_review_time'] ?> days</h3>
                <p>Avg Review Time</p>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="charts-row">
        <!-- Status Distribution -->
        <div class="chart-card">
            <div class="card-header">
                <h2><i class="fas fa-chart-pie"></i> Status Distribution</h2>
            </div>
            <div class="card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        
        <!-- Monthly Trend -->
        <div class="chart-card">
            <div class="card-header">
                <h2><i class="fas fa-chart-line"></i> Monthly Submissions</h2>
            </div>
            <div class="card-body">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Department & Program Breakdown -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="card-header">
                <h2><i class="fas fa-building"></i> By Department</h2>
            </div>
            <div class="card-body">
                <canvas id="departmentChart"></canvas>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="card-header">
                <h2><i class="fas fa-graduation-cap"></i> By Program</h2>
            </div>
            <div class="card-body">
                <canvas id="programChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Advisors Table -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-trophy"></i> Top Advisors</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Advisor Name</th>
                            <th>Total Theses</th>
                            <th>Approved</th>
                            <th>Success Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($analytics['top_advisors'] as $advisor): 
                            $successRate = $advisor['thesis_count'] > 0 ? 
                                round(($advisor['approved_count'] / $advisor['thesis_count']) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?= $rank++ ?></td>
                                <td><?= htmlspecialchars($advisor['full_name']) ?></td>
                                <td><?= $advisor['thesis_count'] ?></td>
                                <td><?= $advisor['approved_count'] ?></td>
                                <td>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-success" style="width: <?= $successRate ?>%">
                                            <?= $successRate ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($userRole === 'advisor'): ?>
    
    <!-- Advisor Statistics -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <h3><?= $analytics['my_advisees'] ?></h3>
                <p>My Advisees</p>
            </div>
        </div>
        
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content">
                <h3><?= $analytics['my_submissions'] ?></h3>
                <p>Total Submissions</p>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <h3><?= $analytics['my_approved'] ?></h3>
                <p>Approved</p>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <h3><?= $analytics['my_pending'] ?></h3>
                <p>Pending</p>
            </div>
        </div>
    </div>
    
    <!-- Advisee Progress -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-list"></i> Advisee Progress</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Thesis Title</th>
                            <th>Status</th>
                            <th>Version</th>
                            <th>Submission Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics['advisee_progress'] as $progress): ?>
                            <tr>
                                <td><?= htmlspecialchars($progress['student_name']) ?></td>
                                <td><?= htmlspecialchars($progress['title']) ?></td>
                                <td><span class="badge <?= getStatusBadge($progress['status']) ?>"><?= ucwords(str_replace('_', ' ', $progress['status'])) ?></span></td>
                                <td>v<?= $progress['current_version'] ?></td>
                                <td><?= formatDate($progress['submission_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>

<!-- Export Options -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-download"></i> Export Reports</h2>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="export.php?type=submissions" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export All Submissions
            </a>
            <a href="export.php?type=users" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export Users
            </a>
            <a href="export.php?type=feedback" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export Feedback
            </a>
            <a href="export.php?type=activity" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export Activity Log
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<?php if ($userRole === 'admin'): ?>
<script>
// Status Distribution Pie Chart
const statusCtx = document.getElementById('statusChart');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($analytics['by_status'], 'status')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($analytics['by_status'], 'count')) ?>,
            backgroundColor: [
                '#3498db',
                '#27ae60',
                '#f39c12',
                '#e74c3c',
                '#9b59b6',
                '#95a5a6'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Monthly Trend Line Chart
const trendCtx = document.getElementById('trendChart');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($analytics['monthly_trend'], 'month')) ?>,
        datasets: [{
            label: 'Submissions',
            data: <?= json_encode(array_column($analytics['monthly_trend'], 'count')) ?>,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Department Bar Chart
const deptCtx = document.getElementById('departmentChart');
new Chart(deptCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($analytics['by_department'], 'department')) ?>,
        datasets: [{
            label: 'Submissions',
            data: <?= json_encode(array_column($analytics['by_department'], 'count')) ?>,
            backgroundColor: '#16a085'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Program Bar Chart
const progCtx = document.getElementById('programChart');
new Chart(progCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map('formatProgramName', array_column($analytics['by_program'], 'program'))) ?>,
        datasets: [{
            label: 'Submissions',
            data: <?= json_encode(array_column($analytics['by_program'], 'count')) ?>,
            backgroundColor: '#f39c12'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 45
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<style>
.charts-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.chart-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.chart-card .card-header {
    background: linear-gradient(135deg, #2c3e50, #3498db);
    color: white;
    padding: 20px;
}

.chart-card .card-body {
    padding: 20px;
}

.date-filter-form {
    margin: 0;
}

.progress {
    border-radius: 5px;
    overflow: hidden;
}

.progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
}

/* DARK MODE */
body.dark-theme .chart-card {
    background: #16213e !important;
}

body.dark-theme .chart-card .card-header {
    background: linear-gradient(135deg, #0f3460, #16213e) !important;
}

body.dark-theme .chart-card .card-body {
    background: #16213e !important;
}

@media (max-width: 768px) {
    .charts-row {
        grid-template-columns: 1fr;
    }
}

@media print {
    .main-header,
    .main-footer,
    .btn,
    .form-group {
        display: none !important;
    }
    
    .card {
        page-break-inside: avoid;
    }
}
</style>