<?php
// admin/appointments.php - View and manage scheduled appointments
session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../public/config/db.php';
require_once __DIR__ . '/../public/config/functions.php';

$pageTitle = 'Appointments Management';
$pageSubtitle = 'View and manage visitor appointments';

// Handle status updates and notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appointmentId = intval($_POST['appointment_id'] ?? 0);
    $action = $_POST['action'];
    
    try {
        if ($action === 'update_status') {
            $newStatus = $_POST['status'] ?? '';
            $adminNotes = trim($_POST['admin_notes'] ?? '');
            
            if (!in_array($newStatus, ['pending', 'confirmed', 'rejected', 'completed'])) {
                throw new Exception('Invalid status');
            }
            
            // Update appointment
            $stmt = $pdo->prepare("UPDATE appointments SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $adminNotes, $appointmentId]);
            
            // Get appointment details for email
            $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
            $stmt->execute([$appointmentId]);
            $appointment = $stmt->fetch();
            
            if ($appointment) {
                // Send notification email to user
                $subject = '';
                $message = '';
                
                if ($newStatus === 'confirmed') {
                    $subject = 'Appointment Confirmed - High Q Academy';
                    $message = "<h2>Your Appointment Has Been Confirmed!</h2>";
                    $message .= "<p>Dear " . htmlspecialchars($appointment['name']) . ",</p>";
                    $message .= "<p>Great news! Your visit to High Q Solid Academy has been <strong>confirmed</strong>.</p>";
                    $message .= "<p><strong>Date:</strong> " . date('F j, Y', strtotime($appointment['visit_date'])) . "<br>";
                    $message .= "<strong>Time:</strong> " . date('g:i A', strtotime($appointment['visit_time'])) . "</p>";
                    $message .= "<p><strong>Location:</strong><br>8 Pineapple Avenue, Aiyetoro<br>Ikorodu North LCDA, Maya, Ikorodu</p>";
                    
                    if ($adminNotes) {
                        $message .= "<p><strong>Additional Information:</strong><br>" . nl2br(htmlspecialchars($adminNotes)) . "</p>";
                    }
                    
                    $message .= "<p>We look forward to seeing you! If you need to reschedule or have any questions, please call us at <strong>0807 208 8794</strong>.</p>";
                    $message .= "<p>Best regards,<br>High Q Solid Academy Team</p>";
                    
                    // Mark notification as sent
                    $pdo->prepare("UPDATE appointments SET notification_sent = 1 WHERE id = ?")->execute([$appointmentId]);
                    
                } elseif ($newStatus === 'rejected') {
                    $subject = 'Appointment Update - High Q Academy';
                    $message = "<h2>Appointment Update</h2>";
                    $message .= "<p>Dear " . htmlspecialchars($appointment['name']) . ",</p>";
                    $message .= "<p>Thank you for your interest in visiting High Q Solid Academy.</p>";
                    $message .= "<p>Unfortunately, we are unable to accommodate your requested appointment on " . date('F j, Y', strtotime($appointment['visit_date'])) . " at " . date('g:i A', strtotime($appointment['visit_time'])) . ".</p>";
                    
                    if ($adminNotes) {
                        $message .= "<p><strong>Reason:</strong><br>" . nl2br(htmlspecialchars($adminNotes)) . "</p>";
                    }
                    
                    $message .= "<p>We'd love to find an alternative time that works for you. Please contact us at <strong>0807 208 8794</strong> or <strong>info@hqacademy.com</strong> to reschedule.</p>";
                    $message .= "<p>Best regards,<br>High Q Solid Academy Team</p>";
                    
                    $pdo->prepare("UPDATE appointments SET notification_sent = 1 WHERE id = ?")->execute([$appointmentId]);
                }
                
                if ($subject && $message) {
                    sendEmail($appointment['email'], $subject, $message);
                }
            }
            
            $_SESSION['success_message'] = 'Appointment status updated and notification sent.';
            
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->execute([$appointmentId]);
            $_SESSION['success_message'] = 'Appointment deleted successfully.';
        }
        
        header('Location: appointments.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');
$dateFilter = $_GET['date'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

if ($searchQuery !== '') {
    $whereConditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchPattern = '%' . $searchQuery . '%';
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
}

if ($dateFilter !== '') {
    $whereConditions[] = "DATE(visit_date) = ?";
    $params[] = $dateFilter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get appointments
$sql = "SELECT * FROM appointments $whereClause ORDER BY visit_date ASC, visit_time ASC, created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Get stats
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM appointments";
$stats = $pdo->query($statsQuery)->fetch();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="admin-header">
    <div>
        <h1><i class="bx bx-calendar"></i> Appointments</h1>
        <p>Manage visitor appointments and schedule</p>
    </div>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <i class="bx bx-check-circle"></i> <?= htmlspecialchars($_SESSION['success_message']) ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
        <i class="bx bx-error-circle"></i> <?= htmlspecialchars($_SESSION['error_message']) ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<!-- Stats Cards -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;color:#2196f3;"><i class="bx bx-calendar"></i></div>
        <div class="stat-info">
            <span>Total Appointments</span>
            <strong><?= number_format($stats['total']) ?></strong>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;color:#ff9800;"><i class="bx bx-time"></i></div>
        <div class="stat-info">
            <span>Pending</span>
            <strong><?= number_format($stats['pending']) ?></strong>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;color:#4caf50;"><i class="bx bx-check-circle"></i></div>
        <div class="stat-info">
            <span>Confirmed</span>
            <strong><?= number_format($stats['confirmed']) ?></strong>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e5f5;color:#9c27b0;"><i class="bx bx-check-double"></i></div>
        <div class="stat-info">
            <span>Completed</span>
            <strong><?= number_format($stats['completed']) ?></strong>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px;">
    <form method="get" class="filters-form" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label style="display:block;margin-bottom:6px;font-weight:600;font-size:14px;">Search</label>
            <input type="text" name="search" placeholder="Search by name, email, or phone" value="<?= htmlspecialchars($searchQuery) ?>" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;">
        </div>
        
        <div style="min-width:160px;">
            <label style="display:block;margin-bottom:6px;font-weight:600;font-size:14px;">Status</label>
            <select name="status" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>
        
        <div style="min-width:160px;">
            <label style="display:block;margin-bottom:6px;font-weight:600;font-size:14px;">Date</label>
            <input type="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;">
        </div>
        
        <button type="submit" class="btn-primary" style="padding:8px 20px;">
            <i class="bx bx-filter"></i> Filter
        </button>
        
        <?php if ($statusFilter !== 'all' || $searchQuery !== '' || $dateFilter !== ''): ?>
            <a href="appointments.php" class="btn-secondary" style="padding:8px 20px;text-decoration:none;display:inline-block;">
                <i class="bx bx-x"></i> Clear
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Appointments Table -->
<div class="card">
    <div class="card-header">
        <h3>Appointments List</h3>
    </div>
    
    <?php if (empty($appointments)): ?>
        <div style="padding:40px;text-align:center;color:#999;">
            <i class="bx bx-calendar-x" style="font-size:48px;margin-bottom:12px;"></i>
            <p>No appointments found.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Visit Date & Time</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $apt): ?>
                        <tr>
                            <td>#<?= $apt['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($apt['name']) ?></strong>
                                <?php if ($apt['message']): ?>
                                    <br><small style="color:#666;" title="<?= htmlspecialchars($apt['message']) ?>">
                                        <i class="bx bx-message-detail"></i> Has message
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size:13px;">
                                    <i class="bx bx-envelope"></i> <?= htmlspecialchars($apt['email']) ?><br>
                                    <?php if ($apt['phone']): ?>
                                        <i class="bx bx-phone"></i> <?= htmlspecialchars($apt['phone']) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?= date('M j, Y', strtotime($apt['visit_date'])) ?></strong><br>
                                <small><?= date('g:i A', strtotime($apt['visit_time'])) ?></small>
                            </td>
                            <td>
                                <?php
                                $statusColors = [
                                    'pending' => 'background:#fff3e0;color:#f57c00;',
                                    'confirmed' => 'background:#e8f5e9;color:#2e7d32;',
                                    'rejected' => 'background:#ffebee;color:#c62828;',
                                    'completed' => 'background:#f3e5f5;color:#7b1fa2;'
                                ];
                                $statusIcons = [
                                    'pending' => 'bx-time',
                                    'confirmed' => 'bx-check-circle',
                                    'rejected' => 'bx-x-circle',
                                    'completed' => 'bx-check-double'
                                ];
                                ?>
                                <span class="badge" style="<?= $statusColors[$apt['status']] ?? '' ?>">
                                    <i class="bx <?= $statusIcons[$apt['status']] ?? '' ?>"></i>
                                    <?= ucfirst($apt['status']) ?>
                                </span>
                            </td>
                            <td>
                                <small><?= date('M j, Y', strtotime($apt['created_at'])) ?></small>
                            </td>
                            <td>
                                <button class="btn-sm btn-primary" onclick="viewAppointment(<?= $apt['id'] ?>)">
                                    <i class="bx bx-show"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- View/Edit Modal -->
<div id="appointmentModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:700px;">
        <div class="modal-header">
            <h3><i class="bx bx-calendar"></i> Appointment Details</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="appointmentDetails">
            <!-- Content loaded via JavaScript -->
        </div>
    </div>
</div>

<script>
function viewAppointment(id) {
    const appointments = <?= json_encode($appointments) ?>;
    const apt = appointments.find(a => a.id == id);
    
    if (!apt) return;
    
    const visitDate = new Date(apt.visit_date + ' ' + apt.visit_time);
    const formattedDate = visitDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    const formattedTime = visitDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    
    const statusColors = {
        'pending': '#ff9800',
        'confirmed': '#4caf50',
        'rejected': '#f44336',
        'completed': '#9c27b0'
    };
    
    let html = `
        <div style="display:grid;gap:20px;">
            <div style="background:#f5f5f5;padding:16px;border-radius:8px;">
                <h4 style="margin:0 0 12px 0;color:#333;">Visitor Information</h4>
                <div style="display:grid;gap:8px;">
                    <div><strong>Name:</strong> ${apt.name}</div>
                    <div><strong>Email:</strong> <a href="mailto:${apt.email}">${apt.email}</a></div>
                    ${apt.phone ? `<div><strong>Phone:</strong> <a href="tel:${apt.phone}">${apt.phone}</a></div>` : ''}
                </div>
            </div>
            
            <div style="background:#f5f5f5;padding:16px;border-radius:8px;">
                <h4 style="margin:0 0 12px 0;color:#333;">Visit Details</h4>
                <div style="display:grid;gap:8px;">
                    <div><strong>Date:</strong> ${formattedDate}</div>
                    <div><strong>Time:</strong> ${formattedTime}</div>
                    <div><strong>Status:</strong> <span style="background:${statusColors[apt.status]};color:#fff;padding:4px 12px;border-radius:12px;font-size:12px;font-weight:600;">${apt.status.toUpperCase()}</span></div>
                    <div><strong>Requested:</strong> ${new Date(apt.created_at).toLocaleString()}</div>
                </div>
            </div>
            
            ${apt.message ? `
            <div style="background:#f5f5f5;padding:16px;border-radius:8px;">
                <h4 style="margin:0 0 12px 0;color:#333;">Message from Visitor</h4>
                <p style="margin:0;white-space:pre-wrap;">${apt.message}</p>
            </div>
            ` : ''}
            
            <form method="post" style="background:#fff;border:2px solid #f0f0f0;padding:20px;border-radius:8px;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="appointment_id" value="${apt.id}">
                
                <div style="margin-bottom:16px;">
                    <label style="display:block;margin-bottom:6px;font-weight:600;">Update Status</label>
                    <select name="status" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
                        <option value="pending" ${apt.status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="confirmed" ${apt.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                        <option value="rejected" ${apt.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                        <option value="completed" ${apt.status === 'completed' ? 'selected' : ''}>Completed</option>
                    </select>
                </div>
                
                <div style="margin-bottom:16px;">
                    <label style="display:block;margin-bottom:6px;font-weight:600;">Admin Notes (visible to user in email)</label>
                    <textarea name="admin_notes" rows="4" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;resize:vertical;" placeholder="Add any additional information or instructions for the visitor...">${apt.admin_notes || ''}</textarea>
                </div>
                
                <div style="display:flex;gap:12px;justify-content:flex-end;">
                    <button type="button" onclick="closeModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="bx bx-save"></i> Save & Send Notification</button>
                </div>
            </form>
            
            <div style="border-top:2px solid #f0f0f0;padding-top:16px;">
                <form method="post" onsubmit="return confirm('Are you sure you want to delete this appointment? This action cannot be undone.');" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="appointment_id" value="${apt.id}">
                    <button type="submit" class="btn-danger" style="background:#f44336;">
                        <i class="bx bx-trash"></i> Delete Appointment
                    </button>
                </form>
            </div>
        </div>
    `;
    
    document.getElementById('appointmentDetails').innerHTML = html;
    document.getElementById('appointmentModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('appointmentModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('appointmentModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<style>
.filters-form input, .filters-form select {
    font-size: 14px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-info span {
    font-size: 13px;
    color: #666;
    margin-bottom: 4px;
}

.stat-info strong {
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

.badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.modal-content {
    background: #fff;
    border-radius: 16px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 30px 90px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 24px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-close {
    background: transparent;
    border: none;
    font-size: 28px;
    color: #999;
    cursor: pointer;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #f5f5f5;
    color: #333;
}

.modal-body {
    padding: 24px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
    border-radius: 6px;
}

.btn-danger {
    background: #f44336;
    color: #fff;
    border: none;
    padding: 10px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-danger:hover {
    background: #d32f2f;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
