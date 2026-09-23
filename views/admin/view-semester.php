<?php
// views/admin/view-semester.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('semesters');
$pageTitle = 'View Semester | College Management System';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: semesters.php');
    exit;
}

use Config\Database;
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT * FROM semesters WHERE id = ?");
$stmt->execute([$id]);
$semester = $stmt->fetch();

if (!$semester) {
    header('Location: semesters.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="calendar" class="w-6 h-6 text-indigo-500"></i> Semester Details
            </h1>
            <a href="semesters.php" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 flex items-center gap-1 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Semesters
            </a>
        </div>
        
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 sm:p-10 text-center border-b border-slate-200 dark:border-slate-700 bg-indigo-50/50 dark:bg-indigo-900/10">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-2xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 mb-6">
                    <span class="text-4xl font-black"><?= htmlspecialchars((string)$semester['semester_number']) ?></span>
                </div>
                <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white mb-2">Semester <?= htmlspecialchars((string)$semester['semester_number']) ?></h2>
                <div class="flex items-center justify-center gap-2 mb-4">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300">
                        <?= htmlspecialchars($semester['academic_year']) ?>
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $semester['status'] === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400' ?>">
                        <?= htmlspecialchars($semester['status']) ?>
                    </span>
                </div>
            </div>
            <div class="p-6 sm:p-10">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-6 text-center">Duration Timeline</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-2xl mx-auto">
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                        <span class="block text-xl font-bold text-slate-800 dark:text-slate-200 mb-2"><?= $semester['start_date'] ? date('M j, Y', strtotime($semester['start_date'])) : 'Not Set' ?></span>
                        <span class="text-sm font-medium text-slate-500">Start Date</span>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                        <span class="block text-xl font-bold text-slate-800 dark:text-slate-200 mb-2"><?= $semester['end_date'] ? date('M j, Y', strtotime($semester['end_date'])) : 'Not Set' ?></span>
                        <span class="text-sm font-medium text-slate-500">End Date</span>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-900/50 p-6 flex justify-end gap-3 border-t border-slate-200 dark:border-slate-700">
                <a href="edit-semester.php?id=<?= $semester['id'] ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold flex items-center gap-2 transition-colors">
                    <i data-lucide="edit-2" class="w-4 h-4"></i> Edit Semester
                </a>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
