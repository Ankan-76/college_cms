<?php
// views/admin/view-subject.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('subjects');
$pageTitle = 'View Subject | College Management System';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: subjects.php');
    exit;
}

use Config\Database;
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT c.*, d.dept_name, s.semester_number, s.academic_year
    FROM courses c
    JOIN departments d ON c.department_id = d.id
    JOIN semesters s ON c.semester_id = s.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: subjects.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="book-open" class="w-6 h-6 text-indigo-500"></i> Subject Details
            </h1>
            <a href="subjects.php" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 flex items-center gap-1 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Subjects
            </a>
        </div>
        
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 sm:p-10 text-center border-b border-slate-200 dark:border-slate-700 bg-indigo-50/50 dark:bg-indigo-900/10">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-2xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 mb-6">
                    <i data-lucide="book" class="w-12 h-12"></i>
                </div>
                <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white mb-2"><?= htmlspecialchars($course['course_name']) ?></h2>
                <p class="text-slate-500 font-medium">Course Code: <?= htmlspecialchars($course['course_code']) ?></p>
            </div>
            <div class="p-6 sm:p-10">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-6 text-center">Curriculum Map</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                        <span class="block text-xl font-bold text-slate-800 dark:text-slate-200 mb-2 truncate" title="<?= htmlspecialchars($course['dept_name']) ?>"><?= htmlspecialchars($course['dept_name']) ?></span>
                        <span class="text-sm font-medium text-slate-500">Department</span>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                        <span class="block text-xl font-bold text-slate-800 dark:text-slate-200 mb-2">Sem <?= htmlspecialchars((string)$course['semester_number']) ?></span>
                        <span class="text-sm font-medium text-slate-500">Semester</span>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                        <span class="block text-3xl font-black text-indigo-600 dark:text-indigo-400 mb-1"><?= htmlspecialchars((string)$course['credits']) ?></span>
                        <span class="text-sm font-medium text-slate-500">Credits</span>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-900/50 p-6 flex justify-end gap-3 border-t border-slate-200 dark:border-slate-700">
                <a href="edit-subject.php?id=<?= $course['id'] ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold flex items-center gap-2 transition-colors">
                    <i data-lucide="edit-2" class="w-4 h-4"></i> Edit Subject
                </a>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
