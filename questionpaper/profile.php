<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('danger', 'Session expired, please try again.');
        redirect('profile.php');
    }
    $old     = $_POST['old_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($old, $user['password'])) {
        flash('danger', 'Current password is incorrect.');
    } elseif (strlen($new) < 8) {
        flash('danger', 'New password must be at least 8 characters long.');
    } elseif ($new !== $confirm) {
        flash('danger', 'New passwords do not match.');
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $user['id']]);
        flash('success', 'Password updated successfully.');
    }
    redirect('profile.php');
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h6 fw-bold mb-0"><i class="bi bi-person"></i> Account details</h2>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted">Name</th>
                            <td><?= e($user['name']); ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Role</th>
                            <td><span class="badge <?= $user['role'] === 'staff' ? 'role-badge-staff' : 'role-badge-student'; ?>"><?= e(role_label($user['role'])); ?></span></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Email</th>
                            <td>
                                <?= e($user['email']); ?>
                                <?php if ($user['email_verified']): ?>
                                    <span class="badge text-bg-success ms-1">Verified</span>
                                <?php else: ?>
                                    <a href="verify.php" class="badge text-bg-danger text-decoration-none ms-1">Verify now</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Mobile</th>
                            <td>
                                <?= e($user['mobile']); ?>
                                <?php if ($user['mobile_verified']): ?>
                                    <span class="badge text-bg-success ms-1">Verified</span>
                                <?php else: ?>
                                    <a href="verify.php" class="badge text-bg-danger text-decoration-none ms-1">Verify now</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Status</th>
                            <td>
                                <span class="badge <?= $user['status'] === 'active' ? 'text-bg-success' : 'text-bg-danger'; ?>">
                                    <?= ucfirst($user['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Registered on</th>
                            <td><?= date('d M Y, h:i A', strtotime($user['created_at'])); ?></td>
                        </tr>
                    </tbody>
                </table>

                <a href="dashboard.php" class="btn btn-app-primary mt-3">
                    <i class="bi bi-speedometer2"></i> Go to my dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h2 class="h6 fw-bold mb-0"><i class="bi bi-key"></i> Change password</h2>
            </div>
            <div class="card-body">
                <form method="post" action="profile.php" class="auth-form" style="max-width:none;">
                    <?= csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current password</label>
                        <input type="password" class="form-control" name="old_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New password</label>
                        <input type="password" class="form-control" name="new_password" required>
                        <div class="form-text">Minimum 8 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm new password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-app-primary">Update password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>