<?php
/**
 * DEVELOPMENT ONLY - DO NOT LEAVE IN PLACE FOR PRODUCTION.
 *
 * Creates two pre-verified demo accounts so you can test the app
 * immediately without completing email/mobile verification:
 *
 *   Staff   : staff@demo.com   / Password@123
 *   Student : student@demo.com / Password@123
 *
 * It also inserts a few sample question paper records (pointing at the
 * demo PDFs bundled in uploads/) so downloads work right away.
 *
 * Open http://localhost/questionpaper/seed_demo.php once, then DELETE
 * this file (or keep it away from any public deployment).
 */
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/plain; charset=utf-8');

$accounts = [
    ['Demo Staff',   'staff@demo.com',   'Password@123', '9876500001', 'staff'],
    ['Demo Student', 'student@demo.com', 'Password@123', '9876500002', 'student'],
];

echo "Seeding demo accounts...\n\n";

$staffId = null;

foreach ($accounts as [$name, $email, $pass, $mobile, $role]) {
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? OR mobile = ? LIMIT 1');
    $stmt->execute([$email, $mobile]);
    $existing = $stmt->fetch();
    if ($existing) {
        echo "SKIP  $email (already registered, id=" . $existing['id'] . ")\n";
        if ($role === 'staff') {
            $staffId = (int)$existing['id'];
        }
        continue;
    }
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $ins = db()->prepare(
        'INSERT INTO users (name, email, mobile, password, role, email_verified, mobile_verified)
         VALUES (?, ?, ?, ?, ?, 1, 1)'
    );
    $ins->execute([$name, $email, $mobile, $hash, $role]);
    $uid = (int)db()->lastInsertId();
    echo "OK    $email  ->  role=$role  password=$pass\n";
    if ($role === 'staff') {
        $staffId = $uid;
    }
}

if (!$staffId) {
    echo "\nDemo staff account not found/created - cannot link sample papers.\n";
    exit(1);
}

// ---- Sample question paper records (only if not already present) ------
$samples = [
    [2, 7, 19, 5, 1, 'Programming in C - Semester End Examination - 2024-2025',     'uploads/demo_ca101.pdf', 'demo_ca101.pdf'],
    [2, 7, 22, 5, 3, 'Database Management Systems - Mid Semester - 2024-2025',       'uploads/demo_ca202.pdf', 'demo_ca202.pdf'],
    [1, 4, 11, 5, 1, 'Programming in C (B.Sc. CS) - Semester End - 2024-2025',       'uploads/demo_cs101.pdf', 'demo_cs101.pdf'],
];

echo "\nSeeding sample question papers...\n\n";

foreach ($samples as [$course, $subcourse, $subject, $year, $sem, $title, $path, $orig]) {
    $full = UPLOAD_DIR . DIRECTORY_SEPARATOR . basename($path);
    $size = is_file($full) ? (int)filesize($full) : 0;

    $stmt = db()->prepare('SELECT id FROM papers WHERE course_id = ? AND subcourse_id = ? AND subject_id = ? AND year_id = ? AND semester = ? LIMIT 1');
    $stmt->execute([$course, $subcourse, $subject, $year, $sem]);
    if ($stmt->fetch()) {
        echo "SKIP  $title (already exists)\n";
        continue;
    }

    $ins = db()->prepare(
        'INSERT INTO papers (course_id, subcourse_id, subject_id, year_id, semester, title,
                             description, file_path, original_name, file_size, downloads, uploaded_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)'
    );
    $ins->execute([
        $course, $subcourse, $subject, $year, $sem, $title,
        'Sample paper seeded for demo purposes.', $path, $orig, $size, $staffId,
    ]);
    echo "OK    $title\n";
}

echo "\nDone. Delete seed_demo.php before going live.\n";