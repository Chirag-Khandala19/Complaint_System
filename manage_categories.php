<?php
require_once 'config/db.php';
require_once 'config/auth.php';
requireRole(['Supervisor']);

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        if ($name) {
            $conn->prepare("INSERT INTO complaint_categories (category_name, description) VALUES (?,?)")->execute([$name, $desc]);
            $success = 'Category added.';
        }
    } elseif ($action === 'toggle') {
        $id = intval($_POST['id']);
        $conn->prepare("UPDATE complaint_categories SET is_active = NOT is_active WHERE category_id = ?")->execute([$id]);
        $success = 'Status updated.';
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $conn->prepare("UPDATE complaint_categories SET category_name=?, description=? WHERE category_id=?")->execute([$name, $desc, $id]);
        $success = 'Category updated.';
    }
}
$categories = $conn->query("SELECT * FROM complaint_categories ORDER BY category_id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>
<div class="container py-4">
    <h4 class="fw-bold mb-4">Manage Complaint Categories</h4>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><h6 class="fw-bold mb-0">Add Category</h6></div>
        <div class="card-body">
            <form method="POST" class="row g-2">
                <input type="hidden" name="action" value="add">
                <div class="col-md-4"><input type="text" class="form-control" name="name" placeholder="Category Name" required></div>
                <div class="col-md-5"><input type="text" class="form-control" name="description" placeholder="Description"></div>
                <div class="col-md-3"><button class="btn btn-primary w-100">Add</button></div>
            </form>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>#</th><th>Category</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                <tr>
                    <td><?= $c['category_id'] ?></td><td><?= htmlspecialchars($c['category_name']) ?></td><td><?= htmlspecialchars($c['description']) ?></td>
                    <td><span class="badge bg-<?= $c['is_active'] ? 'success' : 'secondary' ?>"><?= $c['is_active'] ? 'Active' : 'Disabled' ?></span></td>
                    <td>
                        <form method="POST" class="d-inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $c['category_id'] ?>">
                        <button class="btn btn-sm btn-outline-<?= $c['is_active'] ? 'warning' : 'success' ?>"><?= $c['is_active'] ? 'Disable' : 'Enable' ?></button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>