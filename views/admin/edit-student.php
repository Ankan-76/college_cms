<?php
// views/admin/edit-student.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'Edit Student | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: students.php');
    exit;
}

$error = '';
$success = '';

// Fetch existing data
$stmt = $db->prepare("SELECT name, email, phone, status, department_id, semester_id, roll_number, registration_number FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: students.php');
    exit;
}

// Fetch departments and semesters for dropdowns
$departments = $db->query("SELECT id, dept_name, dept_code FROM departments ORDER BY dept_name")->fetchAll();
$semesters = $db->query("SELECT id, semester_number, academic_year FROM semesters ORDER BY semester_number")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'ACTIVE';
    $department_id = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT);
    $semester_id = filter_input(INPUT_POST, 'semester_id', FILTER_VALIDATE_INT);
    $roll_number = trim($_POST['roll_number'] ?? '');
    $registration_number = trim($_POST['registration_number'] ?? '');

    if (empty($name) || empty($email) || !$department_id || !$semester_id || empty($roll_number) || empty($registration_number)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Check for unique constraints
        $stmt = $db->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            $error = 'Email address is already in use by another student.';
        } else {
            try {
                $stmt = $db->prepare("UPDATE students SET name = ?, email = ?, phone = ?, status = ?, department_id = ?, semester_id = ?, roll_number = ?, registration_number = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $status, $department_id, $semester_id, $roll_number, $registration_number, $id]);

                header('Location: students.php');
                exit;
            } catch (Exception $e) {
                $error = 'Error updating student.';
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="edit" class="w-6 h-6 text-indigo-500"></i> Edit Student
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Update student information.</p>
            </div>
            <a href="students.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Directory
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
                
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Full Name *</label>
                        <input type="text" name="name" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($student['name']) ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email Address *</label>
                        <input type="email" name="email" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($student['email']) ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Phone Number</label>
                        <input type="text" name="phone"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Account Status</label>
                        <select name="status" class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow">
                            <option value="ACTIVE" <?= $student['status'] === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                            <option value="INACTIVE" <?= $student['status'] === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">Academic Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Department *</label>
                        <select name="department_id" required class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow">
                            <option value="">Select Department</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= $student['department_id'] == $dept['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Semester *</label>
                        <select name="semester_id" required class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow">
                            <option value="">Select Semester</option>
                            <?php foreach($semesters as $sem): ?>
                                <option value="<?= $sem['id'] ?>" <?= $student['semester_id'] == $sem['id'] ? 'selected' : '' ?>>
                                    Semester <?= htmlspecialchars((string)$sem['semester_number']) ?> (<?= htmlspecialchars($sem['academic_year']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Roll Number *</label>
                        <input type="text" name="roll_number" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($student['roll_number']) ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Registration Number *</label>
                        <input type="text" name="registration_number" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($student['registration_number']) ?>">
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
