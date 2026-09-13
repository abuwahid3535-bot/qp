<?php
$pageTitle = 'My Papers';
require_once __DIR__ . '/../includes/functions.php';
require_role('staff');
require_verified();

$staffUser = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('danger', 'Session expired, please try again.');
        redirect('manage.php');
    }
    $action   = $_POST['action'] ?? '';
    $paperId  = (int)($_POST['paper_id'] ?? 0);

    if ($action === 'delete' && $paperId > 0) {
        $stmt = db()->prepare('SELECT * FROM papers WHERE id = ? AND uploaded_by = ?');
        $stmt->execute([$paperId, $staffUser['id']]);
        $paper = $stmt->fetch();
        if ($paper) {
            $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . basename($paper['file_path']);
            if (is_file($path)) {
                unlink($path);
            }
            db()->prepare('DELETE FROM papers WHERE id = ?')->execute([$paperId]);
            flash('success', 'Question paper deleted.');
        } else {
            flash('danger', 'Paper not found or you do not have permission to delete it.');
        }
    }
    redirect('manage.php');
}

$stmt = db()->prepare(
    'SELECT p.*, c.code AS course_code, sc.name AS subcourse_name, s.name AS subject_name, y.name AS year_name
     FROM papers p
     JOIN courses c        ON c.id = p.course_id
     JOIN subcourses sc    ON sc.id = p.subcourse_id
     JOIN subjects s       ON s.id = p.subject_id
     JOIN academic_years y ON y.id = p.year_id
     WHERE p.uploaded_by = ?
     ORDER BY p.created_at DESC'
);
$stmt->execute([$staffUser['id']]);
$papers = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h3 fw-bold mb-0"><i class="bi bi-collection"></i> My Uploaded Papers (<?= count($papers); ?>)</h1>
    <a href="upload.php" class="btn btn-app-primary"><i class="bi bi-cloud-arrow-up"></i> Upload New Paper</a>
</div>

<div class="card">
    <div class="table-responsive">
        <?php if ($papers): ?>
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Course</th>
                        <th>Sub-course</th>
                        <th>Subject</th>
                        <th>Year</th>
                        <th>Sem</th>
                        <th>Size</th>
                        <th class="text-center">Downloads</th>
                        <th>Uploaded</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($papers as $p): ?>
                        <tr>
                            <td class="text-truncate" style="max-width:260px;"><?= e($p['title']); ?></td>
                            <td><?= e($p['course_code']); ?></td>
                            <td><?= e($p['subcourse_name']); ?></td>
                            <td><?= e($p['subject_name']); ?></td>
                            <td><?= e($p['year_name']); ?></td>
                            <td><?= (int)$p['semester']; ?></td>
                            <td><?= format_size((int)$p['file_size']); ?></td>
                            <td class="text-center"><?= (int)$p['downloads']; ?></td>
                            <td class="small text-muted"><?= date('d M Y', strtotime($p['created_at'])); ?></td>
                            <td class="text-center">
                                <a class="btn btn-sm btn-outline-primary" href="<?= \requestBase() ?>download.php?paper=<?= (int)$p['id']; ?>">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delModal<?= (int)$p['id']; ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted text-center py-5 mb-0">
                You have not uploaded any papers yet.
                <a href="upload.php">Upload your first question paper</a>.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($papers as $p): ?>
    <div class="modal fade" id="delModal<?= (int)$p['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete question paper?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">This will permanently remove the paper file as well.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="post" action="manage.php">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="paper_id" value="<?= (int)$p['id']; ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>