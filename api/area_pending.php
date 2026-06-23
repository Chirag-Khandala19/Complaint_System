<?php
// API: Area-wise pending complaints
require_once '../config/db.php';
header('Content-Type: application/json');

$stmt = $conn->query("SELECT z.zone_name, sec.sector_name, COUNT(c.complaint_id) as pending_count FROM complaints c JOIN zones z ON c.zone_id=z.zone_id JOIN sectors sec ON c.sector_id=sec.sector_id WHERE c.current_status_id IN (1,2,3,4) GROUP BY z.zone_id, sec.sector_id ORDER BY pending_count DESC");
echo json_encode(['status'=>'success', 'data'=>$stmt->fetchAll()]);
?>