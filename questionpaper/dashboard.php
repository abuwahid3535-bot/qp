<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/functions.php';
require_login();
require_verified();

$user = current_user();
redirect($user['role'] === 'staff' ? 'staff/dashboard.php' : 'student/dashboard.php');