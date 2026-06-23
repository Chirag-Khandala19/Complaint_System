<?php
require_once '../config/db.php';

if (isset($_GET['zone_id'])) {
    $zone_id = intval($_GET['zone_id']);

    $stmt = $conn->prepare("SELECT sector_id, sector_name FROM sectors WHERE zone_id=?");
    $stmt->execute([$zone_id]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>