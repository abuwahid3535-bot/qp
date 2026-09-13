<?php
/**
 * Shared helper functions.
 */

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . requestBase() . $url);
    exit;
}

// ------------------------- Flash messages -------------------------------
function flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

// ------------------------- CSRF ------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ------------------------- Auth helpers ----------------------------------
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Returns the current logged-in user as an array (fresh from DB), or null.
 * Pass $refresh = true to ignore the request-lifetime cache.
 */
function current_user(bool $refresh = false): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    static $user = null;
    if ($user === null || $refresh) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        $user = $row ?: null;
    }
    return $user;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Please login to continue.');
        redirect('login.php');
    }
}

function require_role(string $role): void
{
    require_login();
    $user = current_user();
    if ($user['role'] !== $role) {
        flash('danger', 'You do not have permission to access that page.');
        redirect('dashboard.php');
    }
}

/**
 * Full verification required before accessing the panels.
 */
function require_verified(): void
{
    require_login();
    $user = current_user();
    if ($user['status'] !== 'active') {
        session_destroy();
        redirect('login.php');
    }
    if ((int)$user['email_verified'] === 0 || (int)$user['mobile_verified'] === 0) {
        redirect('verify.php');
    }
}

// ------------------------- OTP helpers -----------------------------------
function generate_otp(int $length = 6): string
{
    $digits = [];
    for ($i = 0; $i < $length; $i++) {
        $digits[] = random_int(0, 9);
    }
    return implode('', $digits);
}

function can_resend_otp(string $sentAt): bool
{
    if (!$sentAt) {
        return true;
    }
    return (time() - strtotime($sentAt)) >= OTP_RESEND_COOLDOWN;
}

/**
 * Generates + stores a fresh email OTP and tries to send it.
 * Returns true when the user can read the OTP (either because it was sent,
 * or DEV_MODE/SMS_SHOW_OTP prints it on screen).
 */
function send_email_otp(array $user): string
{
    $otp = generate_otp();
    $st = db()->prepare(
        'UPDATE users SET email_otp = ?, email_otp_expiry = ?, email_otp_sent_at = NOW() WHERE id = ?'
    );
    $st->execute([$otp, date('Y-m-d H:i:s', time() + OTP_TTL * 60), $user['id']]);

    $subject = APP_NAME . ' - Email Verification OTP';
    $body = "Hello {$user['name']},\n\n"
          . "Your Email verification OTP is: {$otp}\n\n"
          . "It is valid for " . OTP_TTL . " minutes.\n\n"
          . "If you did not request this, you can ignore this mail.\n\n"
          . "Regards,\n" . APP_NAME;

    Mailer::send($user['email'], $subject, $body);
    return $otp;
}

function send_mobile_otp(array $user): string
{
    $otp = generate_otp();
    $st = db()->prepare(
        'UPDATE users SET mobile_otp = ?, mobile_otp_expiry = ?, mobile_otp_sent_at = NOW() WHERE id = ?'
    );
    $st->execute([$otp, date('Y-m-d H:i:s', time() + OTP_TTL * 60), $user['id']]);

    $message = "Your " . APP_NAME . " mobile verification OTP is {$otp}. "
             . 'It is valid for ' . OTP_TTL . ' minutes.';
    SmsService::send($user['mobile'], $message);
    return $otp;
}

function verify_email_otp(array $user, string $otp): bool
{
    if ((int)$user['email_verified'] === 1) {
        return true;
    }
    if (!$user['email_otp'] || !$user['email_otp_expiry']) {
        return false;
    }
    if (time() > strtotime($user['email_otp_expiry'])) {
        return false;
    }
    if (!hash_equals($user['email_otp'], $otp)) {
        return false;
    }
    db()->prepare('UPDATE users SET email_verified = 1, email_otp = NULL, email_otp_expiry = NULL WHERE id = ?')
        ->execute([$user['id']]);
    return true;
}

function verify_mobile_otp(array $user, string $otp): bool
{
    if ((int)$user['mobile_verified'] === 1) {
        return true;
    }
    if (!$user['mobile_otp'] || !$user['mobile_otp_expiry']) {
        return false;
    }
    if (time() > strtotime($user['mobile_otp_expiry'])) {
        return false;
    }
    if (!hash_equals($user['mobile_otp'], $otp)) {
        return false;
    }
    db()->prepare('UPDATE users SET mobile_verified = 1, mobile_otp = NULL, mobile_otp_expiry = NULL WHERE id = ?')
        ->execute([$user['id']]);
    return true;
}

// ------------------------- Lookup helpers --------------------------------
function fetch_course(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM courses WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function fetch_subcourse(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM subcourses WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function fetch_subject(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM subjects WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function fetch_year(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM academic_years WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/**
 * A paper row enriched with the names of related records + uploader name.
 */
function fetch_paper(int $id): ?array
{
    $st = db()->prepare(
        'SELECT p.*, c.name AS course_name, c.code AS course_code,
                sc.name AS subcourse_name, s.name AS subject_name,
                y.name AS year_name, u.name AS uploader_name
         FROM papers p
         JOIN courses c        ON c.id = p.course_id
         JOIN subcourses sc    ON sc.id = p.subcourse_id
         JOIN subjects s       ON s.id = p.subject_id
         JOIN academic_years y ON y.id = p.year_id
         JOIN users u          ON u.id = p.uploaded_by
         WHERE p.id = ?'
    );
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

// ------------------------- File helpers ----------------------------------
function format_size(int $bytes): string
{
    if ($bytes <= 0) {
        return '0 B';
    }
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = (int)floor(log($bytes, 1024));
    return round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
}

function serve_file(string $path, string $downloadName): void
{
    if (!is_file($path)) {
        http_response_code(404);
        exit('File not found on the server.');
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($path));
    header('Pragma: public');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    readfile($path);
    exit;
}

// ------------------------- Misc ------------------------------------------
function role_label(string $role): string
{
    return $role === 'staff' ? 'Staff' : 'Student';
}

function old(string $key): string
{
    return e($_POST[$key] ?? '');
}

function is_pdf_file(array $file): bool
{
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    if ($file['size'] <= 0 || $file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    return $mime === 'application/pdf' && $ext === 'pdf';
}

function load_config(): void
{
    require_once __DIR__ . '/../config/config.php';
}

load_config();

/**
 * Relative base path for linking back to the project root from pages that
 * live in sub-folders (staff/, student/). Returns '' for root pages and
 * '../' for one-level nested pages.
 */
function requestBase(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $root = realpath(dirname(__DIR__));
    $dir  = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? __FILE__));
    $base = '';
    if ($dir && $root && $dir !== $root && strpos($dir, $root) === 0) {
        $segments = count(explode(DIRECTORY_SEPARATOR, trim(substr($dir, strlen($root)), DIRECTORY_SEPARATOR)));
        $base = str_repeat('../', $segments);
    }
    return $base;
}