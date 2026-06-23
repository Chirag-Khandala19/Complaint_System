<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$title = trim($_GET['title'] ?? '');
$spotId = intval($_GET['spot_id'] ?? 0);
$stmt = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE spot_id = ? AND title LIKE ? AND complaint_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute([$spotId, "%$title%"]);
echo json_encode(['found' => $stmt->fetchColumn() > 0]);
?>