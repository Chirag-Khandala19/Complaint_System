<?php
require_once 'config/db.php';
require_once 'config/auth.php';
requireLogin();
$user = getCurrentUser();
$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: complaints.php'); exit(); }

$stmt = $conn->prepare("SELECT c.*, cc.category_name, s.status_name, z.zone_name, sec.sector_name, sp.spot_name, u.full_name as complainant_name, u2.full_name as staff_name FROM complaints c JOIN complaint_categories cc ON c.category_id=cc.category_id JOIN status_master s ON c.current_status_id=s.status_id JOIN zones z ON c.zone_id=z.zone_id JOIN sectors sec ON c.sector_id=sec.sector_id JOIN spots sp ON c.spot_id=sp.spot_id JOIN users u ON c.complainant_id=u.user_id LEFT JOIN users u2 ON c.assigned_staff_id=u2.user_id WHERE c.complaint_id=?");
$stmt->execute([$id]);
$complaint = $stmt->fetch();
if (!$complaint) { header('Location: complaints.php'); exit(); }

// History timeline
$history = $conn->prepare("SELECT ch.*, s1.status_name as old_status, s2.status_name as new_status, u.full_name as changed_by_name FROM complaint_history ch LEFT JOIN status_master s1 ON ch.old_status_id=s1.status_id JOIN status_master s2 ON ch.new_status_id=s2.status_id JOIN users u ON ch.changed_by=u.user_id WHERE ch.complaint_id=? ORDER BY ch.changed_at ASC");
$history->execute([$id]);
$timeline = $history->fetchAll();

// Attachments
$attachments = $conn->prepare("SELECT * FROM complaint_attachments WHERE complaint_id=?");
$attachments->execute([$id]);
$files_list = $attachments->fetchAll();

// Staff list for assignment
$staffList = $conn->query("SELECT user_id, full_name FROM users WHERE role_id = 2 AND is_active = 1")->fetchAll();
$statuses = $conn->query("SELECT * FROM status_master")->fetchAll();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status' && in_array($user['role_name'], ['Staff','Supervisor'])) {
        $newStatusId = intval($_POST['new_status_id']);
        $remark = trim($_POST['remark'] ?? '');
        $oldStatusId = $complaint['current_status_id'];
        
        $conn->beginTransaction();
        $conn->prepare("UPDATE complaints SET current_status_id = ?, updated_at = NOW() WHERE complaint_id = ?")->execute([$newStatusId, $id]);
        if ($newStatusId == 5) $conn->prepare("UPDATE complaints SET resolved_at = NOW() WHERE complaint_id = ?")->execute([$id]);
        if ($newStatusId == 6) $conn->prepare("UPDATE complaints SET closed_at = NOW() WHERE complaint_id = ?")->execute([$id]);
        if ($newStatusId == 7) $conn->prepare("UPDATE complaints SET reopen_count = reopen_count + 1 WHERE complaint_id = ?")->execute([$id]);
        $conn->prepare("INSERT INTO complaint_history (complaint_id, old_status_id, new_status_id, changed_by, remark) VALUES (?,?,?,?,?)")->execute([$id, $oldStatusId, $newStatusId, $user['user_id'], $remark]);
        $conn->commit();
        header("Location: view_complaint.php?id=$id&updated=1"); exit();
    }
    
    if ($action === 'assign' && $user['role_name'] === 'Supervisor') {
        $staffId = intval($_POST['staff_id']);
        $notes = trim($_POST['assign_notes'] ?? '');
        $conn->beginTransaction();
        $conn->prepare("UPDATE complaints SET assigned_staff_id = ?, current_status_id = 3 WHERE complaint_id = ?")->execute([$staffId, $id]);
        $conn->prepare("INSERT INTO assignments (complaint_id, staff_id, assigned_by, notes) VALUES (?,?,?,?)")->execute([$id, $staffId, $user['user_id'], $notes]);
        $conn->prepare("INSERT INTO complaint_history (complaint_id, old_status_id, new_status_id, changed_by, remark) VALUES (?,?,3,?,?)")->execute([$id, $complaint['current_status_id'], $user['user_id'], 'Assigned to staff. '.$notes]);
        $conn->commit();
        header("Location: view_complaint.php?id=$id&updated=1"); exit();
    }
    
    if ($action === 'upload_proof' && $user['role_name'] === 'Staff') {
        if (isset($_FILES['action_proof']) && $_FILES['action_proof']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif','pdf'];
            $ext = strtolower(pathinfo($_FILES['action_proof']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $_FILES['action_proof']['size'] <= 5*1024*1024) {
                $newName = 'action_' . $id . '_' . time() . '.' . $ext;
                $path = 'uploads/actions/' . $newName;
                if (!is_dir('uploads/actions')) mkdir('uploads/actions', 0755, true);
                move_uploaded_file($_FILES['action_proof']['tmp_name'], $path);
                $conn->prepare("INSERT INTO complaint_attachments (complaint_id, file_name, file_path, file_type, file_size, upload_type, uploaded_by) VALUES (?,?,?,?,?,'action_proof',?)")->execute([$id, $_FILES['action_proof']['name'], $path, $ext, $_FILES['action_proof']['size'], $user['user_id']]);
            }
        }
        header("Location: view_complaint.php?id=$id"); exit();
    }
}

// SLA check
$now = time();
$initialSlaBreached = $complaint['initial_response_deadline'] && strtotime($complaint['initial_response_deadline']) < $now && $complaint['current_status_id'] == 1;
$resSlaBreached = $complaint['resolution_deadline'] && strtotime($complaint['resolution_deadline']) < $now && !in_array($complaint['current_status_id'], [5,6]);

function getStatusColor2($s) { $c = ['Submitted'=>'secondary','Verified'=>'info','Assigned'=>'primary','In Progress'=>'warning','Resolved'=>'success','Closed'=>'dark','Reopened'=>'danger','Escalated'=>'danger']; return $c[$s] ?? 'secondary'; }
function getPriorityColor2($p) { $c = ['Low'=>'success','Medium'=>'warning','High'=>'danger','Critical'=>'dark']; return $c[$p] ?? 'secondary'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint #<?= htmlspecialchars($complaint['complaint_code']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>
<div class="container py-4">
    <?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Complaint updated successfully!</div><?php endif; ?>
    
    <?php if ($complaint['is_repeated_flag']): ?>
    <div class="alert alert-warning"><i class="fas fa-flag me-2"></i><strong>Repeated Complaint Flag:</strong> Similar complaint in same area & category within 7 days.</div>
    <?php endif; ?>
    
    <?php if ($resSlaBreached): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><strong>SLA Breached!</strong> Resolution deadline has passed.</div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($complaint['complaint_code']) ?> - <?= htmlspecialchars($complaint['title']) ?></h5>
                    <span class="badge bg-<?= getStatusColor2($complaint['status_name']) ?> fs-6"><?= htmlspecialchars($complaint['status_name']) ?></span>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Category:</strong><br><?= htmlspecialchars($complaint['category_name']) ?></div>
                        <div class="col-md-4"><strong>Priority:</strong><br><span class="badge bg-<?= getPriorityColor2($complaint['priority']) ?>"><?= $complaint['priority'] ?></span></div>
                        <div class="col-md-4"><strong>Complainant:</strong><br><?= htmlspecialchars($complaint['complainant_name']) ?></div>
                        <div class="col-md-4"><strong>Zone:</strong><br><?= htmlspecialchars($complaint['zone_name']) ?></div>
                        <div class="col-md-4"><strong>Sector:</strong><br><?= htmlspecialchars($complaint['sector_name']) ?></div>
                        <div class="col-md-4"><strong>Spot:</strong><br><?= htmlspecialchars($complaint['spot_name']) ?></div>
                        <div class="col-md-4"><strong>Location:</strong><br><?= htmlspecialchars($complaint['exact_location'] ?: 'N/A') ?></div>
                        <div class="col-md-4"><strong>Assigned To:</strong><br><?= htmlspecialchars($complaint['staff_name'] ?: 'Unassigned') ?></div>
                        <div class="col-md-4"><strong>Filed:</strong><br><?= date('d M Y H:i', strtotime($complaint['complaint_date'])) ?></div>
                        <div class="col-md-4"><strong>Response SLA:</strong><br><?= $complaint['initial_response_deadline'] ? date('d M Y H:i', strtotime($complaint['initial_response_deadline'])) : 'N/A' ?> <?= $initialSlaBreached ? '<span class="text-danger">(BREACHED)</span>' : '' ?></div>
                        <div class="col-md-4"><strong>Resolution SLA:</strong><br><?= $complaint['resolution_deadline'] ? date('d M Y H:i', strtotime($complaint['resolution_deadline'])) : 'N/A' ?> <?= $resSlaBreached ? '<span class="text-danger">(BREACHED)</span>' : '' ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Attachments -->
            <?php if (!empty($files_list)): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0">Attachments</h6></div>
                <div class="card-body">
                    <?php foreach ($files_list as $f): ?>
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-paperclip me-2"></i>
                        <a href="<?= htmlspecialchars($f['file_path']) ?>" target="_blank"><?= htmlspecialchars($f['file_name']) ?></a>
                        <span class="badge bg-light text-dark ms-2"><?= $f['upload_type'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Timeline -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0"><i class="fas fa-history me-2"></i>Complaint Timeline</h6></div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($timeline as $t): ?>
                        <div class="timeline-item mb-3 ps-4 border-start border-3 border-primary">
                            <div class="d-flex justify-content-between">
                                <strong><?= $t['old_status'] ? htmlspecialchars($t['old_status']).' → ' : '' ?><?= htmlspecialchars($t['new_status']) ?></strong>
                                <small class="text-muted"><?= date('d M Y H:i', strtotime($t['changed_at'])) ?></small>
                            </div>
                            <small class="text-muted">By: <?= htmlspecialchars($t['changed_by_name']) ?></small>
                            <?php if ($t['remark']): ?><p class="mb-0 mt-1"><?= htmlspecialchars($t['remark']) ?></p><?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Actions -->
            <?php if ($user['role_name'] === 'Supervisor' && !$complaint['assigned_staff_id']): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0">Assign Staff</h6></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="assign">
                        <div class="mb-3">
                            <select class="form-select" name="staff_id" required>
                                <option value="">Select Staff</option>
                                <?php foreach ($staffList as $s): ?>
                                <option value="<?= $s['user_id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3"><textarea class="form-control" name="assign_notes" rows="2" placeholder="Assignment notes"></textarea></div>
                        <button class="btn btn-primary w-100">Assign</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (in_array($user['role_name'], ['Staff','Supervisor']) && $complaint['current_status_id'] == 6): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0">Reopen Complaint</h6></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="new_status_id" value="7">
                        <div class="mb-3"><textarea class="form-control" name="remark" rows="2" placeholder="Reason for reopening" required></textarea></div>
                        <button class="btn btn-danger w-100">Reopen Complaint</button>
                    </form>
                </div>
            </div>
            <?php elseif (in_array($user['role_name'], ['Staff','Supervisor'])): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0">Update Status</h6></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <div class="mb-3">
                            <select class="form-select" name="new_status_id" required>
                                <option value="">Select Status</option>
                                <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s['status_id'] ?>"><?= htmlspecialchars($s['status_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3"><textarea class="form-control" name="remark" rows="2" placeholder="Remark" required></textarea></div>
                        <button class="btn btn-warning w-100">Update</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($user['role_name'] === 'Staff'): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="fw-bold mb-0">Upload Action Proof</h6></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_proof">
                        <input type="file" class="form-control mb-3" name="action_proof" accept=".jpg,.jpeg,.png,.pdf" required>
                        <button class="btn btn-success w-100">Upload</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>