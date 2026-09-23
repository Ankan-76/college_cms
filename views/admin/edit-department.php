<?php
// views/admin/edit-department.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('departments');
$pageTitle = 'Edit Department | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$error = '';
$success = '';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: departments.php");
    exit;
}

// Fetch existing department
$stmt = $db->prepare("SELECT * FROM departments WHERE id = ?");
$stmt->execute([$id]);
$department = $stmt->fetch();

if (!$department) {
    header("Location: departments.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_code = trim($_POST['dept_code'] ?? '');
    $dept_name = trim($_POST['dept_name'] ?? '');
    $total_semesters = filter_input(INPUT_POST, 'total_semesters', FILTER_VALIDATE_INT) ?: 8;

    if (empty($dept_code) || empty($dept_name)) {
        $error = 'Both Department Code and Department Name are required.';
    } else {
        $stmt = $db->prepare("SELECT id FROM departments WHERE dept_code = ? AND id != ?");
        $stmt->execute([$dept_code, $id]);
        if ($stmt->fetch()) {
            $error = 'Department Code already exists.';
        } else {
            try {
                $stmt = $db->prepare("UPDATE departments SET dept_code = ?, dept_name = ?, total_semesters = ? WHERE id = ?");
                $stmt->execute([$dept_code, $dept_name, $total_semesters, $id]);
                header('Location: departments.php');
                exit;
            } catch (PDOException $e) {
                $error = 'An error occurred while updating the department.';
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-3xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="edit" class="w-6 h-6 text-indigo-500"></i> Edit Department
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Update academic department details.</p>
            </div>
            <a href="departments.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Departments
            </a>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden p-6 sm:p-8">
            <?php if ($error): ?>
                <div class="mb-6 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 p-4 rounded-xl text-sm border border-rose-200 dark:border-rose-800 flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                    <p class="font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 p-4 rounded-xl text-sm border border-emerald-200 dark:border-emerald-800 flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
                    <p class="font-medium"><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="space-y-5">
                    <div>
                        <label for="dept_code" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Department Code</label>
                        <input type="text" name="dept_code" id="dept_code" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($department['dept_code']) ?>">
                    </div>
                    <div>
                        <label for="dept_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Department Name</label>
                        <input type="text" name="dept_name" id="dept_name" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($department['dept_name']) ?>">
                    </div>
                    <div>
                        <label for="total_semesters" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Total Semesters</label>
                        <input type="number" name="total_semesters" id="total_semesters" required min="1" max="12"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars((string)$department['total_semesters']) ?>">
                    </div>
                </div>
                <div class="mt-8 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 bg-primary hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 hover:-translate-y-0.5">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
