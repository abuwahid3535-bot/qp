<?php
$pageTitle = 'Register';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('danger', 'Session expired, please try again.');
        redirect('register.php');
    }

    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $mobile   = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = ($_POST['role'] ?? 'student') === 'staff' ? 'staff' : 'student';

    if ($name === '' || mb_strlen($name) < 3) {
        $errors['name'] = 'Please enter your full name (at least 3 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
        $errors['mobile'] = 'Please enter a valid 10-digit mobile number.';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!$errors) {
        $chk = db()->prepare('SELECT id FROM users WHERE email = ? OR mobile = ? LIMIT 1');
        $chk->execute([$email, $mobile]);
        if ($row = $chk->fetch()) {
            $existing = db()->prepare('SELECT email, mobile FROM users WHERE id = ?');
            $existing->execute([$row['id']]);
            $u = $existing->fetch();
            if ($u['email'] === $email) {
                $errors['email'] = 'This email is already registered.';
            }
            if ($u['mobile'] === $mobile) {
                $errors['mobile'] = 'This mobile number is already registered.';
            }
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st = db()->prepare(
            'INSERT INTO users (name, email, mobile, password, role) VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([$name, $email, $mobile, $hash, $role]);

        $uid = (int)db()->lastInsertId();
        $_SESSION['user_id'] = $uid;

        $newUser = db()->prepare('SELECT * FROM users WHERE id = ?');
        $newUser->execute([$uid]);
        $user = $newUser->fetch();

        send_email_otp($user);
        send_mobile_otp($user);

        flash('info', 'Account created. Please verify your email and mobile number to continue.');
        redirect('verify.php');
    }
}

$presetRole = ($_GET['role'] ?? '') === 'staff' ? 'staff' : 'student';

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-form">
    <div class="card">
        <div class="card-header bg-white py-3">
            <h2 class="h5 fw-bold mb-0 text-center"><i class="bi bi-person-plus"></i> Create an Account</h2>
        </div>
        <div class="card-body p-4">
            <form method="post" action="register.php" novalidate>
                <?= csrf_field(); ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Choose your role</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="role" id="roleStudent" value="student" autocomplete="off"
                                   <?= $presetRole === 'student' ? 'checked' : ''; ?>>
                            <label class="btn w-100 <?= $presetRole === 'student' ? 'btn-app-primary' : 'btn-outline-primary'; ?>" for="roleStudent">
                                <i class="bi bi-mortarboard"></i> Student
                            </label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="role" id="roleStaff" value="staff" autocomplete="off"
                                   <?= $presetRole === 'staff' ? 'checked' : ''; ?>>
                            <label class="btn w-100 <?= $presetRole === 'staff' ? 'btn-app-primary' : 'btn-outline-primary'; ?>" for="roleStaff">
                                <i class="bi bi-person-badge"></i> Staff
                            </label>
                        </div>
                    </div>
                    <div class="form-text">Students download papers; staff members upload and manage them.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="name">Full name</label>
                    <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name"
                           value="<?= old('name'); ?>" placeholder="e.g. Ananya Sharma">
                    <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']); ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="email">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email"
                               value="<?= old('email'); ?>" placeholder="you@example.com">
                    </div>
                    <div class="form-text">An OTP will be sent to this email for verification.</div>
                    <?php if (isset($errors['email'])): ?><div class="text-danger small"><?= e($errors['email']); ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="mobile">Mobile number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                        <input type="tel" class="form-control <?= isset($errors['mobile']) ? 'is-invalid' : ''; ?>" id="mobile" name="mobile"
                               value="<?= old('mobile'); ?>" maxlength="10" placeholder="10-digit mobile number">
                    </div>
                    <div class="form-text">An OTP will be sent to this number via SMS for verification.</div>
                    <?php if (isset($errors['mobile'])): ?><div class="text-danger small"><?= e($errors['mobile']); ?></div><?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold" for="password">Password</label>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password">
                        <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']); ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold" for="confirm_password">Confirm password</label>
                        <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password">
                        <?php if (isset($errors['confirm_password'])): ?><div class="invalid-feedback"><?= e($errors['confirm_password']); ?></div><?php endif; ?>
                    </div>
                    <div class="form-text mt-n2">Minimum 8 characters.</div>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-app-primary btn-lg"><i class="bi bi-check-circle"></i> Register</button>
                </div>
            </form>
        </div>
    </div>
    <p class="text-center mt-3 text-muted">Already have an account? <a href="login.php">Login here</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>