<?php
require_once '../config/db.php';

if (isset($_GET['sector_id'])) {
    $sector_id = intval($_GET['sector_id']);

    $stmt = $conn->prepare("SELECT spot_id, spot_name FROM spots WHERE sector_id=?");
    $stmt->execute([$sector_id]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>