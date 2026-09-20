<?php
// views/student/my_subjects.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('STUDENT');
$pageTitle = 'My Subjects | Student Portal';

use Config\Database;
$db = Database::getInstance()->getConnection();
$studentId = $_SESSION['user_id'];

// Get student's department and semester
$stmtStudent = $db->prepare("SELECT department_id, semester_id FROM students WHERE id = ?");
$stmtStudent->execute([$studentId]);
$student = $stmtStudent->fetch();
$departmentId = $student['department_id'] ?? 0;
$semesterId = $student['semester_id'] ?? 0;

// Fetch all courses with faculty assignments
$courseStmt = $db->prepare("
    SELECT c.*, d.dept_name, s.semester_number,
           GROUP_CONCAT(DISTINCT t.name ORDER BY t.name ASC SEPARATOR ', ') as faculty_names
    FROM courses c
    JOIN departments d ON c.department_id = d.id
    JOIN semesters s ON c.semester_id = s.id
    LEFT JOIN course_assignments ca ON c.id = ca.course_id
    LEFT JOIN teachers t ON ca.faculty_id = t.id
    WHERE c.department_id = ? AND c.semester_id = ?
    GROUP BY c.id
    ORDER BY c.course_code ASC
");
$courseStmt->execute([$departmentId, $semesterId]);
$courses = $courseStmt->fetchAll();

$totalCredits = array_sum(array_column($courses, 'credits'));

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="book-open" class="w-6 h-6 text-indigo-500"></i> My Subjects
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Courses enrolled for the current semester · <strong><?= $totalCredits ?></strong> total credits</p>
            </div>
        </div>

        <?php if (empty($courses)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 mb-4">
                    <i data-lucide="book-x" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Subjects Found</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">No courses are registered for your department and semester yet.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($courses as $course): 
                    $colors = ['indigo', 'emerald', 'amber', 'purple', 'rose', 'cyan'];
                    $colorIdx = crc32($course['course_code']) % count($colors);
                    $color = $colors[$colorIdx];
                ?>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transform transition-all duration-300 hover:shadow-md hover:-translate-y-1 group">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-700/50 bg-gradient-to-br from-slate-50 to-white dark:from-slate-800 dark:to-slate-800/80">
                        <div class="flex justify-between items-start mb-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-<?= $color ?>-100 text-<?= $color ?>-800 dark:bg-<?= $color ?>-900/40 dark:text-<?= $color ?>-300 border border-<?= $color ?>-200 dark:border-<?= $color ?>-800/50">
                                <?= htmlspecialchars($course['course_code']) ?>
                            </span>
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700/50 px-2 py-1 rounded">
                                <?= $course['credits'] ?> Credits
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white line-clamp-2" title="<?= htmlspecialchars($course['course_name']) ?>">
                            <?= htmlspecialchars($course['course_name']) ?>
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
                            <i data-lucide="building-2" class="w-4 h-4"></i> <?= htmlspecialchars($course['dept_name']) ?>
                        </p>
                    </div>
                    <div class="p-4 bg-white dark:bg-slate-800">
                        <?php if (!empty($course['faculty_names'])): ?>
                        <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 shrink-0"></i>
                            <span class="font-medium truncate"><?= htmlspecialchars($course['faculty_names']) ?></span>
                        </div>
                        <?php else: ?>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <i data-lucide="user-x" class="w-4 h-4 shrink-0"></i>
                            <span class="font-medium">Faculty not assigned</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
