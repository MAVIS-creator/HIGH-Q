<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/admin_header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM post_utme_registrations WHERE id = ?");
$stmt->execute([$id]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registration) {
    die('Registration not found');
}

// Decode JSON data
$jambSubjects = json_decode($registration['jamb_subjects'] ?? '[]', true);
$jambGrades = json_decode($registration['jamb_grades'] ?? '[]', true);
$olevelSubjects = json_decode($registration['olevel_subjects'] ?? '[]', true);
$olevelGrades = json_decode($registration['olevel_grades'] ?? '[]', true);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $stmt = $pdo->prepare("UPDATE post_utme_registrations SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$_POST['status'], $id]);
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>POST UTME Registration Details</h2>
        <div class="btn-group">
            <a href="post_utme_registrations.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="download_post_utme.php?id=<?= $registration['id'] ?>" class="btn btn-primary">
                <i class="fas fa-download"></i> Download PDF
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Personal Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Institution:</strong> <?= htmlspecialchars($registration['institution_name']) ?></p>
                            <p><strong>Full Name:</strong> <?= htmlspecialchars($registration['surname'] . ' ' . $registration['first_name'] . ' ' . $registration['other_name']) ?></p>
                            <p><strong>Gender:</strong> <?= ucfirst(htmlspecialchars($registration['gender'])) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($registration['email']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Phone:</strong> <?= htmlspecialchars($registration['parents_phone']) ?></p>
                            <p><strong>State of Origin:</strong> <?= htmlspecialchars($registration['state_of_origin']) ?></p>
                            <p><strong>Local Government:</strong> <?= htmlspecialchars($registration['local_government']) ?></p>
                            <p><strong>Nationality:</strong> <?= htmlspecialchars($registration['nationality']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">JAMB Details</h4>
                </div>
                <div class="card-body">
                    <p><strong>JAMB Registration Number:</strong> <?= htmlspecialchars($registration['jamb_registration_number']) ?></p>
                    <p><strong>JAMB Score:</strong> <?= htmlspecialchars($registration['jamb_score']) ?></p>
                    
                    <h5 class="mt-3">JAMB Subjects and Scores</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < count($jambSubjects); $i++): ?>
                            <tr>
                                <td><?= htmlspecialchars($jambSubjects[$i] ?? '') ?></td>
                                <td><?= htmlspecialchars($jambGrades[$i] ?? '') ?></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">O'Level Results</h4>
                </div>
                <div class="card-body">
                    <p><strong>Exam Type:</strong> <?= htmlspecialchars($registration['exam_type']) ?></p>
                    <p><strong>Candidate Name:</strong> <?= htmlspecialchars($registration['candidate_name']) ?></p>
                    <p><strong>Exam Number:</strong> <?= htmlspecialchars($registration['exam_number']) ?></p>
                    
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < count($olevelSubjects); $i++): ?>
                            <tr>
                                <td><?= htmlspecialchars($olevelSubjects[$i] ?? '') ?></td>
                                <td><?= htmlspecialchars($olevelGrades[$i] ?? '') ?></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Passport Photo</h4>
                </div>
                <div class="card-body text-center">
                    <?php if ($registration['passport_photo']): ?>
                    <img src="<?= htmlspecialchars($registration['passport_photo']) ?>" 
                         alt="Passport Photo" class="img-fluid mb-3" style="max-height: 200px;">
                    <?php else: ?>
                    <p class="text-muted">No passport photo uploaded</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Registration Status</h4>
                </div>
                <div class="card-body">
                    <form method="post" class="mb-3">
                        <div class="form-group">
                            <label>Current Status</label>
                            <select name="status" class="form-control">
                                <option value="pending" <?= $registration['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="approved" <?= $registration['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="rejected" <?= $registration['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary mt-2">Update Status</button>
                    </form>
                    
                    <div class="mt-3">
                        <p><strong>Created:</strong> <?= date('Y-m-d H:i', strtotime($registration['created_at'])) ?></p>
                        <p><strong>Last Updated:</strong> <?= date('Y-m-d H:i', strtotime($registration['updated_at'])) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Payment Status</h4>
                </div>
                <div class="card-body">
                    <p>
                        <strong>Form Fee:</strong>
                        <span class="badge bg-<?= $registration['form_fee_paid'] ? 'success' : 'warning' ?>">
                            <?= $registration['form_fee_paid'] ? 'Paid' : 'Pending' ?>
                        </span>
                    </p>
                    <p>
                        <strong>Tutor Fee:</strong>
                        <span class="badge bg-<?= $registration['tutor_fee_paid'] ? 'success' : 'secondary' ?>">
                            <?= $registration['tutor_fee_paid'] ? 'Paid' : 'Not Selected' ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>