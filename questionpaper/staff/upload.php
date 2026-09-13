<?php
$pageTitle = 'Upload Question Paper';
require_once __DIR__ . '/../includes/functions.php';
require_role('staff');
require_verified();

$staffUser = current_user();

$courses    = db()->query('SELECT id, code, name FROM courses ORDER BY code')->fetchAll();
$years      = db()->query('SELECT id, name FROM academic_years ORDER BY id DESC')->fetchAll();
$semesters  = [1, 2, 3, 4, 5, 6];

$sent = [
    'course_id'    => (int)($_POST['course_id'] ?? 0),
    'subcourse_id' => (int)($_POST['subcourse_id'] ?? 0),
    'subject_id'   => (int)($_POST['subject_id'] ?? 0),
    'year_id'      => (int)($_POST['year_id'] ?? 0),
    'semester'     => (int)($_POST['semester'] ?? 0),
    'title'        => trim($_POST['title'] ?? ''),
    'description'  => trim($_POST['description'] ?? ''),
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('danger', 'Session expired, please try again.');
        redirect('upload.php');
    }

    $file = $_FILES['paper_file'] ?? null;

    if (!$sent['course_id'] || !fetch_course($sent['course_id'])) {
        $errors['course_id'] = 'Please choose a course.';
    }
    if (!$sent['subcourse_id'] || !fetch_subcourse($sent['subcourse_id'])) {
        $errors['subcourse_id'] = 'Please choose a sub-course.';
    }
    if (!$sent['subject_id'] || !fetch_subject($sent['subject_id'])) {
        $errors['subject_id'] = 'Please choose a subject.';
    }
    if (!$sent['year_id'] || !fetch_year($sent['year_id'])) {
        $errors['year_id'] = 'Please choose the academic year of the paper.';
    }
    if ($sent['semester'] < 1 || $sent['semester'] > 6) {
        $errors['semester'] = 'Please choose a valid semester.';
    }
    if (!$file || !is_pdf_file($file)) {
        $errors['file'] = 'Please attach a valid PDF file (max ' . format_size(MAX_FILE_SIZE) . ').';
    }
    if (mb_strlen($sent['title']) > 200) {
        $errors['title'] = 'Title must be 200 characters or fewer.';
    }
    if ($file && $file['error'] === UPLOAD_ERR_INI_SIZE) {
        $errors['file'] = 'The file is larger than the server upload limit.';
    }

    if (!$errors) {
        // Make sure the staff member cannot pick a subject that does not
        // belong to the selected sub-course (or course).
        $sub = fetch_subcourse($sent['subcourse_id']);
        $subj = fetch_subject($sent['subject_id']);
        if (!$sub || $sub['course_id'] != $sent['course_id'] || $subj['subcourse_id'] != $sent['subcourse_id']) {
            $errors['mismatch'] = 'The selected subject does not belong to the chosen course / sub-course.';
        }
    }

    if (!$errors) {
        $sub  = fetch_subcourse($sent['subcourse_id']);
        $subj = fetch_subject($sent['subject_id']);
        $yr   = fetch_year($sent['year_id']);

        if ($sent['title'] === '') {
            $sent['title'] = $subj['name'] . ' - Semester ' . $sent['semester'] . ' (' . $yr['name'] . ')';
        }

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = 'paper_' . $sent['course_id'] . '_' . $sent['subcourse_id'] . '_'
                 . $sent['subject_id'] . '_' . $sent['year_id'] . '_s' . $sent['semester']
                 . '_' . time() . '.' . $ext;
        $dest = UPLOAD_DIR . DIRECTORY_SEPARATOR . $newName;

        if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0777, true)) {
            $errors['file'] = 'Uploads directory is not writable.';
        } elseif (!move_uploaded_file($file['tmp_name'], $dest)) {
            $errors['file'] = 'Could not save the uploaded file. Please try again.';
        } else {
            $st = db()->prepare(
                'INSERT INTO papers (course_id, subcourse_id, subject_id, year_id, semester,
                                     title, description, file_path, original_name, file_size, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $st->execute([
                $sent['course_id'],
                $sent['subcourse_id'],
                $sent['subject_id'],
                $sent['year_id'],
                $sent['semester'],
                $sent['title'],
                $sent['description'] !== '' ? $sent['description'] : null,
                'uploads/' . $newName,
                $file['name'],
                (int)$file['size'],
                $staffUser['id'],
            ]);

            flash('success', 'Question paper uploaded successfully.');
            redirect('manage.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header bg-white py-3 d-flex align-items-center">
                <i class="bi bi-cloud-arrow-up fs-4 me-2 text-primary"></i>
                <div>
                    <h2 class="h5 fw-bold mb-0">Upload a Question Paper</h2>
                    <span class="text-muted small">Select the course, academic year, sub-course and subject of the paper.</span>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if (isset($errors['mismatch']) && !isset($errors['course_id'])): ?>
                    <div class="alert alert-danger py-2"><?= e($errors['mismatch']); ?></div>
                <?php endif; ?>

                <form method="post" action="upload.php" enctype="multipart/form-data" data-chained novalidate>
                    <?= csrf_field(); ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="course_id">Course</label>
                            <select class="form-select <?= isset($errors['course_id']) ? 'is-invalid' : ''; ?>" name="course_id" id="course_id" required>
                                <option value="">-- Select course --</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= (int)$c['id']; ?>" <?= $sent['course_id'] === (int)$c['id'] ? 'selected' : ''; ?>>
                                        <?= e($c['code'] . ' - ' . $c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['course_id'])): ?><div class="invalid-feedback d-block"><?= e($errors['course_id']); ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="subcourse_id">Sub-course (Specialisation)</label>
                            <select class="form-select <?= isset($errors['subcourse_id']) ? 'is-invalid' : ''; ?>" name="subcourse_id" id="subcourse_id"
                                    data-value="<?= $sent['subcourse_id'] ? (int)$sent['subcourse_id'] : ''; ?>" required>
                                <option value="">-- Select sub-course --</option>
                            </select>
                            <?php if (isset($errors['subcourse_id'])): ?><div class="invalid-feedback d-block"><?= e($errors['subcourse_id']); ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="subject_id">Subject</label>
                            <select class="form-select <?= isset($errors['subject_id']) ? 'is-invalid' : ''; ?>" name="subject_id" id="subject_id"
                                    data-value="<?= $sent['subject_id'] ? (int)$sent['subject_id'] : ''; ?>" required>
                                <option value="">-- Select subject --</option>
                            </select>
                            <?php if (isset($errors['subject_id'])): ?><div class="invalid-feedback d-block"><?= e($errors['subject_id']); ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="year_id">Academic year</label>
                            <select class="form-select <?= isset($errors['year_id']) ? 'is-invalid' : ''; ?>" name="year_id" id="year_id" required>
                                <option value="">-- Year --</option>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?= (int)$y['id']; ?>" <?= $sent['year_id'] === (int)$y['id'] ? 'selected' : ''; ?>>
                                        <?= e($y['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['year_id'])): ?><div class="invalid-feedback d-block"><?= e($errors['year_id']); ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="semester">Semester</label>
                            <select class="form-select <?= isset($errors['semester']) ? 'is-invalid' : ''; ?>" name="semester" id="semester" required>
                                <option value="">-- Semester --</option>
                                <?php foreach ($semesters as $sem): ?>
                                    <option value="<?= $sem; ?>" <?= $sent['semester'] === $sem ? 'selected' : ''; ?>>
                                        Semester <?= $sem; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['semester'])): ?><div class="invalid-feedback d-block"><?= e($errors['semester']); ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold" for="title">Title <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : ''; ?>" id="title" name="title"
                                   value="<?= e($sent['title']); ?>" placeholder="e.g. Programming in C - Semester End Examination - 2024-2025">
                            <div class="form-text">Leave blank to auto-generate from the subject, semester and year.</div>
                            <?php if (isset($errors['title'])): ?><div class="invalid-feedback d-block"><?= e($errors['title']); ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold" for="description">Short description <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea class="form-control" id="description" name="description" rows="2"
                                      placeholder="e.g. Semester end examination paper, held in May 2025"><?= e($sent['description']); ?></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold" for="paper_file">Question paper PDF</label>
                            <input type="file" class="form-control <?= isset($errors['file']) ? 'is-invalid' : ''; ?>" id="paper_file"
                                   name="paper_file" accept=".pdf,application/pdf" required>
                            <div class="form-text">Only PDF files up to <?= format_size(MAX_FILE_SIZE); ?> are allowed.</div>
                            <?php if (isset($errors['file'])): ?><div class="invalid-feedback d-block"><?= e($errors['file']); ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-app-primary btn-lg">
                            <i class="bi bi-cloud-arrow-up"></i> Upload Paper
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>