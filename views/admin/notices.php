<?php
// views/admin/notices.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('notices');
$pageTitle = 'Manage Notices | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$search = $_GET['search'] ?? '';
$target_role = $_GET['target_role'] ?? '';

$query = "
    SELECT n.*, a.name as author
    FROM notices n
    JOIN admins a ON n.created_by = a.id
    WHERE 1=1
";
$params = [];

if ($search) {
    $query .= " AND n.title LIKE ?";
    $params[] = "%$search%";
}

if ($target_role) {
    $query .= " AND n.target_role = ?";
    $params[] = $target_role;
}

$query .= " ORDER BY n.is_pinned DESC, n.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$notices = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="bell" class="w-6 h-6 text-indigo-500"></i> Communication Board
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage announcements and institutional notices.</p>
            </div>
            <a href="add-notice.php" class="inline-flex items-center gap-2 bg-primary hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-sm focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 hover:-translate-y-0.5">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Create Notice
            </a>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6 mb-6">
            <form method="GET" action="" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Search Notices</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                        </div>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Notice Title..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 dark:text-white transition-colors outline-none">
                    </div>
                </div>

                <div class="w-full md:w-48">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Target Role</label>
                    <select name="target_role" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 dark:text-white transition-colors appearance-none outline-none">
                        <option value="">All Roles</option>
                        <option value="ALL" <?= $target_role === 'ALL' ? 'selected' : '' ?>>All</option>
                        <option value="STUDENT" <?= $target_role === 'STUDENT' ? 'selected' : '' ?>>Student</option>
                        <option value="FACULTY" <?= $target_role === 'FACULTY' ? 'selected' : '' ?>>Faculty</option>
                        <option value="STAFF" <?= $target_role === 'STAFF' ? 'selected' : '' ?>>Staff</option>
                    </select>
                </div>
                
                <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm font-semibold transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm outline-none">
                    Apply
                </button>
                
                <a href="notices.php" class="w-full md:w-auto px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg text-sm transition-colors shadow-sm flex items-center justify-center outline-none" title="Reset Filters">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </a>
            </form>
        </div>

        <div class="mb-2">
            <h2 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Found <?= count($notices) ?> Notices</h2>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Target</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Author & Date</th>
                            <th class="px-6 py-4 text-center text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pinned</th>
                            <th class="px-6 py-4 text-right text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <?php foreach($notices as $notice): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($notice['title']) ?></div>
                                <div class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-0.5">Slug: <?= htmlspecialchars($notice['slug']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $roleClass = 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
                                if ($notice['target_role'] === 'ALL') $roleClass = 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800';
                                else if ($notice['target_role'] === 'FACULTY') $roleClass = 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 border-purple-200 dark:border-purple-800';
                                else if ($notice['target_role'] === 'STUDENT') $roleClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $roleClass ?>">
                                    <?= htmlspecialchars($notice['target_role']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-900 dark:text-slate-200 font-medium"><?= htmlspecialchars($notice['author']) ?></div>
                                <div class="text-xs text-slate-500 dark:text-slate-400"><?= date('M j, Y g:i A', strtotime($notice['created_at'])) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if ($notice['is_pinned']): ?>
                                    <i data-lucide="pin" class="w-4 h-4 text-amber-500 mx-auto fill-amber-500/20"></i>
                                <?php else: ?>
                                    <span class="text-slate-300 dark:text-slate-600">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="view-notice.php?id=<?= $notice['id'] ?>" class="inline-block text-sky-500 hover:text-sky-700 hover:bg-sky-50 dark:hover:bg-sky-900/50 p-1.5 rounded transition-colors outline-none" title="View Details"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                    <a href="edit-notice.php?id=<?= $notice['id'] ?>" class="inline-block text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/50 p-1.5 rounded transition-colors outline-none"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                                    <form action="delete-notice.php" method="POST" class="inline-block m-0 p-0" onsubmit="return confirm('Are you sure you want to delete this notice?');">
                                        <input type="hidden" name="id" value="<?= $notice['id'] ?>">
                                        <button type="submit" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/50 p-1.5 rounded transition-colors outline-none"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($notices)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3">
                                    <i data-lucide="bell" class="w-6 h-6"></i>
                                </div>
                                <p class="text-sm font-medium text-slate-500">No notices published yet.</p>
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
