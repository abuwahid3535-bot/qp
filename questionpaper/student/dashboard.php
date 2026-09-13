<?php
$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/../includes/functions.php';
require_role('student');
require_verified();

$student = current_user();

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
$where  = ['p.course_id = ?'];
$params = [$f['course_id']];
$searched = false;

// A subject is required before any results can be shown.
if ($f['course_id'] && $f['subcourse_id'] && $f['subject_id']) {
    $searched = true;
    $where[]  = 'p.subcourse_id = ?';
    $params[] = $f['subcourse_id'];
    $where[]  = 'p.subject_id = ?';
    $params[] = $f['subject_id'];
    if ($f['year_id']) {
        $where[]  = 'p.year_id = ?';
        $params[] = $f['year_id'];
    }
    if ($f['semester']) {
        $where[]  = 'p.semester = ?';
        $params[] = $f['semester'];
    }

    $sql = 'SELECT p.*, c.code AS course_code, sc.name AS subcourse_name,
                   s.name AS subject_name, y.name AS year_name, u.name AS uploader_name
            FROM papers p
            JOIN courses c        ON c.id = p.course_id
            JOIN subcourses sc    ON sc.id = p.subcourse_id
            JOIN subjects s       ON s.id = p.subject_id
            JOIN academic_years y ON y.id = p.year_id
            JOIN users u          ON u.id = p.uploaded_by
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY p.year_id DESC, p.semester DESC, p.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $papers = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-8">
        <h1 class="h3 fw-bold mb-1"><i class="bi bi-search"></i> Browse &amp; Download Papers</h1>
        <p class="text-muted mb-0">Choose your course and subject to see question papers from previous academic years.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <span class="badge <?= $student['email_verified'] ? 'text-bg-success' : 'text-bg-danger'; ?>">Email verified</span>
        <span class="badge <?= $student['mobile_verified'] ? 'text-bg-success' : 'text-bg-danger'; ?>">Mobile verified</span>
    </div>
</div>

<div class="card mb-4" id="browse">
    <div class="card-header bg-white py-3">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-funnel"></i> Find your papers</h2>
    </div>
    <div class="card-body">
        <form method="get" action="dashboard.php" data-chained class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="course_id">Course</label>
                <select class="form-select" name="course_id" id="course_id" required>
                    <option value="">-- Select course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int)$c['id']; ?>" <?= $f['course_id'] === (int)$c['id'] ? 'selected' : ''; ?>>
                            <?= e($c['code'] . ' - ' . $c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="subcourse_id">Sub-course</label>
                <select class="form-select" name="subcourse_id" id="subcourse_id"
                        data-value="<?= $f['subcourse_id'] ? (int)$f['subcourse_id'] : ''; ?>" required>
                    <option value="">-- Select sub-course --</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="subject_id">Subject</label>
                <select class="form-select" name="subject_id" id="subject_id"
                        data-value="<?= $f['subject_id'] ? (int)$f['subject_id'] : ''; ?>" required>
                    <option value="">-- Select subject --</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="year_id">Academic year <span class="text-muted fw-normal">(optional)</span></label>
                <select class="form-select" name="year_id" id="year_id">
                    <option value="">All years</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= (int)$y['id']; ?>" <?= $f['year_id'] === (int)$y['id'] ? 'selected' : ''; ?>><?= e($y['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="semester">Semester <span class="text-muted fw-normal">(optional)</span></label>
                <select class="form-select" name="semester" id="semester">
                    <option value="">All semesters</option>
                    <?php foreach ($semesters as $sem): ?>
                        <option value="<?= $sem; ?>" <?= $f['semester'] === $sem ? 'selected' : ''; ?>>Semester <?= $sem; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-app-primary"><i class="bi bi-search"></i> Search papers</button>
                <a href="dashboard.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white py-3">
        <h2 class="h6 fw-bold mb-0">
            <i class="bi bi-journal-text"></i>
            <?= $searched ? 'Search results (' . count($papers) . ')' : 'Search results'; ?>
        </h2>
    </div>
    <div class="table-responsive">
        <?php if (!$searched): ?>
            <p class="text-muted text-center py-5 mb-0">Select a course, sub-course and subject above to see available question papers.</p>
        <?php elseif ($papers): ?>
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Paper</th>
                        <th>Subject</th>
                        <th>Course</th>
                        <th>Sub-course</th>
                        <th>Year</th>
                        <th>Sem</th>
                        <th class="text-center">Downloads</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($papers as $p): ?>
                        <tr>
                            <td>
                                <strong><?= e($p['title']); ?></strong>
                                <div class="small text-muted">by <?= e($p['uploader_name']); ?></div>
                            </td>
                            <td><?= e($p['subject_name']); ?></td>
                            <td><?= e($p['course_code']); ?></td>
                            <td><?= e($p['subcourse_name']); ?></td>
                            <td><?= e($p['year_name']); ?></td>
                            <td><?= (int)$p['semester']; ?></td>
                            <td class="text-center"><span class="badge text-bg-light"><?= (int)$p['downloads']; ?></span></td>
                            <td class="text-center">
                                <a class="btn btn-app-primary btn-sm" href="<?= \requestBase() ?>download.php?paper=<?= (int)$p['id']; ?>">
                                    <i class="bi bi-download me-1"></i> Download
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-warning text-center py-5 mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No question papers found for the selected subject. Try another year or subject.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>