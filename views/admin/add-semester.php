<?php
// views/admin/add-semester.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('semesters');
$pageTitle = 'Add Semester | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semester_number = filter_input(INPUT_POST, 'semester_number', FILTER_VALIDATE_INT);
    $academic_year = trim($_POST['academic_year'] ?? '');
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    if (!$semester_number || empty($academic_year)) {
        $error = 'Semester Number and Academic Year are required.';
    } else {
        $stmt = $db->prepare("SELECT id FROM semesters WHERE semester_number = ? AND academic_year = ?");
        $stmt->execute([$semester_number, $academic_year]);
        if ($stmt->fetch()) {
            $error = 'This semester already exists for the given academic year.';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO semesters (semester_number, academic_year, start_date, end_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$semester_number, $academic_year, $start_date, $end_date]);
                header('Location: semesters.php');
                exit;
            } catch (PDOException $e) {
                $error = 'An error occurred while adding the semester.';
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
                    <i data-lucide="plus-circle" class="w-6 h-6 text-indigo-500"></i> Add Semester
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Create a new academic term.</p>
            </div>
            <a href="semesters.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Semesters
            </a>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden p-6 sm:p-8">
            <?php if ($error): ?>
                <div class="mb-6 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 p-4 rounded-xl text-sm border border-rose-200 dark:border-rose-800 flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                    <p class="font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Semester Number *</label>
                            <input type="number" name="semester_number" required min="1" max="12"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                                placeholder="e.g. 1"
                                value="<?= htmlspecialchars($_POST['semester_number'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Academic Year *</label>
                            <input type="text" name="academic_year" required
                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                                placeholder="e.g. 2026-2027"
                                value="<?= htmlspecialchars($_POST['academic_year'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Start Date</label>
                            <input type="date" name="start_date"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                                value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">End Date</label>
                            <input type="date" name="end_date"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                                value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="mt-8 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 bg-primary hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 hover:-translate-y-0.5">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Semester
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
