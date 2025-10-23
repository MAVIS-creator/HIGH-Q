<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count
$total = $pdo->query("SELECT COUNT(*) FROM post_utme_registrations")->fetchColumn();
$totalPages = ceil($total / $perPage);

// Get registrations with pagination
$stmt = $pdo->prepare("
    SELECT r.*, p.status as payment_status, p.amount as paid_amount 
    FROM post_utme_registrations r
    LEFT JOIN payments p ON p.student_id = r.id AND p.type = 'post_utme'
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $offset]);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle bulk download
if (isset($_POST['download']) && isset($_POST['selected'])) {
    $ids = array_map('intval', $_POST['selected']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    $stmt = $pdo->prepare("
        SELECT * FROM post_utme_registrations 
        WHERE id IN ($placeholders)
    ");
    $stmt->execute($ids);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="post_utme_registrations.csv"');
    
    $fp = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($fp, array_keys($data[0]));
    
    // Write data
    foreach ($data as $row) {
        fputcsv($fp, $row);
    }
    
    fclose($fp);
    exit;
}

require 'includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">POST UTME Registrations</h1>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Registered Students
        </div>
        <div class="card-body">
            <form method="post" id="registrationsForm">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Name</th>
                                <th>JAMB Reg. No</th>
                                <th>JAMB Score</th>
                                <th>Course Choice</th>
                                <th>Payment Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><input type="checkbox" name="selected[]" value="<?= $reg['id'] ?>"></td>
                                <td>
                                    <?= htmlspecialchars($reg['surname'] . ' ' . $reg['first_name']) ?>
                                    <?php if ($reg['passport_photo']): ?>
                                    <br>
                                    <a href="<?= htmlspecialchars($reg['passport_photo']) ?>" 
                                       target="_blank" class="small">View Passport</a>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($reg['jamb_registration_number']) ?></td>
                                <td><?= htmlspecialchars($reg['jamb_score']) ?></td>
                                <td><?= htmlspecialchars($reg['course_first_choice']) ?></td>
                                <td>
                                    <?php
                                    $status = $reg['payment_status'] ?? 'pending';
                                    $statusClass = [
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger'
                                    ][$status] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                    <?php if ($reg['paid_amount']): ?>
                                    <br>
                                    <small>₦<?= number_format($reg['paid_amount'], 2) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view_post_utme.php?id=<?= $reg['id'] ?>" 
                                       class="btn btn-sm btn-primary">View Details</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <button type="submit" name="download" class="btn btn-success">
                            <i class="fas fa-download"></i> Download Selected
                        </button>
                    </div>
                    
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.getElementsByName('selected[]');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

<?php require 'includes/admin_footer.php'; ?>
