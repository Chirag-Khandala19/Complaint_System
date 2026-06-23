<?php
require_once 'config/db.php';
session_start();

$errorMessage = '';
$rememberedUser = $_COOKIE['preferred_user'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredUsername = trim($_POST['username'] ?? '');
    $enteredPassword = trim($_POST['password'] ?? '');

    if ($enteredUsername === '' || $enteredPassword === '') {
        $errorMessage = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare(
            "SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id " .
            "WHERE u.username = ? AND u.is_active = 1"
        );
        $stmt->execute([$enteredUsername]);
        $user = $stmt->fetch();

        if ($user) {
            $isPasswordValid = false;

            if (password_verify($enteredPassword, $user['password'])) {
                $isPasswordValid = true;
            } elseif ($user['password'] === md5($enteredPassword)) {
                $isPasswordValid = true;

                $newHash = password_hash($enteredPassword, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $updateStmt->execute([$newHash, $user['user_id']]);
            }

            if ($isPasswordValid) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];

                setcookie('last_login', date('Y-m-d H:i:s'), time() + 86400 * 30, '/');
                setcookie('preferred_user', $enteredUsername, time() + 86400 * 30, '/');

                header('Location: dashboard.php');
                exit();
            }
        }

        $errorMessage = 'Invalid username or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Complaint Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-comments fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold">Personalized Area-Based Complaint Tracker</h3>
                        <p class="text-muted">A unique complaint and resolution platform</p>
                    </div>
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($errorMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <form method="POST" id="loginForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="username" id="username" value="<?= htmlspecialchars($_POST['username'] ?? $rememberedUser) ?>" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="password" id="password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="register.php" class="text-decoration-none me-3">Create new account</a>
                        <a href="forgot_password.php" class="text-decoration-none">Forgot password?</a>
                    </div>
                    <hr>
                    <div class="text-center">
                        <small class="text-muted">Demo Credentials:</small><br>
                        <small><strong>Supervisor:</strong> admin / admin123</small><br>
                        <small><strong>Staff:</strong> staff1 / staff123</small><br>
                        <small><strong>User:</strong> user1 / user123</small>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <small class="text-muted">Enrollment: 230210107029 | Domain: Network & Connectivity</small>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/theme.js"></script>
<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    var u = document.getElementById('username').value.trim();
    var p = document.getElementById('password').value.trim();
    if (!u || !p) { e.preventDefault(); alert('Please fill in all fields.'); }
});
</script>
</body>
</html>