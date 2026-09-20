<?php
// views/admin/assign-subject.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'Assign Subject | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$error = '';
$success = '';

// Fetch all courses
$courses = $db->query("
    SELECT c.id, c.course_code, c.course_name, d.dept_code, s.semester_number
    FROM courses c
    JOIN departments d ON c.department_id = d.id
    JOIN semesters s ON c.semester_id = s.id
    ORDER BY d.dept_code, s.semester_number, c.course_code
")->fetchAll();

// Fetch all faculty
$faculties = $db->query("
    SELECT t.id, t.name, GROUP_CONCAT(d.dept_code ORDER BY d.dept_code ASC SEPARATOR ', ') as dept_codes
    FROM teachers t
    LEFT JOIN teacher_departments td ON t.id = td.teacher_id
    LEFT JOIN departments d ON td.department_id = d.id
    WHERE t.status = 'ACTIVE'
    GROUP BY t.id
    ORDER BY t.name
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $faculty_id = filter_input(INPUT_POST, 'faculty_id', FILTER_VALIDATE_INT);

    if (!$course_id || !$faculty_id) {
        $error = 'Please select both a subject and a faculty member.';
    } else {
        $stmt = $db->prepare("SELECT id FROM course_assignments WHERE course_id = ? AND faculty_id = ?");
        $stmt->execute([$course_id, $faculty_id]);
        if ($stmt->fetch()) {
            $error = 'This faculty member is already assigned to this subject.';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO course_assignments (course_id, faculty_id) VALUES (?, ?)");
                $stmt->execute([$course_id, $faculty_id]);
                header('Location: subject_assignments.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Error assigning subject.';
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-3xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-6 h-6 text-indigo-500"></i> Assign Subject
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Assign a faculty member to teach a subject.</p>
            </div>
            <a href="subject_assignments.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Assignments
            </a>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden p-6 sm:p-8">
            <?php if ($error): ?>
                <div class="mb-6 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 p-4 rounded-xl text-sm border border-rose-200 dark:border-rose-800 flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                    <p class="font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 p-4 rounded-xl text-sm border border-emerald-200 dark:border-emerald-800 flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
                    <p class="font-medium"><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Select Subject *</label>
                        <select name="course_id" required class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow">
                            <option value="">-- Choose Subject --</option>
                            <?php foreach($courses as $course): ?>
                                <option value="<?= $course['id'] ?>" <?= (isset($_POST['course_id']) && $_POST['course_id'] == $course['id']) ? 'selected' : '' ?>>
                                    [<?= htmlspecialchars($course['dept_code']) ?> - Sem <?= htmlspecialchars((string)$course['semester_number']) ?>] <?= htmlspecialchars($course['course_code']) ?>: <?= htmlspecialchars($course['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Select Faculty Member *</label>
                        <select name="faculty_id" required class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow">
                            <option value="">-- Choose Faculty --</option>
                            <?php foreach($faculties as $faculty): ?>
                                <option value="<?= $faculty['id'] ?>" <?= (isset($_POST['faculty_id']) && $_POST['faculty_id'] == $faculty['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($faculty['name']) ?> (<?= htmlspecialchars($faculty['dept_codes'] ?: 'No Dept') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 bg-primary hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 hover:-translate-y-0.5">
                        <i data-lucide="check" class="w-4 h-4"></i> Create Assignment
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
