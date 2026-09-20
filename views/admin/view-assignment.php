<?php
// views/admin/view-assignment.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'View Assignment | College Management System';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: subject_assignments.php');
    exit;
}

use Config\Database;
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT 
        ca.id,
        c.course_code, c.course_name, c.credits,
        t.name AS faculty_name, t.email as faculty_email, t.profile_pic,
        d.dept_code, d.dept_name, s.semester_number, s.academic_year
    FROM course_assignments ca
    JOIN courses c ON ca.course_id = c.id
    JOIN teachers t ON ca.faculty_id = t.id
    JOIN departments d ON c.department_id = d.id
    JOIN semesters s ON c.semester_id = s.id
    WHERE ca.id = ?
");
$stmt->execute([$id]);
$assignment = $stmt->fetch();

if (!$assignment) {
    header('Location: subject_assignments.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="clipboard-check" class="w-6 h-6 text-indigo-500"></i> Subject Assignment Details
            </h1>
            <a href="subject_assignments.php" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 flex items-center gap-1 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Assignments
            </a>
        </div>
        
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <!-- Course Info -->
                <div class="p-8 border-b md:border-b-0 md:border-r border-slate-200 dark:border-slate-700 bg-indigo-50/30 dark:bg-indigo-900/10">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="book-open" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Subject Details</h3>
                        </div>
                    </div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white mb-1"><?= htmlspecialchars($assignment['course_name']) ?></h2>
                    <p class="text-slate-500 font-medium mb-6"><?= htmlspecialchars($assignment['course_code']) ?></p>
                    
                    <ul class="space-y-4">
                        <li class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                            <span class="text-sm font-medium text-slate-500">Department</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($assignment['dept_name']) ?></span>
                        </li>
                        <li class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                            <span class="text-sm font-medium text-slate-500">Semester</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars((string)$assignment['semester_number']) ?> (<?= htmlspecialchars($assignment['academic_year']) ?>)</span>
                        </li>
                        <li class="flex items-center justify-between pb-3">
                            <span class="text-sm font-medium text-slate-500">Credits</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars((string)$assignment['credits']) ?></span>
                        </li>
                    </ul>
                </div>
                
                <!-- Faculty Info -->
                <div class="p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="user-check" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Assigned Faculty</h3>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4 mb-6 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700/50">
                        <img src="<?= $assignment['profile_pic'] ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($assignment['profile_pic']) : 'https://ui-avatars.com/api/?name=' . urlencode($assignment['faculty_name']) . '&background=10b981&color=fff' ?>" alt="Profile" class="w-16 h-16 rounded-full object-cover shadow-sm">
                        <div>
                            <h2 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white"><?= htmlspecialchars($assignment['faculty_name']) ?></h2>
                            <p class="text-sm text-slate-500"><?= htmlspecialchars($assignment['faculty_email']) ?></p>
                        </div>
                    </div>
                    
                    <p class="text-sm text-slate-500 leading-relaxed">
                        This faculty member has full administrative rights over the attendance and grading ledgers for <strong><?= htmlspecialchars($assignment['course_code']) ?></strong> during the active session.
                    </p>
                </div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-900/50 p-6 flex justify-end gap-3 border-t border-slate-200 dark:border-slate-700">
                <form action="delete-assignment.php" method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to revoke this assignment?');">
                    <input type="hidden" name="id" value="<?= $assignment['id'] ?>">
                    <button type="submit" class="px-4 py-2 bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 dark:text-rose-400 rounded-lg text-sm font-bold flex items-center gap-2 transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Revoke Assignment
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
