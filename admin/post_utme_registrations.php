<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/admin_header.php';

$page = $_GET['page'] ?? 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total count
$stmt = $pdo->query("SELECT COUNT(*) FROM post_utme_registrations");
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Get registrations with pagination
$stmt = $pdo->prepare("SELECT * FROM post_utme_registrations ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $offset]);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">POST UTME Registrations</h2>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>JAMB Reg No</th>
                            <th>JAMB Score</th>
                            <th>Course Choice</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td><?= htmlspecialchars($reg['id']) ?></td>
                            <td>
                                <?= htmlspecialchars($reg['surname'] . ' ' . $reg['first_name']) ?>
                            </td>
                            <td><?= htmlspecialchars($reg['jamb_registration_number']) ?></td>
                            <td><?= htmlspecialchars($reg['jamb_score']) ?></td>
                            <td><?= htmlspecialchars($reg['course_first_choice']) ?></td>
                            <td>
                                <span class="badge bg-<?= $reg['status'] === 'approved' ? 'success' : 
                                    ($reg['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst(htmlspecialchars($reg['status'])) ?>
                                </span>
                            </td>
                            <td><?= date('Y-m-d', strtotime($reg['created_at'])) ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="view_post_utme.php?id=<?= $reg['id'] ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="download_post_utme.php?id=<?= $reg['id'] ?>" 
                                       class="btn btn-sm btn-secondary">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === (int)$page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>