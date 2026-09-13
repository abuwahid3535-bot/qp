<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$principalImg = __DIR__ . '/' . PRINCIPAL_PHOTO;
$corrImg      = __DIR__ . '/' . CORRESPONDENT_PHOTO;
$collegeImg   = __DIR__ . '/' . COLLEGE_IMAGE;
?>

<div class="hero mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <span class="badge rounded-pill text-bg-light mb-3"><?= e(APP_NAME); ?></span>
            <h1 class="display-5 fw-bold mb-3"><?= e(COLLEGE_NAME); ?></h1>
            <p class="fs-5 mb-4">
                Access previous year question papers for every course, specialisation and subject.
                <strong>Staff members</strong> upload papers, and <strong>students</strong> download
                them anytime with just a few clicks.
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="register.php" class="btn btn-light btn-lg fw-semibold">
                    <i class="bi bi-person-plus"></i> Create an Account
                </a>
                <a href="login.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
            </div>
        </div>
        <div class="col-lg-5 text-center d-none d-lg-block">
            <?php if (is_file($collegeImg)): ?>
                <img src="<?= COLLEGE_IMAGE; ?>" alt="<?= e(COLLEGE_NAME); ?>"
                     class="img-fluid rounded-4 shadow-lg" style="max-height:280px;object-fit:cover;">
            <?php else: ?>
                <i class="bi bi-file-earmark-pdf-fill" style="font-size:9rem;opacity:.9;"></i>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="text-center mb-4">
    <h2 class="fw-bold">Two roles, two experiences</h2>
    <p class="text-muted">Choose the account type that matches who you are.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card role-card h-100">
            <div class="card-body p-4">
                <div class="role-icon student mb-3"><i class="bi bi-mortarboard"></i></div>
                <h3 class="h4 fw-bold">Student</h3>
                <p class="text-muted">Practise and prepare for examinations with papers from previous academic years.</p>
                <ul class="list-unstyled mb-4">
                    <li><i class="bi bi-check-circle-fill text-success"></i> Pick your course &amp; subject</li>
                    <li><i class="bi bi-check-circle-fill text-success"></i> Browse papers across academic years</li>
                    <li><i class="bi bi-check-circle-fill text-success"></i> Download question papers as PDF</li>
                </ul>
                <div class="d-grid gap-2">
                    <a href="login.php?role=student" class="btn btn-app-primary">Student Login</a>
                    <a href="register.php?role=student" class="btn btn-outline-secondary">Student Registration</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card role-card h-100">
            <div class="card-body p-4">
                <div class="role-icon staff mb-3"><i class="bi bi-person-badge"></i></div>
                <h3 class="h4 fw-bold">Staff</h3>
                <p class="text-muted">Build a complete question paper archive by uploading papers for every course and year.</p>
                <ul class="list-unstyled mb-4">
                    <li><i class="bi bi-check-circle-fill text-warning"></i> Select course, academic year, sub-course &amp; subject</li>
                    <li><i class="bi bi-check-circle-fill text-warning"></i> Upload &amp; organise question papers</li>
                    <li><i class="bi bi-check-circle-fill text-warning"></i> View, search and manage uploaded papers</li>
                </ul>
                <div class="d-grid gap-2">
                    <a href="login.php?role=staff" class="btn btn-app-primary">Staff Login</a>
                    <a href="register.php?role=staff" class="btn btn-outline-secondary">Staff Registration</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 justify-content-center mb-4">
    <div class="col-md-6 col-lg-5">
        <div class="card leadership-card h-100">
            <div class="card-body text-center p-4">
                <span class="badge text-bg-primary mb-2">Principal</span>
                <div class="mx-auto mb-3">
                    <?php if (is_file($principalImg)): ?>
                        <img src="<?= PRINCIPAL_PHOTO; ?>" alt="Principal <?= e(PRINCIPAL_NAME); ?>" class="leadership-photo">
                    <?php else: ?>
                        <i class="bi bi-person-circle leadership-icon"></i>
                    <?php endif; ?>
                </div>
                <h3 class="h5 fw-bold mb-0"><?= e(PRINCIPAL_NAME); ?></h3>
                <p class="text-muted mb-0">Principal</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-5">
        <div class="card leadership-card h-100">
            <div class="card-body text-center p-4">
                <span class="badge text-bg-primary mb-2">Correspondent</span>
                <div class="mx-auto mb-3">
                    <?php if (is_file($corrImg)): ?>
                        <img src="<?= CORRESPONDENT_PHOTO; ?>" alt="Correspondent <?= e(CORRESPONDENT_NAME); ?>" class="leadership-photo">
                    <?php else: ?>
                        <i class="bi bi-person-circle leadership-icon"></i>
                    <?php endif; ?>
                </div>
                <h3 class="h5 fw-bold mb-0"><?= e(CORRESPONDENT_NAME); ?></h3>
                <p class="text-muted mb-0">Correspondent</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-4 text-center">
        <h4 class="fw-bold"><i class="bi bi-shield-check text-success"></i> Verified accounts</h4>
        <p class="text-muted mb-0">
            Every account is activated only after the email address and mobile number are verified with a one-time password (OTP).
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>