<?php
require_once 'config/db.php';
require_once 'config/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Complaint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>
<div class="container py-4">
    <h4 class="fw-bold mb-4"><i class="fas fa-search me-2"></i>Track Complaint (Live Search)</h4>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="input-group">
                <input type="text" class="form-control form-control-lg" id="trackCode" placeholder="Enter Complaint Code (e.g., CMP-2025-001)">
                <button class="btn btn-primary" id="trackBtn"><i class="fas fa-search me-2"></i>Track</button>
            </div>
        </div>
    </div>
    <div id="trackResult"></div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$('#trackBtn').click(function() {
    var code = $('#trackCode').val().trim();
    if (!code) { alert('Enter a complaint code.'); return; }
    $('#trackResult').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
    $.getJSON('api/track_complaint.php', {code: code}, function(data) {
        if (data.error) {
            $('#trackResult').html('<div class="alert alert-danger">'+data.error+'</div>');
        } else {
            var html = '<div class="card border-0 shadow-sm"><div class="card-header bg-white"><h5>'+data.complaint_code+' - '+data.title+'</h5></div><div class="card-body">';
            html += '<div class="row mb-3"><div class="col-md-3"><strong>Status:</strong> <span class="badge bg-primary">'+data.status+'</span></div>';
            html += '<div class="col-md-3"><strong>Priority:</strong> '+data.priority+'</div>';
            html += '<div class="col-md-3"><strong>Category:</strong> '+data.category+'</div>';
            html += '<div class="col-md-3"><strong>Filed:</strong> '+data.complaint_date+'</div></div>';
            html += '<h6 class="fw-bold mt-3">Timeline</h6>';
            data.history.forEach(function(h) {
                html += '<div class="ps-3 mb-2 border-start border-3 border-primary">';
                html += '<strong>'+(h.old_status ? h.old_status+' → ':'')+h.new_status+'</strong><br>';
                html += '<small class="text-muted">'+h.changed_at+' by '+h.changed_by+'</small>';
                if (h.remark) html += '<br><small>'+h.remark+'</small>';
                html += '</div>';
            });
            html += '</div></div>';
            $('#trackResult').html(html);
        }
    });
});
$('#trackCode').keypress(function(e) { if (e.which === 13) $('#trackBtn').click(); });
</script>
</body></html>