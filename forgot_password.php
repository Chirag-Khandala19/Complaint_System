<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
require_once 'config/db.php';

$errorMessage = '';
$successMessage = '';
$currentStep = 'email'; // Possible values: email, reset, done

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'find_account') {
        $emailAddress = trim($_POST['email'] ?? '');

        if ($emailAddress === '') {
            $errorMessage = 'Please enter your email.';
        } else {
            $stmt = $pdo->prepare("SELECT user_id, username, full_name, email FROM users WHERE email = ?");
            $stmt->execute([$emailAddress]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $errorMessage = 'No account found with that email.';
            } else {
                $resetToken = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE user_id = ?");
                $stmt->execute([$resetToken, $expiresAt, $user['user_id']]);

                $_SESSION['reset_token'] = $resetToken;
                $_SESSION['reset_user_id'] = $user['user_id'];
                $currentStep = 'reset';
                $successMessage = 'Account found! Set your new password below.';
            }
        }
    } elseif ($action === 'reset_password') {
        $resetToken = $_SESSION['reset_token'] ?? '';
        $resetUserId = $_SESSION['reset_user_id'] ?? '';
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword === '' || $confirmPassword === '') {
            $errorMessage = 'Please fill in all fields.';
            $currentStep = 'reset';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'Passwords do not match.';
            $currentStep = 'reset';
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = 'Password must be at least 6 characters.';
            $currentStep = 'reset';
        } else {
            $stmt = $pdo->prepare(
                "SELECT user_id FROM users WHERE user_id = ? AND reset_token = ? AND reset_expires > NOW()"
            );
            $stmt->execute([$resetUserId, $resetToken]);

            if (!$stmt->fetch()) {
                $errorMessage = 'Reset session expired. Please start over.';
                $currentStep = 'email';
            } else {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?"
                );
                $stmt->execute([$passwordHash, $resetUserId]);

                unset($_SESSION['reset_token'], $_SESSION['reset_user_id']);
                $successMessage = 'Password reset successful! <a href="login.php">Login now</a>';
                $currentStep = 'done';
            }
        }
    }
}

if (isset($_SESSION['reset_token']) && $currentStep === 'email') {
    $currentStep = 'reset';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Complaint System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">🔑 Forgot Password</h3>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
                    <?php endif; ?>
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success"><?= $successMessage ?></div>
                    <?php endif; ?>

                    <?php if ($currentStep === 'email'): ?>
                    <p class="text-muted">Enter your registered email to reset your password.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="find_account">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">Find My Account</button>
                        <p class="text-center"><a href="login.php">Back to Login</a></p>
                    </form>

                    <?php elseif ($currentStep === 'reset'): ?>
                    <p class="text-muted">Enter your new password below.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="reset_password">
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100 mb-3">Reset Password</button>
                        <p class="text-center"><a href="login.php">Back to Login</a></p>
                    </form>

                    <?php elseif ($currentStep === 'done'): ?>
                    <div class="text-center">
                        <p class="fs-1">✅</p>
                        <a href="login.php" class="btn btn-primary">Go to Login</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>    <script src="assets/js/theme.js"></script></body>
</html>
