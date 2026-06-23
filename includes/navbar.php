<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="fas fa-comments me-2"></i>Complaint Tracker
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <?php if ($_SESSION['role_name'] === 'Complainant'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'register_complaint.php' ? 'active' : '' ?>" href="register_complaint.php">
                        <i class="fas fa-plus-circle me-1"></i>New Complaint
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'complaints.php' ? 'active' : '' ?>" href="complaints.php">
                        <i class="fas fa-list me-1"></i>Complaints
                    </a>
                </li>
                <?php if ($_SESSION['role_name'] === 'Supervisor'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-cogs me-1"></i>Manage
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="manage_categories.php">Categories</a></li>
                        <li><a class="dropdown-item" href="manage_areas.php">Areas</a></li>
                        <li><a class="dropdown-item" href="manage_users.php">Users</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'reports.php' ? 'active' : '' ?>" href="reports.php">
                        <i class="fas fa-chart-bar me-1"></i>Reports
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'track_complaint.php' ? 'active' : '' ?>" href="track_complaint.php">
                        <i class="fas fa-search me-1"></i>Track
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item me-2">
                    <button id="themeToggleBtn" class="btn btn-sm btn-outline-light theme-toggle-btn" type="button" title="Toggle dark/light theme">
                        <i class="fas fa-moon"></i>
                        <span class="d-none d-md-inline ms-1">Theme</span>
                    </button>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($_SESSION['full_name']) ?>
                        <span class="badge bg-light text-primary ms-1"><?= htmlspecialchars($_SESSION['role_name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script src="assets/js/theme.js"></script>