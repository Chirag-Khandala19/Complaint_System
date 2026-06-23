<?php
require_once 'config/db.php';
require_once 'config/auth.php';
requireRole(['Complainant']);

$user = getCurrentUser();
$success = $error = '';

// Fetch categories, zones
$categories = $conn->query("SELECT * FROM complaint_categories WHERE is_active = 1")->fetchAll();
$zones = $conn->query("SELECT * FROM zones WHERE is_active = 1")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $zone_id = intval($_POST['zone_id'] ?? 0);
    $sector_id = intval($_POST['sector_id'] ?? 0);
    $spot_id = intval($_POST['spot_id'] ?? 0);
    $exact_location = trim($_POST['exact_location'] ?? '');
    $priority = $_POST['priority'] ?? 'Medium';
    
    // Server-side validation
    $errors = [];
    if (empty($title) || strlen($title) < 10) $errors[] = 'Title must be at least 10 characters.';
    if (empty($description) || strlen($description) < 20) $errors[] = 'Description must be at least 20 characters.';
    if ($category_id <= 0) $errors[] = 'Please select a category.';
    if ($zone_id <= 0) $errors[] = 'Please select a zone.';
    if ($sector_id <= 0) $errors[] = 'Please select a sector.';
    if ($spot_id <= 0) $errors[] = 'Please select a spot.';
    if (!in_array($priority, ['Low','Medium','High','Critical'])) $errors[] = 'Invalid priority.';
    
    // Check repeated complaint (Special Rule: U is odd)
    $repeatedCheck = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE category_id = ? AND spot_id = ? AND complaint_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $repeatedCheck->execute([$category_id, $spot_id]);
    $isRepeated = $repeatedCheck->fetchColumn() > 0;
    
    if (empty($errors)) {
        // Generate complaint code
        $year = date('Y');
        $lastId = $conn->query("SELECT MAX(complaint_id) FROM complaints")->fetchColumn();
        $nextId = ($lastId ?? 0) + 1;
        $complaintCode = "CMP-{$year}-" . str_pad($nextId, 3, '0', STR_PAD_LEFT);
        
        $now = date('Y-m-d H:i:s');
        $initialDeadline = date('Y-m-d H:i:s', strtotime("+5 hours"));
        $resolutionDeadline = date('Y-m-d H:i:s', strtotime("+48 hours"));
        
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO complaints (complaint_code, title, description, category_id, zone_id, sector_id, spot_id, exact_location, priority, current_status_id, complainant_id, complaint_date, initial_response_deadline, resolution_deadline, is_repeated_flag) VALUES (?,?,?,?,?,?,?,?,?,1,?,NOW(),?,?,?)");
            $stmt->execute([$complaintCode, $title, $description, $category_id, $zone_id, $sector_id, $spot_id, $exact_location, $priority, $user['user_id'], $initialDeadline, $resolutionDeadline, $isRepeated ? 1 : 0]);
            $complaintId = $conn->lastInsertId();
            
            // Add history entry
            $stmt = $conn->prepare("INSERT INTO complaint_history (complaint_id, old_status_id, new_status_id, changed_by, remark) VALUES (?,NULL,1,?,'Complaint submitted')");
            $stmt->execute([$complaintId, $user['user_id']]);
            
            // Handle file upload
            if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg','jpeg','png','gif','pdf','doc','docx'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                $ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed) && $_FILES['proof']['size'] <= $maxSize) {
                    $newName = 'complaint_' . $complaintId . '_' . time() . '.' . $ext;
                    $uploadPath = 'uploads/complaints/' . $newName;
                    if (!is_dir('uploads/complaints')) mkdir('uploads/complaints', 0755, true);
                    move_uploaded_file($_FILES['proof']['tmp_name'], $uploadPath);
                    
                    $stmt = $conn->prepare("INSERT INTO complaint_attachments (complaint_id, file_name, file_path, file_type, file_size, upload_type, uploaded_by) VALUES (?,?,?,?,?,'complaint_proof',?)");
                    $stmt->execute([$complaintId, $_FILES['proof']['name'], $uploadPath, $ext, $_FILES['proof']['size'], $user['user_id']]);
                }
            }
            
            $conn->commit();
            $success = "Complaint registered successfully! Code: <strong>{$complaintCode}</strong>";
            if ($isRepeated) $success .= '<br><span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Flagged as repeated complaint (same area & category within 7 days).</span>';
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Error registering complaint: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Complaint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>
<div class="container py-4">
    <h4 class="fw-bold mb-4"><i class="fas fa-plus-circle me-2"></i>Register New Complaint</h4>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data" id="complaintForm">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-semibold">Complaint Title *</label>
                        <input type="text" class="form-control" name="title" id="title" minlength="10" required placeholder="Brief description of the issue">
                        <div class="invalid-feedback">Title must be at least 10 characters.</div>
                        <small class="text-muted">Category will be auto-detected from keywords in the title.</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Priority *</label>
                        <select class="form-select" name="priority" required>
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description *</label>
                    <textarea class="form-control" name="description" rows="4" minlength="20" required placeholder="Detailed description of the complaint"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Category *</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Zone *</label>
                        <select class="form-select" name="zone_id" id="zone_id" required>
                            <option value="">Select Zone</option>
                            <?php foreach ($zones as $z): ?>
                            <option value="<?= $z['zone_id'] ?>"><?= htmlspecialchars($z['zone_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Sector *</label>
                        <select class="form-select" name="sector_id" id="sector_id" required>
                            <option value="">Select Zone First</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Spot *</label>
                        <select class="form-select" name="spot_id" id="spot_id" required>
                            <option value="">Select Sector First</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Exact Location</label>
                        <input type="text" class="form-control" name="exact_location" placeholder="e.g. Near entrance, Room 101">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Proof (Image/Document)</label>
                        <input type="file" class="form-control" name="proof" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                        <small class="text-muted">Max 5MB. Allowed: jpg, png, gif, pdf, doc</small>
                    </div>
                </div>
                <div id="duplicateWarning" class="alert alert-warning d-none">
                    <i class="fas fa-exclamation-triangle me-2"></i>Similar complaint(s) found in this area. Please review before submitting.
                </div>
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-2"></i>Submit Complaint</button>
            </form>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
// Category detection from title keywords
function detectCategory(title) {
    var keywords = {
        1: ['wifi', 'wireless', 'signal', 'connectivity', 'internet', 'network'], // WiFi Connectivity
        2: ['lan', 'ethernet', 'cable', 'port', 'wired'], // LAN/Ethernet Issues
        3: ['speed', 'slow', 'latency', 'bandwidth', 'download', 'upload'], // Internet Speed
        4: ['server', 'downtime', 'crash', 'unavailable', 'maintenance'], // Server Downtime
        5: ['vpn', 'remote', 'access', 'connection'], // VPN/Remote Access
        6: ['security', 'unauthorized', 'firewall', 'suspicious', 'hack'], // Network Security
        7: ['dns', 'ip', 'resolution', 'conflict', 'address'], // DNS/IP Issues
        8: ['router', 'switch', 'modem', 'hardware', 'failure', 'broken'] // Hardware Failure
    };

    title = title.toLowerCase();
    for (var catId in keywords) {
        for (var keyword of keywords[catId]) {
            if (title.includes(keyword)) {
                return catId;
            }
        }
    }
    return null; // No match
}

// Auto-detect category on title input
$('#title').on('input', function() {
    var title = $(this).val();
    var detectedCatId = detectCategory(title);
    if (detectedCatId) {
        $('select[name="category_id"]').val(detectedCatId);
    }
});

// Zone → Sector
$('#zone_id').change(function() {
    var zoneId = $(this).val();
    $('#sector_id').html('<option value="">Loading...</option>');
    $('#spot_id').html('<option value="">Select Sector First</option>');

    if (zoneId) {
        $.getJSON('api/get_sectors.php', {zone_id: zoneId}, function(data) {
            var opts = '<option value="">Select Sector</option>';
            data.forEach(function(s) {
                opts += '<option value="'+s.sector_id+'">'+s.sector_name+'</option>';
            });
            $('#sector_id').html(opts);
        });
    }
});

// Sector → Spot
$('#sector_id').change(function() {
    var sectorId = $(this).val();
    $('#spot_id').html('<option value="">Loading...</option>');

    if (sectorId) {
        $.getJSON('api/get_spots.php', {sector_id: sectorId}, function(data) {
            var opts = '<option value="">Select Spot</option>';
            data.forEach(function(s) {
                opts += '<option value="'+s.spot_id+'">'+s.spot_name+'</option>';
            });
            $('#spot_id').html(opts);
        });
    }
});

// AJAX duplicate check
$('#title').on('blur', function() {
    var title = $(this).val();
    var spotId = $('#spot_id').val();
    if (title.length > 5 && spotId) {
        $.getJSON('api/check_duplicate.php', {title: title, spot_id: spotId}, function(data) {
            if (data.found) { $('#duplicateWarning').removeClass('d-none'); }
            else { $('#duplicateWarning').addClass('d-none'); }
        });
    }
});

// Client-side validation
document.getElementById('complaintForm').addEventListener('submit', function(e) {
    var title = document.getElementById('title').value.trim();
    if (title.length < 10) { e.preventDefault(); alert('Title must be at least 10 characters.'); }
});
</script>
</body></html>