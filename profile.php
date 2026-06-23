<?php
require_once 'config/db.php';
require_once 'config/auth.php';
requireLogin();
$user = getCurrentUser();
$stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id=r.role_id WHERE u.user_id=?");
$stmt->execute([$user['user_id']]);
$profile = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-5">
                    <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                    <h4><?= htmlspecialchars($profile['full_name']) ?></h4>
                    <span class="badge bg-primary fs-6 mb-3"><?= $profile['role_name'] ?></span>
                    <hr>
                    <p><strong>Username:</strong> <?= $profile['username'] ?></p>
                    <p><strong>Email:</strong> <?= $profile['email'] ?></p>
                    <p><strong>Phone:</strong> <?= $profile['phone'] ?: 'N/A' ?></p>
                    <p><strong>Joined:</strong> <?= date('d M Y', strtotime($profile['created_at'])) ?></p>
                    <?php if (isset($_COOKIE['last_login'])): ?>
                    <p class="text-muted"><small>Last login: <?= $_COOKIE['last_login'] ?></small></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>