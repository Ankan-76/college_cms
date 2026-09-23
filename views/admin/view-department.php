<?php
// views/admin/view-department.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('departments');
$pageTitle = 'View Department | College Management System';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: departments.php');
    exit;
}

use Config\Database;
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT * FROM departments WHERE id = ?");
$stmt->execute([$id]);
$department = $stmt->fetch();

if (!$department) {
    header('Location: departments.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="building-2" class="w-6 h-6 text-indigo-500"></i> Department Details
            </h1>
            <a href="departments.php" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 flex items-center gap-1 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Departments
            </a>
        </div>
        
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 sm:p-10 text-center border-b border-slate-200 dark:border-slate-700 bg-indigo-50/50 dark:bg-indigo-900/10">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-2xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 mb-6">
                    <i data-lucide="building" class="w-12 h-12"></i>
                </div>
                <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white mb-2"><?= htmlspecialchars($department['dept_name']) ?></h2>
                <p class="text-slate-500 font-medium">Department Code: <?= htmlspecialchars($department['dept_code']) ?></p>
            </div>
            <div class="p-6 sm:p-10">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-6 text-center">Academic Structure</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-2xl mx-auto">
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                        <span class="block text-4xl font-black text-indigo-600 dark:text-indigo-400 mb-2"><?= htmlspecialchars((string)$department['total_semesters']) ?></span>
                        <span class="text-sm font-medium text-slate-500">Total Semesters</span>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                        <span class="block text-xl font-bold text-slate-800 dark:text-slate-200 mb-3 pt-2"><?= date('F Y', strtotime($department['created_at'])) ?></span>
                        <span class="text-sm font-medium text-slate-500">Established</span>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-900/50 p-6 flex justify-end gap-3 border-t border-slate-200 dark:border-slate-700">
                <a href="edit-department.php?id=<?= $department['id'] ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold flex items-center gap-2 transition-colors">
                    <i data-lucide="edit-2" class="w-4 h-4"></i> Edit Department
                </a>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
