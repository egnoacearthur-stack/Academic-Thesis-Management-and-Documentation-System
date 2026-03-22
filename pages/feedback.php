<?php
/**
 * Feedback Management Page - FIXED: SQL comma error
 */

if (!hasRole('advisor') && !hasRole('panelist')) {
    die('Access denied');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

$fbFilterProgram = sanitize($_GET['fb_filter_program'] ?? '');
$fbFilterType = sanitize($_GET['fb_filter_type'] ?? '');

// ✅ FIXED: Removed extra comma after student_name
$sql = "SELECT f.*, ts.title as thesis_title, 
            CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.suffix) as student_name
        FROM feedback f 
        JOIN thesis_submissions ts ON f.submission_id = ts.submission_id 
        JOIN users u ON ts.student_id = u.user_id
        WHERE f.reviewer_id = ?";
$types = 'i'; $params = [$userId];

if ($fbFilterProgram !== '') {
    $sql .= " AND ts.program = ?";
    $types .= 's'; $params[] = $fbFilterProgram;
}

if ($fbFilterType !== '') {
    $sql .= " AND f.feedback_type = ?";
    $types .= 's'; $params[] = $fbFilterType;
}

$sql .= " ORDER BY f.created_at DESC";

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
    $myFeedback = $stmt->get_result();
} else {
    $myFeedback = false;
}
?>

<div class="page-header">
    <h1><i class="fas fa-comments"></i> My Feedback History</h1>
</div>

<div class="card">
    <div class="card-header">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <h2 style="margin:0;"><i class="fas fa-list"></i> Feedback I've Provided</h2>
            <div style="display:flex;align-items:center;gap:10px;">
                
                <form method="GET" action="index.php" style="display:flex;gap:8px;align-items:center;margin:0;">
                    <input type="hidden" name="page" value="feedback">
                    <select name="fb_filter_program" class="form-control" style="min-width:160px;">
                        <option value="">All Programs</option>
                        <?php
                            $progRs = $conn->query("SELECT DISTINCT program FROM thesis_submissions WHERE program <> '' ORDER BY program");
                            while ($d = $progRs->fetch_assoc()):
                                $selProg = ($fbFilterProgram === $d['program']) ? 'selected' : '';
                                $display = formatProgramName($d['program']);
                                if ($display === 'BS in Computer Science') continue;
                            ?>
                                <option value="<?= htmlspecialchars($d['program']) ?>" <?= $selProg ?>><?= htmlspecialchars($display) ?></option>
                            <?php endwhile; ?>
                    </select>
                    <select name="fb_filter_type" class="form-control" style="min-width:160px;">
                        <option value="">All Types</option>
                        <?php
                            $typeRs = $conn->query("SELECT DISTINCT feedback_type FROM feedback WHERE feedback_type <> '' ORDER BY feedback_type");
                            while ($t = $typeRs->fetch_assoc()):
                                $selType = ($fbFilterType === $t['feedback_type']) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($t['feedback_type']) ?>" <?= $selType ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', $t['feedback_type']))) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="index.php?page=feedback" class="btn btn-secondary">Reset</a>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if ($myFeedback && $myFeedback->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Thesis Title</th>
                        <th>Student</th>
                        <th>Section</th>
                        <th>Type</th>
                        <th>Rating</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($feedback = $myFeedback->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($feedback['thesis_title']) ?></td>
                            <td><?= htmlspecialchars($feedback['student_name']) ?></td>
                            <td><?= htmlspecialchars($feedback['section'] ?? 'General') ?></td>
                            <td><span class="badge badge-info"><?= ucwords(str_replace('_', ' ', $feedback['feedback_type'])) ?></span></td>
                            <td><?= $feedback['rating'] ? str_repeat('⭐', $feedback['rating']) : 'N/A' ?></td>
                            <td><?= formatDateTime($feedback['created_at']) ?></td>
                            <td>
                                <button onclick="viewFeedback(<?= htmlspecialchars(json_encode($feedback)) ?>)" 
                                        class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">No feedback provided yet. <a href="index.php?page=reviews">Start reviewing theses</a></p>
        <?php endif; ?>
    </div>
</div>

<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div id="modalBody"></div>
    </div>
</div>

<script>
function viewFeedback(feedback) {
    const modal = document.getElementById('feedbackModal');
    const modalBody = document.getElementById('modalBody');
    
    modalBody.innerHTML = `
        <h2>Feedback Details</h2>
        <hr>
        <p><strong>Thesis:</strong> ${feedback.thesis_title}</p>
        <p><strong>Student:</strong> ${feedback.student_name}</p>
        <p><strong>Section:</strong> ${feedback.section || 'General'}</p>
        ${feedback.page_number ? `<p><strong>Page:</strong> ${feedback.page_number}</p>` : ''}
        <p><strong>Type:</strong> ${feedback.feedback_type.replace('_', ' ')}</p>
        ${feedback.rating ? `<p><strong>Rating:</strong> ${'⭐'.repeat(feedback.rating)}</p>` : ''}
        <p><strong>Date:</strong> ${new Date(feedback.created_at).toLocaleString()}</p>
        
        <h3 style="margin-top: 20px;">Feedback</h3>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; line-height: 1.8;">
            ${feedback.feedback_text.replace(/\n/g, '<br>')}
        </div>
    `;
    
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('feedbackModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('feedbackModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<style>
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

/* DARK MODE */
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