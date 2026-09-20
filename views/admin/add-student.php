<?php
// views/admin/add-student.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'Add Student | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$error = '';
$success = '';

// Fetch departments and semesters for dropdowns
$departments = $db->query("SELECT id, dept_name, dept_code FROM departments ORDER BY dept_name")->fetchAll();
$semesters = $db->query("SELECT id, semester_number, academic_year FROM semesters ORDER BY semester_number")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $department_id = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT);
    $semester_id = filter_input(INPUT_POST, 'semester_id', FILTER_VALIDATE_INT);
    $roll_number = trim($_POST['roll_number'] ?? '');
    $registration_number = trim($_POST['registration_number'] ?? '');

    if (empty($name) || empty($email) || empty($password) || !$department_id || !$semester_id || empty($roll_number) || empty($registration_number)) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $db->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email address is already registered.';
        } else {
            try {
                $password_hash = password_hash($password, PASSWORD_ARGON2ID);
                $stmt = $db->prepare("INSERT INTO students (name, email, password_hash, phone, department_id, semester_id, roll_number, registration_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE')");
                $stmt->execute([$name, $email, $password_hash, $phone, $department_id, $semester_id, $roll_number, $registration_number]);

                header('Location: students.php');
                exit;
            } catch (Exception $e) {
                $error = 'Error adding student.';
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
                    <i data-lucide="user-plus" class="w-6 h-6 text-indigo-500"></i> Add Student
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Enroll a new student into the system.</p>
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

            <form method="POST" action="">
                
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Full Name *</label>
                        <input type="text" name="name" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email Address *</label>
                        <input type="email" name="email" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Phone Number</label>
                        <input type="text" name="phone"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Password *</label>
                        <div class="relative">
                            <input type="password" name="password" id="studentPassword" required minlength="6"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow pr-10">
                            <button type="button" onclick="togglePasswordVisibility('studentPassword', 'toggleStudentPasswordIcon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none">
                                <i data-lucide="eye" id="toggleStudentPasswordIcon" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">Academic Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Department *</label>
                        <select name="department_id" required class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow">
                            <option value="">Select Department</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : '' ?>>
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
                                <option value="<?= $sem['id'] ?>" <?= (isset($_POST['semester_id']) && $_POST['semester_id'] == $sem['id']) ? 'selected' : '' ?>>
                                    Semester <?= htmlspecialchars((string)$sem['semester_number']) ?> (<?= htmlspecialchars($sem['academic_year']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Roll Number *</label>
                        <input type="text" name="roll_number" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($_POST['roll_number'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Registration Number *</label>
                        <input type="text" name="registration_number" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($_POST['registration_number'] ?? '') ?>">
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 bg-primary hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 hover:-translate-y-0.5">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Student
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>

<script>
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.setAttribute('data-lucide', 'eye-off');
        } else {
            input.type = 'password';
            icon.setAttribute('data-lucide', 'eye');
        }
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
