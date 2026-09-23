<?php
// views/admin/edit-admin.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('ADMIN');
require_permission('manage_admins');

$pageTitle = 'Edit Admin | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$adminId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$adminId) {
    set_flash_message('Invalid admin ID.', 'error');
    redirect('/views/admin/manage-admins.php');
}

// Prevent editing your own account through this page
if ($adminId == $_SESSION['user_id']) {
    set_flash_message('Use the profile page to edit your own account.', 'error');
    redirect('/views/admin/manage-admins.php');
}

// Fetch admin details
$stmt = $db->prepare("SELECT id, name, email, phone, role, status, profile_pic FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    set_flash_message('Admin account not found.', 'error');
    redirect('/views/admin/manage-admins.php');
}

$isSuperAdmin = ($admin['role'] === 'SUPER ADMIN');

// Fetch all modules for permissions checklist (exclude manage_admins — only Super Admin gets that)
$modulesStmt = $db->query("SELECT id, module_key, module_name, module_group, icon FROM modules WHERE module_key != 'manage_admins' ORDER BY sort_order ASC");
$allModules = $modulesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch current permissions for this admin
$permStmt = $db->prepare("SELECT module_id FROM admin_permissions WHERE admin_id = ?");
$permStmt->execute([$adminId]);
$currentPermissions = $permStmt->fetchAll(PDO::FETCH_COLUMN);

// Available roles
$availableRoles = ['HOD', 'Principal', 'Vice Principal', 'Chairman', 'Cashier', 'Sub Admin'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Invalid CSRF token.', 'error');
        redirect("/views/admin/edit-admin.php?id={$adminId}");
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? '');
    $status = $_POST['status'] ?? 'ACTIVE';
    $selectedPermissions = $_POST['permissions'] ?? [];

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = 'Name is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (!empty($password) && strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (!empty($password) && $password !== $confirmPassword) $errors[] = 'Passwords do not match.';
    if (!in_array($status, ['ACTIVE', 'INACTIVE'])) $errors[] = 'Invalid status.';
    
    // Only validate role if not Super Admin (Super Admin role cannot be changed)
    if (!$isSuperAdmin) {
        if (empty($role) || !in_array($role, $availableRoles)) $errors[] = 'Please select a valid role.';
    } else {
        $role = 'SUPER ADMIN'; // Preserve Super Admin role
    }

    // Check duplicate email (excluding current admin)
    if (empty($errors)) {
        $checkStmt = $db->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
        $checkStmt->execute([$email, $adminId]);
        if ($checkStmt->fetch()) {
            $errors[] = 'An admin with this email already exists.';
        }
    }

    if (!empty($errors)) {
        set_flash_message(implode(' ', $errors), 'error');
    } else {
        try {
            $db->beginTransaction();

            // Update admin details
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_ARGON2ID);
                $updateStmt = $db->prepare("UPDATE admins SET name = ?, email = ?, phone = ?, password_hash = ?, role = ?, status = ? WHERE id = ?");
                $updateStmt->execute([$name, $email, $phone ?: null, $hash, $role, $status, $adminId]);
            } else {
                $updateStmt = $db->prepare("UPDATE admins SET name = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?");
                $updateStmt->execute([$name, $email, $phone ?: null, $role, $status, $adminId]);
            }

            // Update permissions (only for non-Super-Admins)
            if (!$isSuperAdmin) {
                // Clear existing permissions
                $db->prepare("DELETE FROM admin_permissions WHERE admin_id = ?")->execute([$adminId]);
                
                // Insert new permissions
                if (!empty($selectedPermissions)) {
                    // Always ensure dashboard access
                    $dashStmt = $db->prepare("SELECT id FROM modules WHERE module_key = 'dashboard'");
                    $dashStmt->execute();
                    $dashId = $dashStmt->fetchColumn();
                    if ($dashId && !in_array((string)$dashId, $selectedPermissions)) {
                        $selectedPermissions[] = (string)$dashId;
                    }
                    
                    $permInsert = $db->prepare("INSERT INTO admin_permissions (admin_id, module_id) VALUES (?, ?)");
                    foreach ($selectedPermissions as $moduleId) {
                        $moduleId = (int)$moduleId;
                        if ($moduleId > 0) {
                            $permInsert->execute([$adminId, $moduleId]);
                        }
                    }
                }
            }

            $db->commit();
            set_flash_message("Admin '{$name}' updated successfully.", 'success');
            redirect('/views/admin/manage-admins.php');
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Edit admin error: " . $e->getMessage());
            set_flash_message('Failed to update admin account. Please try again.', 'error');
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-3xl mx-auto space-y-6">

        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <i data-lucide="edit-3" class="w-7 h-7 text-indigo-600 dark:text-indigo-400"></i>
                    Edit Admin
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Update account details and permissions for <strong><?= htmlspecialchars($admin['name']) ?></strong>.</p>
            </div>
            <a href="<?= BASE_URL ?>/views/admin/manage-admins.php" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 dark:border-slate-600 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
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

        <!-- Super Admin Warning -->
        <?php if ($isSuperAdmin): ?>
            <div class="p-4 rounded-xl border flex items-start gap-3 bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800">
                <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0 mt-0.5"></i>
                <p class="text-sm font-medium">This is a <strong>Super Admin</strong> account. Role and permissions cannot be modified — Super Admins have unrestricted access to all modules.</p>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Account Details Card -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
                    <h2 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                        Account Details
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email Address <span class="text-rose-500">*</span></label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="phone" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($admin['phone'] ?? '') ?>" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Role <span class="text-rose-500">*</span></label>
                            <?php if ($isSuperAdmin): ?>
                                <input type="text" value="SUPER ADMIN" disabled class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-slate-100 dark:bg-slate-600 text-slate-500 dark:text-slate-400 cursor-not-allowed">
                                <input type="hidden" name="role" value="SUPER ADMIN">
                            <?php else: ?>
                                <select id="role" name="role" required class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                                    <option value="">Select Role...</option>
                                    <?php foreach ($availableRoles as $r): ?>
                                        <option value="<?= htmlspecialchars($r) ?>" <?= $admin['role'] === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">New Password <span class="text-xs text-slate-400 font-normal">(leave blank to keep current)</span></label>
                            <input type="password" id="password" name="password" minlength="8" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors" placeholder="Min. 8 characters">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="8" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors" placeholder="Re-enter new password">
                        </div>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Account Status</label>
                        <select id="status" name="status" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors">
                            <option value="ACTIVE" <?= $admin['status'] === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                            <option value="INACTIVE" <?= $admin['status'] === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Permissions Card (only for non-Super-Admins) -->
            <?php if (!$isSuperAdmin): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <i data-lucide="key" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                        Module Permissions
                    </h2>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="toggleAllPermissions(true)" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors">Select All</button>
                        <span class="text-slate-300 dark:text-slate-600">|</span>
                        <button type="button" onclick="toggleAllPermissions(false)" class="text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition-colors">Deselect All</button>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Select which modules this admin will have access to. Dashboard access is always included.</p>
                    
                    <?php
                    $groupedModules = [];
                    foreach ($allModules as $mod) {
                        $groupedModules[$mod['module_group']][] = $mod;
                    }
                    ?>
                    
                    <div class="space-y-4">
                        <?php foreach ($groupedModules as $group => $modules): ?>
                            <div>
                                <div class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2"><?= htmlspecialchars($group) ?></div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <?php foreach ($modules as $mod): ?>
                                        <?php
                                        $modId = $mod['id'] ?? $mod['module_id'] ?? $mod['ID'] ?? 0;
                                        $isDashboard = ($mod['module_key'] === 'dashboard');
                                        $isChecked = in_array($modId, $currentPermissions);
                                        ?>
                                        <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer <?= $isDashboard ? 'opacity-70' : '' ?>">
                                            <input type="checkbox" name="permissions[]" value="<?= $modId ?>" 
                                                class="permission-checkbox w-4 h-4 text-indigo-600 bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-500 rounded focus:ring-indigo-500 focus:ring-2"
                                                <?= $isDashboard ? 'checked disabled' : '' ?>
                                                <?= $isChecked ? 'checked' : '' ?>>
                                            <?php if ($isDashboard): ?>
                                                <input type="hidden" name="permissions[]" value="<?= $modId ?>">
                                            <?php endif; ?>
                                            <div class="flex items-center gap-2">
                                                <i data-lucide="<?= htmlspecialchars($mod['icon'] ?? 'box') ?>" class="w-4 h-4 text-slate-500 dark:text-slate-400"></i>
                                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?= htmlspecialchars($mod['module_name']) ?></span>
                                            </div>
                                            <?php if ($isDashboard): ?>
                                                <span class="ml-auto text-[10px] font-bold text-slate-400 uppercase">Always On</span>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3">
                <a href="<?= BASE_URL ?>/views/admin/manage-admins.php" class="px-6 py-2.5 border border-slate-200 dark:border-slate-600 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5">
                    <i data-lucide="save" class="w-4 h-4"></i> Update Admin
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    function toggleAllPermissions(checked) {
        document.querySelectorAll('.permission-checkbox:not([disabled])').forEach(cb => {
            cb.checked = checked;
        });
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
