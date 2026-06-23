<?php
require_once 'config/db.php';
require_once 'config/auth.php';
requireRole(['Supervisor']);

// Reopened Complaints Summary (Mandatory Report R=5)
$reopened = $conn->query("SELECT c.complaint_code, c.title, cc.category_name, z.zone_name, c.reopen_count, c.priority, s.status_name, u.full_name as complainant FROM complaints c JOIN complaint_categories cc ON c.category_id=cc.category_id JOIN zones z ON c.zone_id=z.zone_id JOIN status_master s ON c.current_status_id=s.status_id JOIN users u ON c.complainant_id=u.user_id WHERE c.reopen_count > 0 ORDER BY c.reopen_count DESC")->fetchAll();

// Additional reports
$byCategory = $conn->query("SELECT cc.category_name, COUNT(*) as total, SUM(CASE WHEN c.current_status_id IN (5,6) THEN 1 ELSE 0 END) as resolved FROM complaints c JOIN complaint_categories cc ON c.category_id=cc.category_id GROUP BY cc.category_id")->fetchAll();

$byZone = $conn->query("SELECT z.zone_name, COUNT(*) as total, SUM(CASE WHEN c.current_status_id NOT IN (5,6) THEN 1 ELSE 0 END) as pending FROM complaints c JOIN zones z ON c.zone_id=z.zone_id GROUP BY z.zone_id")->fetchAll();

$slaBreached = $conn->query("SELECT c.complaint_code, c.title, c.resolution_deadline, s.status_name FROM complaints c JOIN status_master s ON c.current_status_id=s.status_id WHERE c.resolution_deadline < NOW() AND c.current_status_id NOT IN (5,6)")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>
<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4"><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h4>
    
    <!-- Mandatory Report: Reopened Complaints Summary -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-danger text-white"><h6 class="fw-bold mb-0"><i class="fas fa-star me-2"></i>Mandatory Report: Reopened Complaints Summary (R=5)</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Code</th><th>Title</th><th>Category</th><th>Zone</th><th>Reopen Count</th><th>Priority</th><th>Current Status</th><th>Complainant</th></tr></thead>
                    <tbody>
                    <?php foreach ($reopened as $r): ?>
                    <tr><td><?= htmlspecialchars($r['complaint_code']) ?></td><td><?= htmlspecialchars($r['title']) ?></td><td><?= $r['category_name'] ?></td><td><?= $r['zone_name'] ?></td><td><span class="badge bg-danger"><?= $r['reopen_count'] ?></span></td><td><?= $r['priority'] ?></td><td><?= $r['status_name'] ?></td><td><?= $r['complainant'] ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($reopened)): ?><tr><td colspan="8" class="text-center text-muted py-3">No reopened complaints.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0">Category-wise Summary</h6></div>
                <div class="card-body p-0">
                    <table class="table mb-0"><thead class="table-light"><tr><th>Category</th><th>Total</th><th>Resolved</th></tr></thead><tbody>
                    <?php foreach ($byCategory as $c): ?><tr><td><?= $c['category_name'] ?></td><td><?= $c['total'] ?></td><td><?= $c['resolved'] ?></td></tr><?php endforeach; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0">Zone-wise Pending</h6></div>
                <div class="card-body p-0">
                    <table class="table mb-0"><thead class="table-light"><tr><th>Zone</th><th>Total</th><th>Pending</th></tr></thead><tbody>
                    <?php foreach ($byZone as $z): ?><tr><td></td><td><?= $z['total'] ?></td><td><?= $z['pending'] ?></td></tr><?php endforeach; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
    </div>

    <!-- SLA Breached -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-warning"><h6 class="fw-bold mb-0">SLA Breached Complaints</h6></div>
        <div class="card-body p-0">
            <table class="table mb-0"><thead class="table-light"><tr><th>Code</th><th>Title</th><th>Deadline</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($slaBreached as $s): ?><tr><td><?= $s['complaint_code'] ?></td><td><?= $s['title'] ?></td><td class="text-danger"><?= $s['resolution_deadline'] ?></td><td><?= $s['status_name'] ?></td></tr><?php endforeach; ?>
            <?php if (empty($slaBreached)): ?><tr><td colspan="4" class="text-center text-muted py-3">No SLA breaches.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>