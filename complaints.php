<?php
require_once 'config/db.php';
require_once 'config/auth.php';
requireLogin();
$user = getCurrentUser();

$where = "1=1";
$params = [];
if ($user['role_name'] === 'Complainant') { $where .= " AND c.complainant_id = ?"; $params[] = $user['user_id']; }
elseif ($user['role_name'] === 'Staff') { $where .= " AND (c.assigned_staff_id = ? OR c.assigned_staff_id IS NULL)"; $params[] = $user['user_id']; }

// Filters
if (!empty($_GET['status'])) {
    $statusId = intval($_GET['status']);
    if ($statusId === 7) {
        $where .= " AND (c.current_status_id = ? OR c.reopen_count > 0)";
        $params[] = $statusId;
    } else {
        $where .= " AND c.current_status_id = ?";
        $params[] = $statusId;
    }
}
if (!empty($_GET['category'])) { 
    if ($_GET['category'] == -1) { // Recent Complaints
        $where .= " AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } else {
        $where .= " AND c.category_id = ?"; $params[] = intval($_GET['category']); 
    }
}
if (!empty($_GET['priority'])) { $where .= " AND c.priority = ?"; $params[] = $_GET['priority']; }

// Save filter preference in cookie
if (!empty($_GET['status']) || !empty($_GET['category'])) {
    setcookie('saved_filter_status', $_GET['status'] ?? '', time() + 86400, '/');
    setcookie('saved_filter_category', $_GET['category'] ?? '', time() + 86400, '/');
}

$stmt = $conn->prepare("SELECT c.*, cc.category_name, s.status_name, u.full_name as complainant_name, z.zone_name FROM complaints c JOIN complaint_categories cc ON c.category_id=cc.category_id JOIN status_master s ON c.current_status_id=s.status_id JOIN users u ON c.complainant_id=u.user_id JOIN zones z ON c.zone_id=z.zone_id WHERE $where ORDER BY c.created_at DESC");
$stmt->execute($params);
$complaints = $stmt->fetchAll();
$categories = $conn->query("SELECT * FROM complaint_categories WHERE is_active=1")->fetchAll();
$statusList = $conn->query("SELECT * FROM status_master")->fetchAll();

function getStatusColor3($s) { $c = ['Submitted'=>'secondary','Verified'=>'info','Assigned'=>'primary','In Progress'=>'warning','Resolved'=>'success','Closed'=>'dark','Reopened'=>'danger','Escalated'=>'danger']; return $c[$s] ?? 'secondary'; }
function getPriorityColor3($p) { $c = ['Low'=>'success','Medium'=>'warning','High'=>'danger','Critical'=>'dark']; return $c[$p] ?? 'secondary'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>
<div class="container-fluid py-4">
    <h4 class="fw-bold mb-4"><i class="fas fa-list me-2"></i>Complaints</h4>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All</option>
                        <?php foreach ($statusList as $s): ?>
                        <option value="<?= $s['status_id'] ?>" <?= ($_GET['status'] ?? '') == $s['status_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['status_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">All</option>
                        <option value="-1" <?= ($_GET['category'] ?? '') == '-1' ? 'selected' : '' ?>>Recent Complaints</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= ($_GET['category'] ?? '') == $cat['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priority</label>
                    <select class="form-select" name="priority">
                        <option value="">All</option>
                        <option value="Low" <?= ($_GET['priority'] ?? '') == 'Low' ? 'selected' : '' ?>>Low</option>
                        <option value="Medium" <?= ($_GET['priority'] ?? '') == 'Medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="High" <?= ($_GET['priority'] ?? '') == 'High' ? 'selected' : '' ?>>High</option>
                        <option value="Critical" <?= ($_GET['priority'] ?? '') == 'Critical' ? 'selected' : '' ?>>Critical</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary me-2"><i class="fas fa-filter me-1"></i>Filter</button>
                    <a href="complaints.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 ">
                    <thead class="table-light">
                        <tr><th>Code</th><th>Title</th><th>Category</th><th>Zone</th><th>Status</th><th>Priority</th><th>Date</th><th>Flags</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($complaints as $c): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['complaint_code']) ?></strong></td>
                        <td><?= htmlspecialchars(substr($c['title'], 0, 40)) ?></td>
                        <td><?= htmlspecialchars($c['category_name']) ?></td>
                        <td><?= htmlspecialchars($c['zone_name']) ?></td>
                        <td><span class="badge bg-<?= getStatusColor3($c['status_name']) ?>"><?= $c['status_name'] ?></span></td>
                        <td><span class="badge bg-<?= getPriorityColor3($c['priority']) ?>"><?= $c['priority'] ?></span></td>
                        <td><?= date('d M Y', strtotime($c['complaint_date'])) ?></td>
                        <td>
                            <?php if ($c['is_repeated_flag']): ?><span class="badge bg-warning">Repeated</span><?php endif; ?>
                            <?php if ($c['reopen_count'] > 0): ?><span class="badge bg-danger">Reopened(<?= $c['reopen_count'] ?>)</span><?php endif; ?>
                            <?php if ($c['resolution_deadline'] && strtotime($c['resolution_deadline']) < time() && !in_array($c['current_status_id'],[5,6])): ?><span class="badge bg-danger">SLA!</span><?php endif; ?>
                        </td>
                        <td><a href="view_complaint.php?id=<?= $c['complaint_id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($complaints)): ?><tr><td colspan="9" class="text-center py-4 text-muted">No complaints found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>