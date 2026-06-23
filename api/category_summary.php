<?php
// API: Category-wise complaint summary
require_once '../config/db.php';
header('Content-Type: application/json');

$stmt = $conn->query("SELECT cc.category_name, COUNT(c.complaint_id) as total, SUM(CASE WHEN c.current_status_id IN (5,6) THEN 1 ELSE 0 END) as resolved, SUM(CASE WHEN c.current_status_id NOT IN (5,6) THEN 1 ELSE 0 END) as pending FROM complaints c JOIN complaint_categories cc ON c.category_id=cc.category_id GROUP BY cc.category_id");
echo json_encode(['status'=>'success', 'data'=>$stmt->fetchAll()]);
?>