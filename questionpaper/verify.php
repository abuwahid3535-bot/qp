<?php
$pageTitle = 'Verify Your Account';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('danger', 'Session expired, please try again.');
        redirect('verify.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'verify_email') {
        $otp = trim($_POST['otp'] ?? '');
        if (verify_email_otp(current_user(true), $otp)) {
            flash('success', 'Email verified successfully!');
        } else {
            flash('danger', 'Invalid or expired email OTP. Please try again or resend.');
        }
        redirect('verify.php');
    }

    if ($action === 'verify_mobile') {
        $otp = trim($_POST['otp'] ?? '');
        if (verify_mobile_otp(current_user(true), $otp)) {
            flash('success', 'Mobile number verified successfully!');
        } else {
            flash('danger', 'Invalid or expired mobile OTP. Please try again or resend.');
        }
        redirect('verify.php');
    }

    if ($action === 'resend_email') {
        $u = current_user(true);
        if (!can_resend_otp($u['email_otp_sent_at'])) {
            $wait = OTP_RESEND_COOLDOWN - (time() - strtotime($u['email_otp_sent_at']));
            flash('warning', 'Please wait ' . max(1, $wait) . ' second(s) before resending the email OTP.');
        } else {
            send_email_otp($u);
            flash('info', 'A fresh email OTP has been generated.');
        }
        redirect('verify.php');
    }

    if ($action === 'resend_mobile') {
        $u = current_user(true);
        if (!can_resend_otp($u['mobile_otp_sent_at'])) {
            $wait = OTP_RESEND_COOLDOWN - (time() - strtotime($u['mobile_otp_sent_at']));
            flash('warning', 'Please wait ' . max(1, $wait) . ' second(s) before resending the mobile OTP.');
        } else {
            send_mobile_otp($u);
            flash('info', 'A fresh mobile OTP has been generated.');
        }
        redirect('verify.php');
    }
}

$user = current_user(true);
$emailOk     = (int)$user['email_verified'] === 1;
$mobileOk    = (int)$user['mobile_verified'] === 1;
$allVerified = $emailOk && $mobileOk;

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-form">
    <div class="card">
        <div class="card-header bg-white py-3 text-center">
            <h2 class="h5 fw-bold mb-1"><i class="bi bi-shield-lock"></i> Account Verification</h2>
            <span class="text-muted small">Hello <?= e($user['name']); ?>, verify your email and mobile to continue.</span>
        </div>
        <div class="card-body p-4">
            <?php if ($allVerified): ?>
                <div class="text-center py-4">
                    <i class="bi bi-patch-check-fill text-success" style="font-size:4rem;"></i>
                    <h3 class="fw-bold mt-3">You are all set!</h3>
                    <p class="text-muted">Both your email and mobile number are verified.</p>
                    <a href="dashboard.php" class="btn btn-app-primary btn-lg">
                        <i class="bi bi-speedometer2"></i> Go to Dashboard
                    </a>
                </div>
            <?php else: ?>

                <!-- Email verification -->
                <div class="border rounded-3 p-3 mb-3 <?= $emailOk ? 'border-success' : ''; ?>">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h3 class="h6 fw-bold mb-0">
                            <i class="bi <?= $emailOk ? 'bi-check-circle-fill text-success' : 'bi-envelope'; ?>"></i>
                            Email verification
                        </h3>
                        <?php if ($emailOk): ?>
                            <span class="badge text-bg-success">Verified</span>
                        <?php else: ?>
                            <span class="badge text-bg-warning">Pending</span>
                        <?php endif; ?>
                    </div>
                    <p class="small text-muted mb-2"><?= e($user['email']); ?></p>

                    <?php if (!$emailOk): ?>
                        <?php if (DEV_MODE): ?>
                            <div class="alert alert-info py-2 small mb-2">
                                <strong>Development mode:</strong> no real email is sent. Your email OTP is
                                <span class="otp-code" style="font-size:1.1rem;letter-spacing:.25rem;"><?= e($user['email_otp'] ?? ''); ?></span>
                            </div>
                        <?php endif; ?>
                        <form method="post" action="verify.php" class="row g-2">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="verify_email">
                            <div class="col-7">
                                <input type="text" name="otp" class="form-control text-center" maxlength="6"
                                       pattern="[0-9]{6}" inputmode="numeric" placeholder="Enter 6-digit OTP" required>
                            </div>
                            <div class="col-5">
                                <button type="submit" class="btn btn-app-primary w-100">Verify Email</button>
                            </div>
                        </form>
                        <form method="post" action="verify.php" class="mt-2">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="resend_email">
                            <button type="submit" class="btn btn-link btn-sm p-0">
                                <i class="bi bi-arrow-clockwise"></i> Resend email OTP
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Mobile verification -->
                <div class="border rounded-3 p-3 <?= $mobileOk ? 'border-success' : ''; ?>">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h3 class="h6 fw-bold mb-0">
                            <i class="bi <?= $mobileOk ? 'bi-check-circle-fill text-success' : 'bi-phone'; ?>"></i>
                            Mobile verification
                        </h3>
                        <?php if ($mobileOk): ?>
                            <span class="badge text-bg-success">Verified</span>
                        <?php else: ?>
                            <span class="badge text-bg-warning">Pending</span>
                        <?php endif; ?>
                    </div>
                    <p class="small text-muted mb-2">+91 <?= e($user['mobile']); ?></p>

                    <?php if (!$mobileOk): ?>
                        <?php if (SmsService::visibleToUser()): ?>
                            <div class="alert alert-info py-2 small mb-2">
                                <strong><?= SMS_PROVIDER === 'demo' ? 'Demo mode:' : 'Test mode:'; ?></strong>
                                no real SMS is sent. Your mobile OTP is
                                <span class="otp-code" style="font-size:1.1rem;letter-spacing:.25rem;"><?= e($user['mobile_otp'] ?? ''); ?></span>
                            </div>
                        <?php endif; ?>
                        <form method="post" action="verify.php" class="row g-2">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="verify_mobile">
                            <div class="col-7">
                                <input type="text" name="otp" class="form-control text-center" maxlength="6"
                                       pattern="[0-9]{6}" inputmode="numeric" placeholder="Enter 6-digit OTP" required>
                            </div>
                            <div class="col-5">
                                <button type="submit" class="btn btn-app-primary w-100">Verify Mobile</button>
                            </div>
                        </form>
                        <form method="post" action="verify.php" class="mt-2">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="resend_mobile">
                            <button type="submit" class="btn btn-link btn-sm p-0">
                                <i class="bi bi-arrow-clockwise"></i> Resend mobile OTP
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="text-center mt-3 small">
                    <a href="logout.php">Logout</a> to switch to a different account.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>