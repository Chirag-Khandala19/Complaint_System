<?php
require_once 'config/db.php';
require_once 'config/auth.php';
requireRole(['Supervisor']);

$statusMessage = '';
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $newUsername = trim($_POST['username'] ?? '');
    $newPassword = trim($_POST['password'] ?? '');
    $newFullName = trim($_POST['full_name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newPhone = trim($_POST['phone'] ?? '');
    $newRoleId = intval($_POST['role_id'] ?? 0);

    if ($newUsername !== '' && $newPassword !== '' && $newFullName !== '' && $newEmail !== '' && $newRoleId > 0) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO users (username, password, full_name, email, phone, role_id) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$newUsername, $hashedPassword, $newFullName, $newEmail, $newPhone, $newRoleId]);
        $statusMessage = 'User added.';
    } else {
        $statusMessage = 'Please complete all required fields.';
    }
}

$users = $conn->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id=r.role_id ORDER BY u.user_id")->fetchAll();
$roles = $conn->query("SELECT * FROM roles")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>
<div class="container py-4">
    <h4 class="fw-bold mb-4">Manage Users</h4>
    <?php if ($statusMessage): ?><div class="alert alert-success"><?= htmlspecialchars($statusMessage) ?></div><?php endif; ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><h6 class="fw-bold mb-0">Add User</h6></div>
        <div class="card-body">
            <form method="POST" class="row g-2">
                <input type="hidden" name="action" value="add">
                <div class="col-md-2"><input type="text" class="form-control" name="username" placeholder="Username" required></div>
                <div class="col-md-2"><input type="password" class="form-control" name="password" placeholder="Password" required></div>
                <div class="col-md-2"><input type="text" class="form-control" name="full_name" placeholder="Full Name" required></div>
                <div class="col-md-2"><input type="email" class="form-control" name="email" placeholder="Email" required></div>
                <div class="col-md-1"><input type="text" class="form-control" name="phone" placeholder="Phone"></div>
                <div class="col-md-2"><select class="form-select" name="role_id" required><?php foreach($roles as $r): ?><option value="<?=$r['role_id']?>"><?=$r['role_name']?></option><?php endforeach; ?></select></div>
                <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
            </form>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>#</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach($users as $u): ?>
                <tr><td><?=$u['user_id']?></td><td><?=htmlspecialchars($u['username'])?></td><td><?=htmlspecialchars($u['full_name'])?></td><td><?=$u['email']?></td><td><span class="badge bg-primary"><?=$u['role_name']?></span></td><td><span class="badge bg-<?=$u['is_active']?'success':'secondary'?>"><?=$u['is_active']?'Active':'Inactive'?></span></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>