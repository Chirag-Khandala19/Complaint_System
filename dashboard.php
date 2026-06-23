<?php
require_once 'config/db.php';
require_once 'config/auth.php';
requireLogin();

$user = getCurrentUser();

// Stats
$stats = [];
if ($user['role_name'] === 'Supervisor') {
    $stats['total'] = $conn->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
    $stats['pending'] = $conn->query("SELECT COUNT(*) FROM complaints WHERE current_status_id IN (1,2,3,4)")->fetchColumn();
    $stats['resolved'] = $conn->query("SELECT COUNT(*) FROM complaints WHERE current_status_id IN (5,6)")->fetchColumn();
    $stats['escalated'] = $conn->query("SELECT COUNT(*) FROM complaints WHERE current_status_id = 8")->fetchColumn();
    $stats['sla_breached'] = $conn->query("SELECT COUNT(*) FROM complaints WHERE resolution_deadline < NOW() AND current_status_id NOT IN (5,6)")->fetchColumn();
    $stats['reopened'] = $conn->query("SELECT COUNT(*) FROM complaints WHERE reopen_count > 0")->fetchColumn();
    $recentQuery = "SELECT c.*, cc.category_name, s.status_name, u.full_name as complainant_name FROM complaints c JOIN complaint_categories cc ON c.category_id = cc.category_id JOIN status_master s ON c.current_status_id = s.status_id JOIN users u ON c.complainant_id = u.user_id ORDER BY c.created_at DESC LIMIT 10";
} elseif ($user['role_name'] === 'Staff') {
    $stats['assigned'] = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_staff_id = ?");
    $stats['assigned']->execute([$user['user_id']]);
    $stats['assigned'] = $stats['assigned']->fetchColumn();
    $stats['in_progress'] = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_staff_id = ? AND current_status_id = 4");
    $stats['in_progress']->execute([$user['user_id']]);
    $stats['in_progress'] = $stats['in_progress']->fetchColumn();
    $stats['resolved'] = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_staff_id = ? AND current_status_id IN (5,6)");
    $stats['resolved']->execute([$user['user_id']]);
    $stats['resolved'] = $stats['resolved']->fetchColumn();
    $recentQuery = "SELECT c.*, cc.category_name, s.status_name, u.full_name as complainant_name FROM complaints c JOIN complaint_categories cc ON c.category_id = cc.category_id JOIN status_master s ON c.current_status_id = s.status_id JOIN users u ON c.complainant_id = u.user_id  ORDER BY c.created_at DESC LIMIT 10";
} else {
    $stats['my_total'] = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE complainant_id = ?");
    $stats['my_total']->execute([$user['user_id']]);
    $stats['my_total'] = $stats['my_total']->fetchColumn();
    $stats['my_pending'] = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE complainant_id = ? AND current_status_id IN (1,2,3,4)");
    $stats['my_pending']->execute([$user['user_id']]);
    $stats['my_pending'] = $stats['my_pending']->fetchColumn();
    $stats['my_resolved'] = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE complainant_id = ? AND current_status_id IN (5,6)");
    $stats['my_resolved']->execute([$user['user_id']]);
    $stats['my_resolved'] = $stats['my_resolved']->fetchColumn();
    $recentQuery = "SELECT c.*, cc.category_name, s.status_name FROM complaints c JOIN complaint_categories cc ON c.category_id = cc.category_id JOIN status_master s ON c.current_status_id = s.status_id WHERE c.complainant_id = {$user['user_id']} ORDER BY c.created_at DESC LIMIT 10";
}
$recent = $conn->query($recentQuery)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Network and Connectivity Issues</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Welcome, <?= htmlspecialchars($user['full_name']) ?></h4>
            <small class="text-muted">Domain: Network & Connectivity Issues | Area Model: Zone → Sector → Spot</small>
        </div>
        <?php if ($user['role_name'] === 'Complainant'): ?>
        <a href="register_complaint.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>New Complaint</a>
        <?php endif; ?>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
    <?php if ($user['role_name'] === 'Supervisor'): ?>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center p-3"><h3 class="text-primary fw-bold"><?= $stats['total'] ?></h3><small class="text-muted">Total</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center p-3"><h3 class="text-warning fw-bold"><?= $stats['pending'] ?></h3><small class="text-muted">Pending</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center p-3"><h3 class="text-success fw-bold"><?= $stats['resolved'] ?></h3><small class="text-muted">Resolved</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center p-3"><h3 class="text-danger fw-bold"><?= $stats['escalated'] ?></h3><small class="text-muted">Escalated</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center p-3"><h3 class="text-danger fw-bold"><?= $stats['sla_breached'] ?></h3><small class="text-muted">SLA Breached</small></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm text-center p-3"><h3 class="text-info fw-bold"><?= $stats['reopened'] ?></h3><small class="text-muted">Reopened</small></div></div>
    <?php elseif ($user['role_name'] === 'Staff'): ?>
        <div class="col-md-4"><div class="card border-0 shadow-sm text-center p-3"><h3 class="text-primary fw-bold"><?= $stats['assigned'] ?></h3><small class="text-muted">Assigned to Me</small></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm text-center p-3"><h3 class="text-warning fw-bold"><?= $stats['in_progress'] ?></h3><small class="text-muted">In Progress</small></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm text-center p-3"><h3 class="text-success fw-bold"><?= $stats['resolved'] ?></h3><small class="text-muted">Resolved</small></div></div>
    <?php else: ?>
        <div class="col-md-4"><div class="card border-0 shadow-sm text-center p-3"><h3 class="text-primary fw-bold"><?= $stats['my_total'] ?></h3><small class="text-muted">My Complaints</small></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm text-center p-3"><h3 class="text-warning fw-bold"><?= $stats['my_pending'] ?></h3><small class="text-muted">Pending</small></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm text-center p-3"><h3 class="text-success fw-bold"><?= $stats['my_resolved'] ?></h3><small class="text-muted">Resolved</small></div></div>
    <?php endif; ?>
    </div>

    <!-- SLA Config Info -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold"><i class="fas fa-clock me-2"></i>SLA Configuration</h6>
            <div class="row">
                <div class="col-md-3"><span class="text-muted">Initial Response SLA:</span> <strong>5 hours</strong></div>
                <div class="col-md-3"><span class="text-muted">Resolution SLA:</span> <strong>48 hours</strong></div>
                <div class="col-md-3"><span class="text-muted">Special Rule:</span> <strong>Repeated Complaint Flagging</strong></div>
                <div class="col-md-3"><span class="text-muted">Mandatory Report:</span> <strong>Reopened Complaints Summary</strong></div>
            </div>
        </div>
    </div>

    <!-- Recent Complaints -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><h6 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Recent Complaints</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Code</th><th>Title</th><th>Category</th><th>Status</th><th>Priority</th><th>Date</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['complaint_code']) ?></strong></td>
                            <td><?= htmlspecialchars($c['title']) ?></td>
                            <td><?= htmlspecialchars($c['category_name']) ?></td>
                            <td><span class="badge bg-<?= getStatusColor($c['status_name']) ?>"><?= htmlspecialchars($c['status_name']) ?></span></td>
                            <td><span class="badge bg-<?= getPriorityColor($c['priority']) ?>"><?= htmlspecialchars($c['priority']) ?></span></td>
                            <td><?= date('d M Y', strtotime($c['complaint_date'])) ?></td>
                            <td><a href="view_complaint.php?id=<?= $c['complaint_id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No complaints found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
<?php
function getStatusColor($status) {
    $colors = ['Submitted'=>'secondary','Verified'=>'info','Assigned'=>'primary','In Progress'=>'warning','Resolved'=>'success','Closed'=>'dark','Reopened'=>'danger','Escalated'=>'danger'];
    return $colors[$status] ?? 'secondary';
}
function getPriorityColor($priority) {
    $colors = ['Low'=>'success','Medium'=>'warning','High'=>'danger','Critical'=>'dark'];
    return $colors[$priority] ?? 'secondary';
}
?>