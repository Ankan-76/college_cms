<?php
// views/admin/add-faculty.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'Add Faculty | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$error = '';
$success = '';

$departments = $db->query("SELECT id, dept_name, dept_code FROM departments ORDER BY dept_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $department_ids = isset($_POST['department_ids']) && is_array($_POST['department_ids']) ? $_POST['department_ids'] : [];
    $designation = trim($_POST['designation'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');

    if (empty($name) || empty($email) || empty($password) || empty($department_ids) || empty($designation) || empty($qualification)) {
        $error = 'Please fill in all required fields and select at least one department.';
    } else {
        $stmt = $db->prepare("SELECT id FROM teachers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email address is already registered.';
        } else {
            try {
                $password_hash = password_hash($password, PASSWORD_ARGON2ID);
                
                $profile_pic = null;
                if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['profile_pic'];
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (in_array($file['type'], $allowedTypes) && $file['size'] <= 2 * 1024 * 1024) {
                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'profile_faculty_' . uniqid() . '.' . $ext;
                        $uploadDir = __DIR__ . '/../../uploads/profiles/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            $profile_pic = $filename;
                        }
                    }
                }

                $stmt = $db->prepare("INSERT INTO teachers (name, email, password_hash, phone, designation, qualification, profile_pic, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVE')");
                $stmt->execute([$name, $email, $password_hash, $phone, $designation, $qualification, $profile_pic]);
                
                $teacher_id = $db->lastInsertId();
                
                // Insert departments
                $deptStmt = $db->prepare("INSERT INTO teacher_departments (teacher_id, department_id) VALUES (?, ?)");
                foreach ($department_ids as $dept_id) {
                    $deptStmt->execute([$teacher_id, $dept_id]);
                }

                header('Location: faculty.php');
                exit;
            } catch (Exception $e) {
                $error = 'Error adding faculty member.';
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
                    <i data-lucide="user-plus" class="w-6 h-6 text-indigo-500"></i> Add Faculty
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Onboard a new faculty member into the system.</p>
            </div>
            <a href="faculty.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 flex items-center gap-1">
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

            <form method="POST" action="" enctype="multipart/form-data">
                
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
                            <input type="password" name="password" id="facultyPassword" required minlength="6"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow pr-10">
                            <button type="button" onclick="togglePasswordVisibility('facultyPassword', 'toggleFacultyPasswordIcon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none">
                                <i data-lucide="eye" id="toggleFacultyPasswordIcon" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Profile Picture (Optional)</label>
                        <input type="file" name="profile_pic" accept="image/jpeg, image/png, image/gif"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-2.5 border outline-none transition-shadow file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400">
                        <p class="text-xs text-slate-500 mt-1">JPG, PNG, GIF (Max 2MB)</p>
                    </div>
                </div>

                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">Professional Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Departments *</label>
                        <div class="relative" id="deptDropdownContainer">
                            <button type="button" onclick="document.getElementById('deptDropdown').classList.toggle('hidden')" class="w-full text-left rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm sm:text-sm px-4 py-3 border flex justify-between items-center transition-shadow focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <span id="deptDropdownText">Select Departments</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400"></i>
                            </button>
                            
                            <div id="deptDropdown" class="hidden absolute z-10 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                <div class="p-2 space-y-1">
                                    <?php foreach($departments as $dept): ?>
                                        <?php $isChecked = (isset($_POST['department_ids']) && in_array($dept['id'], $_POST['department_ids'])); ?>
                                        <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg cursor-pointer transition-colors">
                                            <input type="checkbox" name="department_ids[]" value="<?= $dept['id'] ?>" class="dept-checkbox w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-700" <?= $isChecked ? 'checked' : '' ?> onchange="updateDeptText()">
                                            <span class="text-sm font-medium text-slate-700 dark:text-slate-200"><?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)</span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Designation *</label>
                        <input type="text" name="designation" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            placeholder="e.g. Associate Professor"
                            value="<?= htmlspecialchars($_POST['designation'] ?? '') ?>">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Qualification *</label>
                        <input type="text" name="qualification" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            placeholder="e.g. Ph.D. in Computer Science"
                            value="<?= htmlspecialchars($_POST['qualification'] ?? '') ?>">
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 bg-primary hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 hover:-translate-y-0.5">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Faculty Member
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>

<script>
    function updateDeptText() {
        const checkboxes = document.querySelectorAll('.dept-checkbox');
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const textElement = document.getElementById('deptDropdownText');
        
        if (checkedCount === 0) {
            textElement.textContent = 'Select Departments';
        } else if (checkedCount === 1) {
            const checkedLabel = document.querySelector('.dept-checkbox:checked').nextElementSibling.textContent;
            textElement.textContent = checkedLabel;
        } else {
            textElement.textContent = checkedCount + ' departments selected';
        }
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const container = document.getElementById('deptDropdownContainer');
        const dropdown = document.getElementById('deptDropdown');
        if (container && dropdown && !container.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Initialize text on load
    document.addEventListener('DOMContentLoaded', updateDeptText);

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
