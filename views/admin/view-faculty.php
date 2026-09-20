<?php
// views/admin/view-faculty.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'View Faculty | College Management System';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: faculty.php');
    exit;
}

use Config\Database;
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT t.*, GROUP_CONCAT(d.dept_name ORDER BY d.dept_name ASC SEPARATOR ', ') as dept_names
    FROM teachers t
    LEFT JOIN teacher_departments td ON t.id = td.teacher_id
    LEFT JOIN departments d ON td.department_id = d.id
    WHERE t.id = ?
    GROUP BY t.id
");
$stmt->execute([$id]);
$faculty = $stmt->fetch();

if (!$faculty) {
    header('Location: faculty.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="briefcase" class="w-6 h-6 text-emerald-500"></i> Faculty Details
            </h1>
            <a href="faculty.php" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 flex items-center gap-1 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Faculty
            </a>
        </div>
        
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 sm:p-10 flex flex-col md:flex-row gap-8 items-center md:items-start border-b border-slate-200 dark:border-slate-700">
                <img src="<?= $faculty['profile_pic'] ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($faculty['profile_pic']) : 'https://ui-avatars.com/api/?name=' . urlencode($faculty['name']) . '&background=10b981&color=fff' ?>" alt="Profile" class="w-32 h-32 rounded-2xl object-cover shadow-md border-4 border-slate-50 dark:border-slate-700">
                <div class="text-center md:text-left flex-1">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white mb-2"><?= htmlspecialchars($faculty['name']) ?></h2>
                    <p class="text-slate-500 font-medium mb-4"><?= htmlspecialchars($faculty['email']) ?></p>
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <i data-lucide="building" class="w-3.5 h-3.5"></i> <?= htmlspecialchars($faculty['dept_names'] ?: 'No Department') ?>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                            <i data-lucide="award" class="w-3.5 h-3.5"></i> <?= htmlspecialchars($faculty['designation']) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="p-6 sm:p-10 grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Professional Information</h3>
                    <ul class="space-y-4">
                        <li class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/50 pb-3">
                            <span class="text-sm font-medium text-slate-500">Qualifications</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($faculty['qualification']) ?></span>
                        </li>
                        <li class="flex items-center justify-between pb-3">
                            <span class="text-sm font-medium text-slate-500">Joined On</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= date('F j, Y', strtotime($faculty['created_at'])) ?></span>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Personal Details</h3>
                    <ul class="space-y-4">
                        <li class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/50 pb-3">
                            <span class="text-sm font-medium text-slate-500">Phone Number</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($faculty['phone'] ?? 'N/A') ?></span>
                        </li>
                        <li class="flex items-center justify-between pb-3">
                            <span class="text-sm font-medium text-slate-500">Status</span>
                            <span class="text-sm font-bold <?= $faculty['status'] === 'ACTIVE' ? 'text-emerald-500' : 'text-rose-500' ?>"><?= htmlspecialchars($faculty['status']) ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-900/50 p-6 flex justify-end gap-3 border-t border-slate-200 dark:border-slate-700">
                <a href="edit-faculty.php?id=<?= $faculty['id'] ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold flex items-center gap-2 transition-colors">
                    <i data-lucide="edit-2" class="w-4 h-4"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
