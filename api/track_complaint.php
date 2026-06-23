<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$code = trim($_GET['code'] ?? '');
if (empty($code)) { echo json_encode(['error' => 'Complaint code required']); exit; }

$stmt = $conn->prepare("SELECT c.*, cc.category_name, s.status_name FROM complaints c JOIN complaint_categories cc ON c.category_id=cc.category_id JOIN status_master s ON c.current_status_id=s.status_id WHERE c.complaint_code = ?");
$stmt->execute([$code]);
$c = $stmt->fetch();
if (!$c) { echo json_encode(['error' => 'Complaint not found']); exit; }

$hist = $conn->prepare("SELECT ch.*, s1.status_name as old_status, s2.status_name as new_status, u.full_name as changed_by FROM complaint_history ch LEFT JOIN status_master s1 ON ch.old_status_id=s1.status_id JOIN status_master s2 ON ch.new_status_id=s2.status_id JOIN users u ON ch.changed_by=u.user_id WHERE ch.complaint_id=? ORDER BY ch.changed_at ASC");
$hist->execute([$c['complaint_id']]);

echo json_encode([
    'complaint_code' => $c['complaint_code'],
    'title' => $c['title'],
    'status' => $c['status_name'],
    'priority' => $c['priority'],
    'category' => $c['category_name'],
    'complaint_date' => $c['complaint_date'],
    'history' => $hist->fetchAll()
]);
?>