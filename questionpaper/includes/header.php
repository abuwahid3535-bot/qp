<?php
if (!function_exists('e')) {
    require_once __DIR__ . '/../includes/functions.php';
}

$__user = current_user();
$__isLoggedIn = is_logged_in();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle . ' - ' . APP_NAME) : e(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= \requestBase() ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark app-navbar shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= \requestBase() ?>index.php">
            <i class="bi bi-journal-text me-1"></i><?= e(APP_NAME); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($__isLoggedIn): ?>
                    <?php if ($__user['role'] === 'staff'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= \requestBase() ?>staff/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \requestBase() ?>staff/upload.php"><i class="bi bi-cloud-arrow-up"></i> Upload Paper</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \requestBase() ?>staff/manage.php"><i class="bi bi-collection"></i> My Papers</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= \requestBase() ?>student/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= \requestBase() ?>student/dashboard.php#browse"><i class="bi bi-search"></i> Browse Papers</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= \requestBase() ?>index.php">Home</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($__isLoggedIn): ?>
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                            <i class="bi bi-person-circle"></i> <?= e($__user['name']); ?>
                            <span class="badge <?= $__user['role'] === 'staff' ? 'text-bg-warning' : 'text-bg-info'; ?> ms-1"><?= e(role_label($__user['role'])); ?></span>
                        </span>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?= \requestBase() ?>profile.php"><i class="bi bi-gear"></i> Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= \requestBase() ?>logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= \requestBase() ?>login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= \requestBase() ?>register.php"><i class="bi bi-person-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container flex-fill my-4">
    <?php foreach (['success', 'danger', 'warning', 'info'] as $__k): ?>
        <?php $__msg = get_flash($__k); if ($__msg): ?>
            <div class="alert alert-<?= $__k; ?> alert-dismissible fade show" role="alert">
                <?= $__msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>