<?php
session_start();
require_once 'config/db.php';

$formError = '';
$formSuccess = '';
$fullName = '';
$emailAddress = '';
$phoneNumber = '';
$userName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['name'] ?? '');
    $emailAddress = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phone'] ?? '');
    $userName = trim($_POST['register_username'] ?? '');
    $password = $_POST['register_password'] ?? '';
    $confirmPassword = $_POST['register_confirm_password'] ?? '';

    if ($fullName === '' || $emailAddress === '' || $userName === '' || $password === '') {
        $formError = 'All fields marked * are required.';
    } elseif ($password !== $confirmPassword) {
        $formError = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $formError = 'Password must be at least 6 characters.';
    } else {
        // Verify username is unique
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$userName]);

        if ($stmt->fetch()) {
            $formError = 'Username already taken.';
        } else {
            // Verify email is unique
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$emailAddress]);

            if ($stmt->fetch()) {
                $formError = 'Email already registered.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (full_name, email, phone, username, password, role_id) VALUES (?, ?, ?, ?, ?, 3)"
                );
                $stmt->execute([$fullName, $emailAddress, $phoneNumber, $userName, $passwordHash]);
                $formSuccess = 'Registration successful! You can now <a href="login.php">login</a>.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Complaint System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4"><i class="fas fa-user-plus text-primary me-2"></i> Create Account</h3>
                    <p class="text-center text-muted mb-4">Personalized Area-Based Complaint Tracker</p>

                    <?php if ($formError): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($formError) ?></div>
                    <?php endif; ?>
                    <?php if ($formSuccess): ?>
                        <div class="alert alert-success"><?= $formSuccess ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="off">
                        <input type="text" name="fakeusernameremembered" id="fakeusernameremembered" style="display:none">
                        <input type="password" name="fakepasswordremembered" id="fakepasswordremembered" style="display:none">
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required autocomplete="name"
                                   value="<?= htmlspecialchars($fullName) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required autocomplete="email"
                                   value="<?= htmlspecialchars($emailAddress) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" autocomplete="tel"
                                   value="<?= htmlspecialchars($phoneNumber) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" name="register_username" class="form-control" required autocomplete="new-username"
                                   value="<?= htmlspecialchars($userName) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" name="register_password" class="form-control" required autocomplete="new-password" minlength="6">
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password *</label>
                            <input type="password" name="register_confirm_password" class="form-control" required autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-success w-100 mb-3">Register</button>
                        <p class="text-center mb-0">Already have an account? <a href="login.php">Login here</a></p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>    <script src="assets/js/theme.js"></script></body>
</html>
