<?php
// views/admin/manage-admins.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
require_permission('manage_admins');

$pageTitle = 'Manage Admins | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

// Filters
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$query = "SELECT id, name, email, phone, role, status, profile_pic, created_at FROM admins WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($roleFilter)) {
    $query .= " AND role = ?";
    $params[] = $roleFilter;
}
if (!empty($statusFilter)) {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY id ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct roles for filter dropdown
$roles = $db->query("SELECT DISTINCT role FROM admins ORDER BY role")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-7 h-7 text-indigo-600 dark:text-indigo-400"></i>
                    Manage Admins
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Create and manage administrator accounts with role-based access control.</p>
            </div>
            <a href="<?= BASE_URL ?>/views/admin/add-admin.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition-all shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5">
                <i data-lucide="user-plus" class="w-4 h-4"></i> Add New Admin
            </a>
        </div>

        <!-- Flash Message -->
        <?php 
        $flash = get_flash_message();
        if ($flash): 
            $bgClass = $flash['type'] === 'success' 
                ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800' 
                : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800';
            $icon = $flash['type'] === 'success' ? 'check-circle' : 'alert-circle';
        ?>
            <div class="p-4 rounded-xl border flex items-start gap-3 <?= $bgClass ?>">
                <i data-lucide="<?= $icon ?>" class="w-5 h-5 shrink-0 mt-0.5"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Search</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name or email..." class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Role</label>
                    <select name="role" class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= htmlspecialchars($r) ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Status</label>
                    <select name="status" class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                        <option value="">All Statuses</option>
                        <option value="ACTIVE" <?= $statusFilter === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                        <option value="INACTIVE" <?= $statusFilter === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors">
                        <i data-lucide="filter" class="w-4 h-4"></i> Filter
                    </button>
                    <a href="<?= BASE_URL ?>/views/admin/manage-admins.php" class="inline-flex items-center justify-center px-3 py-2.5 border border-slate-200 dark:border-slate-600 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="Clear filters">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <?php
            $totalAdmins = count($admins);
            $activeAdmins = count(array_filter($admins, fn($a) => $a['status'] === 'ACTIVE'));
            $inactiveAdmins = $totalAdmins - $activeAdmins;
            $uniqueRoles = count(array_unique(array_column($admins, 'role')));
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                        <i data-lucide="users" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800 dark:text-white"><?= $totalAdmins ?></div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800 dark:text-white"><?= $activeAdmins ?></div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active</div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center">
                        <i data-lucide="x-circle" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800 dark:text-white"><?= $inactiveAdmins ?></div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Inactive</div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                        <i data-lucide="crown" class="w-5 h-5 text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800 dark:text-white"><?= $uniqueRoles ?></div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Roles</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admins Table -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Admin</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <?php if (empty($admins)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i data-lucide="user-x" class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3"></i>
                                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No admin accounts found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admins as $idx => $admin): ?>
                                <?php
                                $isSelf = ($admin['id'] == $_SESSION['user_id']);
                                $isSuperAdmin = ($admin['role'] === 'SUPER ADMIN');
                                $picUrl = !empty($admin['profile_pic'])
                                    ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($admin['profile_pic'])
                                    : "https://ui-avatars.com/api/?name=" . urlencode($admin['name']) . "&background=4f46e5&color=fff&bold=true";
                                
                                // Role badge colors
                                $roleBadgeClass = match($admin['role']) {
                                    'SUPER ADMIN' => 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white',
                                    'HOD' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                    'Principal' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    'Vice Principal' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
                                    'Chairman' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                                    'Cashier' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                                    default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                };
                                ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 font-medium"><?= $idx + 1 ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <img class="w-10 h-10 rounded-full object-cover border-2 border-slate-200 dark:border-slate-600 shadow-sm" src="<?= $picUrl ?>" alt="">
                                            <div>
                                                <div class="text-sm font-semibold text-slate-800 dark:text-white flex items-center gap-2">
                                                    <?= htmlspecialchars($admin['name']) ?>
                                                    <?php if ($isSelf): ?>
                                                        <span class="text-[10px] font-bold bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 px-1.5 py-0.5 rounded-full uppercase">You</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($admin['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold <?= $roleBadgeClass ?>">
                                            <?php if ($isSuperAdmin): ?><i data-lucide="crown" class="w-3 h-3"></i><?php endif; ?>
                                            <?= htmlspecialchars($admin['role']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($admin['status'] === 'ACTIVE'): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                        <?= date('d M Y', strtotime($admin['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <?php if (!$isSelf): ?>
                                                <a href="<?= BASE_URL ?>/views/admin/edit-admin.php?id=<?= $admin['id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 rounded-lg transition-colors" title="Edit Admin">
                                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit
                                                </a>
                                                <?php if (!$isSuperAdmin): ?>
                                                    <form method="POST" action="<?= BASE_URL ?>/views/admin/delete-admin.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this admin account? This action cannot be undone.')">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 hover:bg-rose-100 dark:hover:bg-rose-900/40 rounded-lg transition-colors" title="Delete Admin">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400 dark:text-slate-500 italic">Current session</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
