<?php
/**
 * Database connection (PDO singleton).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            $detail = (defined('DEBUG_SQL') && DEBUG_SQL) ? $e->getMessage() : '';
            http_response_code(500);
            die(
                '<div style="font-family:sans-serif;max-width:640px;margin:80px auto;padding:24px;' .
                'border:1px solid #f1c40f;border-radius:8px;background:#fffdf5;">' .
                '<h2>Database connection failed</h2>' .
                '<p>Please make sure MySQL is running and the database has been imported.</p>' .
                '<p>See <code>database.sql</code> and <code>config/config.php</code>.</p>' .
                ($detail ? '<pre style="background:#f6f6f6;padding:10px;font-size:12px;">' . htmlspecialchars($detail) . '</pre>' : '') .
                '</div>'
            );
        }
    }

    return $pdo;
}