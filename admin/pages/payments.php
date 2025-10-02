<?php
// admin/pages/payments.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$pageTitle = 'Payments';
require_once __DIR__ . '/../includes/header.php';
    }
    echo json_encode(['status'=>'error','message'=>'Unknown action']); exit;
}

// List payments with pagination, search & filter
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$gateway = trim($_GET['gateway'] ?? '');
$refFilter = trim($_GET['ref'] ?? '');
$userEmail = trim($_GET['user_email'] ?? '');
$params = [];
$where = [];
if ($statusFilter !== '') { $where[] = 'LOWER(p.status) = ?'; $params[] = strtolower($statusFilter); }
if ($search !== '') { $where[] = '(p.reference LIKE ? OR u.email LIKE ? OR u.name LIKE ?)'; $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
if ($fromDate !== '') { $where[] = 'p.created_at >= ?'; $params[] = $fromDate . ' 00:00:00'; }
if ($toDate !== '') { $where[] = 'p.created_at <= ?'; $params[] = $toDate . ' 23:59:59'; }
if ($gateway !== '') { $where[] = 'p.gateway LIKE ?'; $params[] = "%{$gateway}%"; }
if ($refFilter !== '') { $where[] = 'p.reference LIKE ?'; $params[] = "%{$refFilter}%"; }
if ($userEmail !== '') { $where[] = 'u.email LIKE ?'; $params[] = "%{$userEmail}%"; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM payments p {$whereSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$offset = ($page - 1) * $perPage;
$sql = "SELECT p.* FROM payments p {$whereSql} ORDER BY p.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = (int)ceil($total / $perPage);

?>
<div class="roles-page">
    <div class="page-header"><h1><i class="bx bxs-credit-card"></i> Payments</h1></div>
    <script>window.__PAYMENTS_CSRF = '<?= generateToken('payments_form') ?>';</script>
    <div style="margin:12px 0;display:flex;gap:12px;align-items:center;">
        <form method="get" action="index.php" class="filter-form">
            <input type="hidden" name="pages" value="payments">
            <label for="statusFilter">Status:</label>
            <select id="statusFilter" name="status" class="small">
                <option value=""<?= $statusFilter===''? ' selected':'' ?>>All</option>
                <option value="pending"<?= $statusFilter==='pending'? ' selected':'' ?>>Pending</option>
                <option value="confirmed"<?= $statusFilter==='confirmed'? ' selected':'' ?>>Confirmed</option>
                <option value="failed"<?= $statusFilter==='failed'? ' selected':'' ?>>Failed</option>
            </select>
            <label>From: <input class="small" type="date" name="from_date" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>"></label>
            <label>To: <input class="small" type="date" name="to_date" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>"></label>
            <label>Gateway: <input class="small" type="text" name="gateway" placeholder="gateway" value="<?= htmlspecialchars($_GET['gateway'] ?? '') ?>"></label>
            <label>Reference: <input class="medium" type="text" name="ref" placeholder="reference" value="<?= htmlspecialchars($_GET['ref'] ?? '') ?>"></label>
            <label>Email/User: <input class="medium" type="text" name="user_email" placeholder="email" value="<?= htmlspecialchars($_GET['user_email'] ?? '') ?>"></label>
            <button class="btn" type="submit">Filter</button>
        </form>
        <div style="margin-left:auto;color:#666;font-size:13px;">Search: <em><?= htmlspecialchars($search) ?></em></div>
    </div>
    <table class="roles-table">
    <thead><tr><th>ID</th><th>Reference</th><th>Payer Name</th><th>Amount</th><th>Method</th><th>Status</th><th>Payer Account</th><th>Payer Bank</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($payments as $p): ?>
            <?php
                $dataAttrs = 'data-payment-id="' . htmlspecialchars($p['id']) . '"'
                    . ' data-payer-name="' . htmlspecialchars($p['payer_account_name'] ?? '') . '"'
                    . ' data-payer-account="' . htmlspecialchars($p['payer_account_number'] ?? '') . '"'
                    . ' data-payer-bank="' . htmlspecialchars($p['payer_bank_name'] ?? '') . '"'
                    . ' data-reference="' . htmlspecialchars($p['reference'] ?? '') . '"'
                    . ' data-email="' . htmlspecialchars($p['email'] ?? '') . '"'
                    . ' data-amount="' . htmlspecialchars(number_format($p['amount'],2)) . '"';
            ?>
            <tr <?= $dataAttrs ?> >
                <td><?= htmlspecialchars($p['id']) ?></td>
                <td><?= htmlspecialchars($p['reference']) ?></td>
                <td><?= htmlspecialchars($p['payer_account_name'] ?? ($p['name'] ?? '')) ?></td>
                <td><?= htmlspecialchars(number_format($p['amount'],2)) ?></td>
                <td><?= htmlspecialchars($p['gateway'] ?? $p['payment_method']) ?></td>
                <td><?= htmlspecialchars($p['status']) ?></td>
                <td><?= htmlspecialchars($p['payer_account_number'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['payer_bank_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['created_at']) ?></td>
                <td>
                    <?php if (!empty($p['receipt_path'])): ?><a class="btn" href="<?= htmlspecialchars($p['receipt_path']) ?>" target="_blank">Download</a><?php endif; ?>
                    <?php if ($p['status'] === 'pending'): ?>
                        <div style="margin-top:6px;">
                            <button class="btn" onclick="doAction('confirm',<?= $p['id'] ?>)">Confirm</button>
                            <button class="btn" onclick="doAction('reject',<?= $p['id'] ?>)">Reject</button>
                        </div>
                    <?php else: ?>
                        <!-- no actions -->
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Pagination (numbered) -->
    <div style="margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <?php
            // build base query preserving filters
            $qp = $_GET; // current query params
            $makeLink = function($pnum) use ($qp) {
                $qp['page'] = $pnum; return '?' . http_build_query($qp);
            };
            echo '<nav aria-label="Pages">';
            // if many pages, show a sliding window around current page
            $window = 3; // pages before/after
            $start = max(1, $page - $window);
            $end = min($totalPages, $page + $window);
            if ($start > 1) {
                echo '<a class="btn" href="' . $makeLink(1) . '">1</a>';
                if ($start > 2) echo '<span style="padding:6px 8px;color:#666">&hellip;</span>';
            }
            for ($i = $start; $i <= $end; $i++) {
                if ($i == $page) echo '<span style="padding:6px 10px;background:#111;color:#fff;border-radius:4px">' . $i . '</span>'; else echo '<a class="btn" href="' . $makeLink($i) . '">' . $i . '</a>';
            }
            if ($end < $totalPages) {
                if ($end < $totalPages - 1) echo '<span style="padding:6px 8px;color:#666">&hellip;</span>';
                echo '<a class="btn" href="' . $makeLink($totalPages) . '">' . $totalPages . '</a>';
            }
            echo '</nav>';
        ?>
    </div>
</div>

<!-- simple action handler removed in favor of SweetAlert2 modal-based handler below -->

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showToast(type, title){
    Swal.fire({toast:true,position:'top-end',icon:type,title:title,showConfirmButton:false,timer:3000});
}

// Add escapeHtml helper
Swal.escapeHtml = function(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"'`=\/]/g, function (s) {
        return ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        })[s];
    });
};

function doAction(action, id) {
    const fd = new FormData();
    fd.append('action', action);
    fd.append('id', id);

    fetch('index.php?pages=payments', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => {
        if (!r.ok) throw new Error('Network error ' + r.status);
        return r.json();
    })
    .then(data => {
        // normalize status for compatibility with server returning 'ok' or 'success'
        const status = (data.status === 'ok') ? 'success' : data.status;
        const icon = (status === 'ok' || status === 'success') ? 'success' : status;
        Swal.fire({ icon: icon, title: data.message || '' }).then(() => {
            if (status === 'success') location.reload();
        });
    })
    .catch(err => {
        console.error('AJAX error:', err);
        Swal.fire({ icon: 'error', title: 'Unexpected Error', text: err.message });
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php';
