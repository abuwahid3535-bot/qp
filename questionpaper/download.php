<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
require_verified();

$paperId = (int)($_GET['paper'] ?? 0);
$user    = current_user();

if ($paperId <= 0) {
    http_response_code(400);
    exit('Invalid paper id.');
}

$paper = fetch_paper($paperId);
if (!$paper) {
    http_response_code(404);
    exit('Question paper not found.');
}

$fullPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . basename($paper['file_path']);
if (!is_file($fullPath)) {
    http_response_code(404);
    exit('The file for this question paper is missing on the server.');
}

db()->prepare('UPDATE papers SET downloads = downloads + 1 WHERE id = ?')->execute([$paperId]);

$downloadName = basename($paper['original_name']);
if ($downloadName === '' || $downloadName === '.' || $downloadName === '..') {
    $downloadName = 'question_paper_' . $paperId . '.pdf';
}

serve_file($fullPath, $downloadName);