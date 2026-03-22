<?php
/**
 * Repository Page - Archived Approved Theses
 */

requireLogin();

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Search functionality
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$programFilter = isset($_GET['program']) ? sanitize($_GET['program']) : '';
$yearFilter = isset($_GET['year']) ? sanitize($_GET['year']) : '';

// Build search query
$sql = "
    SELECT ts.*, 
        CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as student_name, 
        u.department as student_dept,
           ra.archive_date, ra.download_count 
    FROM thesis_submissions ts 
    JOIN users u ON ts.student_id = u.user_id 
    LEFT JOIN repository_archive ra ON ts.submission_id = ra.submission_id 
    WHERE ts.status = 'approved'
";

$params = [];
$types = '';

if ($searchQuery) {
    $sql .= " AND (ts.title LIKE ? OR ts.abstract LIKE ? OR ts.keywords LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

if ($programFilter) {
    $sql .= " AND ts.program = ?";
    $params[] = $programFilter;
    $types .= 's';
}

if ($yearFilter) {
    $sql .= " AND YEAR(ts.submission_date) = ?";
    $params[] = $yearFilter;
    $types .= 'i';
}

$sql .= " ORDER BY ts.submission_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$theses = $stmt->get_result();

// Get programs for filter
$programs = $conn->query("SELECT DISTINCT program FROM thesis_submissions WHERE status = 'approved' ORDER BY program");

// Get years for filter
$years = $conn->query("SELECT DISTINCT YEAR(submission_date) as year FROM thesis_submissions WHERE status = 'approved' ORDER BY year DESC");

// Handle download tracking
if (isset($_GET['download'])) {
    $submissionId = intval($_GET['download']);
    $conn->query("UPDATE repository_archive SET download_count = download_count + 1 WHERE submission_id = $submissionId");
    
    // Get file path
    $fileStmt = $conn->prepare("SELECT file_path, file_name FROM thesis_submissions WHERE submission_id = ?");
    $fileStmt->bind_param("i", $submissionId);
    $fileStmt->execute();
    $fileData = $fileStmt->get_result()->fetch_assoc();
    
    if ($fileData && file_exists($fileData['file_path'])) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileData['file_name'] . '"');
        header('Content-Length: ' . filesize($fileData['file_path']));
        readfile($fileData['file_path']);
        exit();
    }
}
?>

<div class="page-header">
    <h1><i class="fas fa-archive"></i> Thesis Repository</h1>
    <p style="color: white;">Browse and search approved theses</p>
</div>

<!-- Search and Filters -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-search"></i> Search Repository</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <input type="hidden" name="page" value="repository">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           placeholder="Search by title, abstract, or keywords" 
                           value="<?= htmlspecialchars($searchQuery) ?>">
                </div>
                
                <div class="form-group col-md-3">
                    <label for="program">Program</label>
                    <select id="program" name="program" class="form-control">
                        <option value="">All Programs</option>
                        <?php while ($prog = $programs->fetch_assoc()): ?>
                            <?php $displayProg = formatProgramName($prog['program']); if ($displayProg === 'BS in Computer Science') continue; ?>
                            <option value="<?= htmlspecialchars($prog['program']) ?>" 
                                    <?= $programFilter === $prog['program'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($displayProg) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group col-md-3">
                    <label for="year">Year</label>
                    <select id="year" name="year" class="form-control">
                        <option value="">All Years</option>
                        <?php while ($yr = $years->fetch_assoc()): ?>
                            <option value="<?= $yr['year'] ?>" <?= $yearFilter == $yr['year'] ? 'selected' : '' ?>>
                                <?= $yr['year'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            <a href="index.php?page=repository" class="btn btn-secondary">
                <i class="fas fa-redo"></i> Clear Filters
            </a>
        </form>
    </div>
</div>

<!-- Search Results -->
<div class="card mt-4">
    <div class="card-header">
        <h2><i class="fas fa-book"></i> Approved Theses (<?= $theses->num_rows ?> results)</h2>
    </div>
    <div class="card-body">
        <?php if ($theses->num_rows > 0): ?>
            <div class="thesis-grid">
                <?php while ($thesis = $theses->fetch_assoc()): ?>
                    <div class="thesis-card">
                        <div class="thesis-card-header">
                            <h3><?= htmlspecialchars($thesis['title']) ?></h3>
                        </div>
                        <div class="thesis-card-body">
                            <p class="thesis-meta">
                                <strong><i class="fas fa-user"></i> Author:</strong> 
                                <?= htmlspecialchars($thesis['student_name']) ?>
                            </p>
                            <p class="thesis-meta">
                                <strong><i class="fas fa-building"></i> Program:</strong> 
                                <?= htmlspecialchars(formatProgramName($thesis['program'])) ?>
                            </p>
                            <p class="thesis-meta">
                                <strong><i class="fas fa-calendar"></i> Year:</strong> 
                                <?= date('Y', strtotime($thesis['submission_date'])) ?>
                            </p>
                            <p class="thesis-meta">
                                <strong><i class="fas fa-graduation-cap"></i> Type:</strong> 
                                <?= ucfirst($thesis['thesis_type']) ?>
                            </p>
                            <?php if ($thesis['download_count']): ?>
                                <p class="thesis-meta">
                                    <strong><i class="fas fa-download"></i> Downloads:</strong> 
                                    <?= $thesis['download_count'] ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="thesis-abstract">
                                <strong>Abstract:</strong>
                                <p><?= substr(htmlspecialchars($thesis['abstract']), 0, 200) ?>...</p>
                            </div>
                            
                            <?php if ($thesis['keywords']): ?>
                                <div class="thesis-keywords">
                                    <?php
                                    $keywords = explode(',', $thesis['keywords']);
                                    foreach ($keywords as $keyword):
                                        $keyword = trim($keyword);
                                    ?>
                                        <span class="keyword-tag"><?= htmlspecialchars($keyword) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="thesis-card-footer">
                            <?php if ($userRole !== 'student'): ?>
                                <a href="index.php?page=repository&download=<?= $thesis['submission_id'] ?>" 
                                class="btn btn-success btn-sm">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled title="Students cannot download other theses">
                                    <i class="fas fa-lock"></i> Download Restricted
                                </button>
                            <?php endif; ?>
                            <button onclick="showThesisDetails(<?= htmlspecialchars(json_encode(array_merge($thesis, ['formatted_program' => formatProgramName($thesis['program'])]))) ?>)" 
                                    class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="no-data">No theses found matching your criteria</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for Thesis Details -->
<div id="thesisModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div id="modalBody"></div>
    </div>
</div>

<style>
.thesis-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.thesis-card {
    background: #f8f9fa;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid var(--border-color);
    transition: transform 0.3s, box-shadow 0.3s;
}

.thesis-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.thesis-card-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 20px;
}

.thesis-card-header h3 {
    margin: 0;
    font-size: 1.1rem;
    line-height: 1.4;
}

.thesis-card-body {
    padding: 20px;
}

.thesis-meta {
    margin: 8px 0;
    font-size: 0.9rem;
    color: var(--dark-text);
}

.thesis-abstract {
    margin: 15px 0;
}

.thesis-abstract strong {
    display: block;
    margin-bottom: 8px;
}

.thesis-abstract p {
    color: #7f8c8d;
    line-height: 1.6;
    font-size: 0.9rem;
}

.thesis-keywords {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 15px;
}

.keyword-tag {
    background: var(--secondary-color);
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
}

.thesis-card-footer {
    padding: 15px 20px;
    background: white;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 10px;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
}

.modal-content {
    background: white;
    margin: 50px auto;
    padding: 30px;
    border-radius: 10px;
    width: 80%;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: #000;
}

/* DARK MODE - Modal */
body.dark-theme .modal {
    background: rgba(0,0,0,0.85);
}

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

body.dark-theme .close {
    color: #ecf0f1;
}

body.dark-theme .close:hover {
    color: #3498db;
}
</style>

<script>
function showThesisDetails(thesis) {
    const modal = document.getElementById('thesisModal');
    const modalBody = document.getElementById('modalBody');
    
    const keywords = thesis.keywords ? thesis.keywords.split(',').map(k => 
        `<span class="keyword-tag">${k.trim()}</span>`
    ).join('') : '';
    
    modalBody.innerHTML = `
        <h2>${thesis.title}</h2>
        <hr>
        <p><strong>Author:</strong> ${thesis.student_name}</p>
        <p><strong>Program:</strong> ${thesis.formatted_program || thesis.program}</p>
        <p><strong>Type:</strong> ${thesis.thesis_type}</p>
        <p><strong>Submission Date:</strong> ${new Date(thesis.submission_date).toLocaleDateString()}</p>
        ${thesis.download_count ? `<p><strong>Downloads:</strong> ${thesis.download_count}</p>` : ''}
        
        <h3 style="margin-top: 20px;">Abstract</h3>
        <p style="text-align: justify; line-height: 1.8;">${thesis.abstract}</p>
        
        ${keywords ? `
            <h3 style="margin-top: 20px;">Keywords</h3>
            <div class="thesis-keywords">${keywords}</div>
        ` : ''}
        
        <div style="margin-top: 30px;">
            <?php if ($userRole !== 'student'): ?>
                <a href="index.php?page=repository&download=${thesis.submission_id}" 
                class="btn btn-success">
                    <i class="fas fa-download"></i> Download Thesis
                </a>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>
                    <i class="fas fa-lock"></i> Download Restricted (Students Only View)
                </button>
            <?php endif; ?>
        </div>
    `;
    
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('thesisModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('thesisModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>