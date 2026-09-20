<?php
// views/faculty/my_subjects.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'My Subjects | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/AttendanceController.php';

use Controllers\AttendanceController;

$controller = new AttendanceController();
$courses = $controller->getFacultyCourses($_SESSION['faculty_profile_id']);

?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="book-open" class="w-6 h-6 text-indigo-500"></i> My Assigned Subjects
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">View the courses and subjects you are currently assigned to teach.</p>
            </div>
        </div>

        <?php if (empty($courses)): ?>
            <!-- Empty State -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm animate-fade-in">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 mb-4 transition-transform hover:scale-110 duration-300">
                    <i data-lucide="book-x" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Subjects Assigned</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">You have not been assigned to any subjects for the current academic session.</p>
            </div>
        <?php else: ?>
            <!-- Subjects Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($courses as $course): ?>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transform transition-all duration-300 hover:shadow-md hover:-translate-y-1 group">
                        <div class="p-5 border-b border-slate-100 dark:border-slate-700/50 bg-gradient-to-br from-slate-50 to-white dark:from-slate-800 dark:to-slate-800/80">
                            <div class="flex justify-between items-start mb-4">
                                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50">
                                    <?= htmlspecialchars($course['course_code']) ?>
                                </div>
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700/50 px-2 py-1 rounded">
                                    Sem <?= htmlspecialchars((string)$course['semester_number']) ?>
                                </span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white line-clamp-2" title="<?= htmlspecialchars($course['course_name']) ?>">
                                <?= htmlspecialchars($course['course_name']) ?>
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
                                <i data-lucide="building-2" class="w-4 h-4"></i> <?= htmlspecialchars($course['dept_name']) ?>
                            </p>
                        </div>
                        <div class="p-4 bg-white dark:bg-slate-800 flex justify-between items-center gap-3">
                            <a href="my_students.php?course_id=<?= $course['id'] ?>" class="flex-1 flex justify-center items-center gap-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 dark:bg-slate-700/50 dark:hover:bg-slate-700 dark:text-slate-200 rounded-lg text-sm font-medium transition-colors border border-slate-200 dark:border-slate-600">
                                <i data-lucide="users" class="w-4 h-4 text-slate-500 dark:text-slate-400"></i> Students
                            </a>
                            <a href="take_attendance.php?course_id=<?= $course['id'] ?>" class="flex-1 flex justify-center items-center gap-2 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-300 rounded-lg text-sm font-medium transition-colors border border-indigo-200 dark:border-indigo-800/50">
                                <i data-lucide="clipboard-check" class="w-4 h-4 text-indigo-500 dark:text-indigo-400"></i> Attendance
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<style>
/* Smooth fade-in animation for empty state */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
