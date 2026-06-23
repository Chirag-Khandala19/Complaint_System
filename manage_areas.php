<?php
require_once 'config/db.php';
require_once 'config/auth.php';
requireRole(['Supervisor']);

// echo "<pre>";
// print_r($_GET);
// echo "</pre>";

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_zone') {
        $conn->prepare("INSERT INTO zones (zone_name) VALUES (?)")->execute([trim($_POST['name'])]);
        $success = 'Zone added.';
    } elseif ($action === 'add_sector') {
        $conn->prepare("INSERT INTO sectors (sector_name, zone_id) VALUES (?,?)")->execute([trim($_POST['name']), intval($_POST['zone_id'])]);
        $success = 'Sector added.';
    } elseif ($action === 'add_spot') {
        $conn->prepare("INSERT INTO spots (spot_name, sector_id) VALUES (?,?)")->execute([trim($_POST['name']), intval($_POST['sector_id'])]);
        $success = 'Spot added.';
    }
}

if (isset($_GET['success'])) {
    $type = $_GET['success'];
    if ($type === 'zone_deleted') $success = 'Zone deleted successfully!';
    if ($type === 'sector_deleted') $success = 'Sector deleted successfully!';
    if ($type === 'spot_deleted') $success = 'Spot deleted successfully!';
}

// DELETE LOGIC
if (isset($_GET['delete_zone'])) {
    $zone_id = intval($_GET['delete_zone']);

    // 1. Get sectors
    $stmt = $conn->prepare("SELECT sector_id FROM sectors WHERE zone_id=?");
    $stmt->execute([$zone_id]);
    $sectors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($sectors as $sector) {
        $sector_id = $sector['sector_id'];

        // 2. Get spots
        $stmt2 = $conn->prepare("SELECT spot_id FROM spots WHERE sector_id=?");
        $stmt2->execute([$sector_id]);
        $spots = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        foreach ($spots as $spot) {
            $spot_id = $spot['spot_id'];

            // 3. Get complaints
            $stmt3 = $conn->prepare("SELECT complaint_id FROM complaints WHERE spot_id=?");
            $stmt3->execute([$spot_id]);
            $complaints = $stmt3->fetchAll(PDO::FETCH_ASSOC);

            foreach ($complaints as $comp) {
                $complaint_id = $comp['complaint_id'];

                // 🔴 DELETE complaint_history FIRST
                $conn->prepare("DELETE FROM complaint_history WHERE complaint_id=?")
                     ->execute([$complaint_id]);
            }

            // 4. Delete complaints
            $conn->prepare("DELETE FROM complaints WHERE spot_id=?")->execute([$spot_id]);
        }

        // 5. Delete spots
        $conn->prepare("DELETE FROM spots WHERE sector_id=?")->execute([$sector_id]);
    }

    // 6. Delete sectors
    $conn->prepare("DELETE FROM sectors WHERE zone_id=?")->execute([$zone_id]);

    // 7. Delete zone
    $conn->prepare("DELETE FROM zones WHERE zone_id=?")->execute([$zone_id]);

    header("Location: manage_areas.php?success=zone_deleted");
    exit;
}

if (isset($_GET['delete_sector'])) {
    $id = intval($_GET['delete_sector']);

    // STEP 1: delete complaints
    $conn->prepare("
        DELETE FROM complaints 
        WHERE spot_id IN (
            SELECT spot_id FROM spots WHERE sector_id = ?
        )
    ")->execute([$id]);

    // STEP 2: delete spots
    $conn->prepare("DELETE FROM spots WHERE sector_id=?")->execute([$id]);

    // STEP 3: delete sector
    $conn->prepare("DELETE FROM sectors WHERE sector_id=?")->execute([$id]);

    header("Location: manage_areas.php?success=sector_deleted");
    exit;
}

if (isset($_GET['delete_spot'])) {
    $id = intval($_GET['delete_spot']);

    // get complaints
    $stmt = $conn->prepare("SELECT complaint_id FROM complaints WHERE spot_id=?");
    $stmt->execute([$id]);
    $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($complaints as $comp) {
        $conn->prepare("DELETE FROM complaint_history WHERE complaint_id=?")
             ->execute([$comp['complaint_id']]);
    }

    // delete complaints
    $conn->prepare("DELETE FROM complaints WHERE spot_id=?")->execute([$id]);

    // delete spot
    $conn->prepare("DELETE FROM spots WHERE spot_id=?")->execute([$id]);

    header("Location: manage_areas.php?success=spot_deleted");
    exit;
}

$zones = $conn->query("SELECT * FROM zones")->fetchAll();
$sectors = $conn->query("SELECT s.*, z.zone_name FROM sectors s JOIN zones z ON s.zone_id=z.zone_id")->fetchAll();
$spots = $conn->query("SELECT sp.*, s.sector_name, z.zone_name FROM spots sp JOIN sectors s ON sp.sector_id=s.sector_id JOIN zones z ON s.zone_id=z.zone_id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Areas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .area-card {
            border-radius: 12px;
            border: 1px solid #e8ecf1;
            transition: all 0.3s ease;
            height: 100%;
        }
        .area-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-color: #d0dce6;
        }
        .area-card .card-header {
            background: white;
            color: #333;
            border-radius: 12px 12px 0 0;
            padding: 16px;
            border: none;
        }
        .area-card .card-header h6 {
            font-size: 16px;
            letter-spacing: 0.5px;
            font-weight: bold;
        }
        .area-card .card-header i {
            margin-right: 8px;
            font-size: 18px;
            color: #667eea;
        }
        .area-card .card-body {
            padding: 20px;
        }
        .area-form {
            background: #f8fafb;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .area-form .form-control,
        .area-form .form-select {
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 13px;
            padding: 8px 12px;
        }
        .area-form .form-control:focus,
        .area-form .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .area-form .btn-primary {
            border-radius: 6px;
            background: #667eea;
            border: none;
            font-weight: 500;
            font-size: 13px;
            padding: 8px 16px;
            transition: all 0.2s ease;
        }
        .area-form .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .area-list {
            max-height: 380px;
            overflow-y: auto;
        }
        .area-list::-webkit-scrollbar {
            width: 6px;
        }
        .area-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .area-list::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 10px;
        }
        .area-list::-webkit-scrollbar-thumb:hover {
            background: #999;
        }
        .area-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 10px;
            background: white;
            transition: all 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .area-item:hover {
            background: #f8fafb;
            border-color: #ddd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .area-item-text {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .area-item-text .text-main {
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        .area-item-text .text-sub {
            font-size: 12px;
            color: #999;
        }
        .area-item-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .area-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .area-badge.bg-success {
            background: #28a745 !important;
            color: white;
        }
        .area-badge.bg-secondary {
            background: #6c757d !important;
            color: white;
        }
        .btn-delete {
            border-radius: 6px;
            border: 1px solid #fee;
            background: #fff5f5;
            color: #dc3545;
            font-size: 12px;
            padding: 6px 10px;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-delete:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
            text-decoration: none;
        }
    </style>
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>
<div class="container py-4">
    <h4 class="fw-bold mb-4">Manage Area Hierarchy (Zone → Sector → Spot)</h4>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <div class="row">
        <div class="col-md-4">
            <div class="card area-card">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-map-pin"></i>Zones</h6></div>
                <div class="card-body">
                    <form method="POST" class="area-form">
                        <input type="hidden" name="action" value="add_zone">
                        <div class="input-group">
                            <input type="text" class="form-control" name="name" placeholder="Zone Name" required>
                            <button class="btn btn-primary" type="submit">Add</button>
                        </div>
                    </form>
                    <div class="area-list"><?php foreach ($zones as $z): ?>
                        <div class="area-item">
                            <div class="area-item-text">
                                <div class="text-main"><?= htmlspecialchars($z['zone_name']) ?></div>
                            </div>
                            <div class="area-item-actions">
                                <span class="area-badge bg-<?= $z['is_active']?'success':'secondary' ?>">
                                    <?= $z['is_active']?'Active':'Off' ?>
                                </span>
                                <a href="?delete_zone=<?= $z['zone_id'] ?>" 
                                class="btn-delete"
                                onclick="return confirm('Delete this Zone?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card area-card">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-layer-group"></i>Sectors</h6></div>
                <div class="card-body">
                    <form method="POST" class="area-form">
                        <input type="hidden" name="action" value="add_sector">
                        <select class="form-select mb-2" name="zone_id" required>
                            <option value="">Select Zone</option>
                            <?php foreach ($zones as $z): ?><option value="<?= $z['zone_id'] ?>"><?= htmlspecialchars($z['zone_name']) ?></option><?php endforeach; ?>
                        </select>
                        <div class="input-group">
                            <input type="text" class="form-control" name="name" placeholder="Sector Name" required>
                            <button class="btn btn-primary" type="submit">Add</button>
                        </div>
                    </form>
                    <div class="area-list"><?php foreach ($sectors as $s): ?>
                        <div class="area-item">
                            <div class="area-item-text">
                                <div class="text-sub"><?= htmlspecialchars($s['zone_name']) ?></div>
                                <div class="text-main"><?= htmlspecialchars($s['sector_name']) ?></div>
                            </div>
                            <div class="area-item-actions">
                                <a href="?delete_sector=<?= $s['sector_id'] ?>" 
                                    class="btn-delete"
                                    onclick="return confirm('Delete this Sector?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card area-card">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-crosshairs"></i>Spots</h6></div>
                <div class="card-body">
                    <form method="POST" class="area-form">
                        <input type="hidden" name="action" value="add_spot">
                        <select class="form-select mb-2" name="sector_id" required>
                            <option value="">Select Sector</option>
                            <?php foreach ($sectors as $s): ?><option value="<?= $s['sector_id'] ?>"><?= htmlspecialchars($s['zone_name']) ?> → <?= htmlspecialchars($s['sector_name']) ?></option><?php endforeach; ?>
                        </select>
                        <div class="input-group">
                            <input type="text" class="form-control" name="name" placeholder="Spot Name" required>
                            <button class="btn btn-primary" type="submit">Add</button>
                        </div>
                    </form>
                    <div class="area-list"><?php foreach ($spots as $sp): ?>
                        <div class="area-item">
                            <div class="area-item-text">
                                <div class="text-sub"><?= htmlspecialchars($sp['zone_name']) ?> → <?= htmlspecialchars($sp['sector_name']) ?></div>
                                <div class="text-main"><?= htmlspecialchars($sp['spot_name']) ?></div>
                            </div>
                            <div class="area-item-actions">
                                <a href="?delete_spot=<?= $sp['spot_id'] ?>" 
                                    class="btn-delete"
                                    onclick="return confirm('Delete this Spot?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>