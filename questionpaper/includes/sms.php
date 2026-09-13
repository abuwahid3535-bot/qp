<?php
/**
 * SMS service.
 *
 * Providers:
 *   'demo'      -> no real SMS is sent (best for local demos).
 *   'textlocal' -> sends via the Textlocal HTTP API (India).
 */
class SmsService
{
    private static function shouldPrintOnScreen(): bool
    {
        $force = defined('SMS_SHOW_OTP_ON_SCREEN') && SMS_SHOW_OTP_ON_SCREEN;
        $demo  = defined('SMS_PROVIDER') && SMS_PROVIDER === 'demo';
        return $demo || $force;
    }

    public static function send(string $mobile, string $message): bool
    {
        $provider = defined('SMS_PROVIDER') ? SMS_PROVIDER : 'demo';

        switch ($provider) {
            case 'textlocal':
                return self::sendTextlocal($mobile, $message);
            case 'demo':
            default:
                // Nothing leaves the machine. The OTP is displayed by the
                // verification page because shouldPrintOnScreen() is true.
                return false;
        }
    }

    public static function visibleToUser(): bool
    {
        return self::shouldPrintOnScreen();
    }

    private static function sendTextlocal(string $mobile, string $message): bool
    {
        if (!defined('SMS_API_KEY') || SMS_API_KEY === '' || defined('DEV_MODE') && DEV_MODE) {
            return false;
        }

        $params = [
            'apikey'  => SMS_API_KEY,
            'numbers' => $mobile,
            'sender'  => defined('SMS_SENDER') ? SMS_SENDER : 'QPPORT',
            'message' => $message,
        ];

        $ch = curl_init('https://api.textlocal.in/send/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200 && (bool)$response;
    }
}