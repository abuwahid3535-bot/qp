<?php
/**
 * Email service.
 *
 * Delivery order:
 *   1. PHPMailer (if installed via Composer and MAIL_USE_PHPMAILER is true)
 *   2. PHP's built-in mail() function
 *   3. No real transport -> log only (safe fallback; OTP is still shown
 *      on screen in DEV_MODE).
 */
class Mailer
{
    public static function send(string $to, string $subject, string $plainBody): bool
    {
        if (defined('DEV_MODE') && DEV_MODE) {
            // In development mode we do not depend on a real mail server.
            return false;
        }

        if (self::sendWithPHPMailer($to, $subject, $plainBody)) {
            return true;
        }

        if (function_exists('mail')) {
            $headers  = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>' . "\r\n";
            $headers .= 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
            return @mail($to, $subject, $plainBody, $headers);
        }

        return false;
    }

    private static function sendWithPHPMailer(string $to, string $subject, string $plainBody): bool
    {
        if (!defined('MAIL_USE_PHPMAILER') || !MAIL_USE_PHPMAILER) {
            return false;
        }
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($autoload)) {
            return false;
        }
        require_once $autoload;
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->SMTPSecure = MAIL_SECURE;
            $mail->Port       = MAIL_PORT;
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $plainBody;
            return $mail->send();
        } catch (\Throwable $e) {
            return false;
        }
    }
}