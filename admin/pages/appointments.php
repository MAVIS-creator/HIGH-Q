<?php
// admin/pages/appointments.php - Tailwind React-like UI
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission(['appointments','settings','students']);

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
            $stmt = $pdo->prepare("UPDATE appointments SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $adminNotes, $appointmentId]);
            $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
            $stmt->execute([$appointmentId]);
            $appointment = $stmt->fetch();
            if ($appointment) {
                $subject = ''; $message = '';
                if ($newStatus === 'confirmed') {
                    $subject = 'Appointment Confirmed - High Q Academy';
                    $message = "<h2>Your Appointment Has Been Confirmed!</h2><p>Dear " . htmlspecialchars($appointment['name']) . ",</p><p>Your visit to High Q Solid Academy has been <strong>confirmed</strong>.</p><p><strong>Date:</strong> " . date('F j, Y', strtotime($appointment['visit_date'])) . "<br><strong>Time:</strong> " . date('g:i A', strtotime($appointment['visit_time'])) . "</p><p><strong>Location:</strong><br>8 Pineapple Avenue, Aiyetoro<br>Ikorodu North LCDA, Maya, Ikorodu</p>";
                    if ($adminNotes) $message .= "<p><strong>Additional Information:</strong><br>" . nl2br(htmlspecialchars($adminNotes)) . "</p>";
                    $message .= "<p>We look forward to seeing you! Phone: <strong>0807 208 8794</strong></p>";
                    $pdo->prepare("UPDATE appointments SET notification_sent = 1 WHERE id = ?")->execute([$appointmentId]);
                } elseif ($newStatus === 'rejected') {
                    $subject = 'Appointment Update - High Q Academy';
                    $message = "<h2>Appointment Update</h2><p>Dear " . htmlspecialchars($appointment['name']) . ",</p><p>We are unable to accommodate your requested appointment on " . date('F j, Y', strtotime($appointment['visit_date'])) . " at " . date('g:i A', strtotime($appointment['visit_time'])) . ".</p>";
                    if ($adminNotes) $message .= "<p><strong>Reason:</strong><br>" . nl2br(htmlspecialchars($adminNotes)) . "</p>";
                    $message .= "<p>Please contact us to reschedule: <strong>0807 208 8794</strong></p>";
                    $pdo->prepare("UPDATE appointments SET notification_sent = 1 WHERE id = ?")->execute([$appointmentId]);
                }
                if ($subject && $message) { sendEmail($appointment['email'], $subject, $message); }
                notifyAdminChange($pdo, 'Appointment Status Updated', [
                    'Appointment ID' => $appointmentId,
                    'Visitor Email' => $appointment['email'] ?? 'N/A',
                    'New Status' => $newStatus
                ], (int)($_SESSION['user']['id'] ?? 0));
            }
            $_SESSION['success_message'] = 'Appointment status updated and notification sent.';
            header('Location: index.php?pages=appointments'); exit;
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->execute([$appointmentId]);
            notifyAdminChange($pdo, 'Appointment Deleted', ['Appointment ID' => $appointmentId], (int)($_SESSION['user']['id'] ?? 0));
            $_SESSION['success_message'] = 'Appointment deleted successfully.';
            header('Location: index.php?pages=appointments'); exit;
        }
    } catch (Exception $e) { $_SESSION['error_message'] = 'Error: ' . $e->getMessage(); }
}

$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');
$dateFilter = $_GET['date'] ?? '';
$whereConditions = []; $params = [];
if ($statusFilter !== 'all') { $whereConditions[] = "status = ?"; $params[] = $statusFilter; }
if ($searchQuery !== '') { $whereConditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)"; $params[] = "%$searchQuery%"; $params[] = "%$searchQuery%"; $params[] = "%$searchQuery%"; }
if ($dateFilter !== '') { $whereConditions[] = "DATE(visit_date) = ?"; $params[] = $dateFilter; }
$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
$sql = "SELECT * FROM appointments $whereClause ORDER BY visit_date ASC, visit_time ASC, created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $appointments = $stmt->fetchAll();
$statsQuery = "SELECT COUNT(*) total, SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) pending, SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) confirmed, SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) rejected, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) completed FROM appointments";
$stats = $pdo->query($statsQuery)->fetch();
?>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<div class="space-y-6">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 p-6 shadow-xl text-slate-900">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(0,0,0,0.05),transparent_35%)]"></div>
        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-amber-900/70">Visitor Management</p>
                <h1 class="mt-1 text-2xl sm:text-3xl font-bold">Appointments</h1>
                <p class="mt-1 text-slate-800/80">Manage visitor appointments and schedule</p>
            </div>
            <div class="flex items-center gap-2 text-sm bg-slate-900/10 backdrop-blur-md border border-slate-900/20 rounded-full px-4 py-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <span class="text-slate-900"><?= number_format($stats['total']) ?> total</span>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 flex items-center gap-3">
            <i class="bx bx-check-circle text-emerald-600 text-xl"></i>
            <span class="text-emerald-800"><?= htmlspecialchars($_SESSION['success_message']) ?></span>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 flex items-center gap-3">
            <i class="bx bx-error-circle text-rose-600 text-xl"></i>
            <span class="text-rose-800"><?= htmlspecialchars($_SESSION['error_message']) ?></span>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-4 shadow-sm">
            <div class="h-12 w-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600"><i class="bx bx-calendar text-2xl"></i></div>
            <div><p class="text-xs text-slate-500 font-semibold">Total</p><p class="text-2xl font-bold text-slate-800"><?= number_format($stats['total']) ?></p></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-4 shadow-sm">
            <div class="h-12 w-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600"><i class="bx bx-time text-2xl"></i></div>
            <div><p class="text-xs text-slate-500 font-semibold">Pending</p><p class="text-2xl font-bold text-slate-800"><?= number_format($stats['pending']) ?></p></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-4 shadow-sm">
            <div class="h-12 w-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600"><i class="bx bx-check-circle text-2xl"></i></div>
            <div><p class="text-xs text-slate-500 font-semibold">Confirmed</p><p class="text-2xl font-bold text-slate-800"><?= number_format($stats['confirmed']) ?></p></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-4 shadow-sm">
            <div class="h-12 w-12 rounded-xl bg-violet-50 flex items-center justify-center text-violet-600"><i class="bx bx-check-double text-2xl"></i></div>
            <div><p class="text-xs text-slate-500 font-semibold">Completed</p><p class="text-2xl font-bold text-slate-800"><?= number_format($stats['completed']) ?></p></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
        <form method="get" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="pages" value="appointments">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
                <input type="text" name="search" placeholder="Name, email, or phone" value="<?= htmlspecialchars($searchQuery) ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
            </div>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-400 hover:bg-amber-500 text-slate-900 font-semibold px-4 py-2 text-sm transition"><i class="bx bx-filter"></i>Filter</button>
            <?php if ($statusFilter !== 'all' || $searchQuery !== '' || $dateFilter !== ''): ?>
                <a href="index.php?pages=appointments" class="inline-flex items-center gap-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 py-2 text-sm transition"><i class="bx bx-x"></i>Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Appointments Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60">
            <h2 class="text-lg font-semibold text-slate-800">Appointments List</h2>
        </div>
        <?php if (empty($appointments)): ?>
            <div class="p-10 text-center text-slate-500">
                <div class="mx-auto mb-3 h-14 w-14 rounded-full bg-slate-100 flex items-center justify-center"><i class="bx bx-calendar-x text-3xl text-slate-400"></i></div>
                <p class="font-semibold">No appointments found</p>
                <p class="text-sm">Try adjusting your filters.</p>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">ID</th>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Contact</th>
                        <th class="px-4 py-3 text-left">Visit Date & Time</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Created</th>
                        <th class="px-4 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($appointments as $apt): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-700 font-mono">#<?= $apt['id'] ?></td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800"><?= htmlspecialchars($apt['name']) ?></p>
                            <?php if ($apt['message']): ?>
                                <p class="text-xs text-slate-500"><i class="bx bx-message-detail"></i> Has message</p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs">
                            <p><i class="bx bx-envelope"></i> <?= htmlspecialchars($apt['email']) ?></p>
                            <?php if ($apt['phone']): ?><p><i class="bx bx-phone"></i> <?= htmlspecialchars($apt['phone']) ?></p><?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800"><?= date('M j, Y', strtotime($apt['visit_date'])) ?></p>
                            <p class="text-xs text-slate-500"><?= date('g:i A', strtotime($apt['visit_time'])) ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                            $statusClasses = [
                                'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'confirmed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
                                'completed' => 'bg-violet-50 text-violet-700 border-violet-200'
                            ];
                            $statusIcons = ['pending' => 'bx-time', 'confirmed' => 'bx-check-circle', 'rejected' => 'bx-x-circle', 'completed' => 'bx-check-double'];
                            ?>
                            <span class="inline-flex items-center gap-1 rounded-full border px-2 py-1 text-xs font-semibold <?= $statusClasses[$apt['status']] ?? '' ?>">
                                <i class="bx <?= $statusIcons[$apt['status']] ?? '' ?>"></i><?= ucfirst($apt['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500"><?= date('M j, Y', strtotime($apt['created_at'])) ?></td>
                        <td class="px-4 py-3">
                            <button onclick="viewAppointment(<?= $apt['id'] ?>)" class="inline-flex items-center gap-1 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold px-3 py-1.5 text-xs transition"><i class="bx bx-show"></i>View</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div id="appointmentModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="text-lg font-semibold text-slate-800 flex items-center gap-2"><i class="bx bx-calendar text-amber-500"></i>Appointment Details</h3>
            <button onclick="closeModal()" class="h-8 w-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 transition">&times;</button>
        </div>
        <div class="p-6" id="appointmentDetails"></div>
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
    const statusColors = { 'pending': 'bg-amber-50 text-amber-700', 'confirmed': 'bg-emerald-50 text-emerald-700', 'rejected': 'bg-rose-50 text-rose-700', 'completed': 'bg-violet-50 text-violet-700' };

    let html = `
    <div class="space-y-5">
        <div class="rounded-xl bg-slate-50 p-4">
            <p class="text-xs font-semibold text-slate-500 mb-2">Visitor Information</p>
            <p class="font-semibold text-slate-800">${apt.name}</p>
            <p class="text-sm text-slate-600"><a href="mailto:${apt.email}" class="text-amber-600 hover:underline">${apt.email}</a></p>
            ${apt.phone ? `<p class="text-sm text-slate-600"><a href="tel:${apt.phone}" class="text-amber-600 hover:underline">${apt.phone}</a></p>` : ''}
        </div>
        <div class="rounded-xl bg-slate-50 p-4">
            <p class="text-xs font-semibold text-slate-500 mb-2">Visit Details</p>
            <p class="text-sm"><span class="font-semibold">Date:</span> ${formattedDate}</p>
            <p class="text-sm"><span class="font-semibold">Time:</span> ${formattedTime}</p>
            <p class="text-sm mt-2"><span class="font-semibold">Status:</span> <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold ${statusColors[apt.status]}">${apt.status.toUpperCase()}</span></p>
        </div>
        ${apt.message ? `<div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-semibold text-slate-500 mb-2">Message</p><p class="text-sm text-slate-700 whitespace-pre-wrap">${apt.message}</p></div>` : ''}
        <form method="post" class="rounded-xl border-2 border-slate-100 p-4 space-y-4">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="appointment_id" value="${apt.id}">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Update Status</label>
                <select name="status" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-amber-400">
                    <option value="pending" ${apt.status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="confirmed" ${apt.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                    <option value="rejected" ${apt.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                    <option value="completed" ${apt.status === 'completed' ? 'selected' : ''}>Completed</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Admin Notes</label>
                <textarea name="admin_notes" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm resize-none focus:border-amber-400" placeholder="Optional notes...">${apt.admin_notes || ''}</textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeModal()" class="rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 py-2 text-sm transition">Cancel</button>
                <button type="submit" class="rounded-lg bg-amber-400 hover:bg-amber-500 text-slate-900 font-semibold px-4 py-2 text-sm transition"><i class="bx bx-save"></i> Save & Notify</button>
            </div>
        </form>
        <form method="post" onsubmit="return confirm('Delete this appointment?');" class="pt-4 border-t border-slate-100">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="appointment_id" value="${apt.id}">
            <button type="submit" class="rounded-lg bg-rose-500 hover:bg-rose-600 text-white font-semibold px-4 py-2 text-sm transition"><i class="bx bx-trash"></i> Delete</button>
        </form>
    </div>`;
    document.getElementById('appointmentDetails').innerHTML = html;
    document.getElementById('appointmentModal').classList.remove('hidden');
    document.getElementById('appointmentModal').classList.add('flex');
}
function closeModal() {
    document.getElementById('appointmentModal').classList.add('hidden');
    document.getElementById('appointmentModal').classList.remove('flex');
}
document.getElementById('appointmentModal')?.addEventListener('click', function(e){ if (e.target === this) closeModal(); });
</script>
