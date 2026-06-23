<?php require_once 'config/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center">
        <h1 class="display-1 text-danger">403</h1>
        <h3>Access Denied</h3>
        <p class="text-muted">You don't have permission to access this page.</p>
        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
    </div>
</body></html>