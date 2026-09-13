<?php
$pageTitle = 'Staff Dashboard';
require_once __DIR__ . '/../includes/functions.php';
require_role('staff');
require_verified();

$staffUser = current_user();

// ---- Stats ----------------------------------------------------------------
$totalPapers = (int)db()->query('SELECT COUNT(*) FROM papers')->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM papers WHERE uploaded_by = ?');
$stmt->execute([$staffUser['id']]);
$myPapers = (int)$stmt->fetchColumn();

$stmt = db()->prepare('SELECT COALESCE(SUM(downloads),0) FROM papers WHERE uploaded_by = ?');
$stmt->execute([$staffUser['id']]);
$myDownloads = (int)$stmt->fetchColumn();

// ---- Filters --------------------------------------------------------------
$courses = db()->query('SELECT id, code, name FROM courses ORDER BY code')->fetchAll();
$years   = db()->query('SELECT id, name FROM academic_years ORDER BY id DESC')->fetchAll();
$semesters = [1, 2, 3, 4, 5, 6];

$f = [
    'course_id'    => (int)($_GET['course_id'] ?? 0),
    'subcourse_id' => (int)($_GET['subcourse_id'] ?? 0),
    'subject_id'   => (int)($_GET['subject_id'] ?? 0),
    'year_id'      => (int)($_GET['year_id'] ?? 0),
    'semester'     => (int)($_GET['semester'] ?? 0),
];

$papers = [];
$where  = [];
$params = [];

if ($f['course_id'])    { $where[] = 'p.course_id = ?';    $params[] = $f['course_id']; }
if ($f['subcourse_id']) { $where[] = 'p.subcourse_id = ?'; $params[] = $f['subcourse_id']; }
if ($f['subject_id'])   { $where[] = 'p.subject_id = ?';   $params[] = $f['subject_id']; }
if ($f['year_id'])      { $where[] = 'p.year_id = ?';      $params[] = $f['year_id']; }
if ($f['semester'])     { $where[] = 'p.semester = ?';     $params[] = $f['semester']; }

$sql = 'SELECT p.*, c.code AS course_code, c.name AS course_name,
               sc.name AS subcourse_name, s.name AS subject_name,
               y.name AS year_name, u.name AS uploader_name
        FROM papers p
        JOIN courses c        ON c.id = p.course_id
        JOIN subcourses sc    ON sc.id = p.subcourse_id
        JOIN subjects s       ON s.id = p.subject_id
        JOIN academic_years y ON y.id = p.year_id
        JOIN users u          ON u.id = p.uploaded_by';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY p.created_at DESC LIMIT 100';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$papers = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h3 fw-bold mb-0"><i class="bi bi-speedometer2"></i> Staff Dashboard</h1>
    <a href="upload.php" class="btn btn-app-primary"><i class="bi bi-cloud-arrow-up"></i> Upload New Paper</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-tile" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);">
            <div class="small text-uppercase opacity-75">Question papers in system</div>
            <div class="num"><?= (int)$totalPapers; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-tile" style="background:linear-gradient(135deg,#f59e0b,#ef4444);">
            <div class="small text-uppercase opacity-75">My uploads</div>
            <div class="num"><?= (int)$myPapers; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-tile" style="background:linear-gradient(135deg,#10b981,#0ea5e9);">
            <div class="small text-uppercase opacity-75">Total downloads (my papers)</div>
            <div class="num"><?= (int)$myDownloads; ?></div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white py-3">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-funnel"></i> Filter question papers</h2>
    </div>
    <div class="card-body">
        <form method="get" action="dashboard.php" data-chained class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select class="form-select form-select-sm" name="course_id">
                    <option value="">All courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int)$c['id']; ?>" <?= $f['course_id'] === (int)$c['id'] ? 'selected' : ''; ?>>
                            <?= e($c['code']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Sub-course</label>
                <select class="form-select form-select-sm" name="subcourse_id" data-value="<?= $f['subcourse_id'] ? (int)$f['subcourse_id'] : ''; ?>">
                    <option value="">All sub-courses</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Subject</label>
                <select class="form-select form-select-sm" name="subject_id" data-value="<?= $f['subject_id'] ? (int)$f['subject_id'] : ''; ?>">
                    <option value="">All subjects</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Academic year</label>
                <select class="form-select form-select-sm" name="year_id">
                    <option value="">All years</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= (int)$y['id']; ?>" <?= $f['year_id'] === (int)$y['id'] ? 'selected' : ''; ?>><?= e($y['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Semester</label>
                <select class="form-select form-select-sm" name="semester">
                    <option value="">All semesters</option>
                    <?php foreach ($semesters as $sem): ?>
                        <option value="<?= $sem; ?>" <?= $f['semester'] === $sem ? 'selected' : ''; ?>>Semester <?= $sem; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-9 d-flex gap-2">
                <button type="submit" class="btn btn-app-primary btn-sm"><i class="bi bi-search"></i> Apply filters</button>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-journal-text"></i> Available question papers (<?= count($papers); ?>)</h2>
    </div>
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
                        <th class="text-center">Downloads</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($papers as $p): ?>
                        <tr>
                            <td>
                                <a class="paper-title-link" href="<?= \requestBase() ?>download.php?paper=<?= (int)$p['id']; ?>" title="Download">
                                    <?= e($p['title']); ?>
                                </a>
                                <div class="small text-muted">by <?= e($p['uploader_name']); ?></div>
                            </td>
                            <td><?= e($p['course_code']); ?></td>
                            <td><?= e($p['subcourse_name']); ?></td>
                            <td><?= e($p['subject_name']); ?></td>
                            <td><?= e($p['year_name']); ?></td>
                            <td><?= (int)$p['semester']; ?></td>
                            <td class="text-center"><span class="badge text-bg-light"><?= (int)$p['downloads']; ?></span></td>
                            <td class="text-center">
                                <a class="btn btn-sm btn-outline-primary" href="<?= \requestBase() ?>download.php?paper=<?= (int)$p['id']; ?>">
                                    <i class="bi bi-download"></i>
                                </a>
                                <?php if ((int)$p['uploaded_by'] === (int)$staffUser['id']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delModal<?= (int)$p['id']; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted text-center py-5 mb-0">No question papers match the current filters.</p>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($papers as $p): if ((int)$p['uploaded_by'] === (int)$staffUser['id']): ?>
    <div class="modal fade" id="delModal<?= (int)$p['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete question paper?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <strong><?= e($p['title']); ?></strong> will be removed permanently.
                </div>
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
<?php endif; endforeach; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>