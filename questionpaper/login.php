<?php
$pageTitle = 'Login';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('danger', 'Session expired, please try again.');
        redirect('login.php');
    }

    $loginRole = ($_POST['role'] ?? '') === 'staff' ? 'staff' : 'student';
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $password  = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[$loginRole] = 'Please enter both email and password.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1');
        $st->execute([$email, $loginRole]);
        $user = $st->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[$loginRole] = 'Invalid email or password for the selected role.';
        } elseif ($user['status'] === 'blocked') {
            $errors[$loginRole] = 'This account has been blocked. Contact the administrator.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            if ((int)$user['email_verified'] === 1 && (int)$user['mobile_verified'] === 1) {
                flash('success', 'Welcome back, ' . $user['name'] . '!');
                redirect('dashboard.php');
            }
            flash('info', 'Please finish verifying your email and mobile to continue.');
            redirect('verify.php');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="text-center mb-4">
    <h1 class="fw-bold">Login</h1>
    <p class="text-muted">Choose the account type that matches your role.</p>
</div>

<div class="row g-4 justify-content-center">

    <div class="col-lg-5">
        <div class="card role-card h-100">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <div class="role-icon student mb-2"><i class="bi bi-mortarboard"></i></div>
                    <h2 class="h4 fw-bold mb-1">Student Login</h2>
                    <span class="badge role-badge-student">Download access</span>
                </div>
                <p class="text-muted small text-center mb-3">
                    As a student you can choose your course &amp; subject and download
                    previous year question papers.
                </p>
                <ul class="list-unstyled small mb-4">
                    <li><i class="bi bi-check-circle-fill text-success"></i> Browse papers by course, sub-course &amp; subject</li>
                    <li><i class="bi bi-check-circle-fill text-success"></i> Download question papers (PDF)</li>
                    <li><i class="bi bi-check-circle-fill text-success"></i> Track papers across academic years</li>
                </ul>

                <form method="post" action="login.php" novalidate>
                    <?= csrf_field(); ?>
                    <input type="hidden" name="role" value="student">
                    <div class="mb-2">
                        <input type="email" class="form-control <?= isset($errors['student']) ? 'is-invalid' : ''; ?>"
                               name="email" placeholder="Student email" value="<?= old('email'); ?>">
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control <?= isset($errors['student']) ? 'is-invalid' : ''; ?>"
                               name="password" placeholder="Password">
                        <?php if (isset($errors['student'])): ?>
                            <div class="invalid-feedback"><?= e($errors['student']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-outline-success" type="submit">
                            <i class="bi bi-box-arrow-in-right"></i> Login as Student
                        </button>
                    </div>
                </form>
                <div class="text-center mt-3 small">
                    New student? <a href="register.php?role=student">Create a student account</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card role-card h-100">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <div class="role-icon staff mb-2"><i class="bi bi-person-badge"></i></div>
                    <h2 class="h4 fw-bold mb-1">Staff Login</h2>
                    <span class="badge role-badge-staff">Upload &amp; manage access</span>
                </div>
                <p class="text-muted small text-center mb-3">
                    As a staff member you can upload and organise question papers by selecting the
                    course, academic year, sub-course and subject.
                </p>
                <ul class="list-unstyled small mb-4">
                    <li><i class="bi bi-check-circle-fill text-warning"></i> Upload question papers (PDF)</li>
                    <li><i class="bi bi-check-circle-fill text-warning"></i> Select course, academic year, sub-course &amp; subject</li>
                    <li><i class="bi bi-check-circle-fill text-warning"></i> View, search &amp; manage uploaded papers</li>
                </ul>

                <form method="post" action="login.php" novalidate>
                    <?= csrf_field(); ?>
                    <input type="hidden" name="role" value="staff">
                    <div class="mb-2">
                        <input type="email" class="form-control <?= isset($errors['staff']) ? 'is-invalid' : ''; ?>"
                               name="email" placeholder="Staff email" value="<?= old('email'); ?>">
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control <?= isset($errors['staff']) ? 'is-invalid' : ''; ?>"
                               name="password" placeholder="Password">
                        <?php if (isset($errors['staff'])): ?>
                            <div class="invalid-feedback"><?= e($errors['staff']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-outline-warning" type="submit">
                            <i class="bi bi-box-arrow-in-right"></i> Login as Staff
                        </button>
                    </div>
                </form>
                <div class="text-center mt-3 small">
                    New staff member? <a href="register.php?role=staff">Create a staff account</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>