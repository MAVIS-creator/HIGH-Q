<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("
    SELECT r.*, p.status as payment_status, p.amount as paid_amount,
           p.reference as payment_reference
    FROM post_utme_registrations r
    LEFT JOIN payments p ON p.student_id = r.id AND p.type = 'post_utme'
    WHERE r.id = ?
");
$stmt->execute([$id]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registration) {
    header('Location: post_utme_registrations.php');
    exit;
}

require 'includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">POST UTME Registration Details</h1>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-user-graduate me-1"></i>
                            Student Information
                        </div>
                        <div>
                            <a href="post_utme_registrations.php" class="btn btn-sm btn-secondary">
                                Back to List
                            </a>
                            <?php if ($registration['passport_photo']): ?>
                            <a href="<?= htmlspecialchars($registration['passport_photo']) ?>" 
                               target="_blank" class="btn btn-sm btn-primary">
                                View Passport
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Personal Information</h5>
                            <table class="table">
                                <tr>
                                    <th>Full Name</th>
                                    <td>
                                        <?= htmlspecialchars($registration['surname'] . ' ' . 
                                            $registration['first_name'] . ' ' . 
                                            $registration['other_name']) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Gender</th>
                                    <td><?= ucfirst($registration['gender']) ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($registration['email']) ?></td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td><?= htmlspecialchars($registration['parent_phone']) ?></td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td><?= nl2br(htmlspecialchars($registration['address'])) ?></td>
                                </tr>
                                <tr>
                                    <th>State of Origin</th>
                                    <td><?= htmlspecialchars($registration['state_of_origin']) ?></td>
                                </tr>
                                <tr>
                                    <th>Local Government</th>
                                    <td><?= htmlspecialchars($registration['local_government']) ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>JAMB Details</h5>
                            <table class="table">
                                <tr>
                                    <th>JAMB Reg. Number</th>
                                    <td><?= htmlspecialchars($registration['jamb_registration_number']) ?></td>
                                </tr>
                                <tr>
                                    <th>JAMB Score</th>
                                    <td><?= htmlspecialchars($registration['jamb_score']) ?></td>
                                </tr>
                                <tr>
                                    <th>Subjects & Scores</th>
                                    <td>
                                        <?php
                                        $subjects = json_decode($registration['jamb_subjects'], true);
                                        if ($subjects) {
                                            echo '<ul class="list-unstyled">';
                                            foreach ($subjects as $subject => $score) {
                                                echo '<li><strong>' . htmlspecialchars(ucfirst($subject)) . 
                                                     ':</strong> ' . htmlspecialchars($score) . '</li>';
                                            }
                                            echo '</ul>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>

                            <h5 class="mt-4">Course Preferences</h5>
                            <table class="table">
                                <tr>
                                    <th>First Choice</th>
                                    <td><?= htmlspecialchars($registration['course_first_choice']) ?></td>
                                </tr>
                                <tr>
                                    <th>Second Choice</th>
                                    <td><?= htmlspecialchars($registration['course_second_choice']) ?></td>
                                </tr>
                                <tr>
                                    <th>Institution</th>
                                    <td><?= htmlspecialchars($registration['institution_first_choice']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5>O'Level Results</h5>
                            <table class="table">
                                <tr>
                                    <th>Exam Type</th>
                                    <td><?= htmlspecialchars($registration['exam_type']) ?></td>
                                </tr>
                                <tr>
                                    <th>Candidate Name</th>
                                    <td><?= htmlspecialchars($registration['candidate_name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Exam Number</th>
                                    <td><?= htmlspecialchars($registration['exam_number']) ?></td>
                                </tr>
                                <tr>
                                    <th>Results</th>
                                    <td>
                                        <?php
                                        $results = json_decode($registration['olevel_results'], true);
                                        if ($results) {
                                            echo '<ul class="list-unstyled">';
                                            foreach ($results as $subject => $grade) {
                                                echo '<li><strong>' . htmlspecialchars(ucfirst($subject)) . 
                                                     ':</strong> ' . htmlspecialchars($grade) . '</li>';
                                            }
                                            echo '</ul>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5>Parent Details</h5>
                            <table class="table">
                                <tr>
                                    <th>Father's Name</th>
                                    <td><?= htmlspecialchars($registration['father_name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Father's Phone</th>
                                    <td><?= htmlspecialchars($registration['father_phone']) ?></td>
                                </tr>
                                <tr>
                                    <th>Mother's Name</th>
                                    <td><?= htmlspecialchars($registration['mother_name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Mother's Phone</th>
                                    <td><?= htmlspecialchars($registration['mother_phone']) ?></td>
                                </tr>
                            </table>

                            <h5 class="mt-4">Payment Information</h5>
                            <table class="table">
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <?php
                                        $status = $registration['payment_status'] ?? 'pending';
                                        $statusClass = [
                                            'completed' => 'success',
                                            'pending' => 'warning',
                                            'failed' => 'danger'
                                        ][$status] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>">
                                            <?= ucfirst($status) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td>₦<?= number_format($registration['paid_amount'] ?? 0, 2) ?></td>
                                </tr>
                                <tr>
                                    <th>Reference</th>
                                    <td><?= htmlspecialchars($registration['payment_reference'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th>Tutorial</th>
                                    <td>
                                        <?= $registration['tutor_fee_paid'] ? 'Yes' : 'No' ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/admin_footer.php'; ?>
