<?php
// views/admin/faculty.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'Manage Faculty | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$departments = $db->query("SELECT id, dept_code FROM departments ORDER BY dept_code")->fetchAll();

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$department_id = $_GET['department_id'] ?? '';

$query = "
    SELECT t.id, t.name, t.email, t.phone, t.status, t.designation, t.qualification, t.created_at,
           GROUP_CONCAT(d.dept_code ORDER BY d.dept_code ASC SEPARATOR ', ') as dept_codes
    FROM teachers t
    LEFT JOIN teacher_departments td ON t.id = td.teacher_id
    LEFT JOIN departments d ON td.department_id = d.id
    WHERE 1=1
";
$params = [];

if ($search) {
    $query .= " AND (t.name LIKE ? OR t.email LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}
if ($status) {
    $query .= " AND t.status = ?";
    $params[] = $status;
}
if ($department_id) {
    $query .= " AND EXISTS (SELECT 1 FROM teacher_departments ftd WHERE ftd.teacher_id = t.id AND ftd.department_id = ?)";
    $params[] = $department_id;
}

$query .= " GROUP BY t.id ORDER BY t.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$faculties = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="users" class="w-6 h-6 text-indigo-500"></i> Faculty Directory
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage teaching staff and their department allocations.</p>
            </div>
            <a href="add-faculty.php" class="inline-flex items-center gap-2 bg-primary hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-sm focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 hover:-translate-y-0.5">
                <i data-lucide="user-plus" class="w-4 h-4"></i> Add Faculty
            </a>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6 mb-6">
            <form method="GET" action="" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Search Faculty</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                        </div>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name or Email..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 dark:text-white transition-colors outline-none">
                    </div>
                </div>

                <div class="w-full md:w-48">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 dark:text-white transition-colors appearance-none outline-none">
                        <option value="">All Status</option>
                        <option value="ACTIVE" <?= $status === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                        <option value="INACTIVE" <?= $status === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="w-full md:w-48">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Department</label>
                    <select name="department_id" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 dark:text-white transition-colors appearance-none outline-none">
                        <option value="">All Departments</option>
                        <?php foreach($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $department_id == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['dept_code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm font-semibold transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm outline-none">
                    Apply
                </button>
                
                <a href="faculty.php" class="w-full md:w-auto px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg text-sm transition-colors shadow-sm flex items-center justify-center outline-none" title="Reset Filters">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </a>
            </form>
        </div>

        <div class="mb-2">
            <h2 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Found <?= count($faculties) ?> Faculty Members</h2>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Faculty Member</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Designation</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <?php foreach($faculties as $faculty): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-700 dark:text-indigo-400 font-bold border border-indigo-200 dark:border-indigo-800">
                                        <?= strtoupper(substr($faculty['name'], 0, 1)) ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($faculty['name']) ?></div>
                                        <div class="text-sm text-slate-500 dark:text-slate-400"><?= htmlspecialchars($faculty['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-900 dark:text-white font-medium"><?= htmlspecialchars($faculty['designation']) ?></div>
                                <div class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($faculty['qualification']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800 px-2.5 py-1 rounded-md text-xs font-bold tracking-wider">
                                    <?= htmlspecialchars($faculty['dept_codes'] ?: 'NONE') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($faculty['status'] === 'ACTIVE'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="view-faculty.php?id=<?= $faculty['id'] ?>" class="inline-block text-sky-500 hover:text-sky-700 hover:bg-sky-50 dark:hover:bg-sky-900/50 p-1.5 rounded transition-colors outline-none" title="View Details"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                    <a href="edit-faculty.php?id=<?= $faculty['id'] ?>" class="inline-block text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/50 p-1.5 rounded transition-colors outline-none"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                                    <form action="delete-faculty.php" method="POST" class="inline-block m-0 p-0" onsubmit="return confirm('Are you sure you want to delete this faculty member?');">
                                        <input type="hidden" name="id" value="<?= $faculty['id'] ?>">
                                        <button type="submit" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/50 p-1.5 rounded transition-colors outline-none"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($faculties)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3">
                                    <i data-lucide="users" class="w-6 h-6"></i>
                                </div>
                                <p class="text-sm font-medium text-slate-500">No faculty members found.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
