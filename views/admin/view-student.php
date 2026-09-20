<?php
// views/admin/view-student.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'View Student | College Management System';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: students.php');
    exit;
}

use Config\Database;
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT s.*, d.dept_name, sem.semester_number, sem.academic_year
    FROM students s
    JOIN departments d ON s.department_id = d.id
    JOIN semesters sem ON s.semester_id = sem.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: students.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="user" class="w-6 h-6 text-indigo-500"></i> Student Details
            </h1>
            <a href="students.php" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 flex items-center gap-1 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Students
            </a>
        </div>
        
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 sm:p-10 flex flex-col md:flex-row gap-8 items-center md:items-start border-b border-slate-200 dark:border-slate-700">
                <img src="<?= $student['profile_pic'] ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($student['profile_pic']) : 'https://ui-avatars.com/api/?name=' . urlencode($student['name']) . '&background=4f46e5&color=fff' ?>" alt="Profile" class="w-32 h-32 rounded-2xl object-cover shadow-md border-4 border-slate-50 dark:border-slate-700">
                <div class="text-center md:text-left flex-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white mb-2"><?= htmlspecialchars($student['name']) ?></h2>
                    <p class="text-slate-500 font-medium mb-4"><?= htmlspecialchars($student['email']) ?></p>
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                            <i data-lucide="building" class="w-3.5 h-3.5"></i> <?= htmlspecialchars($student['dept_name']) ?>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400">
                            <i data-lucide="book-open" class="w-3.5 h-3.5"></i> Semester <?= htmlspecialchars((string)$student['semester_number']) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="p-6 sm:p-10 grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Academic Information</h3>
                    <ul class="space-y-4">
                        <li class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/50 pb-3">
                            <span class="text-sm font-medium text-slate-500">Roll Number</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($student['roll_number']) ?></span>
                        </li>
                        <li class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/50 pb-3">
                            <span class="text-sm font-medium text-slate-500">Registration No.</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($student['registration_number']) ?></span>
                        </li>
                        <li class="flex items-center justify-between pb-3">
                            <span class="text-sm font-medium text-slate-500">Academic Year</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($student['academic_year']) ?></span>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Personal Details</h3>
                    <ul class="space-y-4">
                        <li class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/50 pb-3">
                            <span class="text-sm font-medium text-slate-500">Phone Number</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></span>
                        </li>
                        <li class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/50 pb-3">
                            <span class="text-sm font-medium text-slate-500">Status</span>
                            <span class="text-sm font-bold <?= $student['status'] === 'ACTIVE' ? 'text-emerald-500' : 'text-rose-500' ?>"><?= htmlspecialchars($student['status']) ?></span>
                        </li>
                        <li class="flex items-center justify-between pb-3">
                            <span class="text-sm font-medium text-slate-500">Joined On</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= date('F j, Y', strtotime($student['created_at'])) ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-900/50 p-6 flex justify-end gap-3 border-t border-slate-200 dark:border-slate-700">
                <a href="edit-student.php?id=<?= $student['id'] ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold flex items-center gap-2 transition-colors">
                    <i data-lucide="edit-2" class="w-4 h-4"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
