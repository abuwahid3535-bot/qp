<?php
/**
 * ---------------------------------------------------------------------
 *  Question Paper Management System - Global Configuration
 *  Edit these values to match your own setup (XAMPP / WAMP / LAMP).
 * ---------------------------------------------------------------------
 */

// ---------- Application ----------------------------------------------
define('APP_NAME', 'QP Portal');
define('APP_TAGLINE', 'Question Paper Management System');

// Directory where uploaded PDF files are stored (filesystem path).
define('UPLOAD_DIR', __DIR__ . '/../uploads');
// Allowed upload size in bytes (default 10 MB).
define('MAX_FILE_SIZE', 10 * 1024 * 1024);

// Development mode: when TRUE the generated OTP is shown on the
// verification screen so you can test the whole flow without a real
// mail server / SMS gateway. Set to FALSE for production.
define('DEV_MODE', true);

// OTP lifetime in minutes.
define('OTP_TTL', 10);
// Minimum number of seconds between OTP re-sends.
define('OTP_RESEND_COOLDOWN', 60);

// ---------- Development / debugging -----------------------------------
define('DEBUG_SQL', false); // set true to see PDO error details in dev

// ---------- Database ---------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'question_paper_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---------- Email (SMTP via PHPMailer, optional) ------------------------
// If vendor/autoload.php (PHPMailer) exists AND MAIL_USE_PHPMAILER is true,
// emails are sent over SMTP. Otherwise PHP's mail() is used when available.
// In DEV_MODE the OTP is simply shown on screen, so no server is required.
define('MAIL_USE_PHPMAILER', false);
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_SECURE', 'tls'); // 'ssl' or 'tls'
define('MAIL_USER', 'your.email@gmail.com');
define('MAIL_PASS', 'your-app-password');
define('MAIL_FROM', 'no-reply@localhost.local');
define('MAIL_FROM_NAME', APP_NAME);

// ---------- SMS Gateway -------------------------------------------------
// 'demo'    -> no real SMS is sent; the OTP is shown on the verification
//              page (works offline, good for demonstrations).
// 'textlocal' -> sends the OTP via Textlocal (https://www.textlocal.in).
//                Set SMS_API_KEY and SMS_SENDER, and enable SMS_VIA_OTP.
define('SMS_PROVIDER', 'demo');
define('SMS_API_KEY', 'YOUR-TEXTLOCAL-API-KEY');
define('SMS_SENDER', 'QPPORT');
// Force the OTP to also be printed on-screen even when a real SMS provider
// is configured. Useful while testing.
define('SMS_SHOW_OTP_ON_SCREEN', true);

// =====================================================================
//  Bootstrapping (do not edit below this line)
// =====================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/sms.php';